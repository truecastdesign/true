<?php
namespace True;
/**
 * DataCleaner is a class with methods for cleaning all kinds of data that a website might encounter.
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.4.2
 */
class DataCleaner
{
	public static function streetAddress($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^A-Za-z0-9\.\-\,\#\ \;\:\'\°\/]/", '', $str);
	}
	
	# Deprecated
	public static function intOnly($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^0-9]/", '', $str);
	}

	public static function int($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^0-9]/", '', $str);
	}
	
	# Deprecated
	public static function alphaOnly($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^a-zA-Z]/", '', $str);
	}

	public static function alpha($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^a-zA-Z]/", '', $str);
	}
	
	public static function alphaInt($str)
	{
		if (is_null($str))
			return '';

		return preg_replace("/[^a-zA-Z0-9]/", '', $str);
	}
	
	public static function name($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^a-zA-Z0-9\ \.\-\&\/\(\)\,\']/", '', $str); 
	}
	
	public static function decimal($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^0-9\.\-]/", '', $str);
	}
	
	public static function filePath($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^a-zA-Z0-9\-]/", '', $str);
	}
	
	public static function dbField($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^a-zA-Z0-9\-\_\ ]/", '', $str);
	}
	
	public static function creditCard($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^0-9]/", '', $str);
	}

	public static function postalCode($str)
	{
		if (is_null($str))
			return '';
		
		return preg_replace("/[^a-zA-Z0-9\-\ ]/", '', $str);
	}
	
	public static function addDashes($CC_Num, $CC_Type)
	{ 
		$NewCC = '';

		switch ($CC_Type)
		{
			case 'AMEX':
				$NewCC .= substr($CC_Num, 0, 4)."-";
				$NewCC .= substr($CC_Num, 4, 6)."-";
				$NewCC .= substr($CC_Num, 10, 5);
			break;
			case 'Visa' OR 'Discover' OR 'Mastercard':
				for($i=0; $i<4; $i++) $NewCC .= substr($CC_Num, ($i*4), 4)."-";
				$NewCC = substr($NewCC, 0, 19);
			break;
			
		}
		return $NewCC; 
	}
	
	public static function email($str)
	{
		return filter_var($str,FILTER_SANITIZE_EMAIL);
	}
	
	public static function url($str)
	{
		return filter_var($str,FILTER_SANITIZE_URL);
	}

	# remove urls from string
	public static function filterOutURLs($str) 
	{
		if (is_null($str))
			return '';
		
		return preg_replace('/\b((https?|ftp|file|http):\/\/|www\.)[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', ' ', $str);
	}

	public static function ip($str)
	{
		return filter_var($str,FILTER_VALIDATE_IP);
	}

	public static function float($str)
	{
		return filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}
	
	/**
	 * formats a phone number
	 *
	 * @param String $ph - the phone number to be formatted
	 * @param int $type - the type of formatting be done. 
	 * Type:1 555-555-5555|1-555-555-5555 ; 
	 * Type:2 (555) 555-5555|1 (555) 555-5555
	 * Type:3  E.164 format +15555555555
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public static function phoneFormat($ph, $type=1, $noCountryCode=false) 
	{
		if (strstr($ph, 'x'))
			return $ph;

		$onlynums = preg_replace('/[^0-9]/', '', $ph);

		if (strlen($onlynums) == 10) {
			$areacode = substr($onlynums, 0, 3);
			$exch = substr($onlynums, 3, 3);
			$num = substr($onlynums, 6, 4);
		} elseif (strlen($onlynums) == 11) {
			$countryCode = substr($onlynums, 0, 1);
			$areacode = substr($onlynums, 1, 3);
			$exch = substr($onlynums, 4, 3);
			$num = substr($onlynums, 7, 4);
		} else {
			return $ph;
		}
		
		switch($type)
		{
			case 1:
			if (strlen($onlynums)==10) {
				return "$areacode-$exch-$num";
			}
			elseif (strlen($onlynums)==11) {
				if ($noCountryCode) {
					return "$areacode-$exch-$num";
				} else {
					return "$countryCode-$areacode-$exch-$num";
				}				
			}
			break;

			case 2:
				if (strlen($onlynums)==10) {
					return "($areacode) $exch-$num";
				} elseif (strlen($onlynums)==11) {
					if ($noCountryCode) {
						return "($areacode) $exch-$num";
					} else {
						return "$countryCode ($areacode) $exch-$num";
					}
				}
			break;

			case 3:
				// Check if the number starts with '00' or '+', indicating an international format
				if (strpos($ph, '+') === 0 || strpos($ph, '00') === 0) {
					// Assume the number includes the country code
					return '+' . ltrim($onlynums, '0');
				} elseif (strpos($ph, '1') === 0) {
				    return '+'.$onlynums;
				} else {
					// Prepend the default country code (e.g., '1' for US)
					return '+1'.$onlynums;
				}
			break;
		}		
	}

	public static function currency($str)
	{
		if (is_null($str))
			return '';
		
		return '$'.number_format($str, 2, '.', ',');
	}

	public static function titleCase($string) 
	{
		$word_splitters = array(' ', '-', "O'", "L'", "D'", 'St.', 'Mc', 'Mac');
		$lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'");
		$uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'P.O.');
	 
		$string = strtolower($string);
		foreach ($word_splitters as $delimiter)
		{ 
			$words = explode($delimiter, $string); 
			$newwords = array(); 
			foreach ($words as $word)
			{ 
				if (in_array(strtoupper($word), $uppercase_exceptions))
					$word = strtoupper($word);
				else
				if (!in_array($word, $lowercase_exceptions))
					$word = ucfirst($word); 
	 
				$newwords[] = $word;
			}
	 
			if (in_array(strtolower($delimiter), $lowercase_exceptions))
				$delimiter = strtolower($delimiter);
	 
			$string = join($delimiter, $newwords); 
		} 
		return $string; 
	}

	public static function postalCodeFormat($string, $country='US')
	{
		switch ($country) {
			case 'US':
				$string = preg_replace('/[^0-9]/','',$string);
				if (strlen($string) > 5) {
					
					$first5 = substr($string, 0,5);
					$last4 = substr($string,4,4);
					$string = $first5.'-'.$last4;
				}
				return $string;
			break;
			case 'CA':
				$string = preg_replace('/[^0-9a-zA-Z]/','',$string); 
				$first3 = substr($string, 0,3);
				$last3 = substr($string,3,3);
				$string = strtoupper($first3).' '.strtoupper($last3);
				return $string;
			break;
			case 'AU':
				$string = preg_replace('/[^0-9]/','',$string);
				return $string;
			break;
			default:
				return $string;
		}
	}

	public static function splitName($name)
	{
	    $name = trim($name);
	    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
	    $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
	    return array($first_name, $last_name);
	}
	
	public static function convertNum($num) 
	{
		// returns the number as an anglicized string
		$num = (int) $num;    // make sure it's an integer
		if ($num < 0) return "negative".self::convertTri(-$num, 0);
		if ($num == 0) return "zero";
		return self::convertTri($num, 0);
	}
	
	# used by convertNum
	private function convertTri($num, $tri) 
	{
		// recursive fn, converts three digits per pass
		
		$ones = array( "", " one", " two", " three", " four", " five", " six", " seven", " eight", " nine", " ten", " eleven", " twelve", " thirteen", " fourteen", " fifteen", " sixteen", " seventeen", " eighteen", " nineteen" );

		$tens = array( "", "", " twenty", " thirty", " forty", " fifty", " sixty", " seventy", " eighty", " ninety" );

		$triplets = array( "", " thousand", " million", " billion", " trillion", " quadrillion", " quintillion", " sextillion", " septillion", " octillion", " nonillion" );

		// chunk the number, ...rxyy
		$r = (int) ($num / 1000);
		$x = ($num / 100) % 10;
		$y = $num % 100;

		// init the output string
		$str = "";

		// do hundreds
		if ($x > 0)
		 $str = $ones[$x] . " hundred";

		// do ones and tens
		if ($y < 20)
		 $str .= $ones[$y];
		else
		 $str .= $tens[(int) ($y / 10)] . $ones[$y % 10];

		// add triplet modifier only if there
		// is some output to be modified...
		if ($str != "")
		 $str .= $triplets[$tri];

		// continue recursing?
		if ($r > 0)
		 return self::convertTri($r, $tri+1).$str;
		else
		 return $str;
	}
	
	public static function charset_decode_utf_8($string)
	{ 
		$string = str_replace("\n\r", "\n", $string);
		$string = str_replace("\r", "\n", $string);
	
		# Only do the slow convert if there are 8-bit characters 
	   # avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that
	    if(! preg_match("/[\200-\237]/", $string) and ! preg_match("/[\241-\377]/", $string)) 
	        return $string; 

		# decode three byte unicode characters 
		$string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",        
		"'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'", $string); 

		# decode two byte unicode characters 
		$string = preg_replace("/([\300-\337])([\200-\277])/e", 
		"'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'", $string); 

		return $string; 
	}
	
	public static function forMetaTags($str)
	{
		$find = array('"',"'"," & ");
		$replace = array('','',' &amp; ');
		$str = trim(strip_tags(str_replace($find,$replace,$str)));
		# remove extra white space
		return preg_replace(array('/\s{2,}/', '/[\t\n\r]/'), ' ', $str);
	}
	
	public static function encodeQuotes($str)
	{
		$searchChars[] = "'";
		$searchChars[] = '"';
		$replaceChars[] = '&#x27;';
		$replaceChars[] = '&#34;';
		return str_replace($searchChars,$replaceChars,$str);
	}
	
	public static function forHtmlEditors($str)
	{
		return str_replace("&",'&amp;',$str);
	}
	
	public static function htmlOutput($str)
	{
		$find = array(" & ");
		$replace = array(' &amp; ');
		return str_replace($find,$replace,$str);
	}
	
	public static function sanitize($str, $santype = 1)
	{
		if ($santype == 1) return strip_tags($str);
		if ($santype == 2) return htmlentities(strip_tags($str),ENT_QUOTES,'UTF-8');
		if ($santype == 3)
		{
			return htmlentities(strip_tags($str),ENT_QUOTES,'UTF-8');
		}
	}
	
	public static function escape($str)
	{
		return htmlspecialchars($str);
	}
}