<?php
namespace True;
/**
 * App class for main framework interactions
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.2.0
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
		if (!isset($GLOBALS['debug'])) $GLOBALS['debug'] = false;
		set_error_handler(array(
			$this,
			'errorHandler'
		));
	}

	/**
	 * Use this method to load into memory the config settings
	 *
	 * @param string $files - use the config path starting from web root with no starting slash
	 * example: system/config/site.ini
	 * @return void
	 * @author Daniel Baldwin
	 *
	 */
	public function load($files)
	{
		// multiple files
		if (strpos($files, ',')) {
			$filesList = explode(',', $files);
		}
		else { // single file
			$filesList[] = $files;
		}

		foreach($filesList as $file) {
			$file = trim($file);

			// convert file into array
			if (file_exists($file)) {
				$config = parse_ini_file($file, true);

				// if it has sections, remove the config_title array that gets created
				// $configTitle = $config['config_title'];
				// unset($config['config_title']);
				// add the array using the config title as a key to the items array

				if (!is_array($config)) {
					continue;
				}

				// has section headings
				if (is_array($config[key($config) ])) {
					foreach($config as $section => $values) {
						$this->container['config']->{$section} = (object)$values;
					}
				} else { // does not have sections
					foreach($config as $key => $value) {
						$this->container['config']->{$key} = $value;
					}
				}
			}
		}
	}

	/**
	 * return value or values from config file without loading into config items
	 *
	 * @param string $file, file path from web root. example: modules/modname/config.ini
	 * @param string $key (optional) if provided only the value of given key will be returned
	 * @return object|string, will return object of no key is provided and a string if a key is given.
	 * @author Daniel Baldwin
	 *
	 */
	public function getConfig(string $file, string $key = null)
	{
		$config = parse_ini_file($file, true);
		if ($key != null) {
			return $config[$key];
		} else {
			return (object)$config;
		}
	}

	/**
	 * Use the config_title value and the config value to access the value
	 *
	 * Example: $App->config->site->thekey
	 * Use config and then the group label in the ini file. [site]
	 *
	 * @param string $key the key you want to return the value for.
	 * @return string
	 * @author Daniel Baldwin
	 *
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->container)) {
			return $this->container[$key];
		}
	}

	/**
	 * Temporally add to the config object in memory
	 * Example: $App->title->key = 'value';
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 * @author Daniel Baldwin
	 *
	 */
	public function __set($key, $value)
	{
		$this->container[$key] = $value;
	}

	/**
	 * Write a data object to a ini file
	 *
	 * @param $filename, path and filename of ini file
	 * @param $data, array of objects for configs with sections and just an object for no sections for ini file
	 * @param $append, true if you want to append to end of file
	 * @return void
	 * @author Daniel Baldwin
	 *
	 */
	public function write(string $filename, $data, bool $append)
	{
		$content = '';
		$sections = '';
		$globals = '';
		$fileContents = '';

		// no sections

		if (is_object($data)) {
			$values = (array)$data;
			foreach($values as $key => $value) {
				$content.= "\n" . $key . "=" . $this->normalizeValue($value);
			}
		}

		// has sections

		elseif (is_array($data)) {
			foreach($data as $section => $values) {
				$content.= "\n[" . $section . "]";
				foreach($values as $key => $value) {
					$content.= "\n" . $key . "=" . $this->normalizeValue($value);
				}
			}
		}

		if ($append) {
			$fileContents = file_get_contents($filename) . "\n";
		}

		file_put_contents($filename, $fileContents . $content);
	}

	/**
	 * Add GET route (Retrieve a representation of a resource.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function get($pattern, $callable, $customControllerPath = false)
	{
		$this->router(['GET'], $pattern, $callable, $customControllerPath);
	}

	/**
	 * Add POST route (Create, Create a new resource to an existing URL.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function post($pattern, $callable, $customControllerPath = false)
	{
		$this->router(['POST'], $pattern, $callable, $customControllerPath);
	}

	/**
	 * Add PUT route (Create or Update, Create a new resource to a new URL, or modify an existing resource to an existing URL.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function put($pattern, $callable, $customControllerPath = false)
	{
		$this->router(['PUT'], $pattern, $callable, $customControllerPath);
	}

	/**
	 * Add PATCH route (partial update a resources. Use when you only need to update one field of the resource)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function patch($pattern, $callable, $customControllerPath = false)
	{
		$this->router(['PATCH'], $pattern, $callable, $customControllerPath);
	}

	/**
	 * Add DELETE route (Delete an existing resource.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function delete($pattern, $callable, $customControllerPath = false)
	{
		$this->router(['DELETE'], $pattern, $callable, $customControllerPath);
	}

	/**
	 * Add OPTIONS route (determine the options and/or requirements associated with a resource, or the capabilities of a server,)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function options($pattern, $callable, $customControllerPath = false)
	{
		$this->router(['OPTIONS'], $pattern, $callable, $customControllerPath);
	}

	/**
	 * Add route for any HTTP method
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function any($pattern, $callable, $customControllerPath = false)
	{
		$this->router(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable, $customControllerPath);
	}

	/**
	 * Router main method
	 *
	 * @param string $method post, put, get, delete as the method name
	 * $App->post('path or action after the main one in the routes file', function() { // run code });
	 * $App->get('/getScore/:id', function ($request){ echo $request->route->id; });
	 * match the first part of the url and the rest does not mater.
	 * $App->get('/part1/*', function ($request){ # run this code });
	 * match the first part and dump the rest of the url into a variable with given name
	 * $App->get('/part1/part2/*:path', function ($request){ echo $request->route->path; });
	 * $App->get('/path:', 'page-controller.php') # controller inside app/controllers folder
	 * $App->get('/path:', 'vendor/brand/src/page-controller.php', true)  # custom path from base path
	 * The object that is passed to the callback function will be a value object.
	 * $request->route->{variable name} the route match path variable that have a colon in front of them will come in on the route key.
	 * $request->{method name: post,get,delete,put,patch,etc}->{variable name} values using the post method will come in on the post key.
	 * Other server values available:
	 * $request->uri
	 * $request->ip client ip
	 * $request->method request method
	 * $request->https true or false
	 * $request->name domain with sub domain part www.domain.com
	 * @return void
	 * @author Daniel Baldwin
	 *
	 */
	public function router(array $method, $pattern, $callable, $customControllerPath = false)
	{
		if ($this->match) {
			return false;
		}

		$_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);

		// check if method matches

		if (in_array($_SERVER['REQUEST_METHOD'], $method)) {
			$this->match = true;
			$parametersFound = false;
			$patternElements = explode('/', ltrim($pattern, '/'));
			$requestUrl = strtok(filter_var(ltrim($_SERVER["REQUEST_URI"], '/') , FILTER_SANITIZE_URL) , '?');
			$requestUrl = str_replace(['../'], ['/'], $requestUrl);
			$urlElements = explode('/', $requestUrl);
			$callbackArgs = new stdClass();
			$requestKey = strtolower($_SERVER['REQUEST_METHOD']);
			$callbackArgs->uri = $_SERVER['REQUEST_URI'];
			$callbackArgs->method = $_SERVER['REQUEST_METHOD'];
			$callbackArgs->ip = $_SERVER['REMOTE_ADDR'];
			if (array_key_exists('HTTPS', $_SERVER)) {
				$callbackArgs->https = ($_SERVER['HTTPS'] == 'on' ? true : false);
			}
			else {
				$callbackArgs->https = false;
			}

			$callbackArgs->name = $_SERVER['HTTP_HOST'];
			$urlParts = TrueFunctions::parseUrl($_SERVER['HTTP_HOST']);
			$callbackArgs->domain = $urlParts->domain;
			$callbackArgs->subdomain = $urlParts->subdomain;
			$callbackArgs->extension = $urlParts->extension;
			$callbackArgs->file = $urlParts->file;
			$callbackArgs->query = $urlParts->query;

			// if not * found, than check to make sure pattern elements count and url elements count match

			if (strstr($pattern, '*') === false) {
				if (count($patternElements) != count($urlElements)) {
					$this->match = false;
				}
			}

			if ($this->match) {
				foreach($patternElements as $patternElement) {
					$urlElement = array_shift($urlElements);
					if (strstr($patternElement, ':') !== false) {
						if (strstr($patternElement, '*') !== false) {
							$parameterKey = str_replace(['*', ':'], ['', ''], $patternElement);
							if (count($urlElements) > 0) {
								$routeParameters[$parameterKey] = $urlElement . '/' . urldecode(implode('/', $urlElements));
							}
							else {
								$routeParameters[$parameterKey] = $urlElement;
							}

							$this->match = true;
							$parametersFound = true;
							break;
						}
						else {
							$parameterKey = ltrim($patternElement, ':');
							$routeParameters[$parameterKey] = urldecode($urlElement);
							$parametersFound = true;
						}
					}
					else {
						if (strstr($patternElement, '*') !== false) {
							$this->match = true;
							break;
						}
						elseif ($urlElement != $patternElement) {
							$this->match = false;
							break;
						}
					}
				}

				if ($parametersFound) {
					$callbackArgs->route = (object)$routeParameters;
				}
			}

			// given pattern matches the request url

			if ($this->match) {
				$requestBody = file_get_contents('php://input');
				if (!empty($requestBody)) {
					$xml = simplexml_load_string($requestBody, "SimpleXMLElement", LIBXML_NOCDATA);
					$json = json_encode($xml);
					$array = json_decode($json, true);
					if (!empty($xml)) {
						$callbackArgs->$requestKey = (object)$array;
					}
					else {
						$callbackArgs->$requestKey = (object)json_decode($requestBody, true);
					}
				}

				if ($_SERVER['REQUEST_METHOD'] == 'GET' and count($_GET) > 0) {
					$callbackArgs->get = (object)$_GET;
				}

				$callbackArgs->headers = (object)getallheaders();
				if (is_string($callable)) {
					$this->includeController($callable, $callbackArgs, $customControllerPath);
				}
				elseif (is_callable($callable)) {
					$request[] = $callbackArgs;
					$response = call_user_func_array($callable, $request);
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
		if (empty($path)) $path = 'index';
		if ($customControllerPath) return BP. '/' . $path . '.php';
		else return BP. '/app/controllers/' . $path . '.php';
	}

	/**
	 * For outputting formatted content for debugging
	 * @param  array|object|string $data items you want outputted
	 * @return null     
	 */
	public function output($data)
	{
		if(is_array($data) OR is_object($data)) {
			echo "<pre>";
			print_r($data);
			echo "</pre>";
		} else {
			echo "<pre>";
			echo $data;
			echo "</pre>";
		}
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
		header("Location: " . $filename);
		exit;
	}

	/**
	 * when nesting routers, use this to allow matches to work on the second or deeper level.
	 *
	 * @param
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 *
	 */
	public function resetRouter()
	{
		$this->match = false;
	}

	// trigger_error("Error Message", E_USER_WARNING);

	public static function errorHandler($errNo, $errStr, $errFile, $errLine, $errContext)
	{
		$debugError = $errStr . ': FILE:' . $errFile . ' LINE:' . $errLine;

		// $GLOBALS['errorUserError'] = trim($GLOBALS['errorUserError']);

		$GLOBALS['errorUserWarning'] = str_replace(['<ul>', '</ul>'], '', $GLOBALS['errorUserWarning']);

		// $GLOBALS['errorUserNotice'] = trim($GLOBALS['errorUserNotice']);

		switch ($errNo) {
		case E_WARNING: // 2
			if ($GLOBALS['debug']) $GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? '<br /><br />' . $debugError : $debugError;
			break;

		case E_NOTICE: // 8
			if ($GLOBALS['debug']) $GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? '<br />' . $debugError : $debugError;
			break;

		case E_USER_ERROR: // 256
			$GLOBALS['errorUserError'].= !empty($GLOBALS['errorUserError']) ? '<br />' . $errStr : $errStr;
			break;

		case E_USER_WARNING: // 512
			$GLOBALS['errorUserWarning'].= !empty($GLOBALS['errorUserWarning']) ? '<br />' . $errStr : $errStr;
			break;

		case E_USER_NOTICE: // 1024
			$GLOBALS['errorUserNotice'].= !empty($GLOBALS['errorUserNotice']) ? '<br />' . $errStr : $errStr;
			break;

		case E_USER_DEPRECATED: // 16384 - use this error level for errors you don't want the user to see bug for debugging only!
			$GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? '<br /><br />' . $debugError : $debugError;
			break;

		default:
			if ($GLOBALS['debug']) $GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? '<br />' . $errStr : $errStr;
		}
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
		if (is_bool($value)) {
			$value = $this->toBool($value);
			return $value;
		}
		elseif (is_numeric($value)) {
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
		if ($value === true) {
			return 'yes';
		}
		return 'no';
	}
}