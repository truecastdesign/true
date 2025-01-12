<?php

namespace True;

/**
 * PHP authentication system
 *
 *
 * @package True 6 framework
 * @author Daniel Baldwin
 * @version 1.3.1
 */
class AuthenticationJWT
{
	private $loggedIn = false;
	private $userId = null;
	private $fullName = null;
	private $email = null;
	private $user = null;
	private $loginAttempts = null;
	private $JWT = null;
	private $config = null;
	public $supportedAlgorithms;
	
	/**
	 * Construct
	 *
	 * @param class $userClass
	 * @param class $loginAttemptClass
	 * @param class $JWT
	 * @param class $PasswordGenerator  needs a generate method that accepts word count. Passes it 5.
	 * @param class $App True app class with getConfig and writeConfig methods
	 * @param array $config ['attemptsAllowed'=>8, 'alg'=>'RS256', 'privateKey'=>'/path/key.pem', 'publicKey'=>'/path/key.pem', 'pemkeyPassword'=>'string', 'encryptionPasswordFile'=>'trueadminAuth.ini', 'cookie'=>'authjwt', 'ttl'=>time()+60*60*24*30, 'https'=>true, 'httpOnly'=>true]]
	 */
	public function __construct(object $userClass, object $loginAttemptClass, object $JWT, object $PasswordGenerator, object $App, array $config = [])
	{
		$this->supportedAlgorithms = $this->getSupportedAlgorithms();
		
		$this->user = $userClass;
		$this->loginAttempts = $loginAttemptClass;
		$this->JWT = $JWT;
		// defaults
		$this->config = [
			'attemptsAllowed'=>8, 
			'alg'=>'RS256', 
			'privateKeyFile'=>null, 
			'publicKeyFile'=>null, 
			'pemkeyPassword'=>null, 
			'encryptionPasswordFile'=>null, 
			'cookie'=>'authjwt', 
			'ttl'=>60*60*24*30, 
			'https'=>true, 
			'httpOnly'=>true
		];
		
		// merge the defaults with the passed config values
		$this->config = (object) array_merge($this->config, $config);
		
		if (is_null($this->config->privateKeyFile))
			throw new \Exception("The private encription key is missing. Pass it in the 'privateKeyFile' array key in the config paramater of the construct.");		

		if (is_null($this->config->publicKeyFile))
			throw new \Exception("The public encription key is missing. Pass it in the 'publicKeyFile' array key in the config paramater of the construct.");

		if (is_null($this->config->encryptionPasswordFile))
			throw new \Exception("The encryptionPasswordFile with the trueadminAuth.ini file path is missing!");

		// If encryption keys are not available, create them and save password
		if (!file_exists($this->config->privateKeyFile) or !file_exists($this->config->publicKeyFile)) {
			$conf = [
				'private_key_bits'=>4096,
				'encrypt_key_cipher'=>OPENSSL_CIPHER_AES_256_CBC,
				'encrypt_key'=>true,
				'digest_alg'=>"sha512"
			];
			
			$password = $PasswordGenerator->generate(5);

			$pkeyRes = openssl_pkey_new($conf);
			openssl_pkey_export($pkeyRes, $privateKey, $password, $conf);
			
			$publicKey = openssl_pkey_get_details($pkeyRes);
			
			if (empty($privateKey))
				throw new \Exception("The private key failed to generate.");

			if (empty($publicKey["key"]))
				throw new \Exception("The public key failed to generate.");

			file_put_contents($this->config->privateKeyFile, $privateKey);
			
			if (filesize($this->config->privateKeyFile) == 0) {
				unlink($this->config->privateKeyFile);
				throw new \Exception("The private key failed to generate in ".$this->config->privateKeyFile);				
			}

			file_put_contents($this->config->publicKeyFile, $publicKey["key"]);

			if (filesize($this->config->publicKeyFile) == 0) {
				unlink($this->config->publicKeyFile);
				throw new \Exception("The public key failed to generate in ".$this->config->publicKeyFile);
				
			}

			// save password to config file
			$authConfig = $App->getConfig($this->config->encryptionPasswordFile);
			$authConfig->pemkeyPassword = $password;
			$this->config->pemkeyPassword = $password;
			$App->writeConfig($this->config->encryptionPasswordFile, (array)$authConfig);

			$this->logout();
		}
			
		if (is_null($this->config->pemkeyPassword))
			throw new \Exception("The private encription key password is missing. Pass it in the 'pemkeyPassword' array key in the config paramater of the construct.");
	}

