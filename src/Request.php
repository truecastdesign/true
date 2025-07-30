<?php
namespace True;

/**
 * Request object
 * 
 * @version v1.5.3
 * 
 * Available keys
 * method # GET,POST,etc
 * ip # 192.168.0.1
 * status # 200
 * contentType # application/json
 * userAgent
 * referrer # https://www.otherdomain.com/about
 * headers->Authorization
 * https # bool true or false
 * url->path # /about
 * url->host # www.domain.com
 * url->domain # domain.com or sub.domain.com
 * url->full # https://www.domain.com/about
 * url->protocol # https
 * url->protocolhost # https://www.domain.com
 * url->scheme # https://
 * files->fieldname->uploaded # true or false
 * files->fieldname->name # image.jpg
 * files->fieldname->ext # jpg
 * files->fieldname->mime # image/jpeg
 * files->fieldname->move($path, $filename) # moves the file to the $path and with the new $filename
 * get->key # value
 * post->key # value
 * delete->key # value
 * put->key # value
 * patch->key # value
 * all->key # value
 */

class Request
{
	var $method;
	var $ip;
	var $status;
	var $contentType;
	var $userAgent;
	var $referrer;
	var $headers;
	var $https;
	var $url;
	var $files;
	var $get;
	var $post;
	var $delete;
	var $put;
	var $patch;
	var $all;
	var $route;

	public function __construct()
	{
		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$requestKey = strtolower($this->method);
		$this->ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		$this->status = http_response_code() ?: 200;
		$this->contentType = isset($_SERVER['CONTENT_TYPE']) ? strtok($_SERVER['CONTENT_TYPE'], ';') : 'text/html';
		$this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$this->referrer = $_SERVER['HTTP_REFERER'] ?? '';
		$this->headers = (object) $this->getallheaders();
		$this->https = array_key_exists('HTTPS', $_SERVER) ? ($_SERVER['HTTPS'] == 'on' ? true : false) : false;

		$this->url = (object)[];
		$this->url->path = strtok(filter_var($_SERVER["REQUEST_URI"] ?? '/', FILTER_SANITIZE_URL), '?') ?: '/';
		$this->url->host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$this->url->protocol = ($this->https ? 'https' : 'http');
		$this->url->full = $this->url->protocol . '://' . ($this->url->host) . ($_SERVER['REQUEST_URI'] ?? '/');
		$this->url->protocolhost = $this->url->protocol . '://' . $this->url->host;
		$this->url->scheme = $this->url->protocol . '://';
		$this->url->domain = strtok(str_replace('www.', '', $this->url->host), ':') ?: 'localhost';

		// Initialize files
		$this->files = (object)[];
		if (isset($_FILES) && is_array($_FILES)) {
				foreach ($_FILES as $name => $file) {
					if (is_array($file['name'])) {
						for ($i = 0; $i < count($file['name']); $i++) {
								$newFile = [
									'name' => $file['name'][$i] ?? '',
									'uploaded' => isset($file['error'][$i]) && $file['error'][$i] == 0,
									'type' => $file['type'][$i] ?? '',
									'tmp_name' => $file['tmp_name'][$i] ?? '',
									'size' => $file['size'][$i] ?? 0
								];
								if ($newFile['uploaded'] && !empty($newFile['tmp_name'])) {
									$newFile['ext'] = strtolower(pathinfo($newFile['name'], PATHINFO_EXTENSION) ?: '');
									$newFile['mime'] = mime_content_type($newFile['tmp_name']) ?: '';
								}
								$this->files->{$name}[$i] = new \True\File($newFile, $newFile['name']);
						}
					} else {
						$newFile = [
								'name' => $file['name'] ?? '',
								'uploaded' => isset($file['error']) && $file['error'] == 0,
								'type' => $file['type'] ?? '',
								'tmp_name' => $file['tmp_name'] ?? '',
								'size' => $file['size'] ?? 0
						];
						if ($newFile['uploaded'] && !empty($newFile['tmp_name'])) {
								$newFile['ext'] = strtolower(pathinfo($newFile['name'], PATHINFO_EXTENSION) ?: '');
								$newFile['mime'] = mime_content_type($newFile['tmp_name']) ?: '';
						}
						$this->files->{$name} = new \True\File($newFile, $name);
					}
				}
		}

		$contentType = explode(';', $this->contentType);
		$cleanedContentType = trim($contentType[0] ?? 'text/html');
		$requestBody = file_get_contents('php://input') ?: '';

		if ($this->method === 'POST' && in_array($cleanedContentType, ['application/x-www-form-urlencoded', 'multipart/form-data'])) {
			$reconstructedRaw = http_build_query($_POST);
			$this->post = new \True\RequestData($reconstructedRaw);
		} else {
			$this->post = new \True\RequestData($requestBody);
		}
			

		$this->get = new \True\RequestData($_SERVER['QUERY_STRING']);
		$this->put = new \True\RequestData($requestBody);
		$this->delete = new \True\RequestData($requestBody);
		$this->patch = new \True\RequestData($requestBody);
		$this->all = new \True\RequestData($requestBody);

		// Populate GET data
		if (isset($_GET) && is_array($_GET)) {
			foreach ($_GET as $k => $v) {
				$this->get->$k = $v;
			}
		}

		// Populate POST data (handles application/x-www-form-urlencoded and multipart/form-data)
		if (isset($_POST) && is_array($_POST)) {
			foreach ($_POST as $k => $v) {
				$this->post->$k = $v;
			}
		}
		
		if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE']) && !empty($requestBody)) {
			// PUT/PATCH/DELETE: Parse body based on Content-Type
			
			$parsedData = null; // Will hold parsed data

			switch ($cleanedContentType) {
					case 'application/json':
					case 'application/ld+json':
					case 'application/activity+json':
					case 'application/octet-stream':
						$parsedData = json_decode($requestBody);
						if (json_last_error() !== JSON_ERROR_NONE) {
							error_log("JSON parsing error: " . json_last_error_msg());
							$parsedData = new \stdClass(); // Fallback to empty
						}
						break;
					case 'application/x-www-form-urlencoded':
					case 'multipart/form-data':
						
						break;
					case 'text/xml':
					case 'application/xml':
						$xml = simplexml_load_string($requestBody, 'SimpleXMLElement', LIBXML_NOCDATA);
						if ($xml === false) {
							error_log("XML parsing error");
							$parsedData = new \stdClass();
						} else {
							$parsedData = json_decode(json_encode($xml)); // Convert to stdClass
						}
						break;
				
					case 'text/plain':
					default:
						// For binary or unknown, store as raw property
						$parsedData = new \stdClass();
						$parsedData->raw = $requestBody;
						break;
			}

			// Assign parsed data to the method-specific RequestData
			if ($parsedData) {
					foreach ($parsedData as $k => $v) {
						$this->{$requestKey}->$k = $v;
					}
			}
		}

