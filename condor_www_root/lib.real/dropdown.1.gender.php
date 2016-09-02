<?php

require_once("dropdown.1.php");

class Dropdown_Gender extends Dropdown
{
	function Dropdown_Gender()
	{
		$this->key_vals = array(
			"M" => "Male",
			"F" => "Female"
		);
	}
}

?>
