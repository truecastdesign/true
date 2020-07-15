<?php

namespace True;

/**
 * PHP authentication system
 *
 *
 * @package True 6 framework
 * @author Daniel Baldwin
 * @version 1.0.0
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

	public function login($username, $password, $duration = null)
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

		setcookie($this->config->cookie, $jwtToken, $this->config->ttl, '/', $_SERVER['HTTP_HOST'], $this->config->https, $this->config->httpOnly);	 

		return true;
	}

	public function logout()
	{
		setcookie($this->config->cookie, '', time() - 3600, '/', $_SERVER['HTTP_HOST'], $this->config->https, $this->config->httpOnly);
	}

	public function isLoggedIn()
	{
		$jwtToken = $_COOKIE[$this->config->cookie];

		if (!empty($jwtToken)) {
			$payload = $this->JWT->decode($jwtToken, $this->config->key, [$this->config->alg]);

			if (is_numeric($payload)) {
				setcookie($this->config->cookie, $jwtToken, $this->config->ttl, '/', $_SERVER['HTTP_HOST'], $this->config->https, $this->config->httpOnly);
				$this->userId = $payload;
				$this->getUserInfo();
				return true;
			} else {
				return false;
			}
		}
		
		return $this->loggedIn;
	}

	public function getUserInfo()
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
	public function id()
	{
		if($this->loggedIn AND !is_null($this->userId)) {
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
	public function fullName()
	{
		if(is_null($this->fullName) AND $this->id()) 
		{ 
			return $this->fullName = $this->user->fullName($this->id());
		}	
		else 
			return $this->fullName;
	}

	/**
	 * returns the current users email address
	 *
	 * @return string email address
	 * @author Daniel Baldwin
	 */
	public function email()
	{
		return $this->email;
	}
}