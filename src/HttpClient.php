<?php
namespace True;

class HttpClient
{
	/**
	 * Creates a request to a server. Use the get, post, etc. methods to access it.
	 *
	 * @param string $method GET, POST, etc
	 * @param string $endpoint 'http://www.server.com'
	 * @param callable function $callable function($response) {}
	 * @param array $options ['type'=>'json|xml|form', 'timeout'=>'360', 'tlsv1.2'=>true, 'proxy'=>'', 'query'=>'']
	 * @return void
	 */
	public function request($method, $endpoint, $callable, $body, $options) 
	{
		$method = strtoupper($method);
		$ciphers = '';

		if (array_key_exists('headers', $options)) {
			if (is_array($options['headers'])) {
				$headers = $options['headers'];
			} else {
				$headers[] = $options['headers'];
			}			
		}

		if (array_key_exists('timeout', $options)) {
			$timeout = "--connect-timeout ".$options['timeout'];
		} else {
			$timeout = "--connect-timeout 60";
		}

		if (array_key_exists('type', $options)) {
			if ($options['type'] == 'json') {
				$headers[] = 'Content-Type: application/json';
			} elseif ($options['type'] == 'xml') {
				$headers[] = 'Content-Type: application/xml';
			} elseif ($options['type'] == 'form') {
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			}
		} else {
			$options['type'] = 'text';
		}

		if (substr($url, 0, 5 ) == "https") {
			if (array_key_exists('tlsv1.2', $options) and $options['tlsv1.2']) {
				$ciphers = '--ciphers DEFAULT:!TLSv1.0:!TLSv1.1:!SSLv3';
			}
		}

		if (array_key_exists('query', $options)) {
			$endpoint = $endpoint.'?'.http_build_query($options['query']);
		}

		if (is_array($body) and count($body) > 0) {
			if ($options['type'] == 'json') {
				$body = json_encode($body);
				$headers[] = "Accept: application/json";
			} elseif ($options['type'] == 'xml') {
				$xml = new SimpleXMLElement('<root/>');
				array_walk_recursive($body, array($xml, 'addChild'));
				$body = $xml->asXML();
			} elseif ($options['type'] == 'form') {
				$body = http_build_query($body);
			}
		}

		$headers[] = 'Content-Length: '.strlen($body);

		$header = '-H "'.implode('" -H "', $headers).'"';

		$curlStr = "curl -iL -X $method $ciphers $endpoint $timeout $header -d '''$body'''";	
		
		$responseBody = shell_exec($curlStr);

		$response = (object)[];
		
		$response->headers = [];

		$resStatusString = substr($responseBody, 0, strpos($responseBody, "\r\n"));
		list($response->version, $response->status) = explode(" ", $resStatusString);	
		

		$resHeader = substr($responseBody, 0, strpos($responseBody, "\r\n\r\n"));

		$resHeaders = explode("\r\n", $resHeader);

		foreach ($resHeaders as $header) {
			if (! preg_match('/^([^:]+):(.*)$/', $header, $output)) continue;	
			
			if ($output[1] == 'Set-Cookie') {
				$response->headers['Cookies'][] = $output[2];
			} else {
				$response->headers[$output[1]] = $output[2];
			}			
		}

		$response->body = trim(substr($responseBody, strpos($responseBody, "\r\n\r\n")));

		if (is_callable($callable)) {
			$callbackArgs[] = $response;
			call_user_func_array($callable, $callbackArgs);
		}
	}
}