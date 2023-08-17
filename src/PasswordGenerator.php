<?php

namespace True;

/**
 * Generate Word Based Passwords
 * 
 * @version 1.0.1
 * 
*/
class PasswordGenerator
{
	public function generate($words = 4)
	{
		$adjectives = explode(" ",file_get_contents(BP.'/vendor/truecastdesign/true/assets/adjectives.txt'));
		$nouns = explode(" ",file_get_contents(BP.'/vendor/truecastdesign/true/assets/nouns.txt'));
		
		if ($words == 2)
			$password = $adjectives[rand(1,count($adjectives))].' '.$nouns[rand(1,count($nouns))];
		elseif ($words == 3)
			$password = $adjectives[rand(1,count($adjectives))].' '.$adjectives[rand(1,count($adjectives))].' '.$nouns[rand(1,count($nouns))];
		elseif ($words == 4)
			$password = $adjectives[rand(1,count($adjectives))].' '.$nouns[rand(1,count($nouns))].' '.$adjectives[rand(1,count($adjectives))].' '.$nouns[rand(1,count($nouns))];
		elseif ($words == 5)
			$password = $adjectives[rand(1,count($adjectives))].' '.$adjectives[rand(1,count($adjectives))].' '.$nouns[rand(1,count($nouns))].' '.$adjectives[rand(1,count($adjectives))].' '.$nouns[rand(1,count($nouns))];
		return $password;
	}
}
