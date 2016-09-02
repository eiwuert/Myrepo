<?php

class Pay_Date_Calc_2
{
	private $holiday_array;
	private $model_data;
	private $start_timestamp;
	private $days_of_week;
	private $pay_dates;
	private $function_executed;
	private $model_list;
	private $old_name_map;
	public $last_pay_frequency;
	
	public function __construct($holiday_array = array())
	{
		$this->holiday_array = $holiday_array;
		$this->current_model_data = array();
		$this->days_of_week = array("sun" => "sunday","mon" => "monday", "tue" => "tuesday","wed" => "wednesday","thu" => "thursday",
								"fri" => "friday","sat" => "saturday");
		
		$this->model_list = array("weekly_on_day" => array("day_string_one"),
								"every_other_week_on_day" => array("day_string_one","last_paydate"),
								"twice_per_month_on_days" => array("day_int_one","day_int_two"),
								"twice_per_month_on_week_and_day" => array("week_one","week_two","day_string_one"),
								"monthly_on_week_and_day" => array("week_one","day_string_one"),
								"monthly_on_day" => array("day_int_one"),
								"monthly_on_day_of_week_after_day" => array("day_int_one","day_string_one") );
								
		$this->old_name_map = array("dw" => "weekly_on_day", "dwpd" => "every_other_week_on_day","dmdm" => "twice_per_month_on_days",
								"wwdw" => "twice_per_month_on_week_and_day", "dm" => "monthly_on_day",
								"wdw" => "monthly_on_week_and_day", "dwdm" => "monthly_on_day_of_week_after_day");
	}
				
	public function Calculate_Pay_Dates($model_name, $model_data, $direct_deposit = TRUE, $num_dates = 4, $start_date = "now")
	{
		// Check for old style model name
		if( isset($this->old_name_map[strtolower($model_name)]) )
			$model_name = $this->old_name_map[strtolower($model_name)];
					
		// Check for the existence of model_name in our valid model name list.
		if( !array_key_exists( strtolower($model_name), $this->model_list) )
		{
			throw new Exception("Unknown model received: {$model_name}");
		}

		// Validate the start date
		if( !empty($start_date) && trim($start_date) != "" )
		{			
			// Use strtotime to convert start date to a timestamp, start date must be a format recognized by strtotime
			$start_timestamp = strtotime($start_date);
											
			// Check for a valid date
			if( $start_timestamp === FALSE || $start_timestamp == -1)
			{				
				throw new Exception("Start date is not valid or was not recognized by strtotime: {$start_date}");				
			}
			
			// We want our timestamp to be set to noon for the given day
			$start_timestamp = mktime(12,0,0,date("m", $start_timestamp), date("d", $start_timestamp), date("Y", $start_timestamp) );
		}
		else
		{
			throw new Exception("Start date is a required parameter and cannot be empty");
		}
					
		// Valid direct deposit boolean
		if( !is_bool($direct_deposit) )
		{
			throw new Exception("Direct deposit needs to be boolean: {$direct_deposit}");
		}
						
		// Initialize
		$this->start_timestamp = $start_timestamp;
		$this->model_data = $model_data;
		$this->pay_dates = array();
		$this->function_executed = FALSE;
		
		// Do we have all required data for the module and is it valid?
		foreach($this->model_list[$model_name] as $field)
		{
			$this->Validate_Field($field);
		}
		
		// Find future pay dates after this point
		$stamp = $start_timestamp;
		
		// Save the frequency of the model
		switch($model_name)
		{
			case "weekly_on_day":
				$this->last_pay_frequency = "weekly";
			break;
			
			case "every_other_week_on_day":
				$this->last_pay_frequency = "bi-weekly";
			break;
			case "twice_per_month_on_days":			
			case "twice_per_month_on_week_and_day":
				$this->last_pay_frequency = "twice-monthly";
			break;
			
			case "monthly_on_week_and_day":
			case	"monthly_on_day":
			case "monthly_on_day_of_week_after_day":
				$this->last_pay_frequency = "monthly";
			break;
		}
		
		do 
		{
			// Call the function for this model
			$pay_stamp = $this->{$model_name}($stamp);
									
			// Set stamp to the stamp returned from above functions before holiday/weekend adjustment
			$stamp = $pay_stamp;
			
			// Direct deposit needs to move forward a day
			if( !$direct_deposit )
				$pay_stamp = strtotime("+1 day", $pay_stamp);
							
			// If its a weekend or holiday move forward or backward depending on direct deposit
			while( $this->Is_Holiday($pay_stamp) || $this->Is_Weekend($pay_stamp) )
			{
				$pay_stamp = $direct_deposit ? strtotime("-1 day", $pay_stamp): strtotime("+1 day", $pay_stamp);				
			}
			
			// Did we get a non valid result?
			if( $pay_stamp === FALSE || $pay_stamp == -1 )
				throw new Exception("Strtotime failed, range of 1970-2038 exceeded or something bad happened");
			
			// We only want paydates after the starting point
			if( $pay_stamp > $start_timestamp )
				$this->pay_dates[] = date("Y-m-d", $pay_stamp);

		} while( count($this->pay_dates) < $num_dates );				
		
		return $this->pay_dates;
	}
	
