<?
namespace True;
/**
 * DataCleaner is a class with methods for cleaning all kinds of data that a website might encounter.
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.0 
 */
class DataCleaner
{
	function streetAddress($str)
	{
		return preg_replace("/[^A-Za-z0-9\.\-\,\#\ \;\:\'\°\/]/", '', $str);
	}
	
	function intOnly($str)
	{
		#return filter_var($str,FILTER_VALIDATE_INT);
		return preg_replace("/[^0-9]/", '', $str);
	}
	
	function alphaOnly($str)
	{
		return preg_replace("/[^a-zA-Z]/", '', $str);
	}
	
	function alphaInt($str)
	{
		return preg_replace("/[^a-zA-Z0-9]/", '', $str);
	}
	
	function name($str)
	{
		return preg_replace("/[^a-zA-Z0-9\ \.\-\&\/\(\)\,\']/", '', $str); 
	}
	
	function decimal($str)
	{
		return preg_replace("/[^0-9\.\-]/", '', $str);
	}
	
	function filePath($str)
	{
		return preg_replace("/[^a-zA-Z0-9\-]/", '', $str);
	}
	
	function dbField($str)
	{
		return preg_replace("/[^a-zA-Z0-9\-\_\ ]/", '', $str);
	}
	
	function creditCard($str)
	{
		return preg_replace("/[^0-9]/", '', $str);
	}

	function postalCode($str)
	{
		return preg_replace("/[^a-zA-Z0-9\-\ ]/", '', $str);
	}
	
