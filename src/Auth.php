<?php
namespace True;

/**
 * Auth class for authenticating api calls
 * 
 * Add the below code to your .htaccess file in the <IfModule mod_rewrite.c> section.
 * # Pass Authorization headers to an environment variable
 * RewriteRule .? - [E=HTTP_Authorization:%{HTTP:Authorization}]

*
* @package True Framework
* @author Daniel Baldwin
* @version 1.4.0
*/
class Auth
{
	var $bearerTokensFile = BP.'/app/data/auth-tokens';
	var $loginTokensDb = BP.'/app/data/auth-login-tokens.db';
	var $basicAuthDB = BP.'/app/data/basicAuthCredentials.db';
	var $csrfSession = 'kkj43kj';
	var $jwtSession = 'jk4k88d';
	var $jwtPrivateKey = BP.'/app/config/jwt-key.ini';

	/**
	 * bearerTokensFile : should include the base path to a text file
	* loginTokensDb : should be a sqlite database with a 'auth' table with 'username', 'password', 'token', and 'user_id' fields
	* basicAuthDB : location and filename of the basic auth database if different than default.
	* @param array $params [bearerTokensFile, loginTokensDb, basicAuthDB]
	*/
	public function __construct($params = null)
	{
		if (isset($params['bearerTokensFile'])) {
			$this->bearerTokensFile = $params['bearerTokensFile'];
		}

		if (isset($params['loginTokensDb'])) {
			$this->loginTokensDb = $params['loginTokensDb'];
		}

		if (isset($params['basicAuthDB'])) {
			$this->basicAuthDB = $params['basicAuthDB'];
		}
	}

