<?php

namespace True\GoogleTagMangerEvents;

/**
 * 	'method'=>$loginMethod, // The method used to login.
 */

class Login
{
	public function generate($values)
	{
		$data = [];

		if (isset($values['method']) and !empty($values['method']))
			$data['method'] = $values['method'];

		return $data;
	}
}