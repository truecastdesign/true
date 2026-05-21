<?php
namespace True\RateLimiting;

/**
 * APCu-backed cache store for the RateLimiter.
 *
 * Pros: in-memory, atomic increments, no I/O.
 * Cons: per-process (single PHP-FPM pool / single server). For multi-server
 * deployments, write a Redis or Memcached implementation of StoreInterface.
 *
 * Requires `ext-apcu` and `apc.enable_cli=1` if you run CLI tasks that hit
 * the limiter.
 *
 * @author Daniel Baldwin
 * @version 1.0.0
 */
class ApcuStore implements StoreInterface
{
	public function __construct(public string $prefix = 'true:ratelimiter:') {}

	private function k(string $key): string { return $this->prefix . $key; }

	public function get(string $key, mixed $default = null): mixed
	{
		$success = false;
		$value = apcu_fetch($this->k($key), $success);
		return $success ? $value : $default;
	}

	public function add(string $key, mixed $value, int $ttl): bool
	{
		return apcu_add($this->k($key), $value, $ttl);
	}

	public function put(string $key, mixed $value, int $ttl): void
	{
		apcu_store($this->k($key), $value, $ttl);
	}

	public function increment(string $key, int $amount = 1): int
	{
		$result = apcu_inc($this->k($key), $amount, $success);
		return $success ? (int) $result : 0;
	}

	public function decrement(string $key, int $amount = 1): int
	{
		$result = apcu_dec($this->k($key), $amount, $success);
		return $success ? (int) $result : 0;
	}

	public function has(string $key): bool
	{
		return apcu_exists($this->k($key));
	}

	public function forget(string $key): bool
	{
		return apcu_delete($this->k($key));
	}
}
