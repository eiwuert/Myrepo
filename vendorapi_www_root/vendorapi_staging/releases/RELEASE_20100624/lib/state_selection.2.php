<?php
	// Version 1.0.2

	/* DESIGN TYPE
		static
	*/

	/* UPDATES
		Features:
			Displays a <select> item with states with options for different variable data and multiple select.

		Added:
			Version 1.0.1 - added arguments for class and tabindex
			Version 1.0.2 - added id as an argument - Nick White

		Bugs:
	*/

	/* PROTOTYPES
		bool State_Selection ()
		string State_Pulldown ($select_name, $select_value=0, $select_display=0, $selected_state="", $first_blank=true, $exclude="", $mult_height=0, $us_terr=false, $can_prov=false, $class=NULL, $tabindex=NULL, $id=NULL)
	*/

	/* OPTIONAL CONSTANTS
	*/

	/* SAMPLE USAGE
	State_Pulldown ()
		Arguments:
 		$select_name: string - the name of the actual select item
  		$select_value: int - value parameter of select options: 0 = "UT", 1 = "ut", 2 = "Utah"
  		$select_display: int - display of select options: 0 = "UT", 1 = "ut", 2 = "Utah"
  		$selected_state: string - "" = none selected, "UT" = select state with this abbreviation (one state only)
  		$first_blank: boolean - false makes first option blank
  		$exclude: string - comma delimited string of uppercase states to exclude from list - "NM,UT,AL"
  		$mult_height: int - row height of selection box (0 same as 1), also allows multiple select
  		$us_terr: boolean - true displays US territories
  		$can_prov: boolean - true displays Canadian provinces
  		$class: string - string name of stylesheet class
  		$tabindex: int - tabindex
  		$id: string - string name to use as an id for the field

	State_Option_List ()
		Arguments:
  		$select_value: int - value parameter of select options: 0 = "UT", 1 = "ut", 2 = "Utah"
  		$select_display: int - display of select options: 0 = "UT", 1 = "ut", 2 = "Utah"
  		$selected_state: string - "" = none selected, "UT" = select state with this abbreviation (one state only)
  		$first_blank: boolean - false makes first option blank
  		$exclude: string - comma delimited string of uppercase states to exclude from list - "NM,UT,AL"
  		$us_terr: boolean - true displays US territories
  		$can_prov: boolean - true displays Canadian provinces

  		Examples:
  		$newState = new State_Selection;
  		$newState->State_Pulldown ("shipping_state", 0, 2, "UT", true, "PR", 0, true, true, "style1", 5,"id_name");
  		OR can simply be used with default setting:
  		$newState = new State_Selection;
  		$newState->State_Pulldown ("shipping_state");
	*/

	/* REQUIRED DB TABLE STRUCTURE
	*/

class State_Selection
{
	var $us_state_array;
	var $us_terr_array;
	var $canada_prov_array;
	var $state_select_html;

	function State_Selection ()
	{
		$this->state_select_html = "";
		$this->us_state_array = array (
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
			"WY"=>"Wyoming",
			"UK"=>"Unknown"
		);

		$this->us_terr_array = array (
			""=>"-US TERRITORIES",
			"AA"=>"Armed Forces America",
			"AE"=>"Armed Forces Other Areas",
			"AS"=>"American Samoa",
			"AP"=>"Armed Forces Pacific",
			"GU"=>"Guam",
			"MH"=>"Marshall Islands",
			"FM"=>"Micronesia",
			"MP"=>"Norther Mariana Islands",
			"PW"=>"Palau"
		);

		$this->canada_prov_array = array (
			""=>"-CANADIAN PROVINCES",
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
		);

		return true;
	}

