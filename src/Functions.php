<?php
namespace True;

/**
 * summary
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.0 
 */
class Functions
{
    /**
     * Strip a series of characters from the beginning and end of a string
     *
     * @param 
     * @return void
     * @author Daniel Baldwin - danb@truecastdesign.com
     **/
    public function trimWord($string, $pattern, $beginningOrEnd='beginning')
    {
    	if (substr($str, 0, strlen($prefix)) == $prefix)
    	{
		    $str = substr($str, strlen($prefix));
		}
    }
}
