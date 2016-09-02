<?php
	// Version 1.0.0
	
	/* DESIGN TYPE
		static
	*/

	/* UPDATES
		Features:
			Displays a <select> item from an array of items.

		Bugs:
	*/

	/* PROTOTYPES
		bool Select ()
		string Pulldown ($soruce, $select_name, $selected_value="", $first_blank=true, $exclude="", $mult_height=0, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
	*/
	
	/* OPTIONAL CONSTANTS
	*/

	/* SAMPLE USAGE
		Arguments: 
		$source: string/array - 
 		$select_name: string - the name of the actual select item
  		$selected_value: string - "" = none selected, else string value of item to be selected by default
  		$key_to_index: bool - sets key to 0-based index, else uses value or original key
  		$first_blank: boolean - false makes first option blank
  		$exclude: string - comma delimited string of uppercase values to exclude from list - "NM,UT,AL", compares with the option 'value' attribute
  		$mult_height: int - row height of selection box (0 same as 1), also allows multiple select
  		$class: string - string name of stylesheet class
  		$tabindex: int - tabindex
  		$event_handler_string: string - event handler string as a string variable, or escaped string (\" can be substituted with ') - "onclick=\"check_Field();\" onchange=\"verify_Selection();\""

  		Examples:
  		$new_select = new Select;
  		$new_select->Pulldown ("American Express,Visa,Mastercard", "my_field", "Visa", true, true, "Mastercard", 0, "style1", 5, "onclick=\"check_Field();\" onchange=\"verify_Selection();\"");
  		OR can simply be used with default setting:
  		$new_select = new Select;
  		$new_select->Pulldown ("American Express,Visa,Mastercard", "my_field");
	*/

	/* REQUIRED DB TABLE STRUCTURE
	*/
  
class Select
{    
	var $select_html;
	       
	function Select ()
	{
		$this->select_html = "";
		
		return true;
	}
	
	function Pulldown ($source, $select_name, $selected_value="", $key_to_index=false, $first_blank=true, $exclude="", $mult_height=0, $class=NULL, $tabindex=NULL, $event_handler_string=NULL)
	{		 
		$exclude_array = explode (",", $exclude);
		$key_equals_value = false;
		$key_count = 0;
		
		// Start building Select HTML
		$this->select_html .= "<select name=\"$select_name\" id=\"$select_name\" ";
		if ($class != NULL)
		{
			$this->select_html .= " class=\"" . $class . "\"";	
		}
		if ($tabindex != NULL)
		{
			$this->select_html .= " tabindex=\"" . $tabindex . "\"";	
		}
		if ($event_handler_string != NULL)
		{
			$this->select_html .= " " . trim ($event_handler_string);	
		}
		if ($mult_height > 1)
		{
			$first_blank = false;
			$this->select_html .= " size=\"" . $mult_height . "\" multiple";
		}
		$this->select_html .= ">\n";
		if ($first_blank)
		{
			$this->select_html .= "<option></option>\n";
		}
		
		if (gettype ($source) == "string")
		{
			// check if there are enough key/value pairs in string
			if (preg_match_all("/::/", $source, $matches) > preg_match_all("/\|/", $source, $matches))
			{
				$source_split = explode ("|", $source);
				foreach ($source_split as $key_value)
				{
					$key_value_split = explode ("::", $key_value);
					$source_array["" . $key_value_split[0] . ""] = $key_value_split[1];
				}
			}
			else 
			{
				$source_array = explode ("|", $source);
				$key_equals_value = true;
			}
			
			$source = $source_array;
		}
		
		foreach ($source as $key => $value)
		{
			if ($key_to_index)
			{
				$key = $key_count;
			}
			elseif ($key_equals_value)
			{
				$key = $value;
			}
			if (!in_array("".$key."", $exclude_array))
			{
				$this->select_html .= "<option value=\"" . $key . "\"";
				if ($key == $selected_value)
				{
					$this->select_html .= " selected";	
				}
				$this->select_html .= ">" . $value . "</option>\n";	
			}
			
			$key_count++;
		}
		
		$this->select_html .= "</select>";
		
		return $this->select_html;
	}
}
?>
