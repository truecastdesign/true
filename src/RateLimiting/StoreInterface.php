<?php
namespace True\RateLimiting;

/**
 * Minimal cache primitive used by the RateLimiter.
 *
 * Pick (or write) an implementation appropriate for your deployment:
 *   - ApcuStore   — single server, fastest (recommended)
 *   - FileStore   — no extension required, fine for low traffic
 *   - (your own)  — implement this interface for Redis / Memcached / etc.
 *
 * All TTLs are in seconds. `null` from `get()` means "not present or expired".
 *
 * @author Daniel Baldwin
 * @version 1.0.0
 */
interface StoreInterface
{
	/** Return the stored value, or $default if missing/expired. */
	public function get(string $key, mixed $default = null): mixed;

	/** Set only if the key does not already exist. Returns true on success. */
	public function add(string $key, mixed $value, int $ttl): bool;

	/** Set unconditionally. */
	public function put(string $key, mixed $value, int $ttl): void;

	/** Atomically increment a counter by $amount and return the new value. */
	public function increment(string $key, int $amount = 1): int;

	/** Atomically decrement a counter by $amount and return the new value. */
	public function decrement(string $key, int $amount = 1): int;

	/** Whether the key exists and has not expired. */
	public function has(string $key): bool;

	/** Delete the key. */
	public function forget(string $key): bool;
}
