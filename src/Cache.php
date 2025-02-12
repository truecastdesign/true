<?php
namespace True;
/**
 * Cache methods
 *
 * @author Daniel Baldwin
 * @version 1.1.1
 */
class Cache
{
	var $DB;
	var $fields = array('title', 'content', 'type');
		
	public function __construct(Truecast\Hopper $DB)
	{
		$this->DB = $DB;
	}
	
	/**
	 * Set item to cache
	 *
	 * @param string $key
	 * @param bool|integer|string|object|array $content
	 * @return bool
	 */
	public function set($key, $content)
	{ 
		if (empty($key))
			return false;
		$convertedContent = null;
		$type = gettype($content);

		switch ($type) {
			case 'integer':
			case 'boolean':
			case 'string':
				$convertedContent = $content;
			break;
			case 'object':
			case 'array':
				$convertedContent = json_encode($content);
			break;
		}
		
		$this->DB->set('data', ['title'=>$key, 'content'=>$convertedContent, 'type'=>$type]);

		return true;
	}

	/**
	 * Get the cached page based on the key
	 *
	 * @param string $key
	 * @return bool|integer|string|object|array
	 */
	public function get($key)
	{
		$result = $this->DB->get("SELECT * FROM `data` WHERE title=?", [$key], 'object');
		
		if (is_null($result))
			return false;

		switch ($result->type) {
			case 'integer':
			case 'string':
			case 'boolean':
				return $result->content;
			break;
			case 'object':
				return json_decode($result->content);
			break;
			case 'array':
				return json_decode($result->content, true);
			break;
		}
	}

	/**
	 * Delete a page or pages
	 *
	 * @param string|array $key A single key as a string or multiple keys as an array.
 	 * @return bool Returns true on success, false on failure or invalid input.
	 */
	public function delete($key)
	{
		if (is_array($key) && !empty($key)) {
			$placeholders = implode(',', array_fill(0, count($key), '?'));
    		
			$sql = "DELETE FROM `data` WHERE title IN ($placeholders)";
			
			//error_log("Executing SQL: $sql with data: " . implode(', ', $key), 3, BP.'/logs/cache-log.log');

			$keys = array_values($key);

			if ($this->DB->execute($sql, $keys)) {
            //error_log("\nRows deleted: " . $this->DB->rowCount(), 3, BP.'/logs/cache-log.log');
            return true;
			} else {
				error_log("\nFailed to execute deletion\n".$this->DB->getErrors()."\n", 3, BP.'/logs/cache-log.log');
				return false;
			}

			//error_log("Rows deleted: " . $this->DB->rowCount(), 3, BP.'/logs/cache-log.log');
			
		} elseif (is_string($key) && !empty($key))
			return $this->DB->delete('data', $key, 'title');

		return false;
	}

	/**
	 * Empty the entire table
	 *
	 * @return bool
	 */
	public function flush()
	{
		return $this->DB->truncate('data');
	}

	public function getStats()
	{
		$config = $this->DB->getConfig();
		$config->bytes = filesize($config->database);

		$config->records = $this->DB->get('SELECT Count(*) as rows FROM data', [], 'value');

		return $config;
	}
}