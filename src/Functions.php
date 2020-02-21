<?php
namespace True;

/**
 * summary
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.6.2
 */
class Functions
{
	

	/**
		* Encrypt a string
		*
		* @param $str plain text string
		* @param $key private key
		* @return encrypted string
		* @author Daniel Baldwin
		**/
	public static function encrypt($str, $key)
	{
		$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext_raw = openssl_encrypt($str, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
		return base64_encode( $iv.$hmac.$ciphertext_raw );
	}

	/**
		* Decrypt a string
		*
		* @param $str encrypted text string
		* @param $key private key
		* @return plain text string
		* @author Daniel Baldwin
		**/
	public static function decrypt($str, $key)
	{
		$c = base64_decode($str);
		$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len=32);
		$ciphertext_raw = substr($c, $ivlen+$sha2len);
		$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
		$calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
		if (hash_equals($hmac, $calcmac))//PHP 5.6+ timing attack safe comparison
		{
			return $original_plaintext;
		}
	}

	/**
		* Generate a encryption key or token
		*
		* @param int $length
		* @return string
		* @author Daniel Baldwin
		**/
	public static function genToken($length = 64)
	{
		return substr(bin2hex(openssl_random_pseudo_bytes($length)), 0, $length-1);
	}

	/**
		* return the full host name
		*
		* @return string https://www.domain.com
		* @author Daniel Baldwin
		**/
	public static function host()
	{
		return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
	}

	/**
	* Parse a url and return the parts
	*
	* @param string $url loginname:password@sub.site.org:29000/pear/validate.html?happy=me&sad=you#url
	* @return object
	* @author Daniel Baldwin
	**/
	public static function parseUrl($url, $extra=false)
	{
		$output = (object)['file'=>'', 'scheme'=>'', 'host'=>'', 'full_path'=>'', 'path'=>'', 'extension'=>'', 'filename'=>'', 'user'=>'', 'password'=>'', 'query'=>'', 'hash'=>''];

		$file = basename($url); 
		if (strstr($file, '.')) {
			if (strstr($file, '#')) {
				list($first, $last) = explode("#", $file);
				$file = $first;
			}
			if (strstr($file, '?')) {
				list($first, $last) = explode("?", $file);
				$file = $first;
			}

			$output->file = $file;
		}
		
		$parts = parse_url($url);
		if (array_key_exists('scheme', $parts)) {
			$output->scheme = $parts['scheme'];
		}
		if (array_key_exists('host', $parts)) {
			$output->host = $parts['host'];
		}
		if (array_key_exists('path', $parts)) {
			$output->full_path = $parts['path'];
		}
		
		if (!empty($output->full_path)) {
			$pathParts = pathinfo($output->full_path);
			if (is_array($pathParts)) {
				if (array_key_exists('dirname', $pathParts)) {
					$output->path = $pathParts['dirname'];
				}
				if (array_key_exists('extension', $pathParts)) {
					$output->extension = $pathParts['extension'];
				}
				if (array_key_exists('filename', $pathParts)) {			
					$output->filename = $pathParts['filename'];
				}
			}
		}
		
		if ($extra) {
			$output->port = $parts['port'];
			if (array_key_exists('user', $parts))
				$output->user = $parts['user'];
			if (array_key_exists('pass', $parts))
				$output->password = $parts['pass'];
			if (array_key_exists('query', $parts))
				$output->query = $parts['query'];
			if (array_key_exists('fragment', $parts))
				$output->hash = $parts['fragment'];
		}
	
		return $output;
	}

