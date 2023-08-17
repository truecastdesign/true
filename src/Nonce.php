<?php
namespace True;

/**
 * OpenID Based Nonce
 * 
 * PHP Version 5.2.0+
 * 
 * @category  Auth
 * @package   True
 * @author    Bill Shupp <hostmaster@shupp.org> Modified by Daniel Baldwin
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @version 1.0.0
 */
class Nonce
{
	/**
     * Creates a nonce, but does not store it.  You may specify the lenth of the
     * random string, as well as the time stamp to use.
     * 
     * @param int $length Lenth of the random string, defaults to 32
     * @param int $time A unix timestamp in seconds
     * 
     * @return string The nonce
     */
    public static function create($length = 32, $time = null)
    {
        $time = ($time === null) ? time() : $time;
 
        $chars = md5(gmdate('Y-m-d H:i:s', $time));
 
        $length = (int) $length;
        $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars .= 'abcdefghijklmnopqrstuvwxyz';
        $chars .= '1234567890';
 
        $unique = '';
        for ($i = 0; $i < $length; $i++) {
            $unique .= substr($chars, (rand() % (strlen($chars))), 1);
        }
 
        return $unique;
    }
}