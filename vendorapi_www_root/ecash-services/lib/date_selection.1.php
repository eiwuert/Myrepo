<?php
	// Version 1.0.2

	/* DESIGN TYPE
		static
	*/

	/* UPDATES
		Features:
			Displays a <select> form item with years, months or days with options for different variable data/display and multiple select.

		Added:
			Version 1.0.1 - added arguments for class and tabindex

		Bugs:
	*/

	/* PROTOTYPES
		bool Date_Selection ()
		string Year_Pulldown ($select_name, $select_value=0, $select_display=0, $selected_year=-1, $prev_years=0, $post_years=5, $first_blank=true, $mult_height=0, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
		string Month_Pulldown ($select_name, $select_value=1, $select_display=0, $first_blank=false, $selected_month=-1, $mult_height=1, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
		string Day_Pulldown ($select_name, $select_value=0, $select_display=0, $selected_day=-1, $first_blank=false, $specific_date="", $mult_height=0, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
		string Year_Option_List ($select_value=0, $select_display=0, $selected_year=-1, $prev_years=0, $post_years=5, $first_blank=true)
		string Month_Option_List ($select_value=1, $select_display=0, $first_blank=false, $selected_month=-1)
		string Day_Option_List ($select_value=0, $select_display=0, $selected_day=-1, $first_blank=false, $specific_date="")
	*/

	/* OPTIONAL CONSTANTS
	*/

	/* SAMPLE USAGE
	Year_Pulldown ()
		Arguments:
 		$select_name: string - the name of the actual select item
  		$select_value: int - value of select options: 0 = "yy", 1 = "yyyy"
  		$select_display: int - display of select options: 0 = "yy", 1 = "yyyy"
  		$selected_year: int - 0 = none selected, 4 digit year OR if excluded, will default to current year
  		$prev_years: int - # of years to display before current year
		$post_years: int - # of years to display after current year (can be negative number)
		$first_blank: boolean - false makes first option blank
  		$mult_height: int - row height of selection box (0 same as 1), also allows multiple select
  		$class: string - string name of stylesheet class
  		$tabindex: int - tabindex
  		$event_handler_string: string - event handler string as a string variable, or escaped string (\" can be substituted with ') - "onclick=\"check_Field();\" onchange=\"verify_Selection();\""

	Year_Option_List ()
		Arguments:
  		$select_value: int - value of select options: 0 = "yy", 1 = "yyyy"
  		$select_display: int - display of select options: 0 = "yy", 1 = "yyyy"
  		$selected_year: int - 0 = none selected, 4 digit year OR if excluded, will default to current year
  		$prev_years: int - # of years to display before current year
		$post_years: int - # of years to display after current year (can be negative number)
		$first_blank: boolean - false makes first option blank

  		Examples:
  		$date = new Date_Selection;
  		$date->Year_Pulldown ("year", 0, 1, -1, 0, 4, false, 3, "style1", 3, "onclick=\"check_Field();\" onchange=\"verify_Selection();\"");
  		OR can simply be used with default settings:
  		$date = new Date_Selection;
  		$date->Year_Pulldown ("year");

  	Month_Pulldown ()
		Arguments:
 		$select_name: string - the name of the actual select item
  		$select_value: int - value of select options: 0 = "m", 1 = "mm", 2 = "Jan", 3 = "January"
  		$select_display: int - display of select options: 0 = "m", 1 = "mm", 2 = "Jan", 3 = "January"
  		$first_blank: boolean - false makes first option blank
  		$selected_month: int - 0 = none selected, # = # of month (1 -12), if excluded, will default to current month
  		$mult_height: int - row height of selection box (0 same as 1), also allows multiple select
  		$class: string - string name of stylesheet class
  		$tabindex: int - tabindex
  		$event_handler_string: string - event handler string as a string variable, or escaped string (\" can be substituted with ') - "onclick=\"check_Field();\" onchange=\"verify_Selection();\""

  	Month_Option_List ()
		Arguments:
  		$select_value: int - value of select options: 0 = "m", 1 = "mm", 2 = "Jan", 3 = "January"
  		$select_display: int - display of select options: 0 = "m", 1 = "mm", 2 = "Jan", 3 = "January"
  		$first_blank: boolean - false makes first option blank
  		$selected_month: int - 0 = none selected, # = # of month (1 -12), if excluded, will default to current month

  		Examples:
  		$date = new Date_Selection;
  		$date->Month_Pulldown ("month", 0, 1, true, 0, 3, "style1", 3, "onclick=\"check_Field();\" onchange=\"verify_Selection();\"");
  		OR can simply be used with default settings:
  		$date = new Date_Selection;
  		$date->Month_Pulldown ("month");

  	Day_Pulldown ()
		Arguments:
 		$select_name: string - the name of the actual select item
  		$select_value: int - value of select options: 0 = "d", 1 = "dd"
  		$select_display: int - display of select options: 0 = "d", 1 = "dd"
  		$selected_day: int - 0 = none selected, number of day OR if excluded, will default to current day
		$first_blank: boolean - false makes first option blank
		$specific_date: string - displays days of (mm-yyyy) entered
  		$mult_height: int - row height of selection box (0 same as 1), also allows multiple select
  		$class: string - string name of stylesheet class
  		$tabindex: int - tabindex
  		$event_handler_string: string - event handler string as a string variable, or escaped string (\" can be substituted with ') - "onclick=\"check_Field();\" onchange=\"verify_Selection();\""

  	Day_Option_List ()
		Arguments:
  		$select_value: int - value of select options: 0 = "d", 1 = "dd"
  		$select_display: int - display of select options: 0 = "d", 1 = "dd"
  		$selected_day: int - 0 = none selected, number of day OR if excluded, will default to current day
		$first_blank: boolean - false makes first option blank
		$specific_date: string - displays days of (mm-yyyy) entered

  		Examples:
  		$date = new Date_Selection;
  		$date->Day_Pulldown ("day", 0, 1, -1, true, "4-2003", 3, "style1", 3, "onclick=\"check_Field();\" onchange=\"verify_Selection();\"");
  		OR can simply be used with default settings:
  		$date = new Date_Selection;
  		$date->Day_Pulldown ("day");
	*/

