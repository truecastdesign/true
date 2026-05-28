# Rate Limiter

> Throttle requests by IP, user, email, or any other discriminator using named limit configurations. Pluggable storage backends (APCu, file, or your own).

## Overview

`True\RateLimiter` is a fixed-window rate limiter with pluggable storage. Its public API is (`for()`, `limiter()`, `attempt()`, `tooManyAttempts()`, `hit()`, `remaining()`, `availableIn()`, ...) so the familiar patterns translate directly.

Each "limit" defines:

- a **discriminator** — the value that scopes the bucket (IP, user id, email, etc.)
- a **maximum number of attempts** allowed inside the window
- a **decay** — how long the window lasts, in seconds

When the discriminator exceeds the configured attempts inside the window, the limiter reports the key as locked until the window resets.

## Storage backends

Storage is pluggable via `True\RateLimiting\StoreInterface`. Two implementations ship out of the box:

| Store | Use when | Notes |
| --- | --- | --- |
| `True\RateLimiting\ApcuStore` | Single-server deploys | Fastest. Requires `ext-apcu`. Per-PHP-FPM-pool — buckets do **not** cross servers. |
| `True\RateLimiting\FileStore` | Low/medium traffic, no APCu | No extension required. Atomic via `flock()`. Files self-expire and GC on writes. |

For multi-server deploys, write a `StoreInterface` against Redis or Memcached.

## Setup

Construct the limiter with a store and stash it on `$App` (typically in `routes.php` or your bootstrap):

```php
// APCu — recommended on a single server
$App->rateLimiter = new \True\RateLimiter(new \True\RateLimiting\ApcuStore);

// Or file-backed, if APCu isn't available
$App->rateLimiter = new \True\RateLimiter(
    new \True\RateLimiting\FileStore(BP . '/app/data/rate-limits')
);
```

Then register named limiter configurations once at boot. Each callback returns a `Limit` (or array of `Limit`) and may receive request context:

```php
$App->rateLimiter->for('api', fn() =>
    \True\RateLimiting\Limit::perMinute(100)->by($_SERVER['REMOTE_ADDR'])
);

$App->rateLimiter->for('login', fn() =>
    \True\RateLimiting\Limit::perMinute(5)
        ->by(($_POST['email'] ?? '') . '|' . $_SERVER['REMOTE_ADDR'])
);
```

## Defining limits

`True\RateLimiting\Limit` provides factories for common windows:

```php
use True\RateLimiting\Limit;

Limit::perSecond(10);            // 10 per second
Limit::perMinute(60);            // 60 per minute (default decay = 1 minute)
Limit::perMinute(100, 5);        // 100 per 5 minutes
Limit::perMinutes(5, 100);       // same — 
Limit::perHour(1000);            // 1000 per hour
Limit::perDay(10000);            // 10000 per day
Limit::none();                   // pass-through (Unlimited)
```

Chain `->by()` to set the discriminator that scopes the bucket:

```php
Limit::perMinute(5)->by('user:' . $userId);
Limit::perMinute(5)->by($_POST['email'] . '|' . $_SERVER['REMOTE_ADDR']);
```

Optional callbacks:

```php
Limit::perMinute(5)
    ->by($_SERVER['REMOTE_ADDR'])
    ->after(fn(Limit $l) => error_log("hit on {$l->key}"))
    ->response(fn(Limit $l, int $retryAfter) =>
        $App->response(['error' => 'Slow down'], 'json', 429)
    );
```

## Enforcing a limit in a controller

Resolve the named limiter, then check and record the hit. Set the standard `Retry-After` / `X-RateLimit-*` headers so clients can self-throttle:

```php
$limit = $App->rateLimiter->limiter('api')();

if ($App->rateLimiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
    header('Retry-After: ' . $App->rateLimiter->availableIn($limit->key));
    header('X-RateLimit-Limit: ' . $limit->maxAttempts);
    header('X-RateLimit-Remaining: 0');
    http_response_code(429);
    exit('Too Many Requests');
}

$App->rateLimiter->hit($limit->key, $limit->decaySeconds);

header('X-RateLimit-Limit: ' . $limit->maxAttempts);
header('X-RateLimit-Remaining: ' . $App->rateLimiter->remaining($limit->key, $limit->maxAttempts));
header('X-RateLimit-Reset: ' . $App->rateLimiter->resetAt($limit->key));
```

