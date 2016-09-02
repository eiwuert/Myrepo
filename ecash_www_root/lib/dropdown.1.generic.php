<?php

# written by tomr for generic key-value selects...

require_once 'dropdown.1.php';

class Dropdown_Generic extends Dropdown
{

	var $init_genval = true;

	function Dropdown_Generic($key_vals)
	{
		// call parent constructor
		parent::Dropdown();
		// set defaults
		$this->_init();
		$this->key_vals = $key_vals;
	}

	function _init()
	{
		if (false === $this->init_genval)
			return;
		$this->init_genval = false;
		parent::_init();
	}

}

?>
