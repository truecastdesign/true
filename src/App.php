<?php
namespace True;

/**
 * App class for main framework interactions
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.0.10
 */

class App
{
	private $container = [];

	private $match = false;
	private $debug = false;

	/**
     * Create new application
     *
     * @param ContainerInterface|array $container Either a ContainerInterface or an associative array of app settings
     * @throws InvalidArgumentException when no container is provided that implements ContainerInterface
     */
    public function __construct($files = null)
	{
		$this->load($files);

		$GLOBALS['pageErrors'] = '';
		$GLOBALS['errorUserError'] = '';
		$GLOBALS['errorUserWarning'] = '';
		$GLOBALS['errorUserNotice'] = '';

		if(!isset($GLOBALS['debug']))
			$GLOBALS['debug'] = false;
		
		set_error_handler(array($this, 'errorHandler'));
	}

	/**
	 * Use this method to load into memory the config settings
	 *
	 * @param string $files - use the config path starting from web root with no starting slash
	 * example: system/config/site.ini
	 * @return void
	 * @author Daniel Baldwin - danielbaldwin@gmail.com
	 **/
	public function load($files)
	{
		# multiple files
		if(strpos($files, ','))
			$filesList = explode(',', $files);
		else # single file
			$filesList[] = $files;
			
		foreach($filesList as $file)
		{
			$file = trim($file);
			
			# convert file into array
			if(file_exists($file))
			{
				$config = parse_ini_file($file, true);

				# if it has sections, remove the config_title array that gets created
				#$configTitle = $config['config_title'];
				#unset($config['config_title']);
				
				# add the array using the config title as a key to the items array
				if(is_array($config))
					$this->container['config']->{key($config)} = (object) $config[key($config)];
			}			
		}
	}

	/**
	 * return value or values from config file without loading into config items
	 *
	 * @param string $file, file path from web root. example: modules/modname/config.ini
	 * @param string $key (optional) if provided only the value of given key will be returned
	 * @return object|string, will return object of no key is provided and a string if a key is given.
	 * @author Daniel Baldwin - danielbaldwin@gmail.com
	 **/
	public function getConfig(string $file, string $key=null)
	{
		$config = parse_ini_file($file, true);
		if($key != null)
			return $config[$key];
		else
			return (object) $config;
	}
	
	/**
	 * Use the config_title value and the config value to access the value
	 * 
	 * Example: $App->config->site->thekey
	 * Use config and then the group label in the ini file. [site]
	 *
	 * @param string $key the key you want to return the value for.
	 * @return string
	 * @author Daniel Baldwin - danielbaldwin@gmail.com
	 **/
	public function __get($key)
	{	
		if(array_key_exists($key, $this->container))
			return $this->container[$key];
	}

	/**
	 * Temporally add to the config object in memory
	 * Example: $App->title->key = 'value';
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function __set($key, $value)
	{
        $this->container[$key] = $value;
    }

    /**
     * Calling a non-existant method on App checks to see if there's an item
     * in the container that is callable and if so, calls it.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if ($this->container->has($method)) {
            $obj = $this->container->get($method);
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args);
            }
        }

        trigger_error("Method $method is not a valid method",512);
    }
	
	/**
	 * Write a data object to a ini file
	 *
	 * @param $filename, path and filename of ini file
	 * @param $data, array of objects for configs with sections and just an object for no sections for ini file
	 * @param $append, true if you want to append to end of file
	 * @return void
	 * @author Daniel Baldwin - danielbaldwin@gmail.com
	 **/
	public function write(string $filename, $data, bool $append)
	{
		$content = '';
		$sections = '';
		$globals  = '';
		$fileContents  = '';

		# no sections
		if(is_object($data))
		{
			$values = (array) $data;

			foreach($values as $key=>$value)
			{
				$content .= "\n".$key."=".$this->normalizeValue($value);
			}
		}

		# has sections
		elseif(is_array($data))
		{
			foreach($data as $section=>$values)
			{
				$content .= "\n[" . $section . "]";

				foreach($values as $key=>$value)
				{
					$content .= "\n".$key."=".$this->normalizeValue($value);
				}
			}
		}
		
		if($append)
		{
			$fileContents = file_get_contents($filename)."\n";
		}
		
		file_put_contents($filename, $fileContents.$content);
	}

