<?php
namespace True;

use Exception;

/**
 * App class for main framework interactions
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.13.0
 */
class App
{
	private $container = [];
	private $debug = false;
	private $classes = [];
	private $filesList = [];
	private $configUpdateMode = false;
	private $configUpdatePath = [];

	/**
	 * Create new application
	 *
	 * @param string $files example: app/config/site.ini used to load in config files. Comma delimited list of file paths
	 */
	public function __construct($files = null)
	{
		$this->container['config'] = (object)[];
		$this->container['configUpdate'] = (object)[];

		if (!is_null($files))
			$this->load($files);
		
		$GLOBALS['pageErrors'] = '';
		$GLOBALS['errorUserError'] = '';
		$GLOBALS['errorUserWarning'] = '';
		$GLOBALS['errorUserNotice'] = '';
		
		if (!isset($GLOBALS['debug'])) 
			$GLOBALS['debug'] = false;
		
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
				$config = parse_ini_file($file, true, INI_SCANNER_TYPED);
		
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
						$this->filesList[$section] = $file;
						$this->container['config']->{$section} = (object)$values;
						$this->container['configUpdate']->{$section} = (object)$values;
					}
				} else { // does not have sections
					foreach($config as $key => $value) {
						$this->container['config']->{$key} = $value;
						$this->container['configUpdate']->{$key} = $value;
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
	public function getConfig(string $file, ?string $returnKey = null)
	{
		// default to BP./app/config/ dir
		if (substr($file, 0, 1 ) != "/")
			$file = BP.'/app/config/'.$file;
	
		$config = parse_ini_file($file, true, INI_SCANNER_TYPED);

		// Check if parsing was successful
		if ($config === false)
			throw new \Exception("Failed to parse config file: $file");

		$configOutput = (object)[];

		if (!is_null($returnKey) and !empty($returnKey))
			return $config[$returnKey];

		// has section headings
		if (is_array($config[key($config)])) {
			foreach($config as $section => $values) {
				$configOutput->{$section} = (object)$values;
			}
		} else { // does not have sections
			foreach($config as $key => $value) {
				$configOutput->{$key} = $value;
			}
		}

		return $configOutput;
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
		
		file_put_contents($filename, trim($out));
	}

	private function writeConfigRec(array $data, array $parent = array())
	{
		$out = '';
		foreach ($data as $k => $v) {
			
			if (is_array($v) && $this->isNumericArray($v)) {
            // Handle array elements like rateRequestType[] = "LIST"
            foreach ($v as $arrayValue) {
               $out .= $k . '[] = "' . $arrayValue . '"' . PHP_EOL;
            }
       	} elseif (is_array($v)) {
            // Handle associative arrays (subsections)
            $sec = array_merge((array)$parent, (array)$k);
            $out .= (empty($out) ? '' : PHP_EOL) . '[' . join('.', $sec) . ']' . PHP_EOL;
            $out .= $this->writeConfigRec($v, $sec);
       	}
			else {
					//plain key->value case
					if ($v === false)
						$value = 'Off';
					elseif ($v === true)
						$value = 'On';
					elseif (is_float($v))
						$value = $this->formatFloat($v);
					elseif (is_numeric($v))
						$value = $v;
					elseif (is_string($v))
						$value = '"'.$v.'"';
					else
						$value = '';
					
					$out .= $k.' = '.$value.PHP_EOL;
			}
		}
		return $out;
	}

	/**
	 * Helper function to check if array is a numerically indexed array
	 */
	private function isNumericArray(array $array): bool
	{
		return array_keys($array) === range(0, count($array) - 1);
	}

	/**
	 * Helper function to detect precision and format the float accordingly
	 */
	private function formatFloat(float $number): string
	{
		$decimalPart = fmod($number, 1);
		if ($decimalPart == 0.0) {
			return sprintf("%.0F", $number); // No decimals
		}

		$decimalPlaces = strlen(substr(strrchr((string) $number, '.'), 1));
		return sprintf("%.{$decimalPlaces}F", $number);
	}

	/**
     * Update a config value in the .ini file. Does not touch the key values already there.
     *
     * @param string $sectionOrFile - The section in the ini file, file path and name or just the filename if it is in app/config.
     * @param string $key - The key to update or add.
     * @param mixed $value - The value to set.
     * @return void
     */
   public function configUpdate($sectionOrFile, $key, $value)
	{
		// Determine if the input is a filename or section name
		if (substr($sectionOrFile, -4) === '.ini') {
			$file = $sectionOrFile;
			if (substr($file, 0, 1) != "/")
				$file = BP . '/app/config/' . $file;
		} else {
			if (!isset($this->filesList[$sectionOrFile]))
				throw new \Exception("Section '$sectionOrFile' not found in loaded config files.");
			$file = $this->filesList[$sectionOrFile];
		}

		// Parse INI with sections enabled
		$config = parse_ini_file($file, true, INI_SCANNER_TYPED);

		// Determine if this is a flat config file or sectioned
		if (array_keys($config) === range(0, count($config) - 1)) {
			// Indexed array – shouldn't happen, but fail-safe
			throw new \Exception("Invalid INI file format.");
		}

		if (isset($config[$key]) || array_key_exists($key, $config)) {
			// Direct key in flat file
			$config[$key] = $value;
			$this->container['config']->{$key} = $value;
		} else {
			// Assume it's sectioned – grab first section
			$section = key($config);

			// Ensure it's an array (handle scalar edge case)
			if (!is_array($config[$section])) $config[$section] = [];

			$config[$section][$key] = $value;
			if (!isset($this->container['config']->{$section}) || !is_object($this->container['config']->{$section}))
				$this->container['config']->{$section} = (object)[];
			$this->container['config']->{$section}->{$key} = $value;
		}

		// Save it back
		$this->writeConfig($file, $config);
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
	 * Trigger a PHP error with a given severity level.
	 *
	 * Accepts a string or an array of error messages and triggers a user-level error
	 * using PHP's trigger_error(). Severity can be 'notice', 'warning', or 'error'.
	 *
	 * @param string|array $message  The error message(s) to report. Arrays will be joined with newlines.
	 * @param string       $level    The severity level: 'notice', 'warning', or 'error'. Default is 'warning'.
	 *
	 * @return void
	 */
	public function error($message, $level = 'warning')
	{
		// Normalize message to a string
		if (is_array($message)) 
			$message = implode("<br>", $message);

		// Map string level to PHP error constant
		switch (strtolower($level)) {
			case 'notice':
				$phpLevel = E_USER_NOTICE;
				break;
			case 'error':
				$phpLevel = E_USER_ERROR;
				break;
			case 'warning':
			default:
				$phpLevel = E_USER_WARNING;
				break;
		}

		trigger_error($message, $phpLevel);
	}

	// trigger_error("Error Message", E_USER_WARNING);
	public static function errorHandler($errNo, $errStr, $errFile, $errLine, $errContext='')
	{
		$debugError = $errStr . ': FILE:' . $errFile . ' LINE:' . $errLine;

		$lb = '<br>';

		switch ($errNo) {
			case E_WARNING: // 2
				if ($GLOBALS['debug']) $GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? $lb.$lb. $debugError : $debugError;
			break;

			case E_NOTICE: // 8
				if ($GLOBALS['debug']) $GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? $lb. $debugError : $debugError;
			break;

			case E_USER_ERROR: // 256
				$GLOBALS['errorUserError'].= !empty($GLOBALS['errorUserError']) ? $lb. $errStr.$debugError : $errStr.$debugError;
			break;

			case E_USER_WARNING: // 512
				$GLOBALS['errorUserWarning'].= !empty($GLOBALS['errorUserWarning']) ? $lb. $errStr : $errStr;
			break;

			case E_USER_NOTICE: // 1024
				$GLOBALS['errorUserNotice'].= !empty($GLOBALS['errorUserNotice']) ? $lb. $errStr : $errStr;
			break;

			case E_USER_DEPRECATED: // 16384 - use this error level for errors you don't want the user to see bug for debugging only!
				$GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? $lb.$lb. $debugError : $debugError;
			break;

			case E_DEPRECATED: // 8192
				$GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? $lb.$lb. $debugError : $debugError;
			break;

			default:
				if ($GLOBALS['debug']) $GLOBALS['pageErrors'].= !empty($GLOBALS['pageErrors']) ? $lb.$errNo.' '.$errStr.$debugError : $errStr.$debugError;
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
		$noticeBox = isset($params['noticeBox']) ? $params['noticeBox'] : 'displayNoticeBox';
		$debugError = isset($params['debugError']) ? $params['debugError'] : 'displayDebugError';
		$userError = isset($params['userError']) ? $params['userError'] : 'displayUserError';
		$userWarning = isset($params['userWarning']) ? $params['userWarning'] : 'displayUserWarning';
		$userNotice = isset($params['userNotice']) ? $params['userNotice'] : 'displayUserNotice';

		$errors = [
			'userNotice' => isset($GLOBALS['errorUserNotice']) ? $GLOBALS['errorUserNotice'] : '',
			'userWarning' => isset($GLOBALS['errorUserWarning']) ? $GLOBALS['errorUserWarning'] : '',
			'userError' => isset($GLOBALS['errorUserError']) ? $GLOBALS['errorUserError'] : '',
			'pageErrors' => isset($GLOBALS['pageErrors']) ? $GLOBALS['pageErrors'] : ''
		];

		extract($params);
	
		$displayClasses = [
			'userNotice' => $userNotice,
			'userWarning' => $userWarning,
			'userError' => $userError,
			'pageErrors' => $debugError
		];
	
		foreach ($errors as $key => $message) {
			if (!empty($message)) {
				echo '<div id="' . $noticeBox . '"><div id="' . $displayClasses[$key] . '"><div>' . $message . '</div><button id="displayUserCloseButton"></button></div></div>';
			}
		}
	
		if (!empty($errors['pageErrors']) || !empty($errors['userError']) || !empty($errors['userWarning']) || !empty($errors['userNotice'])) {
			echo "<script>
				document.querySelectorAll('#displayUserCloseButton').forEach(button => {
					button.onclick = function() {
						this.closest('#{$noticeBox}').remove();
					}
				});
			</script>";
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