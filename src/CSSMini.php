<?php
namespace True;
/**
 * CSS minify class
 * 
 * access it statically like: $str = TACSSMini::process($str);
 *
 * @package TrueAdmin 5
 * @author Daniel Baldwin, Ibrahim Diallo
 */
class CSSMini
{
	static public function process($css)
	{
      $css = self::remove_spaces($css);
      $css = self::remove_css_comments($css);
		return $css;
	}  

	/**
	* Remove unnecessary spaces from a css string
	* @param String $string
	* @return String
	**/
	static private function remove_spaces($string)
	{
	  $string = preg_replace("/\s{2,}/", " ", $string);
	  $string = str_replace("\n", "", $string);
	  $string = str_replace('@CHARSET "UTF-8";', "", $string);
	  $string = str_replace(', ', ",", $string);
	  return $string;
	}

	/**
	* Remove all comments from css string
	* @param String $css
	* @return String
	**/
	static private function remove_css_comments($css)
	{
	  $file = preg_replace("/(\/\*[\w\'\s\r\n\*\+\,\"\-\.]*\*\/)/", "", $css);
	  return $file;
	}
}