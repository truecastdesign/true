<?php
namespace True\RateLimiting;

/**
 * File-backed cache store for the RateLimiter.
 *
 * Each key is written as one file under `$dir`. Atomic increment is achieved
 * with `flock(LOCK_EX)`. Suitable for low-to-medium traffic; for high traffic
 * use ApcuStore (or a Redis implementation of StoreInterface) instead.
 *
 * Files self-expire on read (the recorded `expires_at` is checked). A 1%
 * chance of GC sweeps the directory on every write to keep it from growing
 * unboundedly when traffic dies down.
 *
 * @author Daniel Baldwin
 * @version 1.0.0
 */
class FileStore implements StoreInterface
{
	public function __construct(public string $dir)
	{
		if (!is_dir($this->dir)) @mkdir($this->dir, 0775, true);
	}

	private function path(string $key): string
	{
		// Hashed file name keeps unsafe characters / long keys out of the
		// filesystem. The key itself is still recoverable from the file
		// contents if you ever need to debug.
		return rtrim($this->dir, '/') . '/' . hash('sha256', $key) . '.cache';
	}

	private function read(string $path): ?array
	{
		if (!is_file($path)) return null;
		$raw = @file_get_contents($path);
		if ($raw === false || $raw === '') return null;
		$data = @unserialize($raw);
		if (!is_array($data) || !isset($data['expires_at'], $data['value'])) return null;
		if ($data['expires_at'] !== 0 && $data['expires_at'] <= time()) {
			@unlink($path);
			return null;
		}
		return $data;
	}

	private function write(string $path, mixed $value, int $ttl): void
	{
		$data = ['expires_at' => $ttl > 0 ? time() + $ttl : 0, 'value' => $value];
		file_put_contents($path, serialize($data), LOCK_EX);
		if (mt_rand(0, 99) === 0) $this->gc();
	}

	public function get(string $key, mixed $default = null): mixed
	{
		$data = $this->read($this->path($key));
		return $data === null ? $default : $data['value'];
	}

	public function add(string $key, mixed $value, int $ttl): bool
	{
		$path = $this->path($key);
		$existing = $this->read($path);
		if ($existing !== null) return false;
		$this->write($path, $value, $ttl);
		return true;
	}

	public function put(string $key, mixed $value, int $ttl): void
	{
		$this->write($this->path($key), $value, $ttl);
	}

	public function increment(string $key, int $amount = 1): int
	{
		$path = $this->path($key);
		$fp = fopen($path, 'c+');
		if (!$fp) return 0;
		try {
			flock($fp, LOCK_EX);
			$raw = stream_get_contents($fp);
			$data = $raw !== '' ? @unserialize($raw) : null;
			if (!is_array($data) || !isset($data['expires_at'], $data['value'])
				|| ($data['expires_at'] !== 0 && $data['expires_at'] <= time())) {
				$data = ['expires_at' => 0, 'value' => 0];
			}
			$data['value'] = ((int) $data['value']) + $amount;
			ftruncate($fp, 0);
			rewind($fp);
			fwrite($fp, serialize($data));
			fflush($fp);
			return (int) $data['value'];
		} finally {
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}

	public function decrement(string $key, int $amount = 1): int
	{
		return $this->increment($key, -$amount);
	}

	public function has(string $key): bool
	{
		return $this->read($this->path($key)) !== null;
	}

	public function forget(string $key): bool
	{
		$path = $this->path($key);
		return is_file($path) ? @unlink($path) : true;
	}

	/** Sweep expired files. Called probabilistically from write(). */
	private function gc(): void
	{
		$now = time();
		foreach (glob(rtrim($this->dir, '/') . '/*.cache') ?: [] as $file) {
			$raw = @file_get_contents($file);
			$data = $raw !== false && $raw !== '' ? @unserialize($raw) : null;
			if (!is_array($data) || ($data['expires_at'] ?? 0) !== 0 && $data['expires_at'] <= $now) {
				@unlink($file);
			}
		}
	}
}
