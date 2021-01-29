<?php
namespace True;

/**
 * Request object
 * 
 * @version v1.0.2
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

	public function __construct()
	{
		$requestKey = strtolower($_SERVER['REQUEST_METHOD']);
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->status = http_response_code();
		$this->contentType = isset($_SERVER['CONTENT_TYPE']) ? strtok($_SERVER['CONTENT_TYPE'], ';'):'text/html';
		$this->userAgent = $_SERVER['HTTP_USER_AGENT'];		
		$this->referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']:'';	
		$this->headers = (object) $this->getallheaders();
		$this->https = array_key_exists('HTTPS', $_SERVER) ? ($_SERVER['HTTPS'] == 'on' ? true:false):false;

		$this->url = (object)[];
		$this->url->path = strtok(filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL), '?');		
		$this->url->host = $_SERVER['HTTP_HOST'];
		$this->url->protocol = $_SERVER['REQUEST_SCHEME'] ?? $this->https ? 'https':'http';
		$this->url->full = $this->url->protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$this->url->protocolhost = $this->url->protocol.'://'.$_SERVER['HTTP_HOST'];
		$this->url->scheme = $this->url->protocol.'://';
		$this->url->domain = strtok(str_replace('www.','',$_SERVER['HTTP_HOST']),':');

		if (isset($_FILES)) {
			$this->files = (object)[];
			foreach ($_FILES as $name=>$file) {
				
				if (is_array($file['name'])) {					
					for ($i=0; $i<count($file['name']); $i++) {
						$newFile = [];
						$newFile = [
							'name'=>$file['name'][$i],
							'uploaded'=>($file['error'][$i]==0? true:false),
							'type'=>$file['type'][$i],
							'tmp_name'=>$file['tmp_name'][$i],
							'size'=>$file['size'][$i]
						];

						if ($newFile['uploaded'] and !empty($newFile['tmp_name'])) {
							$newFile['ext'] = strtolower(pathinfo($newFile['name'], PATHINFO_EXTENSION));
							$newFile['mime'] = mime_content_type($newFile['tmp_name']);
						}
						$this->files->{$name}[$i] = new \True\File($newFile, $newFile['name']);
					}
					
				} else
					$this->files->{$name} = new \True\File($file, $name);
			}
		}

		$cleanedContentType = trim(explode(';', $this->contentType)[0]);		
		
		$this->post = (object) ($_POST ?? []);
		$this->get = (object) ($_GET ?? []);
		$this->put = (object) ($_PUT ?? []);
		$this->patch = (object) ($_PATCH ?? []);
		$this->delete = (object) ($_DELETE ?? []);
		
		if (in_array($cleanedContentType, ['application/json'])) {
			$requestBody = file_get_contents('php://input');
			$requestKey = strtolower($this->method);
			if (!empty($requestBody)) 
				$this->$requestKey = json_decode($requestBody);
		}

		$this->all = (object) array_merge((array) $this->get, (array) $this->post, (array) $this->put, (array) $this->patch, (array) $this->delete);
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