	function State_Pulldown ($select_name, $select_value=0, $select_display=0, $selected_state="", $first_blank=true, $exclude="", $mult_height=0, $us_terr=false, $can_prov=false, $class=NULL, $tabindex=NULL, $event_handler_string=NULL,$id=NULL)
	{
		// Start building Select HTML
		$this->state_select_html = "<select name=\"". $select_name . "\"";
		if ($id != NULL)
		{
			$this->state_select_html .= " id=\"" . $id . "\"";	
		}
		if ($class != NULL)
		{
			$this->state_select_html .= " class=\"" . $class . "\"";
		}
		if ($tabindex != NULL)
		{
			$this->state_select_html .= " tabindex=\"" . $tabindex . "\"";
		}
		if ($event_handler_string != NULL)
		{
			$this->state_select_html .= " " . trim ($event_handler_string);
		}
		if ($mult_height > 1)
		{
			$first_blank = false;
			$this->state_select_html .= " size=\"" . $mult_height . "\" multiple";
		}
		$this->state_select_html .= ">\n";
		$this->state_select_html .= $this->State_Option_List ($select_value, $select_display, $selected_state, $first_blank, $exclude, $us_terr, $can_prov);
		$this->state_select_html .= "</select>";

		return $this->state_select_html;
	}

	function State_Option_List ($select_value=0, $select_display=0, $selected_state="", $first_blank=true, $exclude="", $us_terr=false, $can_prov=false)
	{
		// sort by $select_display
		if ($select_display < 2)
		{
			ksort ($this->us_state_array);
			ksort ($this->us_terr_array);
			ksort ($this->canada_prov_array);
		}
		else
		{
			asort ($this->us_state_array);
			asort ($this->us_terr_array);
			asort ($this->canada_prov_array);
		}

		// Remove excluded States
		if ($exclude != "")
		{
			// Filter through 3 arrays separately to allow territory and Canada spacer options to be "", otherwise array key will overwrite
			$exclude_array = explode (",", $exclude);
			$index = 0;
			foreach ($this->us_state_array as $key => $value)
			{
				if (in_array ($key, $exclude_array))
				{
					array_splice ($this->us_state_array, $index, 1);
					$index--;
				}
				$index++;
			}
			if ($us_terr)
			{
				$index = 0;
				foreach ($this->us_terr_array as $key => $value)
				{
					if (in_array ($key, $exclude_array))
					{
						array_splice ($this->us_terr_array, $index, 1);
						$index--;
					}
					$index++;
				}
			}
			if ($can_prov)
			{
				$index = 0;
				foreach ($this->canada_prov_array as $key => $value)
				{
					if (in_array ($key, $exclude_array))
					{
						array_splice ($this->canada_prov_array, $index, 1);
						$index--;
					}
					$index++;
				}
			}
		}

		// Start building Options HTML
		$option_list = NULL;
		
		if ($first_blank)
		{
			$option_list .= "<option></option>\n";
		}

		// Set arrays to options
		$option_list .= $this->State_Array_To_Options ($this->us_state_array, $select_value, $select_display, $selected_state);
		// Add Canadian and Territories to Select
		if ($us_terr)
			$option_list .= $this->State_Array_To_Options ($this->us_terr_array, $select_value, $select_display, $selected_state);

		if ($can_prov)
			$option_list .= $this->State_Array_To_Options ($this->canada_prov_array, $select_value, $select_display, $selected_state);

		return $option_list;
	}

	// Set arrays to options function
	function State_Array_To_Options ($state_array, $select_value, $select_display, $selected_state)
	{
		$option_list = NULL;
		foreach ($state_array as $abbrev => $name)
		{
			$option_list .= "<option value=\"" . $this->Choose_State_Format ($abbrev, $name, $select_value) . "\"";
			if (($selected_state != "") && (strtolower ($abbrev) == strtolower ($selected_state)))
			{
				$option_list .= " selected";
			}
			$option_list .= ">" . $this->Choose_State_Format ($abbrev, $name, $select_display) . "</option>\n";
		}

		return $option_list;
	}

	// Set the values for the option value/display
	function Choose_State_Format ($abbrev, $name, $value_select)
	{
		switch ($value_select)
		{
			case 0:
				return $abbrev;
				break;
			case 1:
				return strtolower ($abbrev);
				break;
			case 2:
				return $name;
				break;
		}

		return true;
	}
}
?>