	/**
     * Add GET route (Retrieve a representation of a resource.)
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function get($pattern, $callable, $customControllerPath = false)
    {
        $this->router(['GET'], $pattern, $callable, $customControllerPath);
    }

    /**
     * Add POST route (Create, Create a new resource to an existing URL.)
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function post($pattern, $callable, $customControllerPath = false)
    {
        $this->router(['POST'], $pattern, $callable, $customControllerPath);
    }

    /**
     * Add PUT route (Create or Update, Create a new resource to a new URL, or modify an existing resource to an existing URL.)
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function put($pattern, $callable, $customControllerPath = false)
    {
        return $this->router(['PUT'], $pattern, $callable, $customControllerPath);
    }

    /**
     * Add PATCH route (partial update a resources. Use when you only need to update one field of the resource)
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function patch($pattern, $callable, $customControllerPath = false)
    {
        return $this->router(['PATCH'], $pattern, $callable, $customControllerPath);
    }

    /**
     * Add DELETE route (Delete an existing resource.)
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function delete($pattern, $callable, $customControllerPath = false)
    {
        return $this->router(['DELETE'], $pattern, $callable, $customControllerPath);
    }

    /**
     * Add OPTIONS route (determine the options and/or requirements associated with a resource, or the capabilities of a server,)
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function options($pattern, $callable, $customControllerPath = false)
    {
        return $this->router(['OPTIONS'], $pattern, $callable, $customControllerPath);
    }

    /**
     * Add route for any HTTP method
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function any($pattern, $callable, $customControllerPath = false)
    {
        return $this->router(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable, $customControllerPath);
    }

	/**
	 * router for True framework
	 * Creates a request object that is passed as the first parameter to the callback function.
	 * $request->method # the request method i.e. post, get, put, delete, update, etc.
	 * $request->post # value object of posted message
	 * $request->post->var1 # value of key var1
	 * $request->get # value object of get request
	 * $request->cookie # value object of cookies
	 * $request->files # value object of files
	 * $request->server # value object of server variables
	 * #### still need to implement
	 *
	 * @param string $method post, put, get, delete as the method name
	 * $App->post('path or action after the main one in the routes file', function() { // run code });
	 * $App->get('/getScore/:id', function ($id){})
	 * $App->get('/path:', 'page-controller.php') # controller inside app/controllers folder
	 * $App->get('/path:', 'vendor/brand/src/page-controller.php', true)  # custom path from base path
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function router(array $method, $pattern, $callable, $customControllerPath = false)
	{
		if($this->match)
			return false;

		# check if method matches
		if(in_array($_SERVER['REQUEST_METHOD'], $method))
		{
			$this->match = true;
			$patternElements = explode('/',$pattern);
			$requestUrl = strtok(filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL),'?');
			$requestUrl = str_replace(['../'], ['/'], $requestUrl);
			$urlElements = explode('/',$requestUrl);
			$callbackArgs = (object)[];

			$j = 0;
			foreach($patternElements as $element)
			{
				$urlElement = current($urlElements);
				if(strstr($element, ':') !== false)
				{
					$variableName = ltrim($element, ':');
					$pathParts = array_slice($urlElements, $j);

					$callbackArgs->{$variableName} = implode('/', $pathParts);
				}
				else
				{
					if($urlElement != $element)
						$this->match = false;
				}
				$i = next($urlElements);
				$j++;
			}
			#print_r($callbackArgs);
			# given pattern matches the request url
			if($this->match)
			{
				if(in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT']))
				{
					$strJSON = file_get_contents('php://input');
					if(!empty($strJSON))
					{
						$callbackArgs = (object) array_merge((array) json_decode($strJSON), (array) $callbackArgs);
					}
				}
				elseif(in_array($_SERVER['REQUEST_METHOD'], ['GET']))
				{
					$callbackArgs = (object) array_merge($_GET, (array) $callbackArgs);
				}
				
				if(is_string($callable))
				{
					$this->includeController($callable, $callbackArgs, $customControllerPath);
				}

				elseif(is_callable($callable))
				{
					$request[] = $callbackArgs;
					
					call_user_func_array($callable, $request);
				}	
			}
		}
	}

	public function includeController($callableController, $request, $customControllerPath)
	{
		$App = $this;
		include $this->controller($callableController, $customControllerPath);
	}

	public function controller($path, $customControllerPath = false)
	{
		if(empty($path))
			$path = 'index';
		
		if($customControllerPath)
			return __BP__.'/'.$path.'.php';
		else
			return __BP__.'/app/controllers/'.$path.'.php';
	}

	public function output($data)
	{
		if(is_array($data) OR is_object($data))
			print_r($data);
		else
			echo $data;
	}

	/**
	 *  set header location and exit.
	 *
	 * @param string $filename 
	 * @return void
	 * @author Daniel Baldwin
	 */
	public static function go(string $filename)
	{
		header("Location: ".$filename); exit;
	}

