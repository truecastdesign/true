<?php
namespace True;

#### VALIDATION RULES ##########
# required
# clean
# matches[value] - 
# in[value one,value two] - Match with given set of values
# depends[field_name=value|second value]
# min[char number]
# max[char number]
# min_num[number]
# max_num[number]
# alpha
# alpha_numeric
# alpha_numeric_dash - a-z0-9_-
# numeric
# integer - whole numbers that are either nagative or positive
# natural - Natural number  (0,1,2,3, etc.)
# natural_no_zero - Natural number  (1,2,3, etc.)
# name - a-zA-Z0-9. -',&#()
# address - 0-9a-zA-Z. -,:
# base64
# ip - ip address
# date[m d, Y]
# ssn[dk] - social security number, pass the country
# phone
# phone_length - check if length of phone number is 7, 10, or 11 digits
# url

/**
 * Value Validator
 * 
 * @version 1.0.1
 */
class Validator
{
	var $data;
	var $valid = true;
	var $values = [];
	var $errors = [];
	
	public function __construct($data = [])
	{
		$this->data = (array) $data;
	}
	
	/**
	 * Validate data
	 *
	 * @param array $fieldRules ['fieldname'=>'min[5]|max[10]']
	 * @param array $customErrors ['fieldname'=>"Please enter a valid name!"]
	 * @return bool valid=true   not valid=false
	 */
	public function validate($fieldRules = [], $customErrors = [])
	{
		# validate the form data
		foreach ($fieldRules as $field=>$rules) {
			if (isset($field) and isset($rules)) {
				$customErrorMsg = array_key_exists($field, $customErrors)? $customErrors[$field]:null;
				$fieldValue = array_key_exists($field, $this->data)? $this->data[$field]:null;
				$this->rules($field, explode('|', $rules), $fieldValue, $customErrorMsg);
			}				
		}

		return $this->valid;
	}

	# process the rules
	private function rules($field, $rules, $data, $errorMsg)
	{	
		foreach ($rules as $rule) { # Loop through each rule and run it
			$param = false;
			if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match)) {
				$rule = $match[1];
				$param = $match[2];
			}

			$selfRule = 'validate_'.$rule;

			if (!method_exists($this, $selfRule)) { # check if the method exists for the rule
				if (function_exists($rule))
					$result = $rule($data);
				else throw new \Exception("Invalid Rule: ".$rule."!");
			} else { # there is a local method that matches
			 	$result = $this->$selfRule($data, $param); # Run the method and return the result
			}

			# check if there was an error, if there was than if no custom error is set provide it
			if ($result === false) { # invalid
				if (!isset($errorMsg)) # no custom error
				   $this->errors[] = $this->errorMsgs($rule, $field, $param);
				else # custom error
					$this->errors[] = $errorMsg; 

				$this->valid = false; # mark the form as not valid		
			} elseif($result === true)
				$this->values[$field] = $data;
			