	public function login(string $username, string $password): bool
	{
		# check if fields are missing
		if (empty($username) AND empty($password))
			throw new \Exception("Missing Username and Password.");
		
		if (empty($password))
			throw new \Exception("Missing the Password.");
		
		if (empty($username))
			throw new \Exception("Missing the Username.");

		# clean username and password
		$username = trim(strip_tags($username));
		$password = trim(strip_tags($password));

		if ($this->loginAttempts->lockout_time > time()) {
			throw new \Exception("Sorry, please wait ".\True\Functions::timeToStr($this->loginAttempts->lockout_time - time())." before logging in again.");
		}

		# check if they have any attempts left
		if ($this->loginAttempts->count > $this->config->attemptsAllowed) {
			throw new \Exception("Sorry, you have had ".$this->loginAttempts->count." failed login attempts.<br>We temporarily forbid access in order to protect your private information.<br>Please wait 5 minutes before logging in again.");
		}

		# check username and password
		if (!$this->user->checkLogin($username, $password)) {
			$this->loginAttempts->set(["lockout_time"=>time()]);
			throw new \Exception("Account not found.");
		}

		$this->userId = $this->user->getId();

		$userInfo = $this->getUserInfo();
		
		if ($userInfo->google2FAAuth == 'on') {
			$partialToken = $this->JWT->encode([
				'userId' => $userInfo->id,
				'status' => '2fa_pending'
			], $this->config->privateKeyFile, $this->config->pemkeyPassword, $this->config->alg);
	
			$this->setCookie($partialToken); // Set a cookie with the partial JWT
			return false; // Indicate that 2FA is needed
		}
	
		$fullToken = $this->JWT->encode([
			'userId' => $userInfo->id,
			'status' => 'authenticated'
		], $this->config->privateKeyFile, $this->config->pemkeyPassword, $this->config->alg);
	
		$this->loginAttempts->set(["lockout_time"=>0, "count"=>0]); // Reset login attempts after successful login

		$this->setCookie($fullToken); // Set a cookie with the full JWT

		return true;
	}

	public function verifyGoogleAuth2FA(string $twoFACode, int $userId, object $GoogleAuthenticator): bool
	{
		$userInfo = (object) $this->user->get($userId, ['fields'=>'google2FAAuthSecret']);
		$checkResult = $GoogleAuthenticator->verifyCode($userInfo->google2FAAuthSecret, $twoFACode, 2);

		if ($checkResult) {
			// Generate a new JWT for fully authenticated session
			$fullToken = $this->JWT->encode([
				'userId' => $userId,
				'status' => 'authenticated'
			], $this->config->privateKeyFile, $this->config->pemkeyPassword, $this->config->alg);

			$this->setCookie($fullToken);
			return true;
		}
		
		return false;
	}

	/**
	 * When your checking googleAuth2fAStep() and want to return to login form
	 */
	public function cancel2FA()
	{
		setcookie($this->config->cookie, '', -3600, '/', $this->getDomain(), $this->config->https, $this->config->httpOnly);	
	}

	public function logout(): void
	{
		$this->setCookie('', -3600);
	}

