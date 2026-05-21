<?php
namespace True\Middleware;

use True\RateLimiter;
use True\RateLimiting\Limit;
use True\RateLimiting\Unlimited;

/**
 * Router middleware that enforces a rate limit on a route group.
 *
 * Two ways to use it:
 *
 * 1. Inline rate (no `for()` registration required) — second arg is the
 *    max attempts per minute, keyed by client IP and namespaced by `$name`:
 *
 *        $App->router->group('/api/*', function () use ($App) {
 *            $App->router->get('/api/user/*:id', function ($request) use ($App) {
 *                echo $request->route->id;
 *            });
 *        }, [ new \True\Middleware\RateLimitMiddleware('api', 100) ]);
 *
 *    The third argument overrides the decay window (default 60s):
 *
 *        new \True\Middleware\RateLimitMiddleware('api', 100, 60)   // 100 / minute
 *        new \True\Middleware\RateLimitMiddleware('api', 1000, 3600) // 1000 / hour
 *
 * 2. Named limiter (more control — register the Limit with `$App->rateLimiter->for()`
 *    first, then reference it by name and omit the rate arg):
 *
 *        $App->rateLimiter->for('login', fn($request) =>
 *            \True\RateLimiting\Limit::perMinute(5)
 *                ->by(($_POST['email'] ?? '') . '|' . $_SERVER['REMOTE_ADDR'])
 *        );
 *
 *        $App->router->group('/login', function () use ($App) {
 *            // ...
 *        }, [ new \True\Middleware\RateLimitMiddleware('login') ]);
 *
 * Behaviour:
 *   - Returns true (pass-through) when no `$App->rateLimiter` is configured,
 *     when no rate is given and the named limiter doesn't exist, or when the
 *     resolved Limit is `Limit::none()`.
 *   - Returns false (Router stops the group) when the limit is exceeded.
 *     A 429 response is emitted with `Retry-After` and `X-RateLimit-*`
 *     headers, or the Limit's own `response()` callback is invoked if set.
 *   - On a successful pass, increments the counter and sets `X-RateLimit-*`
 *     headers from the most restrictive limit.
 *
 * @author Daniel Baldwin
 * @version 1.0.0
 */
class RateLimitMiddleware
{
	public function __construct(
		protected string $limiterName,
		protected ?int $maxAttempts = null,
		protected int $decaySeconds = 60,
		protected ?RateLimiter $limiter = null
	) {
		if ($this->limiter === null) {
			global $App;
			$this->limiter = $App->rateLimiter ?? null;
		}
	}

	public function __invoke($request): bool
	{
		if (!$this->limiter) return true;

		$limits = $this->resolveLimits($request);
		if ($limits === null) return true;

		foreach ($limits as $limit) {
			if (!$limit instanceof Limit || $limit instanceof Unlimited) continue;
			if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
				$this->respond429($limit);
				return false;
			}
		}

		$primary = null;
		foreach ($limits as $limit) {
			if (!$limit instanceof Limit || $limit instanceof Unlimited) continue;
			$this->limiter->hit($limit->key, $limit->decaySeconds);
			if (is_callable($limit->afterCallback)) {
				call_user_func($limit->afterCallback, $limit);
			}
			if ($primary === null) $primary = $limit;
		}

		if ($primary !== null) {
			header('X-RateLimit-Limit: ' . $primary->maxAttempts);
			header('X-RateLimit-Remaining: ' . $this->limiter->remaining($primary->key, $primary->maxAttempts));
			header('X-RateLimit-Reset: ' . $this->limiter->resetAt($primary->key));
		}

		return true;
	}

	/** @return Limit[]|null Null means "no limiter applies — pass through". */
	protected function resolveLimits($request): ?array
	{
		if ($this->maxAttempts !== null) {
			$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
			$limit = new Limit($this->limiterName . ':' . $ip, $this->maxAttempts, $this->decaySeconds);
			return [$limit];
		}

		$resolver = $this->limiter->limiter($this->limiterName);
		if (!$resolver) return null;

		$result = $resolver($request);
		return is_array($result) ? $result : [$result];
	}

	protected function respond429(Limit $limit): void
	{
		$retryAfter = $this->limiter->availableIn($limit->key);

		if (is_callable($limit->responseCallback)) {
			call_user_func($limit->responseCallback, $limit, $retryAfter);
			exit;
		}

		header('Retry-After: ' . $retryAfter);
		header('X-RateLimit-Limit: ' . $limit->maxAttempts);
		header('X-RateLimit-Remaining: 0');
		header('X-RateLimit-Reset: ' . $this->limiter->resetAt($limit->key));
		http_response_code(429);
		exit('Too Many Requests');
	}
}
