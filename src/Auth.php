<?php
namespace True;

/**
 * Auth class for authenticating api calls

 $Auth->
 *
 * @package default
 * @author 
 */
class Auth
{
    /**
     * undocumented function
     *
     * @param string $randomChars - at least 10 letters and numbers
     * @return void
     * @author Daniel Baldwin - danb@truecastdesign.com
     **/
    public function requestToken($randomChars)
    {
    	if(empty($randomChars))
    	{
    		trigger_error('No random characters provided', 256);
    		return false;
    	}	

    	# get salt
    	$fp = fopen('/dev/urandom', 'r');
		$salt = base64_encode(fread($fp, 16));
		fclose($fp);

		# create token
    	$token = hash("sha256", $randomChars.$salt);

    	# save token to file
    	if(file_put_contents(BP.'/vendor/truecastdesign/true/auth_tokens', $token."\n", FILE_APPEND | LOCK_EX) === false)
    	{
    		trigger_error("Token could not be written to a file!", 256);
    		return "Token could not be written to a file!";
    	}
    	else
    	{
    		return $token;	
		}	    	
    }

    /**
     * Revoke a token when done using it
     *
     * @param string $token
     * @return bool
     * @author Daniel Baldwin - danb@truecastdesign.com
     **/
    public function revokeToken($token)
    {
    	$contents = file_get_contents(BP.'/vendor/truecastdesign/true/auth_tokens');

    	$updatedContents = str_replace($token."\n", '', $contents);

    	if(file_put_contents(BP.'/vendor/truecastdesign/true/auth_tokens', $updatedContents, LOCK_EX) === false)
    	{
    		trigger_error("Token could not be revoked. Could not write to file!", 256);
    		return "Token could not be revoked. Could not write to file!";
    	}
    }

    /**
     * Check if token is valid
     *
     * @param string $token
     * @return bool true:valid, false:not valid
     * @author Daniel Baldwin - danb@truecastdesign.com
     **/
    public function checkToken($token)
    {
		if( strpos(file_get_contents(BP.'/vendor/truecastdesign/true/auth_tokens'), $token) !== false)
			return true;
		else
			return false;
    }
}
