<?php
namespace True;

/**
 * @version 1.1.2
 */
class Router
{
	var $request;
	var $match = false;

	public function __construct($RequestObject)
	{
		$this->request = $RequestObject;
	}

	/**
	 * Add GET route (Retrieve a representation of a resource.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to 
	 *
	 * @return null
	 */
	public function get($pattern, $callable, array $passedVars = [])
	{
		$this->router(['GET'], $pattern, $callable, $passedVars);		
	}

	/**
	 * Add POST route (Create, Create a new resource to an existing URL.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to
	 *
	 * @return null
	 */
	public function post($pattern, $callable, array $passedVars = [], $extra = null)
	{
		$this->router(['POST'], $pattern, $callable, $passedVars);
	}

	/**
	 * Add PUT route (Create or Update, Create a new resource to a new URL, or modify an existing resource to an existing URL.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to
	 *
	 * @return null
	 */
	public function put($pattern, $callable, array $passedVars = [], $extra = null)
	{
		$this->router(['PUT'], $pattern, $callable, $passedVars);
	}

	/**
	 * Add PATCH route (partial update a resources. Use when you only need to update one field of the resource)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to
	 *
	 * @return null
	 */
	public function patch($pattern, $callable, array $passedVars = [], $extra = null)
	{
		$this->router(['PATCH'], $pattern, $callable, $passedVars);
	}

	/**
	 * Add DELETE route (Delete an existing resource.)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to
	 *
	 * @return null
	 */
	public function delete($pattern, $callable, array $passedVars = [], $extra = null)
	{
		$this->router(['DELETE'], $pattern, $callable, $passedVars);
	}

	/**
	 * Add OPTIONS route (determine the options and/or requirements associated with a resource, or the capabilities of a server,)
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to
	 *
	 * @return null
	 */
	public function options($pattern, $callable, array $passedVars = [], $extra = null)
	{
		$this->router(['OPTIONS'], $pattern, $callable, $passedVars);
	}

	/**
	 * Add route for any HTTP method
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to
	 *
	 * @return null
	 */
	public function any($pattern, $callable, array $passedVars = [])
	{
		$this->router(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable, $passedVars);
	}

	/**
	 * Add route for provided HTTP methods
	 *
	 * @param  array $methods  ['GET', 'POST']
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine or controller if string
	 * @param  array $passedVars pass in objects or variables you want the controller file to have access to
	 *
	 * @return null
	 */
	public function map($methods, $pattern, $callable, array $passedVars = [])
	{
		$this->router($methods, $pattern, $callable, $passedVars);
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
	public function router(array $method, $pattern, $callable, $passedVars = [])
	{ 
		if ($this->match)
			return false;

		$_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);

		// check if method matches
		if (in_array($_SERVER['REQUEST_METHOD'], $method)) {
			$this->match = true;
			$parametersFound = false;
			$patternElements = explode('/', ltrim($pattern, '/'));
			$requestUrl = ltrim(strtok(filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL) , '?'), '/');
			$requestUrl = str_replace(['../'], ['/'], $requestUrl);
			$urlElements = explode('/', $requestUrl);
			
			$request = $this->request;
			
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
				# make $request object available whereever $App is available like in the view. Should not be used by controllers. Use the passed $request object where available.
				
				if (is_string($callable))
					$this->includeController($callable, $request, $passedVars);
				elseif (is_callable($callable)) {
					$callbackArgs[] = $request;
					return call_user_func_array($callable, $callbackArgs);
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
			} else {
				$match = true;
			}
		}

		if ($match) {
			if (isset($middlewares) and is_object($middlewares[0])) {
				$request = $this->request;
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
	 * [includeController description]
	 * @param  string $controller		controller file name
	 * @param  object $request						passed in request object
	 * @param  array $passedVars
	 * @return null                       		This does the file including for you.
	 */
	public function includeController($controller, $request, array $passedVars = [])
	{
		extract($passedVars, EXTR_REFS);

		global $App; 
		include $this->controller($controller);
	}

	/**
	 * Return the full controller path
	 * @param  string  $path                 The filename or the path inside the controller dir and the filename
	 * @return string                        Return server root path to controller file
	 */
	public function controller($path)
	{
		if (empty($path)) {
			$path = 'index.php';
		}

		if (!strstr($path, '.php')) {
			$path = $path.'.php';
		}

		if ($this->startsWith($path, BP)) {
			return $path;
		} else {
			return BP. '/app/controllers/' . $path;
		} 
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
	 * Check if protocal is http and forward to https
	 *
	 * @return void
	 */
	public function makeHttps()
	{
		if (!array_key_exists('HTTPS', $_SERVER) and !strstr($_SERVER['HTTP_HOST'], '.test')) {
			$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . $location);
			exit;
		}
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

	public static function startsWith($haystack, $needle)
	{
		  $length = strlen($needle);
		  return (substr($haystack, 0, $length) === $needle);
	}
}