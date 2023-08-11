<?php
namespace True;

/**
 * @version 1.0.3
 * $response = new True\Response;
 * $response = new True\Response(['cacheJson','cacheHTML']); if you want all json or html responses to be cached pass cacheJson, cacheHTML, or both as array values. 
 * $response('{"result":"success"}', 'json', 200, ["Cache-Control: no-cache"]);
 * $response(["result"=>"success"], 'json'); arrays auto convert to json string
 * $response("<h1>Hello World</h1>");
 */
class Response
{
	var $prefs = [];
	
	public function __construct($prefs = [])
	{
		$this->prefs = $prefs;
		if (!isset($this->prefs['hsts']))
			$this->prefs['hsts'] = false;
	}
	
	public function __invoke($body, $type = 'html', $code = 200, $headers = [])
	{
		header('X-Frame-Options: SAMEORIGIN');
		if ($this->prefs['hsts'] or (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on'))
			header('Strict-Transport-Security: max-age=31536000');
		header('X-Content-Type-Options: nosniff');
		header('Referrer-Policy: same-origin');
		header_remove("X-Powered-By");
		header_remove("cache-control");
		
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

		header('Cache-Control: no-store, no-cache, must-revalidate');

		if (in_array('cacheJson', $this->prefs) and $type == 'json') {
			header_remove("Pragma");
         header('Cache-Control: max-age=21600, public');
        	header('Expires: '.gmdate("D, d M Y H:i:s", strtotime("+6 hours")).' GMT');                
      }

		if (in_array('cacheHTML', $this->prefs) and $type == 'html') {
			header_remove("Pragma");
         header('Cache-Control: max-age=604800, public');
			header('Expires: '.gmdate("D, d M Y H:i:s", strtotime("+7 days")).' GMT');               
      }

		if (!is_null($headers)) {
			foreach ($headers as $header) {
				header($header);
			}
		}

		echo $body;
		die();
	}
}