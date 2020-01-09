<?php
namespace True;
/**
 * App class for main framework interactions
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.6.7
 */
class App
{
	private $container = [];
	private $match = false;
	private $debug = false;
	/**
	 * Create new application
	 *
	 * @param string $files example: app/config/site.ini used to load in config files. Comma delimited list of file paths
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
		$this->container['config'] = (object)[];

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
	public function write(string $filename, $data, bool $append = false)
	{
		$content = '';
		$sections = '';
		$globals = '';
		$fileContents = '';

		// has sections
		if (is_array(reset($data))) {
			foreach($data as $section => $values) {
				$content.= "\n[" . $section . "]";
				foreach($values as $key => $value) {
					$content.= "\n" . $key . "=" . $this->normalizeValue($value);
				}
			}
		}
		// no sections
		elseif (is_object($data)) {
			$values = (array)$data;
			foreach($values as $key => $value) {
				$content.= "\n" . $key . "=" . $this->normalizeValue($value);
			}
		}
			echo $content;
		if ($append) {
			$fileContents = file_get_contents($filename) . "\n";
		}

		file_put_contents($filename, $fileContents . $content);
	}

	/**
	 * For http requests, use this for properties on all the request methods as the third paramiter. 
	 * 	['header'=>["Cache-Control: no-cache, no-store, must-revalidate", "Authorization: Bearer "+token]] or
	 * 	['header'=>"Authorization: Bearer "+token]
	 * 	['query'=>['var'=>1, 'var2'=>2]]
	 * 	['body'=>['var'=>1, 'var2'=>2]]
	 * 	['timeout'=>20]
	 * 	['type']
	 * 
	 * 	$App->post('http://www.batterystuff.test/api/search/index/categories/8111', ['var'], function($response) {

	 * 	}, ['type'=>'json', 'header'=>"Authorization: Bearer "+token]);
	 * 
	 * 	$App->get('http://www.batterystuff.test/api/search/index/categories/8111', function($response) {

	 * 	}, ['type'=>'json', 'header'=>"Authorization: Bearer "+token]);
	 */