## The `attempt()` shortcut

When you want to run a callback only if the limit hasn't been exceeded, `attempt()` rolls the check and the hit into one call:

```php
$result = $App->rateLimiter->attempt(
    key: 'login:' . $email,
    maxAttempts: 5,
    callback: fn() => authenticate($email, $password),
    decaySeconds: 60
);

if ($result === false) {
    // Limit exceeded — show a "try again in N seconds" message
    $retryAfter = $App->rateLimiter->availableIn('login:' . $email);
    $App->error("Too many attempts. Try again in {$retryAfter}s.", 'warning');
    $App->go('/login');
}
```

If the callback returns `null`, `attempt()` returns `true` instead so you can distinguish "ran successfully" from "blocked".

## Router middleware

`True\Middleware\RateLimitMiddleware` plugs the limiter into the Router's middleware pipeline, so you don't have to repeat the check / hit / headers dance in every controller.

### Creating the limiter

The middleware needs a `RateLimiter` instance to talk to. The simplest setup is to construct one at the top of `routes.php` and stash it on `$App` — every `RateLimitMiddleware` then picks it up automatically via `global $App`, so you don't have to pass it around:

```php
// routes.php — near the top, before any group() calls

// APCu (recommended on a single server)
$App->rateLimiter = new \True\RateLimiter(new \True\RateLimiting\ApcuStore);

// Or file-backed, if APCu isn't installed
$App->rateLimiter = new \True\RateLimiter(
    new \True\RateLimiting\FileStore(BP . '/app/data/rate-limits')
);
```

If you'd rather not put it on `$App`, build the limiter locally and pass it to the middleware explicitly — see [Passing a limiter explicitly](#passing-a-limiter-explicitly) below.

### Inline rate (the quick path)

For most route groups, just pass the max attempts as the second constructor argument. The middleware keys the bucket by client IP and namespaces it under the name you give:

```php
$App->router->group('/api/*', function () use ($App) {

    $App->router->get('/api/user/*:id', function ($request) use ($App) {
        echo $request->route->id;
    });

}, [ new \True\Middleware\RateLimitMiddleware('api', 100) ]);
```

That's 100 attempts per minute per IP. The third argument overrides the window (in seconds):

```php
new \True\Middleware\RateLimitMiddleware('api', 100, 60)    // 100 / minute
new \True\Middleware\RateLimitMiddleware('api', 1000, 3600) // 1000 / hour
new \True\Middleware\RateLimitMiddleware('upload', 5, 60)   // 5 uploads / minute
```

No `for()` registration is needed in this mode.

### Named limiter (more control)

When you need a non-IP discriminator (e.g. email + IP for login), per-user vs. per-guest limits, multiple stacked windows, or a custom 429 response, register the limit via `for()` and omit the rate argument:

```php
$App->rateLimiter->for('login', fn($request) =>
    \True\RateLimiting\Limit::perMinute(5)
        ->by(($_POST['email'] ?? '') . '|' . $_SERVER['REMOTE_ADDR'])
);

$App->router->group('/login', function () use ($App) {

    $App->router->post('/login', function ($request) use ($App) {
        // ...
    });

}, [ new \True\Middleware\RateLimitMiddleware('login') ]);
```

### Behaviour

When the limit is exceeded the middleware emits a `429 Too Many Requests` with `Retry-After` and `X-RateLimit-*` headers, then calls `exit` — the request terminates immediately, so no later route (including any catch-all at the bottom of `routes.php`) gets a chance to run. If the resolved `Limit` has a `->response()` callback, that runs instead of the default 429 body, then `exit`. On a successful pass it increments the counter and adds the same `X-RateLimit-*` headers to the response.

### Rate-limiting a URL handled by a catch-all

Sometimes you want to throttle a path (e.g. an admin login at `/trueadmin/login`) that isn't registered as an explicit route — it's served by a catch-all like `$App->router->any('/*:path', ...)` at the bottom of `routes.php`. You can still apply the middleware by registering an **empty** group whose pattern matches just that URL:

