<?php

namespace True;

/**
 * PHP authentication system
 *
 *
 * @package True 6 framework
 * @author Daniel Baldwin
 * @version 1.2.0
 */
class AuthenticationJWT
{
	private $loggedIn = false;
	private $userId = null;
	private $fullName = null;
	private $user = null;
	private $loginAttempts = null;
	private $JWT = null;
	private $config = null;
	
	/**
	 * Construct
	 *
	 * @param class $userClass
	 * @param class $loginAttemptClass
	 * @param class $JWT
	 * @param array $config ['cookie'=>'auth1']
	 */
	public function __construct($userClass, $loginAttemptClass, $JWT, $config = [])
	{
		$this->user = $userClass;
		$this->loginAttempts = $loginAttemptClass;
		$this->JWT = $JWT;
		$this->config = (object)['attemptsAllowed'=>8, 'alg'=>'HS512', 'key'=>null, 'cookie'=>'authjwt', 'ttl'=>time()+60*60*24*30, 'https'=>true, 'httpOnly'=>true];
		$this->config = (object) array_merge((array)$this->config, $config);

		if (is_null($this->config->key)) {
			throw new \Exception("The private encription key is missing. Pass it in the 'key' array key in the config paramater of the construct.");
		}
	}

	public function login(string $username, string $password, $duration = null): bool
	{
		# check if fields are missing
		if(empty($username) AND empty($password)) {
			throw new \Exception("Missing Username and Password.");
		}
		
		if(empty($password)) {
			throw new \Exception("Missing the Password.");
		} 
		
		if(empty($username)) {
			throw new \Exception("Missing the Username.");
		}

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

		$this->loggedIn = true;	
		$this->userId = $this->user->getId();

		if (!is_numeric($this->userId)) {
			throw new \Exception("User id not available.");
		}

		$this->getUserInfo();

		$jwtToken = $this->JWT->encode($this->userId, $this->config->key, $this->config->alg);

		$this->setCookie($jwtToken);	 

		return true;
	}

	public function logout(): void
	{
		$this->setCookie('', time() - 3600);
	}

	public function isLoggedIn(): bool
	{
		$jwtToken = $_COOKIE[$this->config->cookie];

		if (empty($jwtToken))
			return false;
		
		try {
			$payload = $this->JWT->decode($jwtToken, $this->config->key, [$this->config->alg]);
		} catch (\Exception $e) {
			return false;
		}

		if (!is_numeric($payload))
			return false;

		$this->loggedIn = true;
		$this->userId = $payload;
		$this->getUserInfo();

		setcookie($this->config->cookie, $jwtToken, $this->config->ttl, '/', $_SERVER['HTTP_HOST'], $this->config->https, $this->config->httpOnly);

		return true;
	}

	public function getUserInfo(): void
	{
		$info = $this->user->get($this->userId);
		
		$this->fullName = $info['first_name'].' '.$info['last_name'];
		
		$this->email = $info['email'];
	}

	/**
	 * return the current user id if they are logged in and false if they are not logged in
	 *
	 * @return int|false user id
	 * @author Daniel Baldwin
	 */
	public function id(): ?int
	{
		if ($this->loggedIn) {
			return $this->userId;
		} else {
			trigger_error("User is not logged in or username not set.",512);
			return false;
		} 
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
		{ 
			return $this->fullName = $this->user->fullName($this->id());
		}	
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

	private function getDomain(): string
	{
		return strtok($_SERVER['HTTP_HOST'], ':');
	}
	
	private function setCookie(string $jwtToken, $time=null): void
	{
		if (is_null($time)) 
			$time = $this->config->ttl;

		setcookie($this->config->cookie, $jwtToken, $time, '/', $this->getDomain(), $this->config->https, $this->config->httpOnly);
	}
}