	/**
	 * when nesting routers, use this to allow matches to work on the second or deeper level.
	 *
	 * @param 
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function resetRouter()
	{
		$this->match = false;
	}

	# trigger_error("Error Message", E_USER_WARNING);
	public static function errorHandler($errNo, $errStr, $errFile, $errLine, $errContext)
	{
		
		$debugError = $errStr.': FILE:'.$errFile.' LINE:'.$errLine;

		#$GLOBALS['errorUserError'] = trim($GLOBALS['errorUserError']);
		$GLOBALS['errorUserWarning'] = str_replace(['<ul>','</ul>'],'',$GLOBALS['errorUserWarning']);
		#$GLOBALS['errorUserNotice'] = trim($GLOBALS['errorUserNotice']);
		
		switch($errNo)
		{
			case E_WARNING: # 2
				if($GLOBALS['debug']) 
					$GLOBALS['pageErrors'] .= !empty($GLOBALS['pageErrors'])? '<br><br>'.$debugError:$debugError;
			break;
			
			case E_NOTICE: # 8
				if($GLOBALS['debug'])
					$GLOBALS['pageErrors'] .= !empty($GLOBALS['pageErrors'])? '<br>'.$debugError:$debugError;
			break;
			
			case E_USER_ERROR: # 256
				$GLOBALS['errorUserError'] .= !empty($GLOBALS['errorUserError'])? '<br>'.$errStr:$errStr;
			break;
			
			case E_USER_WARNING: # 512
				$GLOBALS['errorUserWarning'] .= !empty($GLOBALS['errorUserWarning'])? '<br>'.$errStr:$errStr;
			break;
			
			case E_USER_NOTICE: # 1024
				$GLOBALS['errorUserNotice'] .= !empty($GLOBALS['errorUserNotice'])? '<br>'.$errStr:$errStr;
			break;
			
			case E_USER_DEPRECATED: # 16384 - use this error level for errors you don't want the user to see bug for debugging only!
				$GLOBALS['pageErrors'] .= !empty($GLOBALS['pageErrors'])? '<br><br>'.$debugError:$debugError;
			break;
			
			default:
				if($GLOBALS['debug'])
					$GLOBALS['pageErrors'] .= !empty($GLOBALS['pageErrors'])? '<br>'.$errStr:$errStr;
		}
	}

	/**
	 * Sanitize content method
	 *
	 * @param string $str - string to sanitize
	 * @param string $type - type of sanitizing to do
	 * @return string
	 * @author Daniel Baldwin 
	 **/
	public function clean($str, $type)
	{
		switch($type)
		{
			case 'email':
				return filter_var($str, FILTER_SANITIZE_EMAIL);
			break;

			case 'float':
				return filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT);
			break;

			case 'int':
				return filter_var($str, FILTER_SANITIZE_NUMBER_INT);
			break;

			case 'html':
				return filter_var($str, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			break;

			case 'text':
				return filter_var($str, FILTER_SANITIZE_STRING); # Strip tags
			break;

			case 'url':
				return filter_var($str, FILTER_SANITIZE_URL); # Remove all characters except letters, digits and $-_.+!*'(),{}|\\^~[]`<>#%";/?:@&=.
			break;

			case 'file_path':
				return preg_replace("/[^A-Za-z0-9\.\-\_\ \/]/", '', $str);
			break;

			case 'ip':
				return filter_var($str,FILTER_VALIDATE_IP);
			break;

			case 'street_address':
				return preg_replace("/[^A-Za-z0-9\.\-\,\#\ \/]/", '', $str);
			break;

			case 'alpha':
				return preg_replace("/[^a-zA-Z]/", '', $str);
			break;
			
			case 'alpha_int':
				return preg_replace("/[^a-zA-Z0-9]/", '', $str);
			break;

			case 'name':
				return preg_replace("/[^a-zA-Z0-9\ \.\-]/", '', $str);
			break;

			case 'phone_format':
				$onlynums = preg_replace('/[^0-9]/','',$str);
				if(strlen($onlynums)==10) 
				{
					$areacode = substr($onlynums, 0,3);
					$exch = substr($onlynums,3,3);
					$num = substr($onlynums,6,4);
					return "$areacode-$exch-$num";
				}
				elseif(strlen($onlynums)==11) 
				{
					$countryCode = substr($onlynums, 0,1);
					$areacode = substr($onlynums, 1,3);
					$exch = substr($onlynums,4,3);
					$num = substr($onlynums,7,4);
					return "$countryCode-$areacode-$exch-$num";
				}
				return $str;
			break;			
		}
	}