	/**
		* Get the browser name and version
		*
		* @return array [name=>'', version=>'']
		* @author Daniel Baldwin
		**/
	function getBrowser()
	{
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version= "";

		//First get the platform?
		if (preg_match('/linux|android/i', $u_agent)) {
			$platform = 'linux';
		}
		elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		}
		elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}

		// Next get the name of the useragent yes seperately and for good reason
		if (preg_match('/android/i', $u_agent)) {
			$ub = "Android";
		}
		elseif(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
		{
			$ub = "MSIE";
		}
		elseif(preg_match('/Trident/i',$u_agent))
		{
			$ub = "MSIE";
			$version = '11.0';
		}
		elseif(preg_match('/Windows NT 10/i',$u_agent) && preg_match('/Edge/i',$u_agent)){
			$ub = "Edge";
		}
		elseif(preg_match('/Firefox/i',$u_agent))
		{
			$ub = "Firefox";
		}
		elseif(preg_match('/Chrome/i',$u_agent))
		{
			$ub = "Chrome";
		}
		elseif(preg_match('/Safari/i',$u_agent))
		{
			$ub = "Safari";
		}
		elseif(preg_match('/Opera/i',$u_agent))
		{
			$ub = "Opera";
		}
		elseif(preg_match('/Netscape/i',$u_agent))
		{
			$ub = "Netscape";
		}

		// finally get the correct version number
		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) .
			')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}

		// see how many we have
		if($version == '') {
			$i = count($matches['browser']);
			if ($i != 1) {
				 //we will have two since we are not using 'other' argument yet
				 //see if version is before or after the name
				 if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
						$version= $matches['version'][0];
				 }
				 else {
						$version= $matches['version'][1];
				 }
			}
			else {
				 $version= $matches['version'][0];
			}
		}

		// check if we have a number
		if ($version==null || $version=="") {$version="?";}

		return array(
			'name'      => $ub,
			'version'   => $version
		);
	}

	/**
		* Check if the given browser supports CSS Grid
		*
		* @param array $params ['name'=>'', 'version'=>'']
		* @return bool true it does
		* @author Daniel Baldwin
		**/
	function supportsGrid(array $params)
	{
		switch($params['name']) {
			case 'Safari':
				 if(version_compare($params['version'], '10.1', '>=')) 
						return true;
			break;
			case 'Chrome':
				 if(version_compare($params['version'], '57', '>=')) 
						return true;
			break;
			case 'Edge':
				 if(version_compare($params['version'], '16', '>=')) 
						return true;
			break;
			case 'Firefox':
				 if(version_compare($params['version'], '52', '>=')) 
						return true;
			break;
			case 'Opera':
				 if(version_compare($params['version'], '44', '>=')) 
						return true;
			break;
			
		}
		return false;
	}

	/**
		* Transforms txt in html
		*
		* @param string $txt 
		* @return string html
		* @author Daniel Baldwin
		*/
	public static function txt2html($txt)
	{
		# Do a basic trim
		$txt = trim($txt);

		# Kills double spaces and spaces inside tags.
		while( !( strpos($txt,'  ') === FALSE ) ) $txt = str_replace('  ',' ',$txt);
		$txt = str_replace(' >','>',$txt);
		$txt = str_replace('< ','<',$txt);

		# Transforms accents in html entities.
		$txt = htmlentities($txt);

		# We need some HTML entities back!
		$txt = str_replace('&quot;','"',$txt);
		$txt = str_replace('&lt;','<',$txt);
		$txt = str_replace('&gt;','>',$txt);
		#$txt = str_replace('&amp;','&',$txt);

		$eol = "\n";

		# Basic change of line endings
		if(strpos($txt,"\r\n")) $txt = str_replace("\r\n", $eol, $txt);
		elseif(strpos($txt,"\r")) $txt = str_replace("\r", $eol, $txt);

		# Create lists from lines starting with # and
		$lines = explode("\n", $txt);

		$foundList = false;
		$foundListType = null;
		$txt = '';

		# loop through each line
		foreach($lines as $line)
		{
			if(preg_match("/^([#*]+) (.*)?$/s", $line, $match))
			{
				$parts = explode("\n", $match[2]); # break list item on line ending
				$content = $parts[0]; # get the text before the line ending

				if($foundList == false)
				{
					if($match[1] == '*')
					{
						$txt .= "<ul>\r";
						$foundListType = 'ul';	
					}
					elseif($match[1] == '#')
					{
						$txt .= "<ol>\r";
						$foundListType = 'ol';
					}
				}
				$foundList = true;
				$txt .= "\t<li>".$content."</li>\r";
			}
			else
			{
				if($foundList == true)
				{
					if($foundListType == 'ul')
						$txt .= "</ul>\n";
					elseif($foundListType == 'ol')
						$txt .= "</ol>\n";

					$foundList = false;
					$foundListType = null;
				}

				$txt .= $line."\n";
			}
		}

		//Adjusts links - anything starting with HTTP opens in a new window
		$txt = self::stri_replace("<a href=\"http://","<a target=\"_blank\" href=\"http://",$txt);
		$txt = self::stri_replace("<a href=http://","<a target=\"_blank\" href=http://",$txt);

		

		$html = '<p>'.str_replace($eol.$eol,"</p><p>",$txt).'</p>';
		$html = str_replace($eol,"<br>",$html);
		$html = str_replace("<p></p>","<p>&nbsp;</p>",$html);

		//Wipes after block tags (for when the user includes some html in the text).
		$tags = Array("table","tr","td","blockquote","ul","ol","li","h1","h2","h3","h4","h5","h6","div","p");

		foreach($tags as $tag)
		{
		  $html = self::stri_replace("<p><$tag>","<$tag>",$html);
		  $html = self::stri_replace("</$tag></p>","</$tag>",$html);
		}

		if(strpos($html,"\r")) 
			$html = str_replace("\r", $eol, $html);

		return $html;
	}

	/**
	 * A lightweight convert text with two line breaks to text with <p> tags wrapped around each paragraph. Leaves any existing html in the text alone.
	 *
	 * @param string $string
	 * @return string
	 */
	public function addPtags(string $string): string
	{
		return "<p>" . implode( "</p>\n\n<p>", preg_split( '/(?:\s*\n)+/', $string ) ) . "</p>";
	}

	/**
	 * Convert text with two line breaks to text with <p> tags wrapped around each paragraph. Leaves any existing html in the text alone.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function addPtagsExtra(string $string): string
	{
		#return "<p>" . implode( "</p>\n\n<p>", preg_split( '/(?:\s*\n)+/', $string ) ) . "</p>";

		$pre_tags = array();
		$br = false;

		$pee = $string;

		if ( trim( $pee ) === '' ) {
			return '';
		}

		// Just to make things a little easier, pad the end.
		$pee = $pee . "\n";

		/*
		* Pre tags shouldn't be touched by autop.
		* Replace pre tags with placeholders and bring them back after autop.
		*/
		if ( strpos( $pee, '<pre' ) !== false ) {
			$pee_parts = explode( '</pre>', $pee );
			$last_pee  = array_pop( $pee_parts );
			$pee       = '';
			$i         = 0;

			foreach ( $pee_parts as $pee_part ) {
				$start = strpos( $pee_part, '<pre' );

				// Malformed html?
				if ( $start === false ) {
					$pee .= $pee_part;
					continue;
				}

				$name              = "<pre wp-pre-tag-$i></pre>";
				$pre_tags[ $name ] = substr( $pee_part, $start ) . '</pre>';

				$pee .= substr( $pee_part, 0, $start ) . $name;
				$i++;
			}

			$pee .= $last_pee;
		}
		// Change multiple <br>s into two line breaks, which will turn into paragraphs.
		$pee = preg_replace( '|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee );

		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

		// Add a double line break above block-level opening tags.
		$pee = preg_replace( '!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee );

		// Add a double line break below block-level closing tags.
		$pee = preg_replace( '!(</' . $allblocks . '>)!', "$1\n\n", $pee );

		// Standardize newline characters to "\n".
		$pee = str_replace( array( "\r\n", "\r" ), "\n", $pee );

		// Find newlines in all elements and add placeholders.
		$pee = self::replaceInHtmlTags( $pee, array( "\n" => ' <!-- wpnl --> ' ) );

		// Collapse line breaks before and after <option> elements so they don't get autop'd.
		if ( strpos( $pee, '<option' ) !== false ) {
			$pee = preg_replace( '|\s*<option|', '<option', $pee );
			$pee = preg_replace( '|</option>\s*|', '</option>', $pee );
		}

		/*
		* Collapse line breaks inside <object> elements, before <param> and <embed> elements
		* so they don't get autop'd.
		*/
		if ( strpos( $pee, '</object>' ) !== false ) {
			$pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
			$pee = preg_replace( '|\s*</object>|', '</object>', $pee );
			$pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
		}

		/*
		* Collapse line breaks inside <audio> and <video> elements,
		* before and after <source> and <track> elements.
		*/
		if ( strpos( $pee, '<source' ) !== false || strpos( $pee, '<track' ) !== false ) {
			$pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
			$pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
			$pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
		}

		// Collapse line breaks before and after <figcaption> elements.
		if ( strpos( $pee, '<figcaption' ) !== false ) {
			$pee = preg_replace( '|\s*(<figcaption[^>]*>)|', '$1', $pee );
			$pee = preg_replace( '|</figcaption>\s*|', '</figcaption>', $pee );
		}

		// Remove more than two contiguous line breaks.
		$pee = preg_replace( "/\n\n+/", "\n\n", $pee );

		// Split up the contents into an array of strings, separated by double line breaks.
		$pees = preg_split( '/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY );

		// Reset $pee prior to rebuilding.
		$pee = '';

		// Rebuild the content as a string, wrapping every bit with a <p>.
		foreach ( $pees as $tinkle ) {
			$pee .= '<p>' . trim( $tinkle, "\n" ) . "</p>\n";
		}

		// Under certain strange conditions it could create a P of entirely whitespace.
		$pee = preg_replace( '|<p>\s*</p>|', '', $pee );

		// Add a closing <p> inside <div>, <address>, or <form> tag if missing.
		$pee = preg_replace( '!<p>([^<]+)</(div|address|form)>!', '<p>$1</p></$2>', $pee );

		// If an opening or closing block element tag is wrapped in a <p>, unwrap it.
		$pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee );

		// In some cases <li> may get wrapped in <p>, fix them.
		$pee = preg_replace( '|<p>(<li.+?)</p>|', '$1', $pee );

		// If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
		$pee = preg_replace( '|<p><blockquote([^>]*)>|i', '<blockquote$1><p>', $pee );
		$pee = str_replace( '</blockquote></p>', '</p></blockquote>', $pee );

		// If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
		$pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)!', '$1', $pee );

		// If an opening or closing block element tag is followed by a closing <p> tag, remove it.
		$pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee );

		// Optionally insert line breaks.
		if ( $br ) {
			// Replace newlines that shouldn't be touched with a placeholder.
			$pee = preg_replace_callback( '/<(script|style).*?<\/\\1>/s', '_autop_newline_preservation_helper', $pee );

			// Normalize <br>
			$pee = str_replace( array( '<br>', '<br/>' ), '<br />', $pee );

			// Replace any new line characters that aren't preceded by a <br /> with a <br />.
			$pee = preg_replace( '|(?<!<br />)\s*\n|', "<br />\n", $pee );

			// Replace newline placeholders with newlines.
			$pee = str_replace( '<WPPreserveNewline />', "\n", $pee );
		}

		// If a <br /> tag is after an opening or closing block tag, remove it.
		$pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $pee );

		// If a <br /> tag is before a subset of opening or closing block tags, remove it.
		$pee = preg_replace( '!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee );
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );

		// Replace placeholder <pre> tags with their original content.
		if ( ! empty( $pre_tags ) ) {
			$pee = str_replace( array_keys( $pre_tags ), array_values( $pre_tags ), $pee );
		}

		// Restore newlines in all elements.
		if ( false !== strpos( $pee, '<!-- wpnl -->' ) ) {
			$pee = str_replace( array( ' <!-- wpnl --> ', '<!-- wpnl -->' ), "\n", $pee );
		}

		return $pee;
	}

	/**
	 * Replace characters or phrases within HTML elements only.
	 *
	 * @since 4.2.3
	 *
	 * @param string $haystack The text which has to be formatted.
	 * @param array $replace_pairs In the form array('from' => 'to', ...).
	 * @return string The formatted text.
	 */
	public static function replaceInHtmlTags( $haystack, $replace_pairs ) {
		// Find all elements.
		$textarr = self::htmlSplit( $haystack );
		$changed = false;

		// Optimize when searching for one item.
		if ( 1 === count( $replace_pairs ) ) {
			// Extract $needle and $replace.
			foreach ( $replace_pairs as $needle => $replace ) {
			}

			// Loop through delimiters (elements) only.
			for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
				if ( false !== strpos( $textarr[ $i ], $needle ) ) {
					$textarr[ $i ] = str_replace( $needle, $replace, $textarr[ $i ] );
					$changed       = true;
				}
			}
		} else {
			// Extract all $needles.
			$needles = array_keys( $replace_pairs );

			// Loop through delimiters (elements) only.
			for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
				foreach ( $needles as $needle ) {
					if ( false !== strpos( $textarr[ $i ], $needle ) ) {
						$textarr[ $i ] = strtr( $textarr[ $i ], $replace_pairs );
						$changed       = true;
						// After one strtr() break out of the foreach loop and look at next element.
						break;
					}
				}
			}
		}

		if ( $changed ) {
			$haystack = implode( $textarr );
		}

		return $haystack;
	}

	/**
	 * Separate HTML elements and comments from the text.
	 *
	 * @since 4.2.4
	 *
	 * @param string $input The text which has to be formatted.
	 * @return array The formatted text.
	 */
	public static function htmlSplit( $input ) {
		$comments =
			'!'             // Start of comment, after the <.
			. '(?:'         // Unroll the loop: Consume everything until --> is found.
			.     '-(?!->)' // Dash not followed by end of comment.
			.     '[^\-]*+' // Consume non-dashes.
			. ')*+'         // Loop possessively.
			. '(?:-->)?';   // End of comment. If not found, match all input.

		$cdata =
			'!\[CDATA\['    // Start of comment, after the <.
			. '[^\]]*+'     // Consume non-].
			. '(?:'         // Unroll the loop: Consume everything until ]]> is found.
			.     '](?!]>)' // One ] not followed by end of comment.
			.     '[^\]]*+' // Consume non-].
			. ')*+'         // Loop possessively.
			. '(?:]]>)?';   // End of comment. If not found, match all input.

		$escaped =
			'(?='             // Is the element escaped?
			.    '!--'
			. '|'
			.    '!\[CDATA\['
			. ')'
			. '(?(?=!-)'      // If yes, which type?
			.     $comments
			. '|'
			.     $cdata
			. ')';

		$regex =
			'/('                // Capture the entire match.
			.     '<'           // Find start of element.
			.     '(?'          // Conditional expression follows.
			.         $escaped  // Find end of escaped element.
			.     '|'           // ... else ...
			.         '[^>]*>?' // Find end of normal element.
			.     ')'
			. ')/';
		
		return preg_split( $regex, $input, -1, PREG_SPLIT_DELIM_CAPTURE );
	}

	public static function stri_replace($find, $replace, $string)
	{
			$parts = explode( strtolower($find), strtolower($string) );

			$pos = 0;

			foreach( $parts as $key=>$part ){
			$parts[ $key ] = substr($string, $pos, strlen($part));
			$pos += strlen($part) + strlen($find);
			}

			return( join( $replace, $parts ) );
	}

	public static function dollars($amount)
	{
		return '$'.number_format($amount,2,'.',',');
	}

	public static function lastFourDigits($str)
	{
		return str_repeat("X", (strlen($str) - 4)).substr($str,-4,4);
	}

	public static function percentDiscountPrice($price, $percentOff)
	{
		return ((100 - $percentOff)/100) * $price;
	}

	public static function contains($content, $str, $ignorecase=true)
	{
		if($ignorecase)
		{
			$str = strtolower($str);
			$content = strtolower($content);
		}  
		if($str) { return (strpos($content,$str) !== false)? true:false;}
		else return false;
	}

	/**
	 * Check if all the given keys exist in an array
	 * @param  array $keys  ['key1','key2']
	 * @param  array $array ['key1'=>1, 'key3'=>3]
	 * @return bool true if all the given keys exist in the given array, false if any are missing.
	 */
	public static function keys_exist($keys, $array)
	{
		if (0 === count(array_diff($keys, array_keys($array)))) {
			return true;
		} else {
			return false;
		}
	}

	public static function truncateStr($strString, $nLength = 15, $strTrailing = "...") 
	{
		// Take off chars for the trailing
		$nLength -= strlen($strTrailing);
		if (strlen($strString) > $nLength) return substr($strString, 0, $nLength) . $strTrailing;
		else return $strString; 
	}
	
	/**
	 * get the last part of a url or current request uri and clean it
	 *
	 * @param string|bool $str - either string with url or true to just use the current uri
	 * @return string - last path without any slashes
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public static function getLastPartOfURL($str)
	{
		if(is_bool($str)) {
			$str = $_SERVER["REQUEST_URI"];
		}

		$end = end((explode('/', rtrim($str, '/'))));
		return preg_replace("[^a-zA-Z0-9\-\_]", '', $end);
	}

	/**
	 * check if string starts with another string
	 *
	 * @param string $haystack - string to check
	 * @param string $needle - string to check with
	 * @return bool true if found
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public static function startsWith($haystack, $needle)
	{
		  $length = strlen($needle);
		  return (substr($haystack, 0, $length) === $needle);
	}

	/**
	 * check if string ends with another string
	 *
	 * @param string $haystack - string to check
	 * @param string $needle - string to check with
	 * @return bool true if found
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public static function endsWith($haystack, $needle)
	{
		 $length = strlen($needle);
		 if ($length == 0) {
			  return true;
		 }

		 return (substr($haystack, -$length) === $needle);
	}

	/**
	 * search a multi-record array for a value
	 * $records = [[id=>1, title=>"testing one to three"], [id=>2, title=>"testing three two one"]];
	 * example: findRecords($records, 'field', 'value') // value will be a partial match
	 *
	 * @param array $records
	 * @param string $field - example 'title'
	 * @param string $value - example 'three'
	 * @param string $type - 'array' for simple array, '2dim' for multi-dem array
	 * @return array - returns one or multiple records as a multi-dem array
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public static function findRecords($records, $field, $value, $type)
	{
		$result = [];
		
		# check to see if $records is an array of objects or arrays
		if (is_object(current($records))) {
			foreach ($records as $item) {
				if (stripos($item->$field, strval($value)) !== false) {
					$result[] = $item;
				}
			}
		} else {
			foreach ($records as $key => $item) {
				if (stripos($item[$field], strval($value)) !== false) {
					$result[] = (object) $item;
				}
			}
		}
		
		if ($type == 'array') {
			if (isset($result[0])) {
				return (object) $result[0];
			}			
		} elseif ($type == '2dim') {
			return $result;
		}
	}

	/**
	 * search and count matching records in array
	 * $records = [[id=>1, title=>"testing one to three"], [id=>2, title=>"testing three two one"]];
	 * example: countRecords($records, 'field', 'value') // value will be a partial match
	 *
	 * @param array $records
	 * @param string $field - example 'title'
	 * @param string $value - example 'three' 
	 * @return int count
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public static function countRecords($records, $field, $value)
	{
		$i=0;
		foreach($records as $key => $item) {
			if(stripos($item[$field], $value) !== false) {
				$i++;
			}
		}
		return $i;
	}

	/**
	 * Sort a multidimensional array by array key
	 *
	 * @param array $array - array to sort
	 * @param string $key - array key to sort by
	 * @param string $direction - the direction you want the array to be sorted in. asc for ascending and desc for descending
	 * @return array - the sorted array
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public static function arraySort($array, $key, $direction='asc') 
	{ 
		# if $array is an array of objects
		if(is_object(current($array)))
		{
			for($i=0; $i < sizeof($array); $i++) 
			{ 
				$sort_values[$i] = $array[$i]->$key; 
			} 
		}
		# if $array is an array of arrays
		else
		{
			for($i=0; $i < sizeof($array); $i++) 
			{ 
				$sort_values[$i] = $array[$i][$key]; 
			} 
		}
		
		asort($sort_values); 
		reset($sort_values); 

		while(list($arr_key, $arr_val) = each($sort_values)) 
		{ 
			$sorted_arr[] = $array[$arr_key]; 
		}
		if($direction == 'desc')
			$sorted_arr = array_reverse($sorted_arr);
		return $sorted_arr; 
	} 

	/**
	* [unique_multidim description]
	* @param  array|object $array your multidimensional array or value object
	* @param  string $key   field to test by
	* @return object or array depending on what you pass it.
	*/
	public static function unique_multidim($array, $key)
	{ 
		$temp_array = array(); 
		$i = 0; 
		$key_array = array(); 

		if (is_object(reset($array))) {
			$returnType = 'object';

			foreach ($array as $val) { 
				if (!in_array($val->$key, $key_array)) { 
					$key_array[$i] = $val->$key; 
					$temp_array[$i] = $val; 
				} 
				$i++; 
			} 
		} else {
			foreach ($array as $val) { 
				if (!in_array($val[$key], $key_array)) { 
					$key_array[$i] = $val[$key]; 
					$temp_array[$i] = $val; 
				} 
				$i++; 
			} 
		}

		if ($returnType == 'object') {
			return (object) $temp_array;
		} else {
			return $temp_array;
		}		 
	} 
}