```php
$App->router->group('/trueadmin/login', function () use ($App) {
    // Intentionally empty — the middleware fires when this URL is hit,
    // then the catch-all below handles the actual response.
}, [ new \True\Middleware\RateLimitMiddleware('trueadmin-login', 5, 60) ]);
```

The group's middleware fires on every matching request regardless of whether the callable registers routes. If the limit is exceeded the middleware `exit`s with a 429; otherwise control falls through to whatever route handler does the real work.

### Stacking with other middleware

The middleware array is just a list — order matters. Putting the rate limiter **before** auth lets you throttle anonymous traffic before doing expensive lookups; putting it **after** lets you apply per-user limits using the authenticated identity:

```php
$App->router->group('/api/*', function () use ($App) {
    // ... routes
}, [
    new \True\Middleware\RateLimitMiddleware('api-burst'),  // cheap pre-auth throttle
    new \App\AuthMiddleware,                                // then authenticate
    new \True\Middleware\RateLimitMiddleware('api-user'),   // then per-user throttle
]);
```

### Custom 429 body

The default response is a plain `Too Many Requests` string. To return JSON (or anything else), attach a `->response()` callback on the limit:

```php
$App->rateLimiter->for('api', fn() =>
    Limit::perMinute(100)
        ->by($_SERVER['REMOTE_ADDR'])
        ->response(function ($limit, $retryAfter) use ($App) {
            header('Retry-After: ' . $retryAfter);
            header('Content-Type: application/json');
            http_response_code(429);
            echo json_encode([
                'error' => 'rate_limited',
                'retry_after' => $retryAfter,
            ]);
        })
);
```

### Passing a limiter explicitly

If you aren't stashing the limiter on `$App->rateLimiter`, pass the instance as the fourth constructor argument (use named args to skip the rate/decay):

```php
$apiLimiter = new \True\RateLimiter(new \True\RateLimiting\ApcuStore);
$apiLimiter->for('api', fn() => Limit::perMinute(100)->by($_SERVER['REMOTE_ADDR']));

$App->router->group('/api/*', function () use ($App) {
    // ... routes
}, [ new \True\Middleware\RateLimitMiddleware('api', limiter: $apiLimiter) ]);
```

Or with the inline-rate form:

```php
new \True\Middleware\RateLimitMiddleware('api', 100, 60, $apiLimiter);
```

## Common patterns

### Per-IP API throttle

```php
$App->rateLimiter->for('api', fn() =>
    Limit::perMinute(100)->by($_SERVER['REMOTE_ADDR'])
);
```

### Strict login throttle (email + IP)

Two discriminators stop attackers from cycling IPs against one account *and* one IP from churning through emails:

```php
$App->rateLimiter->for('login', fn() =>
    Limit::perMinute(5)->by(($_POST['email'] ?? '') . '|' . $_SERVER['REMOTE_ADDR'])
);
```

### Per-user vs. per-guest

Return different limits depending on auth state:

```php
$App->rateLimiter->for('api', function () use ($App) {
    if ($userId = $App->auth->userId()) {
        return Limit::perMinute(1000)->by('user:' . $userId);
    }
    return Limit::perMinute(60)->by('ip:' . $_SERVER['REMOTE_ADDR']);
});
```

### Bypass for trusted users

```php
$App->rateLimiter->for('api', function () use ($App) {
    if ($App->auth->isAdmin()) return Limit::none();
    return Limit::perMinute(100)->by($_SERVER['REMOTE_ADDR']);
});
```

### Multiple limits on one endpoint

Return an array of limits to enforce more than one window at the same time (e.g. burst + sustained):

```php
$App->rateLimiter->for('contact-form', fn() => [
    Limit::perMinute(3)->by($_SERVER['REMOTE_ADDR']),   // burst
    Limit::perHour(20)->by($_SERVER['REMOTE_ADDR']),    // sustained
]);

foreach ($App->rateLimiter->limiter('contact-form')() as $limit) {
    if ($App->rateLimiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
        header('Retry-After: ' . $App->rateLimiter->availableIn($limit->key));
        http_response_code(429);
        exit('Too Many Requests');
    }
    $App->rateLimiter->hit($limit->key, $limit->decaySeconds);
}
```