	/**
	 * Inject needed objects
	 *
	 * @param object|array of objects $obj
	 * @return void
	 */
	public function inject($obj)
	{
		if (is_object($obj)) {
			$pathFragments = explode("\\", get_class($obj));
			$name = end($pathFragments);
			$this->{$name} = $obj;
		} elseif (is_array($obj)) {
			foreach ($obj as $item) {
				$pathFragments = explode("\\", get_class($item));
				$name = end($pathFragments);
				$this->{$name} = $item;
			}
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

	/** 
	 * Get one of the valid tokens for use
	 */
	public function getToken()
	{
		$tokens = file_get_contents($this->bearerTokensFile);

		if (!empty($tokens)) {
			return strtok($tokens, "\n");
		}
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
	 * set a new JWT to a cookie
	 * inject your instance of App with a config of site with a property of secure set to true or false; $this->App->config->site->secure
	 *
	 * @param [type] $userId
	 * @param [type] $cookie
	 * @return void
	 */
	public function setJWT($userId, $cookie = null)
	{
		$payload['id'] = $userId;
		$payload['exp'] = time() + (7 * 24 * 60 * 60); # 7 days
		
		if (!file_exists($this->jwtPrivateKey)) {			
			$privateKey = $this->genToken();
			$App->writeConfig($this->jwtPrivateKey, ['privateKey'=>$privateKey]);			
		} else {
			$config = parse_ini_file($this->jwtPrivateKey);
			$privateKey = $config['privateKey'];
		}

		$token = \True\JWT::encode($payload, $privateKey);

		$secure = $this->App->config->site->secure;

		if (is_null($cookie)) {
			$cookie = $this->jwtSession;
		}

		setcookie($cookie, $token, time()+31557600, "/", "", $secure, true);
		
		return $token;
	}

	public function checkJWT($cookie = null)
	{
		if (is_null($cookie)) {
			$cookie = $this->jwtSession;
		}
		
		$token = $_COOKIE[$cookie];
		if (empty($token)) {
			return false;
		}

		$config = parse_ini_file($this->jwtPrivateKey);
		$privateKey = $config['privateKey'];

		try {
			$payload = JWT::decode($token, $privateKey, ['HS256']);
			
			if (!is_numeric($payload->id)) {
				throw new \Exception("Can’t find user id!");
			}

			if (!isset($payload->exp)) {
				return $payload->id;
			}

			if ($payload->exp < time()) {
				throw new \Exception("Token is expired!");
			}

			return (int) $payload->id;
		}
		catch(\Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}

	public function deleteJWT($cookie = null)
	{
		if (is_null($cookie)) {
			$cookie = $this->jwtSession;
		}

		$secure = $this->App->config->site->secure;
		setcookie("ikje", '', time()-2, "/", "", $secure, true);
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
						$headers = $this->getallheaders();
						
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

					case 'basic':
						$headers = $this->getallheaders();
				  
						if (isset($headers['Authorization'])) {
							// Extract the Basic token
							$token = str_replace('Basic ', '', $headers['Authorization']);
				
							// Decode the token
							$decodedToken = base64_decode($token);
							if (!$decodedToken) {
								return false; // Invalid base64
							}
				
							// Split the decoded token into username and password
							list($username, $password) = explode(':', $decodedToken, 2);
				
							// Validate the username and password (replace with your own logic)
							if ($this->validateBasicCredentials($username, $password)) {
								return true; // Authentication successful
							} else {
								return false; // Authentication failed
							}
						} else {
							return false; // No Authorization header
						}
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
	 * Create the database file for the Basic Authentication method
	 */
	public function createBasicAuthDB(): bool
	{
		$DB = new \PDO('sqlite:'.$this->basicAuthDB);

		// Set PDO error mode to exception for better error handling
		$DB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		// SQL statement to create the users table
		$createTableSQL = "
		CREATE TABLE IF NOT EXISTS users (
			 id           INTEGER PRIMARY KEY AUTOINCREMENT,
			 username     VARCHAR(255) NOT NULL UNIQUE,
			 passwordHash VARCHAR(255) NOT NULL,
			 created      DATETIME DEFAULT CURRENT_TIMESTAMP
		);";
  
		// Execute the SQL statement to create the table
		try {
			 $DB->exec($createTableSQL);
			 return true;
		} catch (\PDOException $e) {
			echo 'Failed to create Basic Auth database: ' . $e->getMessage();
			return false;
		}
	}

	/**
	 * Add a new user to the Basic Auth database.
	 *
	 * This method adds a user with a hashed password to the Basic Auth database.
	 * If the username already exists, the method will fail.
	 *
	 * @param string $username The username for the new user (must be unique).
	 * @param string $password The plain-text password for the new user.
	 * 
	 * @return bool Returns true if the user was successfully added, or false on failure.
	 */	
	public function addBasicAuthUser(string $username, string $password): bool
	{
		try {
			// Open the SQLite database connection
			$DB = new \PDO('sqlite:' . $this->basicAuthDB);
			$DB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			// Hash the password
			$passwordHash = password_hash($password, PASSWORD_BCRYPT);

			$created = (new \DateTime('now', new \DateTimeZone('America/Los_Angeles')))->format('Y-m-d H:i:s');


			// SQL statement to insert a new user
			$insertSQL = "INSERT INTO users (username, passwordHash, created) VALUES (?, ?, ?)";

			// Prepare and execute the statement with parameters
			$stmt = $DB->prepare($insertSQL);

			return $stmt->execute([$username, $passwordHash, $created]); // Returns true on success
		} catch (\PDOException $e) {
			// Log or handle the error
			error_log('Failed to add user: ' . $e->getMessage());
			return false; // Return false on failure
		}
	}

	/**
	 * Deletes a Basic Auth user from the database by their ID.
	 *
	 * @param int $id The ID of the user to delete.
	 * @return bool Returns true if the user was successfully deleted, false otherwise.
	 */
	public function deleteBasicAuthUser(int $id) : bool {
		try {
			// Establish a connection to the database
			$DB = new \PDO('sqlite:' . $this->basicAuthDB);
			
			// Prepare the SQL statement
			$stmt = $DB->prepare("DELETE FROM users WHERE id=?");
			
			// Execute the statement with the ID as a parameter
			return $stmt->execute([$id]);
		} catch (\PDOException $e) {
			// Log the error or handle it as necessary
			error_log("Failed to delete user: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Updates a Basic Auth user's username and/or password.
	 *
	 * @param int $id The ID of the user to update.
	 * @param string|null $username The new username (optional, null to keep unchanged).
	 * @param string|null $password The new plaintext password (optional, null to keep unchanged).
	 * @return bool Returns true if the user was successfully updated, false otherwise.
	 */
	public function updateBasicAuthUser(int $id, ?string $username = null, ?string $password = null): bool
	{
		try {
			// Open the SQLite database connection
			$DB = new \PDO('sqlite:' . $this->basicAuthDB);
			$DB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			// Prepare the SQL statement dynamically based on provided parameters
			$updates = [];
			$params = [];

			if (!is_null($username)) {
				$updates[] = "username = ?";
				$params[] = $username;
			}

			if (!is_null($password)) {
				$updates[] = "passwordHash = ?";
				$params[] = password_hash($password, PASSWORD_BCRYPT); // Hash the new password
			}

			// If no updates provided, return false
			if (empty($updates)) {
				return false;
			}

			// Add the ID as the last parameter
			$params[] = $id;

			// Construct the SQL query
			$updateSQL = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

			// Prepare and execute the statement
			$stmt = $DB->prepare($updateSQL);
			return $stmt->execute($params); // Returns true on success
		} catch (\PDOException $e) {
			// Log or handle the error
			error_log('Failed to update user: ' . $e->getMessage());
			return false; // Return false on failure
		}
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

	/**
	* Get all HTTP header key/values as an associative array for the current request.
	* Written by ralouphie - https://github.com/ralouphie
	*
	* A replacement for apache_request_headers()
	* You need to add header redirects like the following for this method to work.
	* RewriteRule .? - [E=HEADER>Authorization:%{HTTP:Authorization}]
	*
	* @return string[string] The HTTP header key/value pairs.
	*/
	protected function getallheaders()
	{
		$headers = array();
		$copy_server = array(
			'CONTENT_TYPE'   => 'Content-Type',
			'CONTENT_LENGTH' => 'Content-Length',
			'CONTENT_MD5'    => 'Content-Md5',
		);
		foreach ($_SERVER as $key => $value) {
			if (substr($key, 0, 6) === 'HEADER') {
				 $key = substr($key, 7);
				 if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
					  $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
					  $headers[$key] = $value;
				 }
			} elseif (isset($copy_server[$key])) {
				 $headers[$copy_server[$key]] = $value;
			}
		}
		if (!isset($headers['Authorization']) and isset($_SERVER['Authorization'])) {
			$headers['Authorization'] = $_SERVER['Authorization'];
		}

		# used for PHP built-in server
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
		}	

		return $headers;
	}

	/**
	 * Validate a user's basic authentication credentials.
	 *
	 * @param string $username The username to validate.
	 * @param string $password The password to validate.
	 * @return bool True if the credentials are valid, false otherwise.
	 */
	function validateBasicCredentials($username, $password): bool
	{
		if (empty($username) || empty($password)) {
			error_log("Validation failed: Missing username or password.");
			return false;
		}

		try {
			// Open SQLite database
			$DB = new \PDO('sqlite:' . $this->basicAuthDB);
			$DB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			// Prepare and execute SQL query
			$dbRes = $DB->prepare("SELECT id, passwordHash FROM users WHERE username = ?");
			$dbRes->execute([$username]);

			$result = $dbRes->fetch(\PDO::FETCH_ASSOC);

			if (!$result) {
					error_log("Validation failed: Username '{$username}' not found.");
					return false;
			}

			// Verify the password
			$isValid = password_verify($password, $result['passwordHash']);
			if ($isValid) {
				error_log("Validation successful for username '{$username}'.");
			} else {
				error_log("Validation failed: Incorrect password for username '{$username}'.");
			}

			return $isValid;
		} catch (\PDOException $ex) {
			error_log("Database error during validation: " . $ex->getMessage());
			return false;
		}
	}


}