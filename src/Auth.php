<?php
namespace True;

/**
 * Auth class for authenticating api calls

*
* @package True Framework
* @author Daniel Baldwin
* @version 1.2.0
*/
class Auth
{
	var $bearerTokensFile = BP.'/app/data/auth-tokens';
	var $loginTokensDb = BP.'/app/data/auth-login-tokens.db';
	var $csrfSession = 'kkj43kj';

	/**
	 * bearerTokensFile : should include the base path to a text file
	* loginTokensDb : should be a sqlite database with a 'auth' table with 'username', 'password', 'token', and 'user_id' fields
	* @param array $params [bearerTokensFile, loginTokensDb]
	*/
	public function __construct($params = null)
	{
		if (isset($params['bearerTokensFile'])) {
			$this->bearerTokensFile = $params['bearerTokensFile'];
		}

		if (isset($params['loginTokensDb'])) {
			$this->loginTokensDb = $params['loginTokensDb'];
		}
	}

	/**
	 * request token
	*
	* @param string $randomChars - at least 10 letters and numbers
	* @return void
	* @author Daniel Baldwin - danb@truecastdesign.com
	**/
	public function requestToken()
	{
		$token = $this->genToken();

		# save token to file
		if(file_put_contents($this->bearerTokensFile, $token."\n", FILE_APPEND | LOCK_EX) === false)
		{
			trigger_error("Token could not be written to a file!", 256);
			return "Token could not be written to a file!";
		}
		else
		{
			return $token;  
		}           
	}

	/**
	 * Genarate a token
	* @return string token
	*/
	public function genToken()
	{
		# get salt
		$fp = fopen('/dev/urandom', 'r');
		$salt = base64_encode(fread($fp, 16));
		fclose($fp);

		# create token
		return hash("sha256", $salt);
	}

	/**
	 * Revoke a token when done using it
	*
	* @param string $token
	* @return bool
	* @author Daniel Baldwin - danb@truecastdesign.com
	**/
	public function revokeToken($token)
	{
		$contents = file_get_contents($this->bearerTokensFile);

		$updatedContents = str_replace($token."\n", '', $contents);

		if(file_put_contents($this->bearerTokensFile, $updatedContents, LOCK_EX) === false)
		{
			trigger_error("Token could not be revoked. Could not write to file!", 256);
			return "Token could not be revoked. Could not write to file!";
		}
	}

	/**
	 * Check if token is valid
	*
	* @param string $token
	* @return bool true:valid, false:not valid
	* @author Daniel Baldwin - danb@truecastdesign.com
	**/
	public function checkToken($token)
	{
		if( strpos(file_get_contents($this->bearerTokensFile), $token) !== false)
			return true;
		else
			return false;
	}

	public function setSessionToken()
	{
		if(PHP_SESSION_ACTIVE != session_status()) {
			session_start();
		}
			
		if (function_exists('openssl_random_pseudo_bytes')) {
			$random = bin2hex(openssl_random_pseudo_bytes(32, $secure));
			# set session authenticity token
			return $_SESSION[$this->csrfSession] = $random;
		}  	
		else {
			trigger_error('The function openssl_random_pseudo_bytes is not available in PHP!',256);
		}
	}

	/**
	 * authenticate the user
	* Note: allow to pass origen domain to limit to local or certain domains or IPs
	*
	* Use in the controller file like this:
	* if ($App->auth->authenticate(['type'=>'bearer'])) {
	*  # success
	* } else {
	*  # failed
	* }
	* @param  array  $params   ['type'=>'bearer', 'token'=>''] the token param is only needed for session based CSRF checking. Pass in the submitted value.
	* @param  function $callbackSuccessful the function that is called if authenticate passes
	* @param  function $callbackFail the function that is called if authenticate fails
	* @return null 
	*/
	public function authenticate(array $params)
	{
		extract($params);
		
		if (isset($type)) {
			switch ($type) {
					case 'bearer': 
						$headers = $this->requestHeaders();
						
						if (isset($headers['Authorization'])) {
							$token = str_replace('Bearer ','',$headers['Authorization']);

							if (!empty($token) and $this->checkToken($token)) {
									return true;
							} else {
									return false;
							}
						} else {
							return false;
						}
					break;

					case 'login':
						if(!isset($username) OR !isset($password)) {
							return false;
						}

						$DB = new \PDO('sqlite:'.$this->loginTokensDb);
						try {
							$dbRes = $DB->prepare("select user_id,password from auth where username=?");
							$dbRes->execute([$username]);
							$result = $dbRes->fetch(2);
						} catch(PDOException $ex) { echo $ex->getMessage(); }
						
						$passwordVerified = ( password_verify($password, $result['password']) ) ? true:false;
						
						if ($passwordVerified) {
							$token = $this->genToken();
							try {
									$dbRes = $DB->prepare('update auth set token=? where user_id=?');
									$dbRes->execute([$token, $result['user_id']]);
							} catch(PDOException $ex) { echo $ex->getMessage(); }
							
							return ['token'=>$token, 'id'=>$result['user_id']];
						} else {
							return false;
						}
					break;

					case 'login-token':
						if(!isset($token)) {
							return false;
						}
			
						$DB = new \PDO('sqlite:'.$this->loginTokensDb);
						try {
							$dbRes = $DB->prepare("select user_id from auth where token=?");
							$dbRes->execute([$token]);
							$result = $dbRes->fetch(2);
						} catch(PDOException $ex) { echo $ex->getMessage(); }
						
						if (is_numeric($result['user_id'])) {
							return $result['user_id'];
						} else {
							return false;
						}
					break;

					case 'session-token':
						if(PHP_SESSION_ACTIVE != session_status()) {
							session_start();
						}
						$sessionToken = $_SESSION[$this->csrfSession];	

						# check session authenticity token
						if ($sessionToken === false or empty($sessionToken)) {
							return false;
						}

						if ($sessionToken != $token) {
							return false;
						}

						return true;		
					break;

					default:
						return false;
			}
		}
	}

