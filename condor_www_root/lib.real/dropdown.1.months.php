<?php

// written by pizza

require_once("dropdown.1.php");

// note: set values before keys!

class Dropdown_Months extends Dropdown
{

	var $months;

	var $short_months;
	var $full_months;

	function Dropdown_Months()
	{
		parent::Dropdown();
		// set defaults
		$this->setup();
		$this->valueFullMonths();
		$this->keyNumeric();
	}

	function setup()
	{
		// we can use these for the key and/or values, so we define them once
		$this->short_months = array(
			"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
		);
		$this->full_months = array(
			"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
		);
	}

	function valueShortMonths()
	{
		$vals = array_keys($this->key_vals);
		if (count($vals) == 0)
		{
			$vals = range(1, 12);
		}
		$this->key_vals = array();
		for ($i = 0; $i < count($this->short_months); $i++)
		{
			$this->key_vals[$this->short_months[$i]] = $vals[$i];
		}
	}

	function valueFullMonths()
	{
		$vals = array_keys($this->key_vals);
		if (count($vals) == 0)
		{
			$vals = range(1, 12);
		}
		$this->key_vals = array();
		for ($i = 0; $i < count($this->full_months); $i++)
		{
			$this->key_vals[$this->full_months[$i]] = $vals[$i];
		}
	}

	function valueNumeric()
	{
		$vals = array_keys($this->key_vals);
		if (count($vals) == 0)
		{
			$vals = range(1, 12);
		}
		$keys = range(1, 12);
		$this->key_vals = array();
		for ($i = 0; $i < count($this->full_months); $i++)
		{
			$this->key_vals[$keys[$i]] = $vals[$i];
		}
	}

	function keyNumeric($zeropad=0)
	{
		$keys = array_values($this->key_vals);
		$this->key_vals = array();
		for ($i = 0; $i < count($keys); $i++)
		{
			$this->key_vals[$keys[$i]] = ($zeropad && $i <= 9 ? "0" . ($i + 1) : $i + 1);
		}
	}

	function keyShortMonths()
	{
		$keys = array_values($this->key_vals);
		$this->key_vals = array();
		for ($i = 0; $i < count($keys); $i++)
		{
			$this->key_vals[$keys[$i]] = $this->short_months[$i];
		}
	}

	function keyFullMonths()
	{
		$keys = array_values($this->key_vals);
		$this->key_vals = array();
		for ($i = 0; $i < count($keys); $i++)
		{
			$this->key_vals[$keys[$i]] = $this->full_months[$i];
		}
	}

}

/*
// test
$months = new Dropdown_Months();
$months->setSelected("5");
$months->display();
*/

?>
