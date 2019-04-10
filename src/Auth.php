<?php
namespace True;

/**
 * Auth class for authenticating api calls

 *
 * @package True Framework
 * @author Daniel Baldwin
 */
class Auth
{
    /**
     * request token
     *
     * @param string $randomChars - at least 10 letters and numbers
     * @return void
     * @author Daniel Baldwin - danb@truecastdesign.com
     **/
    public function requestToken()
    {
        # get salt
        $fp = fopen('/dev/urandom', 'r');
        $salt = base64_encode(fread($fp, 16));
        fclose($fp);

        # create token
        $token = hash("sha256", $salt);

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

    /**
     * authenticate the user
     * Note: allow to pass origen domain to limit to local or certain domains or IPs
     *
     * Use in the controller file like this:
     * if ($App->auth->authenticate(['type'=>'bearer'])) {
     *  # success
     * } else {
     *  # failed
     * }
     * @param  array  $params   ['type'=>'bearer']
     * @param  function $callbackSuccessful the function that is called if authenticate passes
     * @param  function $callbackFail the function that is called if authenticate fails
     * @return null 
     */
    public function authenticate(array $params)
    {
        extract($params);
        
        if (isset($type)) {
            switch ($type) {
                case 'bearer':
                    $headers = getallheaders();
                    if (isset($headers['Authorization'])) {
                        $token = str_replace('Bearer ','',$headers['Authorization']);

                        if (!empty($token) and $this->checkToken($token)) {
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                break;
                default:
                    return false;
            }
        }
    }
}
