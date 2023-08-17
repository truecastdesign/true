<?php
use PHPUnit\Framework\TestCase;
require_once "src/Nonce.php";

final class NonceTest extends TestCase
{
	public function testNonceUniqueness(): void
	{
		$list = [];

		for ($i=0; $i<10000000; $i++) {
			$list[] = \True\Nonce::create();
		}

		$uniqueArray = array_unique($list);
		
		$this->assertSame($list, $uniqueArray);
	}
}