	/**
	 * Add GET route (Retrieve a representation of a resource.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true. 
	 *
	 * @return null
	 */
	public function get($pattern, $callable, $customControllerPath = false)
	{
		if (substr($pattern, 0, 4 ) == "http") {
			$this->request('GET', $pattern, [], $callable, $customControllerPath);
		} else {
			$this->router(['GET'], $pattern, $callable, $customControllerPath);
		}		
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
	public function post($pattern, $callable, $customControllerPath = false, $extra = null)
	{
		if (substr($pattern, 0, 4 ) == "http") {
			$this->request('POST', $pattern, $callable, $customControllerPath, $extra);
		} else {
			$this->router(['POST'], $pattern, $callable, $customControllerPath);
		}
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
	public function put($pattern, $callable, $customControllerPath = false, $extra = null)
	{
		if (substr($pattern, 0, 4 ) == "http") {
			$this->request('PUT', $pattern, $callable, $customControllerPath, $extra);
		} else {
			$this->router(['PUT'], $pattern, $callable, $customControllerPath);
		}
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
	public function patch($pattern, $callable, $customControllerPath = false, $extra = null)
	{
		if (substr($pattern, 0, 4 ) == "http") {
			$this->request('PATCH', $pattern, $callable, $customControllerPath, $extra);
		} else {
			$this->router(['PATCH'], $pattern, $callable, $customControllerPath);
		}
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
	public function delete($pattern, $callable, $customControllerPath = false, $extra = null)
	{
		if (substr($pattern, 0, 4 ) == "http") {
			$this->request('DELETE', $pattern, $callable, $customControllerPath, $extra);
		} else {
			$this->router(['DELETE'], $pattern, $callable, $customControllerPath);
		}
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
	public function options($pattern, $callable, $customControllerPath = false, $extra = null)
	{
		if (substr($pattern, 0, 4 ) == "http") {
			$this->request('OPTIONS', $pattern, $callable, $customControllerPath, $extra);
		} else {
			$this->router(['OPTIONS'], $pattern, $callable, $customControllerPath);
		}
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
	 * Add route for provided HTTP methods
	 *
	 * @param  array $methods  ['GET', 'POST']
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  bool $customControllerPath Custom controller path if true
	 *
	 * @return null
	 */
	public function map($methods, $pattern, $callable, $customControllerPath = false)
	{
		$this->router($methods, $pattern, $callable, $customControllerPath);
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
			$requestUrl = ltrim(strtok(filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL) , '?'), '/');
			$requestUrl = str_replace(['../'], ['/'], $requestUrl);
			$urlElements = explode('/', $requestUrl);
			
			$request = $this->makeRequestObject();

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
					$request->route = (object)$routeParameters;
				}
			}

			// given pattern matches the request url

			if ($this->match) {
				
				$postContentTypes = ['application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'];

				$cleanedContentTypeParts = explode(';', $request->contentType);
				$cleanedContentType = trim($cleanedContentTypeParts[0]);
				
				if ($request->method == 'POST' and in_array($cleanedContentType, $postContentTypes)) {
					// parsed body must be $_POST
					$request->post = (object)$_POST; 
				}

				if ($request->method == 'GET') {
					$request->get = (object)$_GET;
				}

				if (in_array($cleanedContentType, ['application/json'])) {
					$requestBody = file_get_contents('php://input');
					$requestKey = strtolower($request->method);
					if (!empty($requestBody)) {
						$request->$requestKey = (object)json_decode($requestBody, true);
					}
				}

				# make $request object available whereever $App is available like in the view. Should not be used by controllers. Use the passed $request object where available.
				$this->container['request'] = $request;
				
				if (is_string($callable)) { 
					$this->includeController($callable, $request, $customControllerPath);
				}
				elseif (is_callable($callable)) {
					$callbackArgs[] = $request;
					$response = call_user_func_array($callable, $callbackArgs);
				}
			}
		}
	}

	/**
	 * Group routing for running certain code for groups of pages or rest end points.
	 * Allows you to run middleware for certain grouped requests
	 * 
	 * $App->group('/api/*', function() use ($App) {
	 * 	$App->get('/api/one/*:path', function($request) use ($App) {
	 *			Run code
	 * 	});
    *	}, [ new \App\AuthMiddleware ]);
	 *
	 * @param [type] $pattern url path to match with path parts and asterisk. Does not support or pass on variables.
	 * @param function $callable a closure or callable function
	 * @param array of invokable class objects $middlewares You can pass it in as [ new \App\AuthMiddleware ] or pass a closure if you want the code to run in your routes file.
	 * @return void
	 */
	public function group($pattern, $callable, $middlewares = null)
	{
		$match = false;
		$middlewareFail = false;
		$patternElements = explode('/', ltrim($pattern, '/'));
		$requestUrl = ltrim(strtok(filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL) , '?'), '/');
		$requestUrl = str_replace(['../'], ['/'], $requestUrl);
		$urlElements = explode('/', $requestUrl);
		
		foreach($patternElements as $patternElement) {
			$urlElement = array_shift($urlElements);
			
			if (strstr($patternElement, '*') !== false) {
				$match = true;
				break;
			}
			elseif ($urlElement != $patternElement) {
				$match = false;
				break;
			}
		}

		if ($match) {
			if (isset($middlewares) and is_object($middlewares[0])) {
				$request = $this->makeRequestObject();
				foreach ($middlewares as $middleware) {
					if (is_callable($middleware)) {
						$response = call_user_func_array($middleware, [$request]);
						if ($response === false) {
							$middlewareFail = true;
							break;
						}
					}
				}
			}
			
			if (is_callable($callable) and !$middlewareFail) {
				call_user_func($callable);
			}
		}
	}

	/**
	 * Make and return a True request object
	 *
	 * @return object
	 */
	public function makeRequestObject()
	{
		$request = (object)[];
		$requestKey = strtolower($_SERVER['REQUEST_METHOD']);
		$request->path = $_SERVER['REQUEST_URI'];
		$request->method = $_SERVER['REQUEST_METHOD'];
		$request->ip = $_SERVER['REMOTE_ADDR'];
		$request->status = http_response_code();

		if (isset($_SERVER['CONTENT_TYPE'])) {
			$contentParts = explode(';',$_SERVER['CONTENT_TYPE']);
			$request->contentType = $contentParts[0];
		} else {
			$request->contentType = '';
		}

		$request->userAgent = $_SERVER['HTTP_USER_AGENT'];
		
		if (isset($_SERVER['HTTP_REFERER'])) {
			$request->referrer = $_SERVER['HTTP_REFERER'];
		} else {
			$request->referrer = '';
		}			

		$request->headers = (object) $this->getallheaders();

		if (array_key_exists('HTTPS', $_SERVER)) {
			$request->https = ($_SERVER['HTTPS'] == 'on' ? true : false);
		}
		else {
			$request->https = false;
		}

		$request->url = (object)[];
		
		$request->url->host = $_SERVER['HTTP_HOST'];
		$request->url->full = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		
		$urlParts = Functions::parseUrl($request->url->full);
		
		if (isset($urlParts->domain)) {
			$request->url->domain = $urlParts->domain;
		}

		if (isset($urlParts->subdomain)) {
			$request->url->subdomain = $urlParts->subdomain;
		}

		if (isset($urlParts->extension)) {
			$request->url->extension = $urlParts->extension;
		}
		
		if (isset($urlParts->file)) {
			$request->url->file = $urlParts->file;
		}

		if (isset($urlParts->query)) {
			$request->url->query = $urlParts->query;
		}

		if (isset($_FILES)) {
			$request->files = $_FILES;
		}

		return $request;		
	}

	/**
	 * Create and output response
	 * 
	 * $App->response('{"result":"success"}', 'json', 200, ["Cache-Control: no-cache"])
	 */
	public function response($body, $type = 'html', $code = 200, $headers = [])
	{
		header('X-Frame-Options: SAMEORIGIN');
		if ($_SERVER['HTTPS'] == 'on') {
			header('Strict-Transport-Security: max-age=31536000');
		}
		header('X-Content-Type-Options: nosniff');
		header('Referrer-Policy: same-origin');
		header_remove("X-Powered-By");
		
		switch ($type) {
			case 'html':
				header("Content-Type: text/html");
			break;
			case 'json':
				header("Content-Type: application/json");
				if (is_array($body)) {
					$body = json_encode($body);
				}
			break;
			case 'xml':
				header("Content-Type: application/xml");
			break;
			case 'text':
				header("Content-Type: text/plain");
			break;
			default:
				header("Content-Type: text/html");
		}

		if (!is_null($code) and is_numeric($code)) {
			http_response_code($code);
		}

		# load preferences headers
		/** 
		 * place this in your site.ini file and load it into $App
		 * 
		 * [preferences]
		 * cache_json_responses=false
		 * cache_html_responses=true
		 */	
		$prefs = $this->container['config']->preferences;

		if (isset($prefs)) {
			if (isset($prefs->cache_json_responses) and $type == 'json') {
				header_remove("Pragma");
				if ($prefs->cache_json_responses) {
					header('Cache-Control: max-age=21600, public');
					header('Expires: '.gmdate("D, d M Y H:i:s", strtotime("+6 hours")).' GMT');
				} else {
					header('Cache-Control: no-store, no-cache, must-revalidate');					
				}
			} 

			if (isset($prefs->cache_html_responses) and $type == 'html') {
				header_remove("Pragma");
				if ($prefs->cache_html_responses) {
					header('Cache-Control: max-age=604800, public');
					header('Expires: '.gmdate("D, d M Y H:i:s", strtotime("+7 days")).' GMT');
				} else {
					header('Cache-Control: no-store, no-cache, must-revalidate');					
				}
			} 
		}

		if (!is_null($headers)) {
			foreach ($headers as $header) {
				header($header);
			}
		}

		echo $body;
		die();
	}

	/**
	 * Creates a request to a server. Use the get, post, etc. methods to access it.
	 *
	 * @param string $method GET, POST, etc
	 * @param string $url 'http://www.server.com'
	 * @param callable function $callable function($response) {}
	 * @param array $options see comment above get method
	 * @return void
	 */
	public function request($method, $url, $body, $callable, $options)
	{
		$protocol = 'http';
		$stream['method'] = $method;
		$stream['header'] = [];

		if (array_key_exists('header', $options)) {
			if (is_array($options['header'])) {
				$stream['header'] = $options['header'];
			} else {
				$stream['header'][] = $options['header'];
			}			
		}
		
		if (array_key_exists('type', $options)) {
			if ($options['type'] == 'json') {
				$stream['header'][] = 'Content-Type: application/json';
			} elseif ($options['type'] == 'xml') {
				$stream['header'][] = 'Content-Type: application/xml';
			} elseif ($options['type'] == 'form') {
				$stream['header'][] = 'Content-Type: application/x-www-form-urlencoded';
			}
		} else {
			$options['type'] = 'text';
		}

		if (array_key_exists('timeout', $options)) {
			$stream['timeout'] = $options['timeout'];
		}

		if (array_key_exists('proxy', $options)) {
			$stream['proxy'] = $options['proxy'];
		}

		if (substr($url, 0, 5 ) == "https") {
			$stream['ssl'] = ['SNI_enabled' => false];
			$protocol = 'https';
		}

		if (array_key_exists('query', $options)) {
			$url = $url.'?'.http_build_query($context['query']);
		}

		if (is_array($body) and count($body) > 0) {
			if ($options['type'] == 'json') {
				$stream['content'] = json_encode($body);
			} elseif ($options['type'] == 'xml') {
				$xml = new SimpleXMLElement('<root/>');
				array_walk_recursive($body, array($xml, 'addChild'));
				$stream['content'] = $xml->asXML();
			} elseif ($options['type'] == 'form') {
				$stream['content'] = http_build_query($body);
			}
		} elseif (!empty($body)) {
			$stream['content'] = $body;
		}

		$stream['ignore_errors'] = true;
		
		$context = stream_context_create([$protocol=>$stream]);
		#$context['ignore_errors'] = true;

		$responseBody = file_get_contents($url, false, $context);
		
		$response = (object)[];

		$response->body = $responseBody;
		$response->headers = [];

		foreach ($http_response_header as $header) {
			if (! preg_match('/^([^:]+):(.*)$/', $header, $output)) continue;	
			
			if ($output[1] == 'Set-Cookie') {
				$cookies[] = $output[2];
			} else {
				$response->headers[$output[1]] = $output[2];
			}			
		} 

		if (isset($cookies)) {
			$response->headers['Cookies'] = $cookies;
		}
		#preg_match('/^(\w+)\/(\d+\.\d+) (\d+) (.+?)$/', $status_line, $matches)
		preg_match('|HTTP/\d\.\d\s+(\d+)\s+.*|',$http_response_header[0],$match);
		$response->status = $match[1];

		if (is_callable($callable)) {
			$callbackArgs[] = $response;
			call_user_func_array($callable, $callbackArgs);
		}
	}

	/**
     * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
     *
     * @param array $uploadedFiles The non-normalized tree of uploaded file data.
     *
     * @return array A normalized tree of UploadedFile instances.
     */
    private static function parseUploadedFiles(array $uploadedFiles)
    {
        $parsed = [];
        foreach ($uploadedFiles as $field => $uploadedFile) {
            if (!isset($uploadedFile['error'])) {
                if (is_array($uploadedFile)) {
                    $parsed[$field] = static::parseUploadedFiles($uploadedFile);
                }
                continue;
            }

            $parsed[$field] = [];
            if (!is_array($uploadedFile['error'])) {
                $parsed[$field] = new static(
                    $uploadedFile['tmp_name'],
                    isset($uploadedFile['name']) ? $uploadedFile['name'] : null,
                    isset($uploadedFile['type']) ? $uploadedFile['type'] : null,
                    isset($uploadedFile['size']) ? $uploadedFile['size'] : null,
                    $uploadedFile['error'],
                    true
                );
            } else {
                $subArray = [];
                foreach ($uploadedFile['error'] as $fileIdx => $error) {
                    // normalise subarray and re-parse to move the input's keyname up a level
                    $subArray[$fileIdx]['name'] = $uploadedFile['name'][$fileIdx];
                    $subArray[$fileIdx]['type'] = $uploadedFile['type'][$fileIdx];
                    $subArray[$fileIdx]['tmp_name'] = $uploadedFile['tmp_name'][$fileIdx];
                    $subArray[$fileIdx]['error'] = $uploadedFile['error'][$fileIdx];
                    $subArray[$fileIdx]['size'] = $uploadedFile['size'][$fileIdx];

                    $parsed[$field] = static::parseUploadedFiles($subArray);
                }
            }
        }

        return $parsed;
    }

	/**
	 * [includeController description]
	 * @param  string $callableController		controller file name
	 * @param  object $request						passed in request object
	 * @param  boolean $customControllerPath	True if you want to include a custom path
	 * @return null                       		This does the file including for you.
	 */
	public function includeController($callableController, $request, $customControllerPath)
	{
		$App = $this; 
		include $this->controller($callableController, $customControllerPath);
	}

	/**
	 * Return the full controller path
	 * @param  string  $path                 The filename or the path inside the controller dir and the filename
	 * @param  boolean $customControllerPath Whether or not to look inside the controller dir or start as base path
	 * @return string                        Return server root path to controller file
	 */
	public function controller($path, $customControllerPath = false)
	{
		if (empty($path)) {
			$path = 'index.php';
		}

		if (!strstr($path, '.php')) {
			$path = $path.'.php';
		}

		if ($customControllerPath) {
			return BP. '/' . $path;
		} else {
			return BP. '/app/controllers/' . $path;
		} 
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
	* Perform a 301 redirect of the url if needed.
	* json file should use keys for request uri and values for redirected uri
	* json format: {
	"path1/path2/":"path3/",
	"path4/path5/*":"path4/path6/*",  <- matches "path4/path5/path8/" redirects to "path4/path6/path8/" The path that matches the * on the request is moved over to the end of the redirect uri in place of the *
	"path7/path9/*":"path7/path10/",  <- matches "path7/path9/path8/" redirects to "path7/path10/" The path that matches the * on the request is NOT moved over to the end of the redirect uri because there is no * on the redirect
	}
	*
	* @param array $params ['request'=>$_SERVER['REQUEST_URI'], 'lookup'=>BP.'/redirects.json', 'type'=>'301']
	* @return type
	* @throws conditon
	**/
	public static function redirect($params)
	{
		$redirect = null;
		# check to make sure all keys are passed.
		if ( array_diff(['request', 'lookup', 'type'], array_keys($params)) ) {
			trigger_error("One of the required parameters is missing from your array passed.", 256);
			return false;
		}   
	  
		extract($params);
	  
		$redirectList = json_decode(file_get_contents($lookup), true);

		if (json_last_error() !== 0) {
			trigger_error("There was an error parsing the redirects json file. Error: ".json_last_error(), 256);
			return false;
		}

		$requestUri = ltrim($request,'/');

		if (array_key_exists($requestUri, $redirectList)) {
			$redirect = $redirectList[$requestUri];
		} else {
			foreach ($redirectList as $key=>$value) {
				$match = strstr($key, '*', true);
				if ($match !== false) {
					$strLen = strlen($match);
					$matchingPartOfRequest = substr($requestUri, 0,$strLen);
					if ($match == $matchingPartOfRequest) {
						# check whether to add end of url to end of redirct or not 
						if (strpos($value, '*') !== false) {
							$requestLen = strlen($requestUri) - $strLen;
							$redirect = str_replace('*','',$value).substr($requestUri, -$requestLen);
						} else {
							$redirect = $value;
						}						
					}
				}
			}
			
			if ($redirect === null) {
				return false;
			}
		}
		
		switch ($type) {
			case "301": $header = "301 Moved Permanently"; break; # redirects permanently from one URL to another passing link equity to the redirected page
			case "303": $header = "303 See Other"; break; # forces a GET request to the new URL even if original request was POST
			case "307": $header = "307 Temporary Redirect"; break; # forces a GET request to the new URL even if original request was POST
			case "308": $header = "308 Permanent Redirect"; break; # The request and all future requests should be repeated using another URI, using same method
		}

		header("HTTP/1.1 $header"); 
		header("Location: /$redirect");
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
	  * Display system errors to page nicely
	  *
	  * @param Type $var Description
	  * @return type
	  * @throws conditon
	  **/
	public function displayErrors($params = [])
	{
		extract($params);
		if (!isset($noticeBox))
			$noticeBox = 'displayNoticeBox';

		if (!isset($debugError))
			$debugError = 'displayDebugError';

		if (!isset($userError))
			$userError = 'displayUserError';

		if (!isset($userWarning))
			$userWarning = 'displayUserWarning';

		if (!isset($userNotice))
			$userNotice = 'displayUserNotice';
		  
		echo (empty($GLOBALS['pageErrors'])? '':'<div id="'.$noticeBox.'"><div id="'.$debugError.'"><div>'.$GLOBALS['pageErrors'].'</div><button id="displayUserCloseButton"></button></div></div>');
		
		echo (empty($GLOBALS['errorUserError'])? '':'<div id="'.$noticeBox.'"><div id="'.$userError.'"><div>'.$GLOBALS['errorUserError'].'</div><button id="displayUserCloseButton"></button></div></div>');
		
		echo (empty($GLOBALS['errorUserWarning'])? '':'<div id="'.$noticeBox.'"><div id="'.$userWarning.'"><div>'.$GLOBALS['errorUserWarning'].'</div><button id="displayUserCloseButton"></button></div></div>');
		
		echo (empty($GLOBALS['errorUserNotice'])? '':'<div id="'.$noticeBox.'"><div id="'.$userNotice.'"><div>'.$GLOBALS['errorUserNotice'].'</div><button id="displayUserCloseButton"></button></div></div>');
		
		if (!empty($GLOBALS['pageErrors']) or !empty($GLOBALS['errorUserError']) or !empty($GLOBALS['errorUserWarning']) or !empty($GLOBALS['errorUserNotice']) ) {
			echo "<script>document.querySelector('#displayUserCloseButton').onclick = function() {document.querySelector('#{$noticeBox}').parentNode.removeChild(document.querySelector('#{$noticeBox}'))}</script>";
		}
	}

	/**
	 * Use to benchmark a process
	 *
	 * @return null
	 */
	public function benchmarkStart()
	{
		$this->startTime = round(microtime(true) * 1000);
	}

	/**
	 * Use to benchmark a process
	 *
	 * @return String echo out the method response. 
	 */
	public function benchmarkEnd()
	{
		if ($this->startTime == 0) {
			return 'Benchmark not started';
		}

		return "Completed in ".(round(microtime(true) * 1000) - $this->startTime)." ms - ";
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

	/**
	* Get all HTTP header key/values as an associative array for the current request.
	* Written by ralouphie - https://github.com/ralouphie
	*
	* A replacement for apache_request_headers()
	* You need to add header redirects like the following for this method to work.
	* RewriteRule .? - [E=HTTP_>Authorization:%{HTTP:Authorization}]
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
			if (substr($key, 0, 5) === 'HTTP_') {
				 $key = substr($key, 5);
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
		return $headers;
	}
}