		// Build $this->all: Merge GET with the active method's data
		
		// Always include GET
		foreach ((array) $this->get as $k => $v) {
			$this->all->$k = $v;
		}
		// Add active method's data (overwrites if keys conflict)
		$activeData = $this->{$requestKey} ?? new \True\RequestData('');
		foreach ((array) $activeData as $k => $v) {
			$this->all->$k = $v;
		}

		// Handle all content types
		// if ($this->method !== 'GET' || !empty($requestBody)) {
		// 	switch ($cleanedContentType) {
		// 		case 'application/json':
		// 		case 'application/ld+json':
		// 		case 'application/activity+json':
		// 			$decodedJson = json_decode($requestBody);  // Decodes to nested stdClass objects
		// 			if (json_last_error() === JSON_ERROR_NONE && is_object($decodedJson)) {
		// 				$this->$requestKey = new \True\RequestData($requestBody);
						
		// 				foreach ($decodedJson as $k => $v)
		// 					$this->$requestKey->$k = $v;
		// 			}
		// 		break;
		// 		case 'application/x-www-form-urlencoded':
		// 			parse_str($requestBody, $formData);
		// 			if (is_array($formData)) {
		// 				$this->$requestKey = new \True\RequestData($requestBody);
		// 				foreach ($formData as $k => $v) {
		// 						$this->$requestKey->$k = $v;
		// 				}
		// 			}
		// 			break;
		// 		case 'multipart/form-data':
		// 			// Already handled by $_POST and $_FILES
		// 			break;
		// 		case 'text/xml':
		// 		case 'application/xml':
		// 			$xml = simplexml_load_string($requestBody, 'SimpleXMLElement', LIBXML_NOCDATA);
		// 			if ($xml) {
		// 				$this->$requestKey = new \True\RequestData($requestBody);
		// 				$array = json_decode(json_encode((array)$xml), true);
		// 				foreach ($array as $k => $v) {
		// 						$this->$requestKey->$k = $v;
		// 				}
		// 			}
		// 			break;
		// 		case 'application/octet-stream':
		// 			$this->$requestKey = new \True\RequestData($requestBody);
		// 			$this->$requestKey->raw = $requestBody;
		// 			break;
		// 		default:
		// 			$this->$requestKey = new \True\RequestData($requestBody);
		// 			$this->$requestKey->raw = $requestBody;
		// 			break;
		// 	}

