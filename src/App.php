<?php
namespace True;

use Exception;

/**
 * App class for main framework interactions
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.11.3
 */
class App
{
	private $container = [];
	private $debug = false;
	private $classes = [];
	/**
	 * Create new application
	 *
	 * @param string $files example: app/config/site.ini used to load in config files. Comma delimited list of file paths
	 */
	public function __construct($files = null)
	{
		$this->container['config'] = (object)[];
		
		if (is_string($files)) {
			$this->load($files);
		}
		
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

			// default to BP./app/config/ dir
			if (substr($file, 0, 1 ) != "/") {
				$file = BP.'/app/config/'.$file;
			}

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
		// default to BP./app/config/ dir
		if (substr($file, 0, 1 ) != "/")
			$file = BP.'/app/config/'.$file;
	
		$config = parse_ini_file($file, true, INI_SCANNER_TYPED);
		if ($key != null)
			return $config[$key];
		else
			return (object)$config;
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
		if (array_key_exists($key, $this->container))
			return $this->container[$key];
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
	 * Call class
	 * Example: $App->title('sdfsd');
	 *
	 * @param class instance $method
	 * @param array $args
	 * @return void
	 */
	function __call($method, $args=[])
   {
    	call_user_func_array($this->container[$method], $args);
   }

	/**
	 * Write a data object to a ini file
	 *
	 * @param $filename, path and filename of ini file
	 * @param array|object $data []
	 * @return void
	 * @author Daniel Baldwin
	 *
	 */
	public function writeConfig(string $filename, $data, array $parent = array())
	{
		$out = $this->writeConfigRec((array)$data);
		
		if (substr($filename, 0, 1 ) != "/")
			$filename = BP.'/app/config/'.$filename;
		
		file_put_contents($filename, $out);
	}

	private function writeConfigRec(array $data, array $parent = array())
	{
		$out = '';
		foreach ($data as $k => $v) {
			if (is_array($v)) {
					//subsection case
					//merge all the sections into one array...
					$sec = array_merge((array) $parent, (array) $k);
					//add section information to the output
					$out .= '[' . join('.', $sec) . ']' . PHP_EOL;
					//recursively traverse deeper
					$out .= $this->writeConfigRec($v, $sec);
			}
			else {
					//plain key->value case
					if ($v === false)
						$value = 'Off';
					elseif ($v === true)
						$value = 'On';
					elseif (is_numeric($v))
						$value = $v;
					else
						$value = '"'.$v.'"';
					
					$out .= $k.' = '.$value.PHP_EOL;
			}
		}
		return $out;
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
		  
		echo (empty($GLOBALS['errorUserNotice'])? '':'<div id="'.$noticeBox.'"><div id="'.$userNotice.'"><div>'.$GLOBALS['errorUserNotice'].'</div><button id="displayUserCloseButton"></button></div></div>');

		echo (empty($GLOBALS['errorUserWarning'])? '':'<div id="'.$noticeBox.'"><div id="'.$userWarning.'"><div>'.$GLOBALS['errorUserWarning'].'</div><button id="displayUserCloseButton"></button></div></div>');

		echo (empty($GLOBALS['errorUserError'])? '':'<div id="'.$noticeBox.'"><div id="'.$userError.'"><div>'.$GLOBALS['errorUserError'].'</div><button id="displayUserCloseButton"></button></div></div>');

		echo (empty($GLOBALS['pageErrors'])? '':'<div id="'.$noticeBox.'"><div id="'.$debugError.'"><div>'.$GLOBALS['pageErrors'].'</div><button id="displayUserCloseButton"></button></div></div>');
		
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

		return "Completed in ".(round(microtime(true) * 1000) - $this->startTime)." ms";
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