	/**
	 * Encrypt a string
	 *
	 * @param $str plain text string
	 * @param $key private key
	 * @return encrypted string
	 * @author Daniel Baldwin
	 **/
	public static function encrypt($str, $key)
	{
		$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext_raw = openssl_encrypt($str, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
		return base64_encode( $iv.$hmac.$ciphertext_raw );
	}

	/**
	 * Decrypt a string
	 *
	 * @param $str encrypted text string
	 * @param $key private key
	 * @return plain text string
	 * @author Daniel Baldwin
	 **/
	public static function decrypt($str, $key)
	{
		$c = base64_decode($str);
		$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len=32);
		$ciphertext_raw = substr($c, $ivlen+$sha2len);
		$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
		$calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
		if (hash_equals($hmac, $calcmac))//PHP 5.6+ timing attack safe comparison
		{
		    return $original_plaintext;
		}
	}

	/**
	 * Generate a encryption key or token
	 *
	 * @param int $length
	 * @return string
	 * @author Daniel Baldwin
	 **/
	public static function genToken($length = 64)
	{
		return bin2hex(openssl_random_pseudo_bytes($length));
	}

	/**
	 * return the full host name
	 *
	 * @return string https://www.domain.com
	 * @author Daniel Baldwin
	 **/
	public static function host()
	{
		return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
	}

	/**
	 * Transforms txt in html
	 *
	 * @param string $txt 
	 * @return string html
	 * @author Daniel Baldwin
	 */
	public static function txt2html($txt)
	{
		//Kills double spaces and spaces inside tags.
	  while( !( strpos($txt,'  ') === FALSE ) ) $txt = str_replace('  ',' ',$txt);
	  $txt = str_replace(' >','>',$txt);
	  $txt = str_replace('< ','<',$txt);

	  //Transforms accents in html entities.
	  $txt = htmlentities($txt);

	  //We need some HTML entities back!
	  $txt = str_replace('&quot;','"',$txt);
	  $txt = str_replace('&lt;','<',$txt);
	  $txt = str_replace('&gt;','>',$txt);
	  $txt = str_replace('&','&',$txt);

	  //Ajdusts links - anything starting with HTTP opens in a new window
	  $txt = self::stri_replace("<a href=\"http://","<a target=\"_blank\" href=\"http://",$txt);
	  $txt = self::stri_replace("<a href=http://","<a target=\"_blank\" href=http://",$txt);

	  //Basic formatting
	  if(strpos($txt,"\r\n")) $eol = "\r\n";
	  elseif(strpos($txt,"\n")) $eol = "\n";
	  elseif(strpos($txt,"\r")) $eol = "\r";
	  
	  $html = '<p>'.str_replace($eol.$eol,"</p><p>",$txt).'</p>';
	  $html = str_replace($eol,"<br>",$html);
	  $html = str_replace("<p></p>","<p>&nbsp;</p>",$html);

	  //Wipes after block tags (for when the user includes some html in the text).
	  $tags = Array("table","tr","td","blockquote","ul","ol","li","h1","h2","h3","h4","h5","h6","div","p");

	  foreach($tags as $tag)
	  {
		  $html = self::stri_replace("<p><$tag>","<$tag>",$html);
		  $html = self::stri_replace("</$tag></p>","</$tag>",$html);
	  }

	  return $html;
	}

	public static function stri_replace($find, $replace, $string)
	{
		$parts = explode( strtolower($find), strtolower($string) );

		$pos = 0;

		foreach( $parts as $key=>$part ){
		$parts[ $key ] = substr($string, $pos, strlen($part));
		$pos += strlen($part) + strlen($find);
		}

		return( join( $replace, $parts ) );
	}
	
	/**
	 * normalize a Value by determining the Type
	 *
	 * @param string $value value
	 * @return string
	 */
	protected function normalizeValue($value)
	{
		$delim = '"';

		if (is_bool($value))
		{
			$value = $this->toBool($value);
			return $value;
		}
		elseif (is_numeric($value))
		{
			return $value;
		}
		
		$value = $delim . $value . $delim;

		return $value;
	}
	
	/**
	 * converts string to a representable Config Bool Format
	 *
	 * @param string $value value
	 * @return string
	 */
	protected function toBool($value)
	{
		if ($value === true)
		{
			return 'yes';
		}
		return 'no';
	}
}