class Date_Selection
{
	var $cur_year;
	var $cur_month;
	var $cur_day;
	var $year_html;
	var $month_html;
	var $day_html;

	function Date_Selection ()
	{
		$this->cur_year = date ("Y");
		$this->cur_month = date ("n");
		$this->cur_day = date ("j");
		$this->year_html = "";
		$this->month_html = "";
		$this->day_html = "";

		return true;
	}

	function Year_Pulldown ($select_name, $select_value=0, $select_display=1, $selected_year=-1, $prev_years=0, $post_years=5, $first_blank=false, $mult_height=0, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
	{
		$this->year_html = "<select name=\"" . $select_name . "\"";
		if ($class != NULL)
		{
			$this->year_html .= " class=\"" . $class . "\"";
		}
		if ($tabindex != NULL)
		{
			$this->year_html .= " tabindex=\"" . $tabindex . "\"";
		}
		if ($event_handler_string != NULL)
		{
			$this->year_html .= " " . trim ($event_handler_string);
		}
		if ($mult_height > 1)
		{
			$this->year_html .= " size=\"" . $mult_height . "\" multiple";
		}
		$this->year_html .= ">\n";
		$this->year_html .= $this->Year_Option_List ($select_value, $select_display, $selected_year, $prev_years, $post_years, $first_blank);
		$this->year_html .= "</select>";

		return $this->year_html;
	}

	function Year_Option_List ($select_value=0, $select_display=1, $selected_year=-1, $prev_years=0, $post_years=5, $first_blank=false)
	{
		if ($first_blank)
		{
			$option_list .= "<option></option>\n";
		}
		$beg_year = $this->cur_year - $prev_years;
		$end_year = $this->cur_year + $post_years;
		for ($i = $beg_year; $i <= $end_year; $i++)
		{
			$formatted_year = $this->choose_Year_Format ($i, $select_value);
			$option_list .= "<option value=\"$formatted_year\"";
			if (( $formatted_year == $selected_year) || (($i == $this->cur_year) && ($selected_year == -1)))
			{
				$option_list .= " selected";
			}
			$option_list .= ">" . $this->choose_Year_Format ($i, $select_display) . "</option>\n";
		}

		return $option_list;
	}

	function Month_Pulldown ($select_name, $select_value=1, $select_display=0, $first_blank=false, $selected_month=-1, $mult_height=1, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
	{
		$this->month_html = "<select name=\"" . $select_name . "\"";
		if ($class != NULL)
		{
			$this->month_html .= " class=\"" . $class . "\"";
		}
		if ($tabindex != NULL)
		{
			$this->month_html .= " tabindex=\"" . $tabindex . "\"";
		}
		if ($event_handler_string != NULL)
		{
			$this->month_html .= " " . trim ($event_handler_string);
		}
		if ($mult_height > 1)
		{
			$this->month_html .= " size=\"" . $mult_height . "\" multiple";
		}
		$this->month_html .= ">\n";
		$this->month_html .= $this->Month_Option_list ($select_value, $select_display, $first_blank, $selected_month);
		$this->month_html .= "</select>";

		return $this->month_html;
	}

	function Month_Option_list ($select_value=1, $select_display=0, $first_blank=false, $selected_month=-1)
	{
		// Determine $month_value
		switch ($select_value)
		{
			case "0":
				$month_value = "mon";
				break;
			case "1":
				$month_value = "mon";
				break;
			case "2":
				$month_value = "month";
				break;
			case "3":
				$month_value = "month";
				break;
			default:
				break;
		}
		// Determine $month_display
		switch ($select_display)
		{
			case "0":
				$month_display = "mon";
				break;
			case "1":
				$month_display = "mon";
				break;
			case "2":
				$month_display = "month";
				break;
			case "3":
				$month_display = "month";
				break;
			default:
				break;
		}

		if ($first_blank)
		{
			$option_list .= "<option></option>\n";
		}
		for ($i = 1; $i <= 12; $i++)
		{
			$cur_stamp = getdate (mktime (0,0,0,$i,1,2000));
			$option_list .= "<option value=\"" . $this->strip_Month($cur_stamp[$month_value], $select_value) . "\"";
			if (($i == $selected_month) || (($i == $this->cur_month) && ($selected_month == -1)))
			{
					$option_list .= " selected";
			}
			$option_list .= ">" . $this->strip_Month ($cur_stamp[$month_display], $select_display) . "</option>\n";
		}

		return $option_list;
	}

	function Day_Pulldown ($select_name, $select_value=0, $select_display=0, $selected_day=-1, $first_blank=false, $specific_date="", $mult_height=0, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
	{
		$this->day_html = "<select name=\"" . $select_name . "\"";
		if ($class != NULL)
		{
			$this->day_html .= " class=\"" . $class . "\"";
		}
		if ($tabindex != NULL)
		{
			$this->day_html .= " tabindex=\"" . $tabindex . "\"";
		}
		if ($event_handler_string != NULL)
		{
			$this->day_html .= " " . trim ($event_handler_string);
		}
		if ($mult_height > 1)
		{
			$this->day_html .= " size=\"" . $mult_height . "\" multiple";
		}
		$this->day_html .= ">\n";
		$this->day_html .= $this->Day_Option_List ($select_value, $select_display, $selected_day, $first_blank, $specific_date);
		$this->day_html .= "</select>";

		return $this->day_html;
	}

	function Day_Option_List ($select_value=0, $select_display=0, $selected_day=-1, $first_blank=false, $specific_date="")
	{
		if ($first_blank)
		{
			$option_list .= "<option></option>\n";
		}
		$days_limit = 31;
		if ($specific_date != "")
		{
			$date_array = explode ("-", $specific_date);
			$days_limit = date("t", mktime(0, 0, 0, $date_array[0], 1, $date_array[1]));
		}
		for ($i = 1; $i <= $days_limit; $i++)
		{
			$option_list .= "<option value=\"" . $this->choose_Day_Format ($i, $select_value) . "\"";
			if (($i == $selected_day) || (($i == $this->cur_day) && ($selected_day == -1)))
			{
				$option_list .= " selected";
			}
			$option_list .= ">" . $this->choose_Day_Format ($i, $select_display) . "</option>\n";
		}

		return $option_list;
	}

	function choose_Year_Format ($y_value, $value_select)
	{
		if ($value_select == "0")
		{
			$y_value = substr ($y_value, 2, 2);
		}
		return $y_value;
	}

	function strip_Month ($m_value, $value_select)
	{
		if (($value_select == "1") && (strlen ($m_value) == 1))
		{
				$m_value = "0" . $m_value;
		}
		elseif ($value_select == "2")
		{
			$m_value = substr ($m_value, 0, 3);	
		}
		
		return $m_value;
	}
	
	function choose_Day_Format ($d_value, $value_select)
	{
		if (($value_select == "1") && (strlen ($d_value) == 1))
		{
			$d_value = "0" . $d_value;
		}	
		
		return $d_value;
	}
}
?>