	/**
	 * check if user is logged in
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function isLoggedIn(): bool
	{
		$jwtToken = $_COOKIE[$this->config->cookie] ?? '';
		
		if (empty($jwtToken))
			return false;
		
		try {
			$payload = $this->JWT->decode($jwtToken, $this->config->publicKeyFile, [$this->config->alg]);
			
			if (!is_numeric($payload->userId))
				return false;
			
			$this->userId = $payload->userId;
			
			$userInfo = $this->getUserInfo();

			if ($userInfo->google2FAAuth == 'on')
				if ($payload->status != 'authenticated')
					return false;		

			$this->setCookie($jwtToken);

			return true;
		} catch (\Exception $ex) {
			throw new \Exception("User is not logged in. ".$ex->getMessage());
		}
	}

	function googleAuth2fAStep(): bool 
	{
		$jwtToken = $_COOKIE[$this->config->cookie] ?? '';
		
		if (empty($jwtToken))
			return false;

		try {
			$payload = $this->JWT->decode($jwtToken, $this->config->publicKeyFile, [$this->config->alg]);

			return ($payload->status == '2fa_pending')? true:false;
			
		} catch (\Exception $ex) {
			
		}
	}

	/**
	 * @return object full user info
	 */
	public function getUserInfo()
	{
		$info = $this->user->get($this->userId, ['type'=>'array']);
		
		$this->fullName = $info['first_name'].' '.$info['last_name'];
		
		$this->email = $info['email'];

		return (object) $info;
	}

	/**
	 * return the current user id if they are logged in and false if they are not logged in
	 *
	 * @return int|false user id
	 * @author Daniel Baldwin
	 */
	public function id(): ?int
	{
		if (is_numeric($this->userId))
			return $this->userId;
		else 
			throw new \Exception("User is not logged in or username not set.");
	}

	/**
	 * Get the full name of the admin user
	 *
	 * @return string users name
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function fullName(): string
	{
		if(is_null($this->fullName) AND $this->id()) 
			return $this->fullName = $this->user->fullName($this->id());	
		else 
			return $this->fullName;
	}

	/**
	 * Returns the current logged in username
	 *
	 * @return false or string username
	 */
	public function username(): ?string
	{
		if (!is_numeric($this->id()))
			return false;

		return $this->user->username($this->id());
	}

	/**
	 * returns the current users email address
	 *
	 * @return string email address
	 * @author Daniel Baldwin
	 */
	public function email(): string
	{
		return $this->email;
	}

	public function getTokenPayload()
	{
		$jwtToken = $_COOKIE[$this->config->cookie] ?? '';
		if (empty($jwtToken)) {
			return null;
		}

		try {
			$payload = $this->JWT->decode($jwtToken, $this->config->publicKeyFile, [$this->config->alg]);
			return $payload;
		} catch (\Exception $e) {
			return null;
		}
	}

	public function updateToken(array $additionalClaims = []): void
	{
		$payload = $this->JWT->decode($_COOKIE[$this->config->cookie], $this->config->publicKeyFile, [$this->config->alg]);

		$newPayload = array_merge((array) $payload, $additionalClaims);
		$fullToken = $this->JWT->encode($newPayload, $this->config->privateKeyFile, $this->config->pemkeyPassword, $this->config->alg);

		$this->setCookie($fullToken);
	}

	private function getDomain(): string
	{
		return strtok($_SERVER['HTTP_HOST'], ':');
	}
	
	private function setCookie(string $jwtToken, $time=null): void
	{  
		if (is_null($time)) 
			$time = $this->config->ttl;
	
		setcookie($this->config->cookie, $jwtToken, intval(time()+$time), '/', $this->getDomain(), $this->config->https, $this->config->httpOnly);
	}

	// Dynamically fetch the list of supported algorithms
	private function getSupportedAlgorithms(): array
	{
		$supportedAlgs = [];

		// Check if OpenSSL supports RSA algorithms
		if (function_exists('openssl_sign')) {
			$supportedAlgs = array_merge($supportedAlgs, ['RS256', 'RS384', 'RS512']);
		}

		// Add HMAC algorithms from hash_algos()
		$hashAlgos = hash_algos();
		if (in_array('sha256', $hashAlgos)) $supportedAlgs[] = 'HS256';
		if (in_array('sha384', $hashAlgos)) $supportedAlgs[] = 'HS384';
		if (in_array('sha512', $hashAlgos)) $supportedAlgs[] = 'HS512';

		return $supportedAlgs;
	}
}