	// Validate the available field types
	private function Validate_Field($field)
	{
		if( empty($this->model_data[$field]) || trim($this->model_data[$field]) == "" )
			throw new Exception("{$field} is required for this model type");
						
		switch($field)
		{
			case "day_string_one":
				// If we have a short version of the name, change it to a long
				if( isset($this->days_of_week[strtolower($this->model_data['day_string_one'])]) )
					$this->model_data['day_string_one'] = $this->days_of_week[strtolower($this->model_data['day_string_one'])];
			
				if( !in_array( strtolower($this->model_data['day_string_one']), $this->days_of_week) )
					throw new Exception("{$this->model_data['day_string_one']} is not a valid day of week");
			break;
			
			case "day_int_one":
			case "day_int_two":
				if( !is_numeric($this->model_data[$field]) || $this->model_data[$field] < 1 || $this->model_data[$field] > 32 )
					throw new Exception("{$this->model_data[$field]} is not valid for {$field}");
			break;
			
			case "week_one":
			case "week_two":
				if( is_numeric($this->model_data[$field]) )
				{
					$this->model_data[$field] = (int) $this->model_data[$field];
					
					if( $this->model_data[$field] < 1 || $this->model_data[$field] > 5 )
						throw new Exception("{$this->model_data[$field]} is not valid for {$field}");
				}
				else
				{
					throw new Exception("{$this->model_data[$field]} is not valid for {$field}");
				}			
			break;
			
			case "last_paydate":
				$stamp = strtotime("{$this->model_data['last_paydate']} 12:00");
				if( ($stamp === FALSE || $stamp == -1) )
				{
					throw new Exception("Start date is not valid or was not recognized by strtotime: {$this->model_data['last_paydate']}");
				}
			break;
		}
		return TRUE;
	}
	
	private function Weekly_On_Day($start_stamp)
	{
		if( !$this->function_executed )
		{
			$this->function_executed = TRUE;
			return strtotime("this {$this->model_data['day_string_one']} 12:00", $start_stamp);
		}		
		return strtotime("next {$this->model_data['day_string_one']} 12:00", $start_stamp);
	}
	
	private function Every_Other_Week_On_Day($start_stamp)
	{
		if ($this->function_executed)
		{
			return strtotime("+2 weeks", $start_stamp);		
		}

		$day_of_week_map = array_flip(array_keys($this->days_of_week));

		$dow = strtotime($this->model_data['last_paydate']);
		$date_info = getdate($dow);

		$difference = $day_of_week_map[$this->model_data['paydate']['biweekly_day']] - $date_info['wday'];
		if ($difference > 3)
		{
			$difference = $difference - 7;
		}
		elseif ($difference < -3)
		{
			$difference = 7 + $difference;
		}

		$dow = mktime(12,0,0,date("m", $dow), date("d", $dow) + $difference,
			      date("Y", $dow));

		$this->function_executed = TRUE;
		return $dow;
	}
	
	private function Monthly_On_Day($start_stamp)
	{
		$year = date("Y", $start_stamp);
		$month = date("m", $start_stamp);
		$day = $this->model_data['day_int_one'];		
				
		if( $day > date("d", $start_stamp) && !$this->function_executed )
		{
			$day = min($day, date("t", $start_stamp));
			$this->function_executed = TRUE;				
			return mktime(12,0,0,$month,$day,$year);
		}
		
		$next_month = mktime(12,0,0,$month+1,1,$year);
		$day = min($day, date("t", $next_month));
		return  mktime(12,0,0,$month+1,$day,$year);		
	}
	
	private function Twice_Per_Month_On_Days($start_stamp)
	{
		$year = date("Y", $start_stamp);
		$month = date("m", $start_stamp);
		$day1 = min( $this->model_data['day_int_one'], date("t", $start_stamp) );
		$day2 = min( $this->model_data['day_int_two'], date("t", $start_stamp) );
		
		if( strtotime("{$year}-{$month}-{$day1} 12:00") > $start_stamp )
		{
			$return_stamp = strtotime("{$year}-{$month}-{$day1} 12:00");
		}
		elseif( strtotime("{$year}-{$month}-{$day2} 12:00") > $start_stamp )
		{
			$return_stamp = strtotime("{$year}-{$month}-{$day2} 12:00");
		}
		else
		{
			$next_month = mktime(12,0,0,$month+1,1,$year);
			$day1 = min($this->model_data['day_int_one'], date("t", $next_month) );
			$return_stamp = mktime(12,0,0,$month+1,$day1,$year);
		}
		return $return_stamp;
	}
	
