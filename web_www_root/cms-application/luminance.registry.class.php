<?php
/**
 * Copyright 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * Class: Registry
 * We're going to use this Registry class instead of using global variables all
 * over the place.
 * 
 **/
 
class LuminanceRegistry
{
	private $vars = array();
	
	public function __set($name, $value)
	{
		$this->vars[$name] = $value;
	}
	
	public function __get($name)
	{
		if (array_key_exists($name, $this->vars)) {
			return $this->vars[$name];
		}
		
		$trace = debug_backtrace();
		
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);

		return null;
	}
}
?>
