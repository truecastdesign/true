<?php
namespace True;

/**
 * Request object
 * 
 * @version v1.0.0
 * 
 * Available keys
 * method # post
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
	public function __construct()
	{
		$request = (object)[];
		$requestKey = strtolower($_SERVER['REQUEST_METHOD']);
		$request->method = $_SERVER['REQUEST_METHOD'];
		$request->ip = $_SERVER['REMOTE_ADDR'];
		$request->status = http_response_code();

		if (isset($_SERVER['CONTENT_TYPE'])) {
			$contentParts = explode(';',$_SERVER['CONTENT_TYPE']);
			$request->contentType = $contentParts[0];
		} else
			$request->contentType = '';

		$request->userAgent = $_SERVER['HTTP_USER_AGENT'];
		
		if (isset($_SERVER['HTTP_REFERER']))
			$request->referrer = $_SERVER['HTTP_REFERER'];
		else
			$request->referrer = '';			

		$request->headers = (object) $this->getallheaders();

		if (array_key_exists('HTTPS', $_SERVER))
			$request->https = ($_SERVER['HTTPS'] == 'on' ? true : false);
		else
			$request->https = false;

		$request->url = (object)[];
		$request->url->path = strtok(filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL), '?');		
		$request->url->host = $_SERVER['HTTP_HOST'];
		$request->url->full = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$request->url->protocol = $_SERVER['REQUEST_SCHEME'];
		$request->url->protocolhost = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
		$request->url->scheme = $_SERVER['REQUEST_SCHEME'].'://';
		$request->url->domain = str_replace('www.','',$_SERVER['HTTP_HOST']);

		if (isset($_FILES)) {
			$request->files = (object)[];
			foreach ($_FILES as $name=>$file)
				$request->files->{$name} = new \True\File($file);			
		}

		$postContentTypes = ['application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'];
				
		$cleanedContentTypeParts = explode(';', $request->contentType);
		$cleanedContentType = trim($cleanedContentTypeParts[0]);
		
		if (isset($_POST))
			$request->post = (object)$_POST; 

		if (isset($_GET))
			$request->get = (object)$_GET;

		if (isset($_PUT))
			$request->put = (object)$_PUT;

		if (isset($_PATCH))
			$request->patch = (object)$_PATCH;

		if (isset($_DELETE))
			$request->delete = (object)$_DELETE;

		if (isset($_REQUEST))
			$request->all = (object)$_REQUEST;
		
		if (in_array($cleanedContentType, ['application/json'])) {
			$requestBody = file_get_contents('php://input');
			$requestKey = strtolower($request->method);
			if (!empty($requestBody))
				$request->$requestKey = json_decode($requestBody);
		}

		return $request;
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