<?php
namespace True;

/**
 * summary
 *
 * @package True Framework
 * @author Daniel Baldwin
 * @version 1.0.1
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
        return bin2hex(openssl_random_pseudo_bytes($length));
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
     * Get the browser name and version
     *
     * @return array [name=>'', version=>'']
     * @author Daniel Baldwin - danb@truecastdesign.com
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
     * @author Daniel Baldwin - danb@truecastdesign.com
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
        //Kills double spaces and spaces inside tags.
      while( !( strpos($txt,'  ') === FALSE ) ) $txt = str_replace('  ',' ',$txt);
      $txt = str_replace(' >','>',$txt);
      $txt = str_replace('< ','<',$txt);

      //Transforms accents in html entities.
      $txt = htmlentities($txt);

      //We need some HTML entities back!
      $txt = str_replace('&quot;','"',$txt);
      $txt = str_replace('&lt;','<',$txt);
      $txt = str_replace('&gt;','>',$txt);
      $txt = str_replace('&','&',$txt);

      //Ajdusts links - anything starting with HTTP opens in a new window
      $txt = self::stri_replace("<a href=\"http://","<a target=\"_blank\" href=\"http://",$txt);
      $txt = self::stri_replace("<a href=http://","<a target=\"_blank\" href=http://",$txt);

      //Basic formatting
      if(strpos($txt,"\r\n")) $eol = "\r\n";
      elseif(strpos($txt,"\n")) $eol = "\n";
      elseif(strpos($txt,"\r")) $eol = "\r";
      
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

      return $html;
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
}