	function addDashes($CC_Num, $CC_Type)
	{ 
		switch($CC_Type)
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
	
	function email($str)
	{
		return filter_var($str,FILTER_SANITIZE_EMAIL);
	}
	
	function url($str)
	{
		return filter_var($str,FILTER_SANITIZE_URL);
	}
	
	function ip($str)
	{
		return filter_var($str,FILTER_VALIDATE_IP);
	}

	function float($str)
	{
		return filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}
	
	/**
	 * formats a phone number
	 *
	 * @param String $ph - the phone number to be formatted
	 * @param int $type - the type of formatting be done. Type:1 555-555-5555|1-555-555-5555 ; Type:2 (555) 555-5555|1 (555) 555-5555
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	function phoneFormat($ph, $type=1) 
	{
		$onlynums = preg_replace('/[^0-9]/','',$ph);

		if(strlen($onlynums)==10) 
		{
			$areacode = substr($onlynums, 0,3);
			$exch = substr($onlynums,3,3);
			$num = substr($onlynums,6,4);
			
		}
		elseif(strlen($onlynums)==11) 
		{
			$countryCode = substr($onlynums, 0,1);
			$areacode = substr($onlynums, 1,3);
			$exch = substr($onlynums,4,3);
			$num = substr($onlynums,7,4);
			
		}
		else
		{
			return $ph;
		}
		
		
		switch($type)
		{
			case 1:
			if(strlen($onlynums)==10)
			{
				return "$areacode-$exch-$num";
			}
			elseif(strlen($onlynums)==11) 
			{
				return "$countryCode-$areacode-$exch-$num";
			}
			break;

			case 2:
			if(strlen($onlynums)==10)
			{
				return "($areacode) $exch-$num";
			}
			elseif(strlen($onlynums)==11) 
			{
				return "$countryCode ($areacode) $exch-$num";
			}
			break;
		}
		
	}

	function titleCase($string) 
	{
		$word_splitters = array(' ', '-', "O'", "L'", "D'", 'St.', 'Mc');
		$lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'");
		$uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX');
	 
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

	function splitName($name)
	{
	    $name = trim($name);
	    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
	    $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
	    return array($first_name, $last_name);
	}
	
	public function convertNum($num) 
	{
		// returns the number as an anglicized string
		$num = (int) $num;    // make sure it's an integer
		if ($num < 0) return "negative".convertTri(-$num, 0);
		if ($num == 0) return "zero";
		return convertTri($num, 0);
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
		 return convertTri($r, $tri+1).$str;
		else
		 return $str;
	}
	
	function charset_decode_utf_8($string)
	{ 
		$string = str_replace("\n\r", "\n", $string);
		$string = str_replace("\r", "\n", $string);
	
		# Only do the slow convert if there are 8-bit characters 
	   # avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that
	    if(! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string)) 
	        return $string; 

		# decode three byte unicode characters 
		$string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",        
		"'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'", $string); 

		# decode two byte unicode characters 
		$string = preg_replace("/([\300-\337])([\200-\277])/e", 
		"'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'", $string); 

		return $string; 
	}
	
	function forMetaTags($str)
	{
		$find = array('"',"'"," & ");
		$replace = array('','',' &amp; ');
		$str = trim(strip_tags(str_replace($find,$replace,$str)));
		# remove extra white space
		return preg_replace(array('/\s{2,}/', '/[\t\n\r]/'), ' ', $str);
	}
	
	public function encodeQuotes($str)
	{
		$searchChars[] = "'";
		$searchChars[] = '"';
		$replaceChars[] = '&#x27;';
		$replaceChars[] = '&#34;';
		return str_replace($searchChars,$replaceChars,$str);
	}
	
	function forHtmlEditors($str)
	{
		return str_replace("&",'&amp;',$str);
	}
	
	function htmlOutput($str)
	{
		$find = array(" & ");
		$replace = array(' &amp; ');
		return str_replace($find,$replace,$str);
	}
	
	function sanitize($str, $santype = 1)
	{
		if ($santype == 1) return strip_tags($str);
		if ($santype == 2) return htmlentities(strip_tags($str),ENT_QUOTES,'UTF-8');
		if ($santype == 3)
		{
			if(!get_magic_quotes_gpc()) return addslashes(htmlentities(strip_tags($str),ENT_QUOTES,'UTF-8'));
			else return htmlentities(strip_tags($str),ENT_QUOTES,'UTF-8');
		}
	}
	
	function escape($str)
	{
		return mysql_real_escape_string($str);
	}
	
	function xss($str)
	{
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = self::xss_clean($str[$key]);
			}

			return $str;
		}
		
		# Protect GET variables in URLs
		$str = preg_replace('|\&([a-z\_0-9]+)\=([a-z\_0-9]+)|i', self::xss_hash()."\\1=\\2", $str);
		
		/*
		* Validate standard character entities
		*
		* Add a semicolon if missing.  We do this to enable
		* the conversion of entities to ASCII later.
		*
		*/
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

		/*
		* Validate UTF16 two byte encoding (x00) 
		*
		* Just as above, adds a semicolon if missing.
		*
		*/
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

		/*
		* Un-Protect GET variables in URLs
		*/
		$str = str_replace(self::xss_hash(), '&', $str);
		
		/*
		* URL Decode
		*
		* Just in case stuff like this is submitted:
		*
		* <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		*
		* Note: Use rawurldecode() so it does not remove plus signs
		*
		*/
		$str = rawurldecode($str);

		/*
		* Convert character entities to ASCII 
		*
		* This permits our tests below to work reliably.
		* We only convert entities that are within tags since
		* these are the ones that will pose security problems.
		*
		*/

		#$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);

		#$str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_html_entity_decode_callback'), $str);

		/*
		* Remove Invisible Characters Again!
		*/
		static $non_displayables;

		if ( ! isset($non_displayables))
		{
			// every control character except newline (dec 10), carriage return (dec 13), and horizontal tab (dec 09),
			$non_displayables = array(
										'/%0[0-8bcef]/',			// url encoded 00-08, 11, 12, 14, 15
										'/%1[0-9a-f]/',				// url encoded 16-31
										'/[\x00-\x08]/',			// 00-08
										'/\x0b/', '/\x0c/',			// 11, 12
										'/[\x0e-\x1f]/'				// 14-31
									);
		}

		do
		{
			$cleaned = $str;
			$str = preg_replace($non_displayables, '', $str);
		}
		while ($cleaned != $str);

		/*
		* Convert all tabs to spaces
		*
		* This prevents strings like this: ja	vascript
		* NOTE: we deal with spaces between characters later.
		* NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
		* so we use str_replace.
		*
		*/

 		if (strpos($str, "\t") !== FALSE)
		{
			$str = str_replace("\t", ' ', $str);
		}

		/*
		* Capture converted string for later comparison
		*/
		$converted_string = $str;

		/*
		* Not Allowed Under Any Conditions
		*/
		
		$never_allowed_str = array('<'=>'','>'=>'','\\'=>'','"'=>'&quot;');

		foreach ($never_allowed_str as $key => $val)
		{
			$str = str_replace($key, $val, $str);   
		}

		/*foreach ($never_allowed_regex as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);   
		}*/

		/*
		* Makes PHP tags safe
		*
		*  Note: XML tags are inadvertently replaced too:
		*
		*	<?xml
		*
		* But it doesn't seem to pose a problem.
		*
		*/
		if ($is_image === TRUE)
		{
			// Images have a tendency to have the PHP short opening and closing tags every so often
			// so we skip those and only do the long opening tags.
			$str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
		}
		else
		{
			$str = str_replace(array('<?', '?'.'>'),  array('&lt;?', '?&gt;'), $str);
		}

		/*
		* Compact any exploded words
		*
		* This corrects words like:  j a v a s c r i p t
		* These words are compacted back to their correct state.
		*
		*/
		$words = array('javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
		foreach ($words as $word)
		{
			$temp = '';

			for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
			{
				$temp .= substr($word, $i, 1)."\s*";
			}

			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}

		/*
		* Remove disallowed Javascript in links or img tags
		* We used to do some version comparisons and use of stripos for PHP5, but it is dog slow compared
		* to these simplified non-capturing preg_match(), especially if the pattern exists in the string
		*/
		do
		{
			$original = $str;

			if (preg_match("/<a/i", $str))
			{
				$str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array($this, '_js_link_removal'), $str);
			}

			if (preg_match("/<img/i", $str))
			{
				$str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array($this, '_js_img_removal'), $str);
			}

			if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str))
			{
				$str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
			}
		}
		while($original != $str);

		unset($original);

		/*
		* Remove JavaScript Event Handlers
		*
		* Note: This code is a little blunt.  It removes
		* the event handler and anything up to the closing >,
		* but it's unlikely to be a problem.
		*
		*/
		$event_handlers = array('[^a-z_\-]on\w*','xmlns');

		if ($is_image === TRUE)
		{
			/*
			* Adobe Photoshop puts XML metadata into JFIF images, including namespacing, 
			* so we have to allow this for images. -Paul
			*/
			unset($event_handlers[array_search('xmlns', $event_handlers)]);
		}

		$str = preg_replace("#<([^><]+?)(".implode('|', $event_handlers).")(\s*=\s*[^><]*)([><]*)#i", "<\\1\\4", $str);

		/*
		* Sanitize naughty HTML elements
		*
		* If a tag containing any of the words in the list
		* below is found, the tag gets converted to entities.
		*
		* So this: <blink>
		* Becomes: &lt;blink&gt;
		*
		*/
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

		/*
		* Sanitize naughty scripting elements
		*
		* Similar to above, only instead of looking for
		* tags it looks for PHP and JavaScript commands
		* that are disallowed.  Rather than removing the
		* code, it simply converts the parenthesis to entities
		* rendering the code un-executable.
		*
		* For example:	eval('some code')
		* Becomes:		eval&#40;'some code'&#41;
		*
		*/
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);


		return $str;
	}
	
	function xss_hash()
	{
		if (phpversion() >= 4.2)
			mt_srand();
		else
			mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);

		$xss_hash = md5(time() + mt_rand(0, 1999999999));

		return $xss_hash;
	}
	
	/** 
	* Sanitize Naughty HTML 
	* 
	* Callback function for xss_clean() to remove naughty HTML elements 
	* 
	* @access    private 
	* @param    array 
	* @return    string 
	*/ 
	function _sanitize_naughty_html($matches) 
	{ 
	    // encode opening brace 
	    $str = '&lt;'.$matches[1].$matches[2].$matches[3]; 

	    // encode captured opening or closing brace to prevent recursive vectors 
	    $str .= str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]); 

	    return $str; 
	}
	
	/** 
	* JS Link Removal 
	* 
	* Callback function for xss_clean() to sanitize links 
	* This limits the PCRE backtracks, making it more performance friendly 
	* and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in 
	* PHP 5.2+ on link-heavy strings 
	* 
	* @access    private 
	* @param    array 
	* @return    string 
	*/ 
	function _js_link_removal($match) 
	{ 
	    $attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1])); 
	    return str_replace($match[1], preg_replace("#href=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]); 
	} 

	/** 
	* JS Image Removal 
	* 
	* Callback function for xss_clean() to sanitize image tags 
	* This limits the PCRE backtracks, making it more performance friendly 
	* and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in 
	* PHP 5.2+ on image tag heavy strings 
	* 
	* @access    private 
	* @param    array 
	* @return    string 
	*/ 
	function _js_img_removal($match) 
	{ 
	    $attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1])); 
	    return str_replace($match[1], preg_replace("#src=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]); 
	}
	
	/** 
	* Compact Exploded Words 
	* 
	* Callback function for xss_clean() to remove whitespace from 
	* things like j a v a s c r i p t 
	* 
	* @access    public 
	* @param    type 
	* @return    type 
	*/ 
	function _compact_exploded_words($matches) 
	{ 
	    return preg_replace('/\s+/s', '', $matches[1]).$matches[2]; 
	}
}