	private function Twice_Per_Month_On_Week_And_Day($start_stamp)
	{
		return $this->Week_And_Day($start_stamp, $this->model_data['day_string_one'], $this->model_data['week_one'], $this->model_data['week_two']);
	}
	
	private function Monthly_On_Week_And_Day($start_stamp)
	{
		return $this->Week_And_Day($start_stamp, $this->model_data['day_string_one'], $this->model_data['week_one']);
	}
	
	private function Monthly_On_Day_Of_Week_After_Day($start_stamp)
	{
		$year = date("Y", $start_stamp);
		$month = date("m", $start_stamp);
		$day = min($this->model_data['day_int_one'], date("t", $start_stamp));
							
		if( $day > date("d", $start_stamp) )
		{
			$this->function_executed = TRUE;
			$operator = ( strcasecmp( date("l", mktime(12,0,0,$month,$day,$year)), $this->model_data['day_string_one'] ) == 0 ) ? "next": "this";
			return strtotime("{$operator} {$this->model_data['day_string_one']} 12:00", mktime(12,0,0,$month,$day,$year));
		}
		
		$next_month = mktime(12,0,0,$month+1,1,$year);		
		$day = min($this->model_data['day_int_one'], date("t", $next_month));
		$operator = ( strcasecmp( date("l", mktime(12,0,0,$month+1,$day,$year)), $this->model_data['day_string_one'] ) == 0 ) ? "next": "this";
				
		return strtotime("{$operator} {$this->model_data['day_string_one']} 12:00", mktime(12,0,0,$month+1,$day,$year));
	}
			
	// Helper function for twice per month on week/day and monthly on week/day
	private function Week_And_Day($start_stamp, $day, $week_one, $week_two = -1)
	{
		$stamp = $start_stamp;
		
		for($i = 0; $i < 10000; $i++)
		{
			if( !isset($last_stamp) || $stamp > strtotime("+1 month", $last_stamp) )
			{
				$stamp = strtotime( date("Y-m", $stamp) . "-01" . " 12:00");
				$stamp = strtotime("this {$day} 12:00", $stamp);
				$last_stamp = $stamp;
				$week = 1;
			}
								
			if( ($week == $week_one || $week == $week_two) && $stamp > $start_stamp && strcasecmp( date("l", $stamp), $day ) == 0 )
				return $stamp;
			
			$stamp = strtotime("next {$day} 12:00", $stamp);
			$week++;
		}
		return FALSE;
	}
	
	// Returns true if the date is saturday or sunday
	private function Is_Weekend($timestamp)
	{
		return in_array( date("w", $timestamp), array(0,6) ) ? TRUE: FALSE;
	}
	
	// Returns true if the date is found in the holiday array
	private function Is_Holiday($timestamp)
	{
		foreach($this->holiday_array as $holiday)
		{
			if( date("Y-m-d", strtotime("{$holiday}")) == date("Y-m-d", $timestamp) )
				return TRUE;
		}
		return FALSE;
	}
	
	// Helper function to return the next or last business day
	private function Business_Day($date, $type = "forward")
	{
		$date_stamp = strtotime($date);
		$str_operation = strtolower($type) == "forward" ? "+1 day" : "-1 day";
		$date_stamp = strtotime($str_operation, $date_stamp);
		
		if( $date_stamp === FALSE || $date_stamp == -1)
		{
			throw new Exception("Date is not valid or was not recognized by strtotime: {$date}");
		}
						
		while( $this->Is_Weekend($date_stamp) || $this->Is_Holiday($date_stamp) )
		{
			$date_stamp = strtotime($str_operation, $date_stamp);
		}	
		return date("Y-m-d", $date_stamp);			
	}
	
	// Shift all dates that can be recognized by strtotime in an array either forward or backward one business day
	public function Shift_Dates($date_array, $direction = "forward")
	{
		$shifted_dates = array();
		
		if( is_array($date_array) && count($date_array) )
		{
			foreach($date_array as $date)
			{
				$shifted_dates[] = $this->Business_Day($date, $direction);
			}
		}
		return $shifted_dates;
	}
	
	// Returns the business date before the pay date for dates generated by the last Calculate_Pay_Dates call
	public function Get_Billing_Dates()
	{
		return $this->Shift_Dates($this->pay_dates, "backward");
	}
	
	public function Get_Next_Business_Day($date)
	{
		return $this->Business_Day($date, "forward");
	}
	
	public function Get_Last_Business_Day($date)
	{
		return $this->Business_Day($date, "backward");
	}
}

?>
