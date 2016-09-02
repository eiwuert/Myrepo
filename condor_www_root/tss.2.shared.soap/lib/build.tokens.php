<?php
	
	interface Token
	{
		public function Value($value = NULL);
	}
	
	class Select implements Token
	{
		
		protected $name = NULL;
		protected $class = NULL;
		protected $tab_index = NULL;
		protected $multiple = FALSE;
		protected $height = NULL;
		protected $options = array();
		protected $selected = NULL;
		protected $use_keys = TRUE;
		
		public function __construct($name = NULL, $options = NULL, $selected = NULL, $use_keys = TRUE, $class = NULL, $tab_index = NULL, $height = NULL, $multiple = NULL)
		{
			
			// set ourselves up
			if (!is_null($name)) $this->Name($name);
			if (!is_null($options)) $this->Options($options, $use_keys);
			if (!is_null($selected)) $this->Selected($selected);
			if (!is_null($class)) $this->CSS_Class($class);
			if (!is_null($tab_index)) $this->Tab_Index($tab_index);
			if (!is_null($height)) $this->Height($height);
			if (!is_null($multiple)) $this->Multiple($multiple);
			
		}
		
		public function Name($name = NULL)
		{
			
			if (is_string($name)) $this->name = $name;
			elseif (is_null($name)) $name = $this->name;
			else $name = FALSE;
			
			return($name);
			
		}
		
		public function CSS_Class($class = NULL)
		{
			
			if (is_string($class)) $this->class = $class;
			elseif (is_null($class)) $class = $this->class;
			else $class = FALSE;
			
			return($class);
			
		}
		
		public function Tab_Index($index = NULL)
		{
			
			if (is_string($index) || is_numeric($index)) $this->tab_index = $index;
			elseif (is_null($index)) $index = $this->tab_index;
			else $index = FALSE;
			
			return($index);
			
		}
		
		public function Multiple($multiple = NULL)
		{
			
			if (is_bool($multiple)) $this->multiple = $multiple;
			elseif (is_null($multiple)) $multiple = $this->multiple;
			else $multiple = NULL;
			
			return($multiple);
			
		}
		
		public function Height($height = NULL)
		{
			
			if (is_numeric($height)) $this->height = (int)$height;
			elseif (is_null($height)) $height = $this->height;
			else $height = FALSE;
			
			return($height);
			
		}
		
		public function Selected($selected = NULL)
		{
			
			if (is_string($selected) || is_numeric($selected)) $this->selected = $selected;
			elseif (is_null($selected)) $selected = $this->selected;
			else $selected = FALSE;
			
			return($selected);
			
		}
		
		public function Options($options = NULL, $use_keys = TRUE)
		{
			
			if (is_array($options))
			{
				$this->options = $options;
				$this->use_keys = $use_keys;
			}
			elseif (is_null($options))
			{
				$options = $this->options;
			}
			else
			{
				$options = FALSE;
			}
			
			return($options);
			
		}
		
		public function Value($value = NULL)
		{
			
			if (is_null($value))
			{
				
				$value = $this->Generate($this->name, $this->options, $this->selected, $this->use_keys, $this->class, $this->tab_index, $this->height, $this->multiple);
				
			}
			else
			{
				// set our selected value
				$this->selected = $value;
			}
			
			return($value);
			
		}
		
		protected function Generate()
		{
			
			$out = FALSE;
			
			if ($this->name && count($this->options))
			{
				
				$out = '<select name="'.$this->name.'" id="'.$this->name.'"';
				if (!is_null($this->class)) $out .= ' class="'.$this->class.'"';
				if (!is_null($this->tab_index)) $out .= ' tabindex="'.$this->tab_index.'"';
				if (is_numeric($this->height)) $out .= ' size="'.$this->height.'"';
				if ($this->multiple===TRUE) $out.= ' multiple';
				$out .= '>';
				
				foreach ($this->options as $key=>$value)
				{
					
					if (!$this->use_keys) $key = $value;
					
					$out .= '<option value="'.$key.'"';
					
					if ((!is_null($this->selected)) && ($key == $this->selected))
					{
						$out .= ' selected';
					}
					
					$out .= '>'.$value.'</option>';
					
				}
				
				$out .= '</select>';
				
			}
			
			return($out);
			
		}
		
	}
	
	class Range_Select extends Select
	{
		
		protected $start;
		protected $end;
		protected $interval;
		protected $blank_first = FALSE;
		protected $use_keys = FALSE;
		
		public function __construct($name = NULL, $start = NULL, $end = NULL, $interval = NULL, $selected = NULL, $blank_first = NULL, $tab_index = NULL)
		{
			
			if (!is_null($name)) $this->Name($name);
			if (!is_null($start)) $this->Start($start);
			if (!is_null($end)) $this->End($end);
			if (!is_null($interval)) $this->Interval($interval);
			if (!is_null($selected)) $this->Selected($selected);
			if (!is_null($blank_first)) $this->Blank_First($blank_first);
			if (!is_null($tab_index)) $this->Tab_Index($tab_index);
			
		}
		
		public function Blank_First($blank = NULL)
		{
			
			if (is_bool($blank)) $this->blank_first = $blank;
			elseif (is_null($blank)) $blank = $this->blank_first;
			else $blank = NULL;
			
			return($blank);
			
		}		
		
		public function Start($start = NULL)
		{
			
			if (is_numeric($start)) $this->start = $start;
			elseif (is_null($start)) $start = $this->start;
			else $start = FALSE;
			
			return($start);
			
		}
		
		public function End($end = NULL)
		{
			
			if (is_numeric($end)) $this->end = $end;
			elseif (is_null($end)) $end = $this->end;
			else $end = FALSE;
			
			return($end);
			
		}
		
		public function Interval($interval = NULL)
		{
			
			if (is_numeric($interval)) $this->interval = $interval;
			elseif (is_null($interval)) $interval = $this->interval;
			else $interval = FALSE;
			
			return($interval);
			
		}
				
		protected function Generate()
		{
			
			$out = FALSE;
			
			if (is_numeric($this->start) && is_numeric($this->end) && is_numeric($this->interval))
			{
				
				$this->options = range($this->start, $this->end, $this->interval);
				if ($this->blank_first) $this->options = array(''=>'') + $this->options;
				
				$out = parent::Generate();
				
			}
			
			return($out);
			
		}
		
	}
	
	class Date_Select extends Select
	{
		
		protected $start_date;
		protected $end_date;
		protected $interval;
		protected $display;
		protected $value;
		protected $blank_first = FALSE;
		protected $default_today = TRUE;
		
		public function __construct($name = NULL, $start_date = NULL, $end_date = NULL, $interval = NULL, $selected = NULL, $display = NULL, $value = NULL, $default_today = NULL, $blank_first = NULL, $tab_index = NULL)
		{
			
			if (!is_null($name)) $this->Name($name);
			if (!is_null($start_date)) $this->Start_Date($start_date);
			if (!is_null($end_date)) $this->End_Date($end_date);
			if (!is_null($interval)) $this->Interval($interval);
			if (!is_null($selected)) $this->Selected($selected);
			if (!is_null($display)) $this->Display_Format($display);
			if (!is_null($value)) $this->Value_Format($value);
			if (!is_null($default_today)) $this->Default_Today($default_today);
			if (!is_null($blank_first)) $this->Blank_First($blank_first);
			if (!is_null($tab_index)) $this->Tab_Index($tab_index);
			
		}
		
		public function Blank_First($blank = NULL)
		{
			
			if (is_bool($blank)) $this->blank_first = $blank;
			elseif (is_null($blank)) $blank = $this->blank_first;
			else $blank = NULL;
			
			return($blank);
			
		}
		
		public function Default_Today($default = NULL)
		{
			
			if (is_bool($default)) $this->default_today = $default;
			elseif (is_null($default)) $default = $this->default_today;
			else $default = NULL;
			
			return($default);
			
		}
		
		public function Start_Date($start = NULL)
		{
			
			if (is_string($start)) $start = strtotime($start);
			
			if (is_numeric($start)) $this->start_date = $start;
			elseif (is_null($start)) $start = $this->start_date;
			else $start = FALSE;
			
			return($start);
			
		}
		
		public function End_Date($end = NULL)
		{
			
			if (is_string($end)) $end = strtotime($end);
			
			if (is_numeric($end)) $this->end_date = $end;
			elseif (is_null($end)) $end = $this->end_date;
			else $end = FALSE;
			
			return($end);
			
		}
		
		public function Display_Format($format = NULL)
		{
			
			if (is_string($format)) $this->display = $format;
			elseif (is_null($format)) $format = $this->display;
			else $format = FALSE;
			
			return($format);
			
		}
		
		public function Value_Format($format = NULL)
		{
			
			if (is_string($format)) $this->value = $format;
			elseif (is_null($format)) $format = $this->value;
			else $format = FALSE;
			
			return($format);
			
		}
		
		public function Interval($interval = NULL)
		{
			
			// simple validation
			if (is_string($interval) && preg_match('/\d+\s+\w+/', $interval) && (strtotime('+'.$interval)!==FALSE))
			{
				$this->interval = $interval;
			}
			elseif (is_null($interval))
			{
				$interval = $this->interval;
			}
			else
			{
				$interval = FALSE;
			}
			
			return($interval);
			
		}
		
		protected function Generate()
		{
			
			$out = FALSE;
			
			if (is_numeric($this->start_date) && is_numeric($this->end_date) && $this->interval && $this->value && $this->display)
			{
				
				$date = $this->start_date;
				
				$options = array();
				if ($this->blank_first) $options[''] = '';
				
				$end = strtotime('+'.$this->interval, $this->start_date);
				$end = ($this->end_date < $end) ? $end : $this->end_date;
				
				while ($date <= $end)
				{
					$options[date($this->value, $date)] = date($this->display, $date);
					$date = strtotime('+'.$this->interval, $date);
				}
				
				// default to today if we don't have anything selected
				if (is_null($this->selected) && $this->default_today)
				{
					$this->selected = date($this->value);
				}
				
				$this->options = $options;
				$out = parent::Generate();
				
			}
			
			return($out);
			
		}
		
	}
	
	class Block_Errors implements Token
	{
		
		protected $errors;
		
		public function __construct($errors = NULL)
		{
			
			if (is_array($errors)) $this->errors = $errors;
			
		}
		
		public function Value($value = NULL)
		{
			
			if (is_array($value))
			{
				$this->errors = $value;
			}
			elseif (is_null($value))
			{
				$value = $this->Generate();
			}
			else
			{
				$value = FALSE;
			}
			
			return($value);
			
		}
		
		protected function Generate()
		{
			
			$out = FALSE;
			
			if (count($this->errors))
			{
				
				$out = '<br/><div id="wf-trunk-errors-container">';
				$out .= '<div id="wf-trunk-errors-header">ERRORS</div>';
				$out .= '<div id="wf-trunk-error-body">';
				
				foreach ($this->errors as $error)
				{
					$out .= ' * '.$error.'<br/>';
				}
				
				$out .= '</div>';
				$out .= '<div id="wf-trunk-errors-footer">To continue, please correct the error(s) below.</div>';
				$out .= '</div><br/>';
				
			}
			
			return($out);
			
		}
		
	}
	
	class Tokens
	{
		
		const DAYS = 'days';
		const MONTHS = 'months';
		const YEARS = 'years';
		
		public static $direct_deposit = array(''=>'', 'FALSE'=>'Paper Check', 'TRUE'=>'Electronic Deposit', 'OTHER'=>'Other');
		public static $account_type = array(''=>'', 'CHECKING'=>'Checking', 'SAVINGS'=>'Savings');
		
		public static $best_call = array
		(
			'MORNING'=>'Morning (9:00 to 12:00)',
			'AFTERNOON' => 'Afternoon (12:00 to 5:00)',
			'EVENING' => 'Evening (5:00 - 9:00)'
		);
		
		public static $income_frequency = array('WEEKLY'=>'Weekly', 'BI_WEEKLY'=>'Bi-Weekly', 'TWICE_MONTHLY'=>'Twice Monthly',
			'MONTHLY'=>'Monthly');
		
		static function Select($name, $options, $selected = NULL, $class = NULL, $tab_index = NULL, $height = NULL, $multiple = FALSE, $event = NULL)
		{
			
			$out = '<select name="'.$name.'" id="'.$name.'"';
			if (!is_null($class)) $out .= ' class="'.$class.'"';
			if (!is_null($tab_index)) $out .= ' tabindex="'.$tab_index.'"';
			if (is_numeric($height)) $out .= ' size="'.$size.'"';
			if ($multiple===TRUE) $out.= ' multiple';
			if (!is_null($event)) $out .= ' '.$event;
			$out .= '>';
			
			foreach ($options as $key=>$value)
			{
				
				$out .= '<option value="'.$key.'"';
				if ($key == $selected) $out .= ' selected';
				$out .= '>'.$value.'</option>';
				
			}
			
			$out .= '</select>';
			return($out);
			
		}
		
		static function Date_Select($name, $start, $end, $format, $selected = NULL, $class = NULL, $tab_index = NULL, $height = NULL)
		{
		}
		
	}
	
	function Gen_Date_Select ($start, $end, $order="asc")
	{
		
		$select_html = '';
		$selected_value = '';
		
		switch (strtolower ($order))
		{
			case "asc":
				for ($i=$start; $i <= $end; $i++)
				{
					// Build the string
					$select_html .= "\t<option value='".$i."'".($selected_value == $i ? " selected=\"selected\"" : "").">".$i."</option>\n";
				}
				break;

			case "desc":
				for ($i=$end; $i >= $start; $i--)
				{
					// Build the string
					$select_html .= "\t<option value='".$i."'".($selected_value == $i ? " selected=\"selected\"" : "").">".$i."</option>\n";
				}
				break;
		}

		return $select_html;
	}

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
				"WY"=>"Wyoming"
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

		function State_Pulldown ($select_name, $select_value=0, $select_display=0, $selected_state="", $first_blank=true, $exclude="", $mult_height=0, $us_terr=false, $can_prov=false, $class=NULL, $tabindex=NULL, $disabled=0, $event_handler_string=NULL)
		{
			// Start building Select HTML
			$this->state_select_html .= "<select name=\"". $select_name . "\" id=\"". $select_name ."\"" . " onchange=\"_check_state(this.value)\"";
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
			if ($disabled != 0)
			{
				$this->state_select_html .= " disabled=\"disabled\"";
			}
			$this->state_select_html .= ">\n";
			$this->state_select_html .= $this->State_Option_List ($select_value, $select_display, $selected_state, $first_blank, $exclude, $us_terr, $can_prov);
			$this->state_select_html .= "</select>";

			return $this->state_select_html;
		}

		function State_Option_List ($select_value=0, $select_display=0, $selected_state="", $first_blank=true, $exclude="", $us_terr=false, $can_prov=false)
		{
			
			$option_list = '';
			
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

			if ($exclude != "")
			{
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

			if ($first_blank)
			{
				$option_list .= "<option></option>\n";
			}

			$option_list .= $this->State_Array_To_Options ($this->us_state_array, $select_value, $select_display, $selected_state);

			if ($us_terr)
				$option_list .= $this->State_Array_To_Options ($this->us_terr_array, $select_value, $select_display, $selected_state);

			if ($can_prov)
				$option_list .= $this->State_Array_To_Options ($this->canada_prov_array, $select_value, $select_display, $selected_state);

			return $option_list;
		}

		function State_Array_To_Options ($state_array, $select_value, $select_display, $selected_state)
		{
			
			$option_list = '';
			
			foreach ($state_array as $abbrev => $name)
			{
				$option_list .= "<option value=\"" . $this->Choose_State_Format ($abbrev, $name, $select_value) . "\"";
				if (($selected_state != "") && (strtolower ($abbrev) == strtolower ($selected_state)))
				{
					$option_list .= " selected=\"selected\"";
				}
				$option_list .= ">" . $this->Choose_State_Format ($abbrev, $name, $select_display) . "</option>\n";
			}

			return $option_list;
		}

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

	function Generate_Select_Options( $option_values , $selected_option)
	{
		if ($option_values)
		{
			$options = '';
			foreach($option_values as $key => $val)
			{
				$selected =  ($key == $selected_option) ? 'SELECTED' : '';
				$options .= "<option value='".$key."' ".$selected."> ".$val."\n";
			}

			return $options;
		}
		return false;
	}

	function Create_CC_Expiration_Date_Options ($selected_options)
	{
		list ($selected_month, $selected_year) = $selected_options;

		$options = array();
		$months = array();
		for($m = 1; $m<=12; $m++)
		{
			$m = ($m<10) ? '0'.$m : $m;
			$months[$m] = $m;
		}
		$options[] = Generate_Select_Options( $months , $selected_month);


		$years = array();
		$year_start = date("Y");
		for($y = $year_start; $y<=($year_start+10); $y++) $years[$y] = $y;
		$options[] = Generate_Select_Options( $years , $selected_year);

		return $options;
	}

	class birthdate
	{
		var $min_age;
		var $lifespan;
		var $month = "dobm";
		var $day = "dobd";
		var $year = "doby";
		var $single = FALSE;
		var $tabindex = FALSE;
		var $format = "0000-00-00";
		var $month_array = array(
			"01"=>"January",
			"02"=>"February",
			"03"=>"March",
			"04"=>"April",
			"05"=>"May",
			"06"=>"June",
			"07"=>"July",
			"08"=>"August",
			"09"=>"September",
			"10"=>"October",
			"11"=>"November",
			"12"=>"December");
		var $current_year;

		/* $name_str should be in one of two formats:
				"month_name/day_name/year_name" for separate fields, or
				"birthdate_name" for a single field to be submitted.
				Note: a single field submission will require that the
				"write_javascript" method be called in the document head.
		*/
		function birthdate ($minimum_age_int=18, $lifespan_int=99, $name_str="dobm/dobd/doby", $tabindex_int="")
		{
			$this->min_age = $minimum_age_int;
			$this->lifespan = $lifespan_int;
			$this->current_year = date ("Y");
			if ($tabindex_int != "")
			{
				$this->tabindex = $tabindex_int;
			}
			$name_array = explode ("/", $name_str);
			if (count ($name_array) == 3)
			{
				$this->month = $name_array [0];
				$this->day = $name_array [1];
				$this->year = $name_array [2];
			}
			elseif (count ($name_array) == 1)
			{
				$this->single = $name_array [0];
			}
			else
			{
				echo "Incorrect name format<br />\n";
				return FALSE;
			}
		}

		function write_javascript ()
		{
			if ($this->single)
			{
				echo "<script type=\"text/javascript\">\n";
				echo "\tfunction build_date (date_element_obj, date_element_type, date_obj_id){\n";
				echo "\t\tdate_obj = document.getElementById(date_obj_id);\n";

				echo "\t\tif (date_element_obj.value != \"\"){\n";

				echo "\t\t\tswitch(date_element_type){\n";

				echo "\t\t\t\tcase \"m\":\n";
				echo "\t\t\t\t\tm_str = date_obj.value;\n";
				echo "\t\t\t\t\tdate_obj.value = m_str.substr(0, 4) + \"-\" + date_element_obj.value + \"-\" + m_str.substr(8);\n";
				echo "\t\t\t\t\tbreak;\n";

				echo "\t\t\t\tcase \"d\":\n";
				echo "\t\t\t\t\td_str = date_obj.value;\n";
				echo "\t\t\t\t\tdate_obj.value = d_str.substr(0, 4) + \"-\" + d_str.substr(5, 2) + \"-\" + date_element_obj.value;\n";
				echo "\t\t\t\t\tbreak;\n";

				echo "\t\t\t\tcase \"y\":\n";
				echo "\t\t\t\t\ty_str = date_obj.value;\n";
				echo "\t\t\t\t\tdate_obj.value = date_element_obj.value + \"-\" + y_str.substr(5, 2) + \"-\" + y_str.substr(8);\n";
				echo "\t\t\t\t\tbreak;\n";

				echo "\t\t\t}\n";

				echo "\t\t}\n";

				echo "\t}\n";
				echo "</script>\n";
			}
		}
		/*Checks to see if two values are equal and if so returns the String selected*/
		function isSelectedOption($value1, $value2)
		{
			return (($value1 == $value2)?"selected=\"selected\"":"");
		}

		function display ($s_month="", $s_day="", $s_year="")
		{
			if ($this->single)
			{
				echo "<input type=\"hidden\" name=\"".$this->single."\" id=\"single_date\" value=\"".$this->format."\" />\n";
			}
			// month
			echo "<select name=\"".$this->month."\" id=\"".$this->month."\"";
			if ($this->single)
			{
				echo " onchange=\"build_date(this, 'm', 'single_date')\"";
			}
			if ($this->tabindex)
			{
				echo " tabindex=\"".$this->tabindex."\"";
			}
			echo ">\n";
			echo "\t<option value=\"\"></option>\n";
			foreach ($this->month_array as $key => $value)
			{
				echo "\t<option ".$this->isSelectedOption($key, $s_month)." value=\"".$key."\">".$value."</option>\n";
			}
			echo "</select>\n";

			// day
			echo "<select name=\"".$this->day."\" id=\"".$this->day."\"";
			if ($this->single)
			{
				echo " onchange=\"build_date(this, 'd', 'single_date')\"";
			}
			if ($this->tabindex)
			{
				echo " tabindex=\"".($this->tabindex+1)."\"";
			}
			echo ">\n";
			echo "\t<option value=\"\"></option>\n";
			for ($i=1; $i<32; $i++)
			{
				$day_val = $i < 10 ? "0".$i : $i;
				echo "\t<option ".$this->isSelectedOption($i, $s_day)." value=\"".$day_val."\">".$i."</option>\n";
			}
			echo "</select>\n";

			// year
			echo "<select name=\"".$this->year."\" id=\"".$this->year."\"";
			if ($this->single)
			{
				echo " onchange=\"build_date(this, 'y', 'single_date')\"";
			}
			if ($this->tabindex)
			{
				echo " tabindex=\"".($this->tabindex+2)."\"";
			}
			echo ">\n";
			echo "\t<option value=\"\"></option>\n";
			$start_year = $this->current_year - $this->min_age;
			for ($i=$start_year; $i>=($this->current_year-$this->lifespan);$i--)
			{
				echo "\t<option ".$this->isSelectedOption($i, $s_year)." value=\"".$i."\">".$i."</option>\n";
			}
			echo "</select>\n";


		}

		function create_dob_string ($s_month="", $s_day="", $s_year="")
		{
			$dob_string = "";
			if ($this->single)
			{
				$dob_string .= "<input type=\"hidden\" name=\"".$this->single."\" id=\"single_date\" value=\"".$this->format."\" />\n";
			}
			// month
			$dob_string .= "<select name=\"".$this->month."\" id=\"".$this->month."\"";
			if ($this->single)
			{
				$dob_string .= " onchange=\"build_date(this, 'm', 'single_date')\"";
			}
			if ($this->tabindex)
			{
				$dob_string .= " tabindex=\"".$this->tabindex."\"";
			}
			$dob_string .= ">\n";
			$dob_string .= "\t<option value=\"\"></option>\n";
			foreach ($this->month_array as $key => $value)
			{
				$dob_string .= "\t<option ".$this->isSelectedOption($key, $s_month)." value=\"".$key."\">".$value."</option>\n";
			}
			$dob_string .= "</select>\n";

			// day
			$dob_string .= "<select name=\"".$this->day."\" id=\"".$this->day."\"";
			if ($this->single)
			{
				$dob_string .= " onchange=\"build_date(this, 'd', 'single_date')\"";
			}
			if ($this->tabindex)
			{
				$dob_string .= " tabindex=\"".($this->tabindex+1)."\"";
			}
			$dob_string .= ">\n";
			$dob_string .= "\t<option value=\"\"></option>\n";
			for ($i=1; $i<32; $i++)
			{
				$day_val = $i < 10 ? "0".$i : $i;
				$dob_string .= "\t<option ".$this->isSelectedOption($i, $s_day)." value=\"".$day_val."\">".$i."</option>\n";
			}
			$dob_string .= "</select>\n";

			// year
			$dob_string .= "<select name=\"".$this->year."\" id=\"".$this->year."\"";
			if ($this->single)
			{
				$dob_string .= " onchange=\"build_date(this, 'y', 'single_date')\"";
			}
			if ($this->tabindex)
			{
				$dob_string .= " tabindex=\"".($this->tabindex+2)."\"";
			}
			$dob_string .= ">\n";
			$dob_string .= "\t<option value=\"\"></option>\n";
			$start_year = $this->current_year - $this->min_age;
			for ($i=$start_year; $i>=($this->current_year-$this->lifespan);$i--)
			{
				$dob_string .= "\t<option ".$this->isSelectedOption($i, $s_year)." value=\"".$i."\">".$i."</option>\n";
			}
			$dob_string .= "</select>\n";

			return $dob_string;
		}
	}
	
	class Direct_Deposit
	{
		
		
		
	}
	
?>
