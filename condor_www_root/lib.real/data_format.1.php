<?php

class Data_Format_1
{
	private $date_display;
	private $error_array;
		
	function __construct()
	{
		$this->date_display = "m/d/Y";		
		$this->error_array = array();
	}

	public function Display($type, &$value)
	{
		// Lets not format anything that has a blank value
		if( strlen( trim($value) ) )
		{
			switch ($type)
			{
				case "date":
					$value = date ($this->date_display, strtotime ($value));
				break;
	
				case "social":
					$value = substr ($value, 0, 3)."-".substr ($value, 3, 2)."-".substr ($value,5);
				break;
	
				case "phone":
					$value = "(".substr ($value, 0, 3).") ".substr ($value,3, 3)."-".substr ($value,6);
				break;
	
				case "phone2":
					$value = substr ($value, 0, 3)."-".substr ($value, 3, 3)."-".substr ($value,6);
				break;
				
				case "money":
					$value = sprintf ("%0.2f", $value);
				break;
					
				case "upper case":
					$value = strtoupper ($value);
				break;
	
				case "email":
				case "lower case":
					$value = strtolower ($value);
				break;
	
				case "smart_case":
					$value = ucwords (strtolower ($value));
				break;
				
				case "sentence":
					$value = ucfirst( strtolower($value) );
				break;
				
				case "zip":
					if(strlen($value) > 5) $value = substr($value, 0, 5) . "-" . substr($value, 5);
				break;
					
				default:
					$this->error_array[] = "{$type} is an invalid format";
				break;				
			}
		}

		return count($this->error_array) ? FALSE : TRUE;
	}
	
	public function Display_Many($list, &$data)
	{
		if( isset($list) && is_array($list) && isset($data) && count($data) )
		{
			foreach($list as $field => $format_type)
			{
				if( (is_object($data) && isset($data->{$field})) || (is_array($data) && isset($data[$field])) )
				{
					$this->Display($format_type, $data->{$field});					
				}
			}
			return TRUE;
		}
		return FALSE;
	}
	
	public function Get_Error_Array()
	{
		return $this->error_array;
	}
}

?>