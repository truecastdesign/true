<?php

namespace True;

/**
 * Send email class using SMTP Authentication
 * 
 * @version 1.3.1
 * 
$mail = new \True\Email('domain.com', 465);  // ssl and tcp are turned on or off automatacally based on the port provided.
$mail->setLogin('user@domain.com', 'password')
->setFrom('user@domain.com', 'name')
->addReplyTo('user@domain.com', 'name')
->addTo('user@domain.com', 'name')
->addCc('user@domain.com', 'name')
->addBcc('user@domain.com', 'name')
->addAttachment(BP.'/path/to/filename.jpg')
->addHeader('header-title', 'header value')
->setCharset('utf-16', 'header value') // default: utf-8;  values: utf-16, utf-32, ascii, iso 8859-1 
->setSubject('Test subject')
->setTextMessage('Plain text message')
->setHtmlMessage('<strong>HTML Text Message</strong>')
->setHTMLMessageVariables('name'=>'John Doe', 'phone'=>'541-555-5555', 'message'=>'Plain text message')
->addHeader('X-Auto-Response-Suppress', 'All')
->addDKIM(BP.'/app/data/dkim.private', 'domain.com');

if ($mail->send()) {
	echo 'SMTP Email has been sent' . PHP_EOL;   
} else {
	echo 'An error has occurred. Please check the logs below:' . PHP_EOL;
	pr($mail->getLogs());
}
*/
class Email
{
	const CRLF = "\r\n";
	const OK = 250;

	/** @var string $server */
	protected $server;

	/** @var string $hostname */
	protected $hostname;

	/** @var int $port */
	protected $port;

	/** @var resource $socket */
	protected $socket;

	/** @var string $username */
	protected $username;

	/** @var string $password */
	protected $password;

	/** @var int $connectionTimeout */
	protected $connectionTimeout;

	/** @var int $responseTimeout */
	protected $responseTimeout;

	/** @var string $subject */
	protected $subject;

	/** @var array $to */
	protected $to = array();

	/** @var array $cc */
	protected $cc = array();

	/** @var array $bcc */
	protected $bcc = array();

	/** @var array $from */
	protected $from = array();

	/** @var array $replyTo */
	protected $replyTo = array();

	/** @var array $attachments */
	protected $attachments = array();

	/** @var string|null $protocol */
	protected $protocol = null;

	/** @var string|null $textMessage */
	protected $textMessage = null;

	/** @var string|null $htmlMessage */
	protected $htmlMessage = null;

	/** @var bool $isHTML */
	protected $isHTML = false;

	/** @var bool $isTLS */
	protected $isTLS = false;

	/** @var array $logs */
	protected $logs = array();

	/** @var string $charset */
	protected $charset = 'utf-8';

	/** @var array $headers */
	protected $headers = array();

	# DKIM variables
	protected $privateKey;
	protected $domainName;
	protected $selector = 'default';
	protected $hashMethod = null;
	protected $insertDKIM = false;

	/**
	 * Class constructor
	*  -- Set server name, port and timeout values
	*
	* @param string $server
	* @param int $port
	* @param string $protocol // leave null for auto detection. Values: tcp or ssl
	* @param int $connectionTimeout
	* @param int $responseTimeout
	* @param string|null $hostname
	*/
	public function __construct($server, $port = 25, $protocol = null, $connectionTimeout = 30, $responseTimeout = 8, $hostname = null)
	{
		$this->port = $port;
		$this->server = $server;
		$this->protocol = $protocol;
		$this->connectionTimeout = $connectionTimeout;
		$this->responseTimeout = $responseTimeout;
		$this->hostname = empty($hostname) ? gethostname() : $hostname;
		$this->headers['MIME-Version'] = '1.0';
	}

	/**
	 * Add to recipient email address
	*
	* @param string $address
	* @param string|null $name
	* @return Email
	*/
	public function addTo($address, $name = null)
	{
		$this->to[] = array($address, $name);

		return $this;
	}

	/**
	 * Add carbon copy email address
	*
	* @param string $address
	* @param string|null $name
	* @return Email
	*/
	public function addCc($address, $name = null)
	{
		$this->cc[] = array($address, $name);

		return $this;
	}

