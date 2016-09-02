<?php

// written by pizza
// this class represents a dropdown 

require_once("dropdown.1.php");

//NOTE: start and end are inclusive

class Dropdown_Range extends Dropdown
{

	var $range;

	var $format = false;

	function Dropdown_Numeric()
	{
		parent::Dropdown();
	}

	function setFormat($sprintf_format)
	{
		$this->format = $sprintf_format;
	}

	function zeroPad($bool, $places=2)
	{
		if (!$bool)
		{
			unset($this->format);
		}
		else
		{
			$this->format = "%0" . $places . "d";
		}
	}

	# set values, such as 1,2,3
	function setValues($vals)
	{
		assert(is_array($vals));
		$this->range = $vals;
	}

	function _format($n)
	{
		return ($this->format ? sprintf($this->format, $n) : $n);
	}

	// run this before display
	// by default, all values will be the mean of the high and low values shown
	function buildKeyVals()
	{

		$c = count($this->range);

		if ($c == 0)
		{
			return false;
		}

		$formats = array_map(array($this, "_format"), $this->range);

		$val = 0;
		$key = "";

		for ($i = 0; $i < $c; $i++)
		{
			$n = $this->range[$i];
			switch ($i)
			{
			case 0:
				$val = $n;
				$key = $formats[$i] . " or less";
				break;
			case $n - 1:
				$val = $this->range[$i];
				$key = $formats[$i] . " or more";
				break;
			default:
				$val = ($n + $this->range[$i+1]) / 2;
				$key = $formats[$i] . " - " . $formats[$i+1];
			}
			$this->key_vals[$val] = $key;
		}

		ksort($this->key_vals);

	}

	function display($return=false)
	{
		$this->buildKeyVals();
		// call parent to actually display
		return parent::display($return);
	}

}

// test
/*
$years = new Dropdown_Numeric();
// only show birthdates for some who could be 18 or over
$years->setStart(date("Y") - 17);
$years->setIncrement(-1);
$years->setEnd($years->getStart() - 100);
$years->display();
*/

/*
$monthday = new Dropdown_Numeric(1, 1, 5);
$monthday->setFormat("%02d");
echo "display: " . $monthday->display(true) . ":";
*/


?>