When two limits share the same discriminator, the limiter rewrites the duplicates to `Limit::fallbackKey()` so each window has its own bucket.

## API reference

### `RateLimiter`

| Method | Purpose |
| --- | --- |
| `for(string $name, Closure $cb)` | Register a named limiter. The callback returns a `Limit` or `Limit[]`. |
| `limiter(string $name): ?Closure` | Resolve a named limiter. The returned closure invokes the callback and disambiguates duplicate keys. |
| `attempt(string $key, int $max, Closure $cb, int $decay = 60)` | Run `$cb` if the key is under the limit, then hit. Returns `false` if blocked. |
| `tooManyAttempts(string $key, int $max): bool` | Check the bucket without recording a hit. |
| `hit(string $key, int $decay = 60): int` | Increment by 1. Returns the new count. |
| `increment(string $key, int $decay = 60, int $amount = 1)` | Increment by `$amount`. Resets the window if expired. |
| `decrement(string $key, int $decay = 60, int $amount = 1)` | Decrement by `$amount`. |
| `attempts(string $key): int` | Current hit count (0 if missing). |
| `resetAttempts(string $key): bool` | Forget the counter, keep the lockout timer. |
| `remaining(string $key, int $max): int` | Hits left before the limit kicks in. |
| `retriesLeft(string $key, int $max): int` | Alias for `remaining()`. |
| `clear(string $key): void` | Forget both the counter and the timer. |
| `availableIn(string $key): int` | Seconds until the window resets. |
| `resetAt(string $key): int` | UNIX timestamp the window resets at — for `X-RateLimit-Reset`. |
| `cleanRateLimiterKey(string $key)` | Normalise a key so HTML entities / unicode don't break file-backed stores. |
| `getStore(): StoreInterface` | Underlying store. Handy for tests / cleanup. |

### `Limit`

| Method | Purpose |
| --- | --- |
| `Limit::perSecond(int $max, int $decay = 1)` | N hits per second. |
| `Limit::perMinute(int $max, int $decayMin = 1)` | N hits per minute. |
| `Limit::perMinutes(int $decayMin, int $max)` | N hits per X minutes. |
| `Limit::perHour(int $max, int $decayHours = 1)` | N hits per hour. |
| `Limit::perDay(int $max, int $decayDays = 1)` | N hits per day. |
| `Limit::none(): Unlimited` | Pass-through sentinel — never limits. |
| `->by(mixed $key)` | Set the discriminator that scopes the bucket. |
| `->after(callable $cb)` | Callback invoked after each accepted hit. |
| `->response(callable $cb)` | Callback that generates the 429 response. |
| `->fallbackKey(): string` | Stable de-dup key when multiple limits share a bucket. |

### `StoreInterface`

Write your own backend by implementing these methods (all TTLs are seconds):

| Method | Purpose |
| --- | --- |
| `get(string $key, mixed $default = null): mixed` | Return the value or `$default` if missing/expired. |
| `add(string $key, mixed $value, int $ttl): bool` | Set only if the key doesn't already exist. |
| `put(string $key, mixed $value, int $ttl): void` | Set unconditionally. |
| `increment(string $key, int $amount = 1): int` | Atomic increment. Returns the new value. |
| `decrement(string $key, int $amount = 1): int` | Atomic decrement. Returns the new value. |
| `has(string $key): bool` | Whether the key exists and is unexpired. |
| `forget(string $key): bool` | Delete the key. |

## Notes & caveats

- **Single-server only with `ApcuStore`.** APCu memory is per-PHP-FPM-pool, so buckets do not cross servers. For multi-server, implement `StoreInterface` against Redis or Memcached.
- **Fixed window, not sliding.** A user who exhausts the limit at the very end of a window can immediately consume the full quota again as soon as the window resets. 

- **Lockout vs. counter.** `tooManyAttempts()` consults a separate `:timer` key, so even if the counter expires the user remains locked out until the window's timer ends. This prevents counter-reset bypasses.
- **CLI tasks with APCu.** If you hit the limiter from CLI scripts (e.g. queue workers), set `apc.enable_cli=1` or those calls will silently no-op.

- **FileStore directory.** Make sure the directory is writable by PHP and excluded from any backup/sync that might fight `flock()`.