	/**
	 * Add blind carbon copy email address
	*
	* @param string $address
	* @param string|null $name
	* @return Email
	*/
	public function addBcc($address, $name = null)
	{
		$this->bcc[] = array($address, $name);

		return $this;
	}

	/**
	 * Add email reply to address
	*
	* @param string $address
	* @param string|null $name
	* @return Email
	*/
	public function addReplyTo($address, $name = null)
	{
		$this->replyTo[] = array($address, $name);

		return $this;
	}

	/**
	 * Add file attachment
	*
	* @param string $attachment
	* @return Email
	*/
	public function addAttachment($attachment)
	{
		if (file_exists($attachment)) {
			$this->attachments[] = $attachment;
		}

		return $this;
	}

	public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;

		return $this;
	}

	/**
	 * Sign your email with DKIM (DomainKeys Identified Mail) for better deliveribility
	 *
	 * @param string $privateKey full path to key file
	 * @param string $domainName
	 * @param string $selector
	 * @param string $hashMethod available rsa-sha1 or rsa-sha256 (default)
	 * @return void
	 */
	public function addDKIM(string $privateKey, string $domainName, string $selector = 'default', string $hashMethod = 'rsa-sha256')
	{
		$this->insertDKIM = true;
		$this->privateKey = file_get_contents($privateKey);
		$this->domainName = $domainName;
		$this->selector = $selector;
		$this->hashMethod = $hashMethod;

		return $this;
	}

	private function generateDKIM()
	{
		if (is_null($this->hashMethod)) 
			$this->hashMethod = defined('OPENSSL_ALGO_SHA256')? 'rsa-sha256':'rsa-sha1';

		if (!in_array($this->hashMethod, ['rsa-sha256','rsa-sha1']))
			throw new \Exception("The DKIM hashing algorithm must be rsa-sha1 or rsa-sha256. ".$this->hashMethod." provided.");

		$pkeyId = openssl_get_privatekey($this->privateKey, '');

		if (!$pkeyId)
			throw new \Exception('Unable to load DKIM Private Key ['.openssl_error_string().']');

		$headers = '';
		foreach ($this->headers as $k=>$v) {
			$headers .= strtolower(trim($k)).': '.$v;
		}

		if (!openssl_sign($headers, $signature, $pkeyId, $this->hashMethod))
         throw new \Exception('Unable to sign DKIM Hash ['.openssl_error_string().']');
		
		$bodyHash = base64_encode($signature);
		
		$params = [
			'v'=>'1', 
			'a'=>$this->hashMethod,
			'c'=>'relaxed/relaxed',
			'd'=>$this->domainName,
			'h'=>'mime-version:content-type:content-transfer-encoding:subject:from:to:'.implode(':', array_keys($this->headers)),
			's'=>$this->selector,
			'bh'=>$bodyHash,
			'i'=>"@".$this->domainName
		];

		$string = '';
		foreach ($params as $k => $v)
			$string .= $k.'='.$v.'; ';
      $string = trim($string);

		$this->addHeader('DKIM-Signature', $string);
	}

	/**
	 * Set SMTP Login authentication
	*
	* @param string $username
	* @param string $password
	* @return Email
	*/
	public function setLogin($username, $password)
	{
		$this->username = $username;
		$this->password = $password;

		return $this;
	}

	/**
	 * Get message character set
	*
	* @param string $charset
	* @return Email
	*/
	public function setCharset($charset)
	{
		$this->charset = $charset;

		return $this;
	}

	/**
	 * Set SMTP Server protocol
	* -- default value is null (no secure protocol)
	*
	* @param string $protocol 'tls'
	* @return Email
	*/
	public function setProtocol($protocol = null)
	{
		if ($protocol == 'tls') {
			$this->isTLS = true;
		}

		$this->protocol = $protocol;

		return $this;
	}

	/**
	 * Set from email address and/or name
	*
	* @param string $address
	* @param string|null $name
	* @return Email
	*/
	public function setFrom($address, $name = null)
	{
		$this->from = array($address, $name);

		return $this;
	}

	/**
	 * Set email subject string
	*
	* @param string $subject
	* @return Email
	*/
	public function setSubject($subject)
	{
		$this->subject = $subject;

		return $this;
	}

	/**
	 * Set plain text message body
	*
	* @param string $message
	* @return Email
	*/
	public function setTextMessage($message)
	{
		$this->textMessage = $message;

		return $this;
	}

	/**
	 * Set html message body
	*
	* @param string $message
	* @return Email
	*/
	public function setHtmlMessage($message)
	{
		$this->htmlMessage = $message;

		return $this;
	}

	/**
	 * Set html message body
	*
	* @param array $params ['name'=>'My Name', 'message'=>"Message\nthis is just plan text."]
	* The variables get outputted in the order you put them in the array.
	* If it finds a message key, it will convert the value to html.
	* 
	* @return Email
	*/
	public function setHTMLMessageVariables($params): object
	{
		if (is_object($params))
			$params = (array) $params;

		if (!is_array($params))
			throw new \Exception("setHTMLMessageVariables needs to be passed an array or object");
		
		foreach ($params as $key=>$value) {
			if ($key == 'message') {
				$this->htmlMessage .= \True\Functions::txt2html($value);
			} else {
				$this->htmlMessage .= "<p>$value</p>";
			}
		}
		
		return $this;
	}

	/**
	 * Get log array
	* -- contains commands and responses from SMTP server
	*
	* @return array
	*/
	public function getLogs()
	{
		return $this->logs;
	}

	/**
	 * Send email to recipient via mail server
	*
	* @return bool
	*/
	public function send()
	{
		$message = '';
		
		$this->socket = fsockopen(
			$this->getServer(),
			$this->port,
			$errorNumber,
			$errorMessage,
			$this->connectionTimeout
		);

		$this->logs['SocketInfo'] = "Server: ".$this->getServer()."; Port: ".$this->port;
		$this->logs['Socket'] = "Error:".$errorNumber." ".$errorMessage;

		if (empty($this->socket)) {			
			return false;
		}

		$this->logs['CONNECTION'] = $this->getResponse();
		$this->logs['HELLO'][1] = $this->sendCommand('EHLO ' . $this->hostname);

		if ($this->isTLS) {
			$this->logs['STARTTLS'] = $this->sendCommand('STARTTLS');
			stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
			$this->logs['HELLO'][2] = $this->sendCommand('EHLO ' . $this->hostname);
		}

		$this->logs['AUTH'] = $this->sendCommand('AUTH LOGIN');
		$this->logs['USERNAME'] = $this->sendCommand(base64_encode($this->username));
		$this->logs['PASSWORD'] = $this->sendCommand(base64_encode($this->password));
		$this->logs['MAIL_FROM'] = $this->sendCommand('MAIL FROM: <' . $this->from[0] . '>');

		$recipients = array_merge($this->to, $this->cc, $this->bcc);
		foreach ($recipients as $address) {
			$this->logs['RECIPIENTS'][] = $this->sendCommand('RCPT TO: <' . $address[0] . '>');
		}

		$this->headers['Date'] = date('r');
		$this->headers['Subject'] = $this->subject;
		$this->headers['From'] = $this->formatAddress($this->from);
		$this->headers['Return-Path'] = $this->formatAddress($this->from);
		$this->headers['To'] = $this->formatAddressList($this->to);

		if (!empty($this->replyTo)) {
			$this->headers['Reply-To'] = $this->formatAddressList($this->replyTo);
		}

		if (!empty($this->cc)) {
			$this->headers['Cc'] = $this->formatAddressList($this->cc);
		}

		if (!empty($this->bcc)) {
			$this->headers['Bcc'] = $this->formatAddressList($this->bcc);
		}

		$boundary = md5(uniqid(microtime(true), true));

		if (!empty($this->attachments)) {
			$this->headers['Content-Type'] = 'multipart/mixed; boundary="mixed-' . $boundary . '"';
			$message = '--mixed-' . $boundary . self::CRLF;
			$message .= 'Content-Type: multipart/alternative; boundary="alt-' . $boundary . '"' . self::CRLF . self::CRLF;
		} else {
			$this->headers['Content-Type'] = 'multipart/alternative; boundary="alt-' . $boundary . '"';
		}

		if (!empty($this->textMessage)) {
			$message .= '--alt-' . $boundary . self::CRLF;
			$message .= 'Content-Type: text/plain; charset=' . $this->charset . self::CRLF;
			$message .= 'Content-Transfer-Encoding: base64' . self::CRLF . self::CRLF;
			$message .= chunk_split(base64_encode($this->textMessage)) . self::CRLF;
		}

		if (!empty($this->htmlMessage)) {
			$message .= '--alt-' . $boundary . self::CRLF;
			$message .= 'Content-Type: text/html; charset=' . $this->charset . self::CRLF;
			$message .= 'Content-Transfer-Encoding: base64' . self::CRLF . self::CRLF;
			$message .= chunk_split(base64_encode($this->htmlMessage)) . self::CRLF;
		}

		$message .= '--alt-' . $boundary . '--' . self::CRLF . self::CRLF;

		if (!empty($this->attachments)) {
			foreach ($this->attachments as $attachment) {
				$filename = pathinfo($attachment, PATHINFO_BASENAME);
				$contents = file_get_contents($attachment);
				$type = mime_content_type($attachment);
				if (!$type) {
					$type = 'application/octet-stream';
				}

				$message .= '--mixed-' . $boundary . self::CRLF;
				$message .= 'Content-Type: ' . $type . '; name="' . $filename . '"' . self::CRLF;
				$message .= 'Content-Disposition: attachment; filename="' . $filename . '"' . self::CRLF;
				$message .= 'Content-Transfer-Encoding: base64' . self::CRLF . self::CRLF;
				$message .= chunk_split(base64_encode($contents)) . self::CRLF;
			}

			$message .= '--mixed-' . $boundary . '--';
		}

		$headers = '';
		foreach ($this->headers as $k => $v) {
			$headers .= $k . ': ' . $v . self::CRLF;
		}

		if ($this->insertDKIM)
			$this->generateDKIM();

		$this->logs['MESSAGE'] = $message;
		$this->logs['HEADERS'] = $headers;
		$this->logs['DATA'][1] = $this->sendCommand('DATA');
		$this->logs['DATA'][2] = $this->sendCommand($headers . self::CRLF . $message . self::CRLF . '.');
		$this->logs['QUIT'] = $this->sendCommand('QUIT');
		fclose($this->socket);

		return substr($this->logs['DATA'][2], 0, 3) == self::OK;
	}

	/**
	 * Get server url
	* -- if set SMTP protocol then prepend it to server
	*
	* @return string
	*/
	protected function getServer()
	{
		if (is_null($this->protocol)) {
			switch ($this->port) {
				case 25:
					$this->protocol = 'tcp';
				break;
				case 587:
				case 465:
				case 2525:
					$this->protocol = 'ssl';
				break;
				default:
					$this->protocol = 'tcp';
			}
		}
		
		return ($this->protocol) ? $this->protocol . '://' . $this->server : $this->server;
	}

	/**
	 * Get Mail Server response
	* @return string
	*/
	protected function getResponse()
	{
		$response = '';
		stream_set_timeout($this->socket, $this->responseTimeout);
		while (($line = fgets($this->socket, 515)) !== false) {
			$response .= trim($line) . "\n";
			if (substr($line, 3, 1) == ' ') {
					break;
			}
		}

		return trim($response);
	}

	/**
	 * Send command to mail server
	*
	* @param string $command
	* @return string
	*/
	protected function sendCommand($command)
	{
		fputs($this->socket, $command . self::CRLF);

		return $this->getResponse();
	}

	/**
	 * Format email address (with name)
	*
	* @param array $address
	* @return string
	*/
	protected function formatAddress($address)
	{
		return (empty($address[1])) ? $address[0] : '"' . $address[1] . '" <' . $address[0] . '>';
	}

	/**
	 * Format email address to list
	*
	* @param array $addresses
	* @return string
	*/
	protected function formatAddressList(array $addresses)
	{
		$data = array();
		foreach ($addresses as $address) {
			$data[] = $this->formatAddress($address);
		}

		return implode(', ', $data);
	}
}
