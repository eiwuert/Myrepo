<?php

// written by pizza

require_once("dropdown.1.php");

//NOTE: start and end are inclusive

class Dropdown_Numeric extends Dropdown
{

	var $start;
	var $increment;
	var $end;

	var $nth;
	var $format;

	var $init_numeric = true;

	function Dropdown_Numeric($opts=NULL)
	{

		$this->_init();

		if (NULL === $opts)
			return parent::Dropdown($opts);

		assert(is_array($opts));

		reset($opts);
		while (list($k,$v) = each($opts))
		{
			#echo "$k:$v,";
			$nuke = true;
			switch (strtoupper($k))
			{
			case "START":
			case "BEGIN":
				$this->setStart($v);
				break;
			case "STOP":
			case "END":
				$this->setEnd($v);
				break;
			case "INCREMENT":
			case "INCR":
			case "INC":
				$this->setIncrement($v);
				break;
			case "NTH":
				$this->setNth($v);
				break;
			default:
				$nuke = false;
				break;
			}
			# get rid of opion so parent doesn't have to process it
			if ($nuke)
			{
				unset($opts[$k]);
			}
		}
		
		parent::Dropdown($opts);
	}

	function _init()
	{
		if (false === $this->init_numeric)
			return;
		parent::_init();
		$this->setStart(1);
		$this->setEnd(3);
		$this->setIncrement(1);
		$this->init_numeric = false;
		$this->nth = false;
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

	function setStart($start)
	{
		$this->start = $start;
	}

	function getStart()
	{
		return $this->start;
	}

	function setIncrement($increment)
	{
		$increment = intval($increment);
		if ($increment == 0)
		{
			die("Dropdown_Numeric->setIncrement: Invalid increment of 0. You'll never get anywhere incrementing by zero!!");
		}
		$this->increment = $increment;
	}

	function getIncrement()
	{
		return $this->increment;
	}

	function setEnd($end)
	{
		$this->end = $end;
	}

	function getEnd()
	{
		return $this->end;
	}

	/* do we add st, nd, rd, th? */
	function setNth($bool)
	{
		$this->nth = (bool)$bool;
	}

	function getNth()
	{
		return $this->nth;
	}

	function display($return=false)
	{

		// check basic logic
		if (
			($this->start > $this->end && $this->increment > 0)
			|| ($this->start < $this->end && $this->increment < 0)
		) {
			die("Dropdown_Numeric: you can't get from '{$this->start}' to '{$this->end}' by an increment of '{$this->increment}'");
		}

		// generate key_vals pairs
		for ($i = $this->start; ; )
		{
			if ($this->format)
			{
				$this->key_vals[sprintf($this->format, $i)] = $i;
			}
			else if ($this->nth)
			{
				$this->key_vals[$i] = $this->_nth($i);
			}
			else
			{
				$this->key_vals[$i] = $i;
			}
			// put this here because end is inclusive, not exclusive
			if ($i == $this->end)
			{
				break;
			}
			// works with either positive or negative increment
			$i += $this->increment;
		}

		// call parent to actually display
		return parent::display($return);

	}

	/* private methods */

	function _nth($n)
	{
		switch ($n % 100)
		{
		case 11:
		case 12:
		case 13:
			return $n . "th";
			break;
		default:
			switch ($n % 10)
			{
			case 1:
				return $n . "st";
				break;
			case 2:
				return $n . "nd";
				break;
			case 3:
				return $n . "rd";
				break;
			case 0:
			case 4:
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
			default:
				return $n . "th";
				break;
			}
			break;
		}
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