		// 	// Build $this->all using RequestData
		// 	$this->all = new \True\RequestData($requestBody);
		// 	$dataSources = [
		// 		(array) $this->get,
		// 		(array) $this->post,
		// 		(array) $this->put,
		// 		(array) $this->patch,
		// 		(array) $this->delete
		// 	];
		// 	foreach ($dataSources as $source) {
		// 		foreach ($source as $k => $v) {
		// 			$this->all->$k = $v;
		// 		}
		// 	}
		// }
	}

	/**
	 * Determine if the current path matches a pattern, using * as a wildcard.
	 *
	 * @param string $pattern The pattern to match against (e.g., 'about', 'products*', etc.)
	 * @return bool True if the pattern matches the current path, false otherwise.
	 * 
	 * Example: matches: /path  /pathtwo  /pathtwo/paththree
	 * <?=$App->request->is('/path')? 'active':''?>
	 * <?=$App->request->is('/pathtwo*')? 'active':''?>
	 */
	public function is($pattern)
	{
		if ($pattern === '/')
			return $this->url->path === '/';
		
		$basePattern = str_replace('*', '', $pattern);

		if (substr($pattern, -1) === '*')
			return strpos($this->url->path, $basePattern) === 0;
		else
			return $this->url->path === $basePattern;
	}

	/**
	 * Check if the request matches a specific method and contains one or more keys,
	 * with optional value or function-based checks.
	 *
	 * @param string $method The HTTP method to check (e.g., 'POST', 'GET', 'PUT').
	 * @param string|string[] $keys A single key or an array of keys to look for in the request data.
	 * @param mixed $value Optional. If a value is provided and is a callable (e.g., 'is_numeric', 'is_string', or a closure), 
	 *   the function will be called with the value of the first key. 
	 *   If a regular value is provided, a strict comparison is performed.
	 *   If omitted, only key existence is checked.
	 * @return bool True if the method matches, all keys exist, and (optionally) the value or function check passes for the first key; false otherwise.
	 *
	 * Examples:
	 * $App->request->has('POST', 'username'); // Checks if 'username' exists in POST data.
	 * $App->request->has('GET', ['id', 'token']); // Checks if both 'id' and 'token' exist in GET data.
	 * $App->request->has('PUT', 'status', 'active'); // Checks if 'status' exists in PUT data and equals 'active'.
	 * $App->request->has('GET', 'user_id', 'is_numeric'); // Checks if 'user_id' exists in GET and is numeric.
	 * $App->request->has('GET', 'name', function($val) { return strlen($val) > 3; }); // Checks with a custom closure.
	 */
	public function has(string $method, $keys, $value = null): bool
	{
		$method = strtolower($method);
		$data = $this->$method ?? null;

		if ($method !== 'get' && $this->method !== strtoupper($method))
			return false;

		$keys = is_array($keys) ? $keys : [$keys];

		foreach ($keys as $key)
			if (!is_object($data) || !property_exists($data, $key))
				return false;

		if ($value !== null) {
			$actualValue = $data->{$keys[0]};
			if (is_callable($value))
				return $value($actualValue);
			return $actualValue === $value;
		}

		return true;
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
		$headers = [];
		$copy_server = [
			'CONTENT_TYPE'   => 'Content-Type',
			'CONTENT_LENGTH' => 'Content-Length',
			'CONTENT_MD5'    => 'Content-Md5',
		];
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
		if (!isset($headers['Authorization']) && isset($_SERVER['Authorization'])) {
			$headers['Authorization'] = $_SERVER['Authorization'];
		}
		return $headers;
	}
}