<?php
namespace True;

#[\AllowDynamicProperties]
class RequestData {
	public function __get($name) {
		return property_exists($this, $name) ? $this->$name : null;
	}
	public function __set($name, $value) {
		$this->$name = $value;
	}
}