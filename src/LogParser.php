<?php
namespace True;

/**
 * Parse and iterate apache log files
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.1.0
 * Supports .gz log files as of 1.1.0
 */
class LogParser implements \Iterator
{
	var $list = [];
	
	public function __construct($logFile)
	{
		$this->parse($logFile);
	}

	public function rewind()
	{
		reset($this->list);
	}

	public function current()
	{
		return current($this->list);
	}

	public function next() 
	{
		return next($this->list);
	}

	public function valid() 
	{
		return key($this->list) !== null;
  	}

  	function key()
  	{
		return key($this->list);
  	}

	public function parse($logFile)
	{
		$ac_arr = [];
		if ($this->endsWith($logFile, '.gz'))
			$ac_arr = gzfile($logFile);
		else
			$ac_arr = file($logFile);		

		$astring = join("", $ac_arr);
		$astring = preg_replace("/(\n|\r|\t)/", "", $astring);

		$records = preg_split("/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $astring, -1, PREG_SPLIT_DELIM_CAPTURE);
		$sizerecs = sizeof($records);

		// now split into records
		$i = 1;
		$each_rec = 0;
		while($i<$sizerecs) {
			$ip = $records[$i];
			$all = $records[$i+1];
			// parse other fields
			preg_match("/\[(.+)\]/", $all, $match);
			$access_time = date("Y-m-d H:i:s",strtotime($match[1]));
			$all = str_replace($match[1], "", $all);
			preg_match("/\"[A-Z]{3,7} (.[^\"]+)/", $all, $match);
			$http = $match[1];
			$link = explode(" ", $http);
			$all = str_replace("\"[A-Z]{3,7} $match[1]\"", "", $all);
			preg_match("/([0-9]{3})/", $all, $match);
			$success_code = $match[1];
			$all = str_replace($match[1], "", $all);
			preg_match("/\"(.[^\"]+)/", $all, $match);
			$ref = $match[1];
			$all = str_replace("\"$match[1]\"", "", $all);
			preg_match("/\"(.[^\"]+)/", $all, $match);
			$browser = $match[1];
			$all = str_replace("\"$match[1]\"", "", $all);
			preg_match("/([0-9]+\b)/", $all, $match);
			$bytes = $match[1];
			$all = str_replace($match[1], "", $all);
			
			$this->list[] = (object)['ip'=>$ip, 'datetime'=>$access_time, 'page'=>$link[0], 'type'=>$link[1], 'code'=>$success_code, 'size'=>$bytes, 'referer'=>$ref, 'client'=>$browser];

			// advance to next record
			$i = $i + 2;
			$each_rec++;
		}
	}

	function endsWith(string $haystack, string $needle) {
		$length = strlen($needle);
		if (!$length)
			return true;
		return substr($haystack, -$length) === $needle;
	}
}