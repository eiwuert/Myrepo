<?php

class Dropdown_Dates
{
	private $days;
	private $months;
	private $years;
	private $hours;
	private $minutes;
	private $day_selected;
	private $month_selected;
	private $year_selected;
	private $minute_selected;
	private $hour_selected;
	private $prefix;
	private $handler;


	public function __construct()
	{
		$this->days = range(1, 31);
		$this->months = range(1, 12);
		$this->years = range(date("Y", strtotime("next year")), 1905);
		$this->hours = range(00, 23);
		$this->minutes = range(1, 59);
		
		$this->day_selected = NULL;
		$this->month_selected = NULL;
		$this->year_selected = NULL;
		$this->hour_selected = NULL;
		$this->minute_selected = NULL;
		$this->handler = "";
		
		$this->prefix = "";		
	}
	
	public function Set_Handler_Code($code)
	{
		$this->handler = "onChange=\"{$code}\"";
		return true;
	}

	public function Set_Prefix($prefix)
	{
		$this->prefix = $prefix;
		
		return TRUE;
	}
	
	public function Use_Date_String($date_string)
	{
		if( !empty($date_string) )
		{
			$date_stamp = strtotime($date_string);
			
			$this->day_selected = date("j", $date_stamp);
			$this->month_selected = date("n", $date_stamp);
			$this->year_selected = date("Y", $date_stamp);

			return TRUE;
		}
		return FALSE;		
	}


	public function Set_Minute($minute_string)
	{
		if( !empty($minute_string) )
		{
			$this->minute_selected = $minute_string;
			return TRUE;
		}
		return FALSE;		
	}


	public function Set_Hour($hour_string)
	{
		if( !empty($hour_string) )
		{
			$this->hour_selected = $hour_string;
			return TRUE;
		}
		return FALSE;		
	}

	public function Set_Day($day_string)
	{
		if( !empty($day_string) )
		{
			$this->day_selected = $day_string;
			return TRUE;
		}
		return FALSE;		
	}

	public function Set_Month($month_string)
	{
		if( !empty($month_string) )
		{
			$this->month_selected = $month_string;
			return TRUE;
		}
		return FALSE;		
	}

	public function Set_Year($year_string)
	{
		if( !empty($year_string) )
		{
			$this->year_selected = $year_string;
			return TRUE;
		}
		return FALSE;		
	}
	
	public function Generate_Drop($drop_array, $selected = NULL)
	{
		$drop_str = "";
		
		if (!is_null($selected))
		{
			if (strlen($selected) < 2)
			{
				$selected = str_pad($selected, 2, '0', STR_PAD_LEFT);
			}
		}

		foreach($drop_array as $drop_item)
		{
			if (strlen($drop_item) < 2)
			{
				$drop_item_value = str_pad($drop_item, 2, '0', STR_PAD_LEFT);
			}
			else
			{
				$drop_item_value = $drop_item;
			}

			if( !is_null($selected) && $drop_item_value == $selected )
			{
				$drop_str .= "<option value=\"{$drop_item_value}\" selected>{$drop_item_value}</option>\n";
			}
			else
			{
				$drop_str .= "<option value=\"{$drop_item_value}\">{$drop_item_value}</option>\n";
			}
		}
		return $drop_str;
	}
	
	public function Set_Range($set, $start, $end)
	{
		if( in_array( strtolower($set), array('days', 'months', 'years') ) )
		{
			if( is_int($start) && is_int($end) && $start > $end )
			{
				$this->{$set} = range($start, $end);
			}
		}
		return FALSE;
	}
	
	public function Fetch_Drop_Days($day = NULL)
	{
		$day = !is_null($day) ? $day : $this->day_selected;
		
		return <<<EOD
<SELECT id="{$this->prefix}day" name="{$this->prefix}day" {$this->handler}>  
{$this->Generate_Drop($this->days, $day)}
</SELECT>
EOD;
	}
	
	public function Fetch_Drop_Months($month = NULL)
	{
		$month = !is_null($month) ? $month : $this->month_selected;
		
		return <<<EOD
<SELECT id="{$this->prefix}month" name="{$this->prefix}month" {$this->handler}> 
{$this->Generate_Drop($this->months, $month)}
</SELECT>
EOD;
        }
	
	public function Fetch_Drop_Years($year = NULL)
	{
		$year = !is_null($year) ? $year : $this->year_selected;
		
		return <<<EOD
<SELECT id="{$this->prefix}year" name="{$this->prefix}year" {$this->handler}> 
{$this->Generate_Drop($this->years, $year)} 
</SELECT>
EOD;
	}

	public function Fetch_Drop_Hours($hours = NULL)
	{
		$hours = !is_null($hours) ? $hours : $this->hour_selected;
		
		return <<<EOD
<SELECT id="{$this->prefix}hour" name="{$this->prefix}hour" {$this->handler}> 
{$this->Generate_Drop($this->hours, $hours)} 
</SELECT>
EOD;
	}


	public function Fetch_Drop_Minutes($minutes = NULL)
	{
		$minutes = !is_null($minutes) ? $minutes : $this->minute_selected;
		
		return <<<EOD
<SELECT id="{$this->prefix}minute" name="{$this->prefix}minute" {$this->handler}> 
{$this->Generate_Drop($this->minutes, $minutes)} 
</SELECT>
EOD;
	}


	public function Fetch_Drop_All($day = NULL, $month = NULL, $year = NULL)
	{
		return $this->Fetch_Drop_Months($month) . $this->Fetch_Drop_Days($day) . $this->Fetch_Drop_Years($year);
	}


	public function Fetch_Drop_All_WITH_HOUR_MIN($day = NULL, $month = NULL, $year = NULL, $hour = NULL, $minute = NULL)
	{
		return $this->Fetch_Drop_Months($month) . $this->Fetch_Drop_Days($day) . $this->Fetch_Drop_Years($year) . " <b>:</b> " . $this->Fetch_Drop_Hours($hour) . $this->Fetch_Drop_Minutes($minute);
	}

}

?>
