<?php
namespace True;

use Closure;
use True\RateLimiting\Limit;
use True\RateLimiting\StoreInterface;
use True\RateLimiting\Unlimited;

/**
 *
 * Public surface: `for()`, `limiter()`, `attempt()`,
 * `tooManyAttempts()`, `hit()`, `increment()`, `decrement()`, `attempts()`,
 * `resetAttempts()`, `remaining()`, `retriesLeft()`, `clear()`,
 * `availableIn()`, `cleanRateLimiterKey()`.
 *
 * Storage is pluggable via True\RateLimiting\StoreInterface. Default
 * implementations:
 *   - True\RateLimiting\ApcuStore — single-server, in-memory (recommended)
 *   - True\RateLimiting\FileStore — filesystem-backed, no deps
 *
 * Typical setup in your bootstrap (e.g. routes.php or App boot):
 *
 *     $App->rateLimiter = new \True\RateLimiter(new \True\RateLimiting\ApcuStore);
 *
 *     // Named limiter — used in route metadata or middleware
 *     $App->rateLimiter->for('api', fn() =>
 *         \True\RateLimiting\Limit::perMinute(100)->by($_SERVER['REMOTE_ADDR'])
 *     );
 *
 *     $App->rateLimiter->for('login', fn() =>
 *         \True\RateLimiting\Limit::perMinute(5)
 *             ->by(($_POST['email'] ?? '') . '|' . $_SERVER['REMOTE_ADDR'])
 *     );
 *
 * In a controller / middleware:
 *
 *     $limit = $App->rateLimiter->limiter('api')();
 *     if ($App->rateLimiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
 *         header('Retry-After: ' . $App->rateLimiter->availableIn($limit->key));
 *         header('X-RateLimit-Limit: ' . $limit->maxAttempts);
 *         header('X-RateLimit-Remaining: 0');
 *         http_response_code(429);
 *         exit('Too Many Requests');
 *     }
 *     $App->rateLimiter->hit($limit->key, $limit->decaySeconds);
 *     header('X-RateLimit-Limit: ' . $limit->maxAttempts);
 *     header('X-RateLimit-Remaining: ' . $App->rateLimiter->remaining($limit->key, $limit->maxAttempts));
 *     header('X-RateLimit-Reset: ' . (time() + $App->rateLimiter->availableIn($limit->key)));
 *
 * @author Daniel Baldwin
 * @version 1.0.0
 */
class RateLimiter
{
	protected StoreInterface $store;
	protected array $limiters = [];

	public function __construct(StoreInterface $store)
	{
		$this->store = $store;
	}

	/**
	 * Register a named rate limiter configuration. The callback returns a
	 * Limit (or an array of Limit) and may receive request context as args.
	 *
	 *     $limiter->for('api', function ($request) {
	 *         return Limit::perMinute(100)->by($request->ip());
	 *     });
	 */
	public function for(string $name, Closure $callback): static
	{
		$this->limiters[$name] = $callback;
		return $this;
	}

	/**
	 * Resolve a named limiter. The returned closure invokes the registered
	 * callback and post-processes the result so that, when several Limits
	 * share a key, conflicts are rewritten to each Limit's `fallbackKey()`.
	 */
	public function limiter(string $name): ?Closure
	{
		$callback = $this->limiters[$name] ?? null;
		if (!is_callable($callback)) return null;

		return function (...$args) use ($callback) {
			$result = $callback(...$args);
			if (!is_array($result)) return $result;

			// Disambiguate duplicate keys.
			$seen = [];
			$dupes = [];
			foreach ($result as $limit) {
				$k = (string) ($limit->key ?? '');
				if (isset($seen[$k])) { $dupes[$k] = true; }
				$seen[$k] = true;
			}
			foreach ($result as $limit) {
				if (isset($dupes[(string) ($limit->key ?? '')])) {
					$limit->key = $limit->fallbackKey();
				}
			}
			return $result;
		};
	}

