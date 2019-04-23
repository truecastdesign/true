<?php
namespace True;

/**
 * General authentication class
 *
 * @package Truecast
 * @author Daniel Baldwin
 * @version 3.2.1
 * 
 * Fixed password and username cleaning to allow for capitals.
 * Change the realName() function to fullName() to match the users method called.
 */

class Authentication
{

	private $hidden_hash_var = null;
	private $LOGGED_IN = false;
	private $id_hash = null;
	private $loginTime = 300; # 300 = 5 min.
	private $loginAttemptsAllowed = 5;
	private $loginTimeOut = 300; # 300 = 5 min.
	private $feedback = null;
	private $curUser = null;
	private $curRealName = null;
	private $cookieUser = null;
	private $cookieHash = null;
	private $cookieLockOut = null;
	private $cookieCount = null;
	private $loginLockout = null;
	private $failCount = 0;
	private $allow = null;
	private $isAdmin = false;
	private $userEmail = null;
	private $hostName = null;
	
	/**
	 * construct
	 *
	 * @param obj|array $config [private_key=>, login_time=>, cookie_user=>, cookie_hash=>, cookie_lockout=>, cookie_count=>]
	 * @param obj $userClass class to check user login. Need a checkLogin method and a set method with a login_ip field. Needs a fullName method that accepts a id and return the users full name. Needs a get method that returns user fields accepting the user's id to look it up.
	 * @param obj $loginAttemptClass needs to be an instance of the TALoginAttempts class.
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function __construct($config, $userClass, $loginAttemptClass)
	{
		if(is_array($config))
			$config = (object) $config;

		# $privateKey : authentication private key
		# $loginTime : custom login time in seconds, send 0 for default time of 5 min.
		# $cookieUser : cookie name for user, i.e. sitename_user
		# $cookieHash : cookie name for hash, i.e. sitename_hash
		# $cookieCount : cookie name for count, i.e. sitename_count
		# $userTable : database table where user credentials are compared with.
		# $firstNameField : database field where user has first name.
		# $lastNameField : database field where user has last name.
		# $db : database object
		
		unset($this->LOGGED_IN);
		$this->hidden_hash_var = $config->private_key;
		$this->loginTime = ($config->login_time > 0)? $config->login_time:$this->loginTime;
		$this->cookieUser = $config->cookie_user;
		$this->cookieHash = $config->cookie_hash;
		$this->cookieLockOut = $config->cookie_lockout;
		$this->cookieCount = $config->cookie_count;
		$this->user = $userClass;
		$this->loginAttempt = $loginAttemptClass;
		
		# fix for Chrome which will not set cookie if domain is localhost
		$this->hostName = ($_SERVER['SERVER_NAME'] == 'localhost') ? false : '.'.$_SERVER['HTTP_HOST'];
	}
	
	public function isUserloggedin()
	{
	    $this->getCookies(); # have we already run the hash checks? If so, return the pre-set, trusted var
	    if($this->curUser AND $this->id_hash) //are both cookies present?
		{
	        /*
	         Create a hash of the user name that was passed in from the cookie as well as the trusted hidden variable
				If this hash matches the cookie hash, then all cookie vars must be correct and thus trustable
	        */
			$hash=md5($this->curUser.$this->hidden_hash_var);
			if($hash == $this->id_hash)
			{
			    //hashes match - set a global var so we can call this function repeatedly without redoing the md5()'s
			    $this->LOGGED_IN=true;
			    return true;
			} 
			else //hash didn't match - must be a hack attempt?
			{
			    $this->LOGGED_IN=false;
			    return false;
			}
	    } 
		else 
		{
	        $this->LOGGED_IN=false;
	        return false;
	    }
	}
	
	public function login($username, $password)
	{
		if(!$username AND !$password)
		{
			trigger_error("Missing Username and Password.",512);        
			return false;
		}
		elseif(!$password) 
		{
			trigger_error("Missing the Password.",512);        
			return false;
		} 
		elseif(!$username) 
		{
			trigger_error("Missing the Username",512);        
			return false;
		}
		else 
		{
			$username = trim(strip_tags($username));
			$password = trim(strip_tags($password));
			$errTooManyAttempts = "Sorry, you have had ".$this->loginAttemptsAllowed." failed login attempts. 
		<br />We temporarily forbid access in order to protect your private information.
		<br />Please wait 5 minutes before logging in again.";
			if(!$_COOKIE[$this->cookieCount]) setcookie($this->cookieCount,"2",(time()+8000),'/',$this->hostName);
			if(!isset($_COOKIE[$this->cookieLockOut])) setcookie($this->cookieLockOut,"0",(time()+8000),'/',$this->hostName);
			
			# If they are at their login limit.
			if($_COOKIE[$this->cookieCount] == $this->loginAttemptsAllowed)
			{
				# When they reach their max login times, add one more login time
				# so this code will not run and the code below will.
				trigger_error($errTooManyAttempts,512);
				$this->failCount = $_COOKIE[$this->cookieCount]+1;
				setcookie($this->cookieCount,$_COOKIE[$this->cookieCount]+1,(time()+8000),'/',$this->hostName);
				setcookie($this->cookieLockOut,$this->get_microtime(),(time()+8000),'/',$this->hostName);
				$this->loginAttempt->set(array("lockout_time"=>$this->get_microtime(),"count"=>$this->failCount));
				$allowLogin = 0;
				return false;
			}
			
			# If they are over their login limit.
			if($_COOKIE[$this->cookieCount] > $this->loginAttemptsAllowed)
			{
			    # See if they have waited 5 min before trying again.
				# LockoutTime is the time when they went over their limit

				$difference = abs($this->get_microtime() - $_COOKIE[$this->cookieLockOut]);
			    $diffSeconds = round($difference);
			    if($diffSeconds > $this->loginTimeOut)
				{
					# they failed but have a new set of chances
					setcookie($this->cookieLockOut,0,(time()+8000),'/',$this->hostName);
					setcookie($this->cookieCount,0,(time()+8000),'/',$this->hostName);
					$allowLogin = 1;
				}	
				else
				{
					if($_COOKIE[$this->cookieLockOut]==0)
						setcookie($this->cookieCount,0,(time()+8000),'/',$this->hostName);
					$this->loginAttempt->set(array("lockout_time"=>$this->get_microtime(), "count"=>$this->failCount));
					trigger_error($errTooManyAttempts,512);
					$allowLogin = 0;
					return false;
				}
			}
			
			if($_COOKIE[$this->cookieCount] < $this->loginAttemptsAllowed) $allowLogin = 1;
			
			if($allowLogin) # Attempt login
			{
				if($this->user->checkLogin($username, $password)) # Found correct Login
				{
					#$this->user->set(array("id"=>$this->user->getId(),"login_ip"=>$_SERVER['REMOTE_ADDR']));
					$this->userSetTokens($this->user->getId());
					$this->LOGGED_IN = true;
					setcookie($this->cookieLockOut,0,(time()+8000),'/',$this->hostName);
					setcookie($this->cookieCount,0,(time()+8000),'/',$this->hostName);
					return true;
				}
				else
				{
					$this->failCount = $_COOKIE[$this->cookieCount]+1;
					setcookie($this->cookieCount,$_COOKIE[$this->cookieCount]+1,(time()+8000),'/',$this->hostName);
					trigger_error("Account not found.",512);
					return false;
				}
			}
		}
	}

	public function logout()
	{
		setcookie($this->cookieUser,'',(time()-60),'/',$this->hostName);
		setcookie($this->cookieHash,'',(time()-60),'/',$this->hostName);
		$this->LOGGED_IN = false;
	}
	
	/**
	 * return the current user id if they are logged in and false if they are not logged in
	 *
	 * @return int|false user id
	 * @author Daniel Baldwin
	 */
	public function id()
	{
		if($this->LOGGED_IN AND $this->curUser) return $this->curUser;
		else
		{
			trigger_error("User is not logged in or username not set.",512);
			return false;
		} 
	}
	
	/**
	 * caches the admin user info that will be readily needed
	 *
	 * @return void
	 * @author Daniel Baldwin
	 */
	public function cacheUserInfo()
	{
		$info = $this->user->get($this->id());
		
		if($info['admin'] == 1) $this->isAdmin = true;
		else $this->isAdmin = false;
			
		$this->curRealName = $info['first_name'].' '.$info['last_name'];
		
		$this->userEmail = $info['email'];
	}
	
	/**
	 * Get the full name of the admin user
	 *
	 * @return string users name
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function fullName()
	{
		if(is_null($this->curRealName) AND $this->id()) 
		{ 
			return $this->curRealName = $this->user->fullName($this->id());
		}	
		else 
			return $this->curRealName;
	}

	/**
	 * *DEPRECATED* Calls fullName()
	 *
	 * @return string users name
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function realName()
	{
		return $this->fullName();
	}
	
	/**
	 * returns whether of not the logged in user is an admin
	 *
	 * @return bool
	 * @author Daniel Baldwin
	 */
	public function isAdmin()
	{
		return $this->isAdmin;
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
	
	/**
	 * set the user cookies
	 *
	 * @param int $userId 
	 * @return bool 
	 * @author Daniel Baldwin
	 */
	private function userSetTokens($userId)
	{
	    // call this once you have confirmed user name and password are correct in the database
		if(empty($userId))
		{
			trigger_error("User Name Missing When Setting Tokens",512);
			return false;
		}
		$this->curUser = $userId;

	    //create a hash of the two variables we know
		$this->id_hash = md5($this->curUser.$this->hidden_hash_var);
		
	    //set cookies for one month - set to any amount or use 0 for a session cookie
		setcookie($this->cookieUser,$this->curUser,(time()+$this->loginTime),'/',$this->hostName);
		setcookie($this->cookieHash,$this->id_hash,(time()+$this->loginTime),'/',$this->hostName);
		return true;
	}
	
	/**
	 * caches the current status of the cookies
	 *
	 * @return void
	 * @author Daniel Baldwin
	 */
	private function getCookies()
	{
		$this->curUser = $_COOKIE[$this->cookieUser];
		$this->id_hash = $_COOKIE[$this->cookieHash];
	}
	
	/**
	 * convence method that calls userSetTokens
	 *
	 * @return void
	 * @author Daniel Baldwin
	 */
	public function refresh()
	{
		$this->userSetTokens($this->curUser);
	}
	
	/**
	 * check if they are logged in
	 *
	 * @return void
	 * @author Daniel Baldwin
	 */
	public function canLogin()
	{
		if(!isset($_COOKIE[$this->cookieCount]))
		{
			$count = ($this->failCount)? $this->failCount:$_COOKIE[$this->cookieCount];
			return array("loginAllow"=>true,"count"=>$count);
		}	
		if($_COOKIE[$this->cookieCount] < $this->loginAttemptsAllowed)
		{
			$count = ($this->failCount)? $this->failCount:$_COOKIE[$this->cookieCount];
			return array("loginAllow"=>true,"count"=>$count);
		}	
		elseif($_COOKIE[$this->cookieCount] > $this->loginAttemptsAllowed)
		{
		    # See if they have waited 5 min before trying again.
			# LockoutTime is the time when they went over their limit

			$difference = abs($this->get_microtime() - $_COOKIE[$this->cookieLockOut]);
			$diffSeconds = round($difference);
			if($diffSeconds > $this->loginTimeOut)
			{
				return array("loginAllow"=>true,"count"=>0);
			}
			else
			{
				$count = ($this->failCount)? $this->failCount:$_COOKIE[$this->cookieCount];
				return array("loginAllow"=>false,"count"=>$count);
			}	
		}
		elseif($_COOKIE[$this->cookieCount] == $this->loginAttemptsAllowed)
		{
			$difference = abs($this->get_microtime() - $_COOKIE[$this->cookieLockOut]);
			$diffSeconds = round($difference);
			if($diffSeconds > $this->loginTimeOut)
			{
				return array("loginAllow"=>true,"count"=>0);
			}
			else
			{
				$count = ($this->failCount)? $this->failCount:$_COOKIE[$this->cookieCount];
				return array("loginAllow"=>false,"count"=>$count);
			}
		}
		
	}
	
	/**
	 * get microtime
	 *
	 * @return string time
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	private function get_microtime()
	{
	  	$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = doubleval($mtime[1]) + doubleval($mtime[0]);
		return ($mtime);
	}
}
?>