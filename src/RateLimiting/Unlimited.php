<?php
namespace True\RateLimiting;

/**
 * A Limit subclass that bypasses rate limiting entirely. Returned by
 * `Limit::none()` and recognised by the RateLimiter — it short-circuits
 * `tooManyAttempts()` to false without touching the store.
 *
 * @author Daniel Baldwin
 * @version 1.0.0
 */
class Unlimited extends Limit
{
	public function __construct()
	{
		parent::__construct('', PHP_INT_MAX, 0);
	}
}
