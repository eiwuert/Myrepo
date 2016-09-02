<?php

// written by pizza

require_once("dropdown.1.php");

// use in a bitmask
define("STATE_OPT_USA", 1);
define("STATE_OPT_MIL", 2);
define("STATE_OPT_TER", 4);
define("STATE_OPT_CAN", 8);
define("STATE_OPT_DEF", STATE_OPT_USA);
define("STATE_OPT_ALL", STATE_OPT_USA | STATE_OPT_MIL | STATE_OPT_TER | STATE_OPT_CAN);

// note: set values before keys!

class Dropdown_States extends Dropdown
{

	var $mask = STATE_OPT_DEF;
	var $states;

	var $init_states = true;

	function Dropdown_States($opts=NULL)
	{
		// call parent constructor
		parent::Dropdown($opts);
		// set defaults
		$this->_init();
	}

	function _init()
	{
		if (false === $this->init_states)
			return;
		$this->init_states = false;
		parent::_init();
		$this->states = array (
		STATE_OPT_USA => array(
			"AL"=>"Alabama",
			"AK"=>"Alaska",
			"AZ"=>"Arizona",
			"AR"=>"Arkansas",
			"CA"=>"California",
			"CO"=>"Colorado",
			"CT"=>"Connecticut",
			"DE"=>"Delaware",
			"DC"=>"District of Columbia",
			"FL"=>"Florida",
			"GA"=>"Georgia",
			"HI"=>"Hawaii",
			"ID"=>"Idaho",
			"IL"=>"Illinois",
			"IN"=>"Indiana",
			"IA"=>"Iowa",
			"KS"=>"Kansas",
			"KY"=>"Kentucky",
			"LA"=>"Louisiana",
			"ME"=>"Maine",
			"MD"=>"Maryland",
			"MA"=>"Massachusetts",
			"MI"=>"Michigan",
			"MN"=>"Minnesota",
			"MS"=>"Mississippi",
			"MO"=>"Missouri",
			"MT"=>"Montana",
			"NE"=>"Nebraska",
			"NV"=>"Nevada",
			"NH"=>"New Hampshire",
			"NJ"=>"New Jersey",
			"NM"=>"New Mexico",
			"NY"=>"New York",
			"NC"=>"North Carolina",
			"ND"=>"North Dakota",
			"OH"=>"Ohio",
			"OK"=>"Oklahoma",
			"OR"=>"Oregon",
			"PA"=>"Pennsylvania",
			"PR"=>"Puerto Rico",
			"RI"=>"Rhode Island",
			"SC"=>"South Carolina",
			"SD"=>"South Dakota",
			"TN"=>"Tennessee",
			"TX"=>"Texas",
			"UT"=>"Utah",
			"VT"=>"Vermont",
			"VI"=>"Virgin Islands",
			"VA"=>"Virginia",
			"WA"=>"Washington",
			"WV"=>"West Virginia",
			"WI"=>"Wisconsin",
			"WY"=>"Wyoming"
		),
		STATE_OPT_MIL => array(
			"AA"=>"Armed Forces America",
			"AE"=>"Armed Forces Other Areas",
			"AS"=>"American Samoa",
			"AP"=>"Armed Forces Pacific",
			"GU"=>"Guam",
			"MH"=>"Marshall Islands",
			"FM"=>"Micronesia",
			"MP"=>"Norther Mariana Islands",
			"PW"=>"Palau"
		),
		STATE_OPT_CAN => array(
			"BC"=>"British Columbia",
			"NB"=>"New Brunswick",
			"MB"=>"Manitoba",
			"NF"=>"Newfoundland",
			"NT"=>"Northwest Territories",
			"NS"=>"Nova Scotia",
			"ON"=>"Ontario",
			"PE"=>"Prince Edward Island",
			"QC"=>"Quebec",
			"SK"=>"Saskatchewan",
			"YT"=>"Yukon"
			)
		);
		
	}

	// which types of states do you want? $dd->setType(STATE_OPT_USA|STATE_OPT_CAN);
	function setType($mask)
	{
		$this->mask = $mask;
	}

	// call this before display, builds only states we want
	function buildKeyVals()
	{
		// reset key_vals
		$this->key_vals = array();

		reset($this->states);
		while(list($opt,$data) = each($this->states))
		{
			// bitwise compare
			if ($opt === $this->mask & $opt)
			{
				$this->key_vals = array_merge($this->key_vals, $data);
			}
		}
		ksort($this->key_vals);
	}

	function display($return=false)
	{
		$this->buildKeyVals();
		return parent::display($return);
	}

}

/*
// test
$months = new Dropdown_Months();
$months->setSelected("5");
$months->display();
*/

?>