			# if the test returns content 
			if(!is_bool($result))
				$this->values[$field] = $result;
		}
	}

	# @param (string) $rule - rule, (string) $field - form field name, (string) $param - value sent by rule
	private function errorMsgs($rule, $field, $param)
	{
		$matchField = null;
		$stripChars = array('_','-','*');
		
		$fieldLabel = ucwords(str_replace($stripChars,' ',$field));
		
		if ($rule == 'matches') 
			$matchField = ucwords(str_replace($stripChars,' ',$param));
		
		if ($rule == 'depends') {
		   $parts = explode('=',$param);
    		$dependField = trim($parts[0]);
		} else
			$dependField = '';
		
		# error messages. Feel free to change if needed.
		$errors['required'] = "The $fieldLabel field is required.";		
		$errors['name'] = "Your $fieldLabel may only contain a-zA-Z0-9. -',&#() characters.";		
		$errors['address'] = "Your $fieldLabel may only contain 0-9a-zA-Z. -,: characters.";		
		$errors['matches'] = "The $fieldLabel field does not match the $matchField field.";		
		$errors['depends'] = "The $fieldLabel field is needed because the $dependField field was answered.";
		$errors['in'] = "The $fieldLabel field needs to contain one of the following $param.";
		$errors['email'] = "Your $fieldLabel is required and is not valid.";
		$errors['emails'] = "The $fieldLabel field must contain all valid email addresses.";
		$errors['url'] = "The $fieldLabel field must contain a valid URL.";
		$errors['ip'] = "The $fieldLabel field must contain a valid IP address.";
		$errors['min'] = "The $fieldLabel field must be at least $param characters in length.";
		$errors['max'] = "The $fieldLabel field can not exceed $param characters in length.";
		$errors['alpha'] = "The $fieldLabel field may only contain alphabetical characters.";
		$errors['alpha_numeric'] = "The $fieldLabel field may only contain alpha-numeric characters.";
		$errors['alpha_numeric_dash'] = "The $fieldLabel field may only contain alpha-numeric characters, underscores, and dashes.";
		$errors['numeric'] = "The $fieldLabel field must contain only numbers.";
		$errors['integer'] = "The $fieldLabel field must contain an integer.";
		$errors['natural'] = "The $fieldLabel field must contain only positive whole numbers.";
		$errors['natural_no_zero'] = "The $fieldLabel field must contain a whole numbers greater than zero.";
		$errors['base64'] = "The $fieldLabel field contains invalid characters.";
		$errors['phone'] = "Your $fieldLabel has invalid characters in it or is not filled in. Valid characters: numbers, spaces, pound, parentheses, and dashes.";		
		$errors['date'] = "Your $fieldLabel does not match a date with the format $param";		
		$errors['ssn'] = "Your $fieldLabel does not match a valid social security number format with dashes.";		
		$errors['phone_length'] = "Your $fieldLabel needs to contain 7, 10 or 11 digits.";		
	
		return ($errors[$rule]? $errors[$rule]:$errors['required']);
	}

	# get the field errors
	public function errors(): array
	{
		return $this->errors;	
	}	
	
	# Required
	function validate_required($str)
	{
		if(!is_array($str)) return (trim($str) == '')? FALSE:TRUE;
		else return (!empty($str));
	}

	# XSS Clean	
	function validate_clean($str)
	{
		return htmlspecialchars_decode(html_entity_decode(stripslashes($str)));
	}
	
	# Match one field to another
	function validate_matches($str, $str2)
	{
		if (empty($str) or empty($str2)) return FALSE;			
		return hash_equals($str,$str2)? FALSE:TRUE;
	}

	# Match with given set of values
	# Use: field=in[value one,value two]
	function validate_in($str, $values)
	{
		return in_array($str, explode(',', $values))? true:false;
	}
	
	# Match one field to another
	# use: 'rule'=>'depends[field_name=value^second value]'
	function validate_depends($str, $fieldInfo)
	{
		$parts = explode('=',$fieldInfo);
		$dependField = trim($parts[0]);
		$dependValue = trim($parts[1]);
		$str = trim($str);
		if (empty($str)) 
			return false;

		if (strstr($dependValue, '|')) { # more than one value
			$result = false;
			$values = explode("|",$dependValue);
			foreach ($values as $val) {
				if ($this->data[$dependField] == $val)
					return true;
				return false;
			}
			return $result;
		} else {
		    if ($this->data[$dependField] == $dependValue)
		      return true;
		    return false;
		}
	}
	
	# Minimum Length
	function validate_min($str, $val)
	{
		if (preg_match("/[^0-9]/", $val)) return FALSE;
		if (function_exists('mb_strlen')) return (mb_strlen($str) < $val)? FALSE:TRUE;
		return (strlen($str) < $val)? FALSE:TRUE;
	}
	
	# Max Length
	function validate_max($str, $val)
	{
		if(preg_match("/[^0-9]/", $val)) return FALSE;
		if (function_exists('mb_strlen')) return (mb_strlen($str) > $val) ? FALSE : TRUE;
		return (strlen($str) > $val) ? FALSE : TRUE;
	}

	# Minimum Length
	function validate_min_num($str, $val)
	{
		if (!is_numeric($str)) return false;
		return $str >= $val? true:false;
	}
	
	# Max Length
	function validate_max_num($str, $val)
	{
		if (!is_numeric($str)) return false;
		return $str <= $val? true:false;
	}
	
	# Email
	function validate_email($str)
	{
		return (filter_var($str, FILTER_VALIDATE_EMAIL)===false? false:true);
	}
	
	# Emails
	function validate_emails($str)
	{
		if (strpos($str, ',') === FALSE) return $this->validate_email(trim($str));
		
		foreach(explode(',', $str) as $email)
			if (trim($email) != '' && $this->validate_email(trim($email)) === FALSE) return FALSE;
	
		return TRUE;
	}
	
	# Alpha
	function validate_alpha($str)
	{
		return ( ! preg_match("/^([a-z])+$/i", $str)) ? FALSE : TRUE;
	}
	
	# Alpha-numeric
	function validate_alpha_numeric($str)
	{
		return ( ! preg_match("/^([a-z0-9])+$/i", $str)) ? FALSE : TRUE;
	}
	
	# Alpha-numeric with underscores and dashes
	function validate_alpha_numeric_dash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}
	
	# numeric
	function validate_numeric($str)
	{
		return (!is_numeric($str)) ? FALSE : TRUE;
	}

	# Integer	
	function validate_integer($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]+$/', $str);
	}

	# Is a Natural number  (0,1,2,3, etc.)
   function validate_natural($str)
   {   
   	return (bool)preg_match( '/^[0-9]+$/', $str);
   }

	# Is a Natural number, but not a zero  (1,2,3, etc.)
	function validate_natural_no_zero($str)
   {
		if ( ! preg_match( '/^[0-9]+$/', $str))
			return FALSE;

		if ($str == 0)
			return FALSE;

		return TRUE;
	}

	# check for a valid person's name. a-zA-Z0-9. -',&#()
	function validate_name($str) 
	{
		if($str == '') return false;
		return (bool) preg_match('/^[a-zA-Z0-9\. \-\'\,\&\#\(\)]*$/', $str);
	}
	
	# street address. 0-9a-zA-Z. -,:
	function validate_address($str) 
	{
		if($str == '') return false;
		return (bool) preg_match('/^[0-9a-zA-Z\. \-\,\:]*$/', $str);
	}

	# Base64 string
	function validate_base64($str)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
	}
	
	# IP address
	function validate_ip($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP);
	}
	
	# date
	function validate_date($str, $format = 'Y-m-d')
	{	
		$d = \DateTime::createFromFormat($format, $str);
    	return $d && $d->format($format) === $str;
	}

	# social security number
	function validate_ssn($str, $country='us') 
	{
		switch($country)
		{
			case 'us':
				$regex  = '/\\A\\b[0-9]{3}-[0-9]{2}-[0-9]{4}\\b\\z/i';
			break;
			case 'dk':
				$regex  = '/\\A\\b[0-9]{6}-[0-9]{4}\\b\\z/i';
			break;
			case 'nl':
				$regex  = '/\\A\\b[0-9]{9}\\b\\z/i';
			break;
		}
		return (bool) preg_match($regex, $str);
	}

	# phone
	function validate_phone($str)
	{
		if ($str == '') return false;
		return (bool) preg_match('/^[0-9\. \-\#\(\)]*$/', $str);
	}
	
	# check if length of phone number is 7, 10, or 11 digits
	function validate_phone_length($str)
	{
		if ($str == '') return false;
    	$numberOfDigits = strlen(preg_replace("/[^0-9]/", "", $str));
      return ($numberOfDigits == 7 OR $numberOfDigits == 10 OR $numberOfDigits == 11) ? true:false;
	}

	# URL
	function validate_url($str)
	{
		return (filter_var($str, FILTER_VALIDATE_URL)===false? false:true);
	}
	
	# Pass the result of a custom function into validator 
	function inject($str, $result)
	{
		if($result==1) return true;
		else return false;
	}	
}