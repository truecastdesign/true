<?php

namespace True;

class GoogleAuthenticator
{
	var $codeLength = 6;
	
	/**
	 * Generates a URL that is used to show a QR code.
	 *
	 * Account names may not contain a double colon (:). Valid account name
	 * examples:
	 *  - "John.Doe@gmail.com"
	 *  - "John Doe"
	 *  - "John_Doe_976"
	 *
	 * The Issuer may not contain a double colon (:). The issuer is recommended
	 * to pass along. If used, it will also be appended before the accountName.
	 *
	 * The previous examples with the issuer "Acme inc" would result in label:
	 *  - "Acme inc:John.Doe@gmail.com"
	 *  - "Acme inc:John Doe"
	 *  - "Acme inc:John_Doe_976"
	 *
	 * The contents of the label, issuer and secret will be encoded to generate
	 * a valid URL.
	 *
	 * @param string      $accountName The account name to show and identify
	 * @param string      $secret      The secret is the generated secret unique to that user
	 * @param string|null $issuer      Where you log in to
	 * @param int         $size        Image size in pixels, 200 will make it 200x200
	 *
	 * @return string
	 */
	public static function generateQRCode(string $accountName, string $secret, string $issuer = null, int $size = 200): string
	{
		if (empty($accountName)) {
			throw new \Exception("The account name can't be empty!");
		}
		
		if (false !== strpos($accountName, ':')) {
			throw new \Exception("The account name can't contain a colon!");
		}

		if (empty($secret)) {
			throw new \Exception("Invalid Secret!");
		}

		$otpauthString = 'otpauth://totp/%s?secret=%s';

		if (null !== $issuer) {
			if (empty($issuer) || false !== strpos($issuer, ':')) {
				throw new \Exception("Invalid issuer $issuer!");
			}

			// use both the issuer parameter and label prefix as recommended by Google for BC reasons
			$accountName = $issuer.':'.$accountName;
			$otpauthString .= '&issuer=%s';
		}

		$otpauthString = rawurlencode(sprintf($otpauthString, $accountName, $secret, $issuer));

		return sprintf(
			'https://chart.googleapis.com/chart?chs=%1$dx%1$d&chld=M|0&cht=qr&chl=%2$s',
			$size,
			$otpauthString
		);
	}
	
	/**
	* @param string $secret
	* @param string $code
	*/
	public function checkCode($secret, $code): bool
	{
		$result = 0;
		$now = time();

		// current period
		$result += hash_equals($this->getCode($secret, $now), $code);

		// previous period, happens if the user was slow to enter or it just crossed over
		$result += hash_equals($this->getCode($secret, $now - 30), $code);

		// next period, happens if the user is not completely synced and possibly a few seconds ahead
		$result += hash_equals($this->getCode($secret, $now+30), $code);

		return $result > 0;
	}

	public function setOTPCookie($username, $secret): void
	{
		$time = floor(time() / (3600 * 24)); // get day number
		//about using the user agent: It's easy to fake it, but it increases the barrier for stealing and reusing cookies nevertheless
		// and it doesn't do any harm (except that it's invalid after a browser upgrade, but that may be even intented)
		$cookie = $time.':'.hash_hmac('sha1', $username.':'.$time.':'.$_SERVER['HTTP_USER_AGENT'], $secret);
		setcookie('otp', $cookie, time() + (30 * 24 * 3600), null, null, null, true);
	}

	public function hasValidOTPCookie($username, $secret)
	{
		// 0 = tomorrow it is invalid
		$daysUntilInvalid = 0;
		$time = (string) floor((time() / (3600 * 24))); // get day number
		if (isset($_COOKIE['otp'])) {
			list($otpday, $hash) = explode(':', $_COOKIE['otp']);

			if ($otpday >= $time - $daysUntilInvalid && $hash == hash_hmac('sha1', $username.':'.$otpday.':'.$_SERVER['HTTP_USER_AGENT'], $secret)) {
					return true;
			}
		}

		return false;
	}

	/**
	 * Calculate the code, with given secret and point in time.
	 *
	 * @param string   $secret
	 * @param int|null $timeSlice
	 *
	 * @return string
	 */
	private function getCode($secret, $timeSlice = null)
	{
		if ($timeSlice === null) 
			$timeSlice = floor(time() / 30);

		$secretkey = $this->base32Decode($secret);

		// Pack time into binary string
		$time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
		// Hash it with users secret key
		$hm = hash_hmac('SHA1', $time, $secretkey, true);
		// Use last nipple of result as index/offset
		$offset = ord(substr($hm, -1)) & 0x0F;
		// grab 4 bytes of the result
		$hashpart = substr($hm, $offset, 4);

		// Unpak binary value
		$value = unpack('N', $hashpart);
		$value = $value[1];
		// Only 32 bits
		$value = $value & 0x7FFFFFFF;

		$modulo = pow(10, $this->codeLength);

		return str_pad($value % $modulo, $this->codeLength, '0', STR_PAD_LEFT);
	}

	/**
		* Generate a encryption key or token
		*
		* @param int $length
		* @return string
		* @author Daniel Baldwin
		**/
	public static function genSecret($length = 64)
	{
		return substr(bin2hex(openssl_random_pseudo_bytes($length)), 0, $length-1);
	}

	private function base32Decode($secret)
	{
		if (empty($secret)) return '';

		$base32chars = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X','Y', 'Z', '2', '3', '4', '5', '6', '7', '=',];
		$base32charsFlipped = array_flip($base32chars);

		$paddingCharCount = substr_count($secret, $base32chars[32]);
		$allowedValues = array(6, 4, 3, 1, 0);
		if (!in_array($paddingCharCount, $allowedValues)) {
			return false;
		}
		for ($i = 0; $i < 4; ++$i) {
			if ($paddingCharCount == $allowedValues[$i] && substr($secret, -($allowedValues[$i])) != str_repeat($base32chars[32], $allowedValues[$i])) 
				return false;
		}
		$secret = str_split(str_replace('=', '', $secret));
		$binaryString = '';
		for ($i = 0; $i < count($secret); $i = $i + 8) {
			$x = '';
			if (!in_array($secret[$i], $base32chars)) 
				return false;
			for ($j = 0; $j < 8; ++$j) {
				$x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
			}
			$eightBits = str_split($x, 8);
			for ($z = 0; $z < count($eightBits); ++$z) {
				$binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
			}
		}
		return $binaryString;
	}
}