	/**
	 * Add user to login table
	* @param array $params ['username', 'password', 'id']
	* @return bool|int id if successfully inserted user or false if there was an error
	*/
	public function addUser($params)
	{
		extract($params);

		if(!isset($password) or !isset($username) or !isset($id)) {
			trigger_error("Username, password, or id is empty!", 256);
			return false;
		}

		$DB = new \PDO('sqlite:'.$this->loginTokensDb);
		$dbRes = $DB->prepare("select user_id from auth where username=? or user_id=?");
		$dbRes->execute([$username, $id]);
		$result = $dbRes->fetch(2);
		
		if (isset($result['user_id'])) {
			trigger_error("Username ".$username." or user id ".$id." is already in the database.", 256);
			return false;
		}

		$password = $this->hashPassword($password);

		$dbRes = $DB->prepare("insert into auth (username,password,user_id) values(?,?,?)");
		$dbRes->execute([$username, $password, $id]);

		return $dbRes->lastInsertId();
	}

	/**
	 * delete user from login table
	* @param  array $params ['id']
	* @return bool true if successful and false
	*/
	public function deleteUser($params)
	{
		extract($params);

		if(!isset($id)) {
			trigger_error("id is empty!", 256);
			return false;
		}

		$DB = new \PDO('sqlite:'.$this->loginTokensDb);
		$dbRes = $DB->prepare("delete from auth where user_id=?");
		$dbRes->execute([$id]);

		return true;
	}

	/**
	 * update user in login table
	* @param  array $params ['username', 'password', 'id']
	* @return bool true if successful and false
	*/
	public function updateUser(array $params)
	{
		extract($params);

		if (count($params) == 0) {
			trigger_error("No parameters to update!", 256);
			return false;
		}

		if (!isset($id)) {
			trigger_error("No user id provided!", 256);
			return false;
		}

		$setFields = [];
		$setValues = [];

		if (isset($username) and !empty($username)) {
			$setFields[] = 'username';
			$setValues[] = $username;
		}

		if (isset($password) and !empty($password)) {
			$setFields[] = 'password';
			$setValues[] = $this->hashPassword($password);
		}

		$setValues[] = $id;

		$DB = new \PDO('sqlite:'.$this->loginTokensDb);
		$dbRes = $DB->prepare('update auth set '.implode("=?, ",$setFields).'=? where user_id=?');
		$dbRes->execute($setValues);

		return true;
	}

	/**
	 * Get a users fields
	 *
	 * @param array $params ['id|user_id'=>1, 'fields'=>'username,token']
	 * @return object
	 */
	public function getUser(array $params)
	{
		extract($params);

		if (is_numeric($id)) {
			$user_id = $id;
		} 

		if (!isset($fields) OR empty($fields)) {
			$fields = '*';
		}
		
		if (is_numeric($user_id)) {
			$DB = new \PDO('sqlite:'.$this->loginTokensDb);
			$dbRes = $DB->prepare('select '.$fields.' from auth where user_id=?');
			$dbRes->execute([$user_id]);
			return $dbRes->fetch(PDO::FETCH_OBJ);
		}
	}

	/**
	 * update user in login table
	* @param  array $params ['token']
	* @return bool true if successful and false
	*/
	public function logoutUser(array $params)
	{
		extract($params);

		if (!isset($token)) {
			trigger_error("No user token provided!", 256);
			return false;
		}

		$DB = new \PDO('sqlite:'.$this->loginTokensDb);
		$dbRes = $DB->prepare("update auth set token='' where token=?");
		$dbRes->execute([$token]);

		return true;
	}

	/**
	 * create hash for password
	*
	* @param string $value password to hash
	* @param int $cost default is 15, higher numbers are more secure.
	* @return string full hashed password for putting in database
	* @author Daniel Baldwin
	*/
	public function hashPassword($value, $cost = 15)
	{
		return password_hash($value, PASSWORD_BCRYPT, ["cost"=>$cost]);
	}

	/**
	 * A replacement for apache_request_headers()
	 * You need to add header redirects like the following for this method to work.
	 * RewriteRule .? - [E=HEADER>Authorization:%{HTTP:Authorization}]
	 * @return array
	 */
	public function requestHeaders()
	{
		$arh = [];
		$rx_http = '/\AHEADER>/';
		foreach($_SERVER as $key => $val) {
			if( preg_match($rx_http, $key) ) {
				$arh_key = preg_replace($rx_http, '', $key);
				
				$arh[$arh_key] = $val;
			}
		}
		return( $arh );
	}
}