	/**
	 * Attempt to execute $callback. If the key is already over $maxAttempts,
	 * returns false without invoking the callback. Otherwise runs the
	 * callback, hits the counter, and returns the callback's return value
	 * (or true if it returned null).
	 */
	public function attempt(string $key, int $maxAttempts, Closure $callback, int $decaySeconds = 60): mixed
	{
		if ($this->tooManyAttempts($key, $maxAttempts)) return false;

		$result = $callback();
		if ($result === null) $result = true;

		$this->hit($key, $decaySeconds);
		return $result;
	}

	/**
	 * True if the given key has been hit >= $maxAttempts within the current
	 * window. If the counter has expired but the lockout timer hasn't, the
	 * key is still considered limited (matches Laravel's semantics).
	 */
	public function tooManyAttempts(string $key, int $maxAttempts): bool
	{
		if ($maxAttempts >= PHP_INT_MAX) return false; // Unlimited shortcut

		if ($this->attempts($key) >= $maxAttempts) {
			if ($this->store->has($this->cleanRateLimiterKey($key) . ':timer')) {
				return true;
			}
			$this->resetAttempts($key);
		}
		return false;
	}

	/** Increment by 1, returning the new count. */
	public function hit(string $key, int $decaySeconds = 60): int
	{
		return $this->increment($key, $decaySeconds, 1);
	}

	/** Increment by $amount, resetting the window if expired. */
	public function increment(string $key, int $decaySeconds = 60, int $amount = 1): int
	{
		$key = $this->cleanRateLimiterKey($key);

		// Window timer — only set once per window. Stores the absolute
		// timestamp at which the window resets, used by availableIn().
		$this->store->add($key . ':timer', time() + $decaySeconds, $decaySeconds);

		// Counter — initialise to 0 if absent, then increment atomically.
		$added = $this->store->add($key, 0, $decaySeconds);
		$hits  = $this->store->increment($key, $amount);

		// If `add()` reported the counter was already there but the value
		// we got back equals $amount, the previous bucket had just expired
		// and the increment created a fresh counter without a TTL. Re-set
		// it with the proper decay so it expires correctly.
		if (!$added && $hits === $amount) {
			$this->store->put($key, $amount, $decaySeconds);
		}

		return $hits;
	}

	/** Decrement by $amount, returning the new count. */
	public function decrement(string $key, int $decaySeconds = 60, int $amount = 1): int
	{
		return $this->increment($key, $decaySeconds, -$amount);
	}

	/** Current attempt count for the key (0 if missing). */
	public function attempts(string $key): int
	{
		$key = $this->cleanRateLimiterKey($key);
		return (int) $this->store->get($key, 0);
	}

	/** Forget the counter (keeps the lockout timer). */
	public function resetAttempts(string $key): bool
	{
		return $this->store->forget($this->cleanRateLimiterKey($key));
	}

	/** Hits remaining before the limit kicks in. */
	public function remaining(string $key, int $maxAttempts): int
	{
		return max(0, $maxAttempts - $this->attempts($key));
	}

	/** Alias for remaining(). */
	public function retriesLeft(string $key, int $maxAttempts): int
	{
		return $this->remaining($key, $maxAttempts);
	}

	/** Forget both the counter and the lockout timer. */
	public function clear(string $key): void
	{
		$key = $this->cleanRateLimiterKey($key);
		$this->store->forget($key);
		$this->store->forget($key . ':timer');
	}

	/** Seconds remaining in the current window (0 if the window has reset). */
	public function availableIn(string $key): int
	{
		$key = $this->cleanRateLimiterKey($key);
		$timer = (int) $this->store->get($key . ':timer', 0);
		return max(0, $timer - time());
	}

	/**
	 * Absolute UNIX timestamp at which the window resets. Convenient for
	 * the `X-RateLimit-Reset` header.
	 */
	public function resetAt(string $key): int
	{
		$key = $this->cleanRateLimiterKey($key);
		return (int) $this->store->get($key . ':timer', time());
	}

	/**
	 * Normalise keys so HTML entities / unicode don't break filesystem-style
	 * stores.
	 */
	public function cleanRateLimiterKey(string $key): string
	{
		return preg_replace('/&([a-z])[a-z]+;/i', '$1', htmlentities($key));
	}

	/** Underlying store (handy for tests / cleanup). */
	public function getStore(): StoreInterface
	{
		return $this->store;
	}
}
