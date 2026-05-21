<?php
namespace True\RateLimiting;

/**
 * A rate-limit configuration object.
 *
 * Mirrors the public API of Laravel's `Illuminate\Cache\RateLimiting\Limit`
 * (factories `perSecond`, `perMinute`, `perHour`, `perDay`, plus `by`,
 * `after`, `response`, `fallbackKey`) so existing patterns translate.
 *
 * Typical use:
 *
 *     // Anywhere you have access to the RateLimiter:
 *     $limiter->for('api', fn($request) =>
 *         Limit::perMinute(100)->by($request->ip())
 *     );
 *
 *     // Strict login throttle keyed on email + IP:
 *     $limiter->for('login', fn($request) =>
 *         Limit::perMinute(5)->by($request->post->email . '|' . $request->ip())
 *             ->response(fn() => $App->response(['error' => 'Slow down'], 'json', 429))
 *     );
 *
 * @author Daniel Baldwin
 * @version 1.0.0
 */
class Limit
{
	/** Optional namespace appended to the user-supplied key. */
	public mixed $key = '';

	/** Maximum hits allowed inside the window. */
	public int $maxAttempts;

	/** Window length, in seconds. */
	public int $decaySeconds;

	/** Optional callback invoked after each accepted hit. */
	public mixed $afterCallback = null;

	/** Optional callback used to generate the 429 response. */
	public mixed $responseCallback = null;

	public function __construct(mixed $key = '', int $maxAttempts = 60, int $decaySeconds = 60)
	{
		$this->key          = $key;
		$this->maxAttempts  = $maxAttempts;
		$this->decaySeconds = $decaySeconds;
	}

	/** N hits per second (default decay = 1s). */
	public static function perSecond(int $maxAttempts, int $decaySeconds = 1): static
	{
		return new static('', $maxAttempts, $decaySeconds);
	}

	/** N hits per minute (default decay = 1 min). */
	public static function perMinute(int $maxAttempts, int $decayMinutes = 1): static
	{
		return new static('', $maxAttempts, 60 * $decayMinutes);
	}

	/** N hits per X minutes — note: argument order matches Laravel. */
	public static function perMinutes(int $decayMinutes, int $maxAttempts): static
	{
		return new static('', $maxAttempts, 60 * $decayMinutes);
	}

	/** N hits per hour (default decay = 1 hour). */
	public static function perHour(int $maxAttempts, int $decayHours = 1): static
	{
		return new static('', $maxAttempts, 3600 * $decayHours);
	}

	/** N hits per day (default decay = 1 day). */
	public static function perDay(int $maxAttempts, int $decayDays = 1): static
	{
		return new static('', $maxAttempts, 86400 * $decayDays);
	}

	/** A pass-through "no limit" sentinel — see Unlimited. */
	public static function none(): Unlimited
	{
		return new Unlimited;
	}

	/** Set the discriminator that scopes the bucket (e.g. user id or IP). */
	public function by(mixed $key): static
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * Register an "after" callback that runs every time the limit accepts
	 * a request. The callback receives the Limit instance.
	 */
	public function after(callable $callback): static
	{
		$this->afterCallback = $callback;
		return $this;
	}

	/**
	 * Register a callback that generates the response when the limit is
	 * exceeded. Receives the Limit instance + the seconds-until-reset.
	 */
	public function response(callable $callback): static
	{
		$this->responseCallback = $callback;
		return $this;
	}

	/** Stable de-duplication key — used when several limits share a bucket. */
	public function fallbackKey(): string
	{
		$prefix = $this->key !== '' && $this->key !== null ? "{$this->key}:" : '';
		return "{$prefix}attempts:{$this->maxAttempts}:decay:{$this->decaySeconds}";
	}
}
