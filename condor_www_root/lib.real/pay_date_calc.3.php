<?php
/**
 * @package scheduling
 */

define("PDC_YEAR_LIMIT", 25);

class Pay_Date_Calc_3
{
	private $holiday_array;
	private $model_data;
	private $model_name;
	private $start_timestamp;
	private $model_list;
	private $old_name_map;

	public function __construct($holiday_array = array())
	{
		if ($holiday_array instanceof ECash_Models_Reference_List)
			$holiday_array = $holiday_array->toArray('holiday');
		$this->holiday_array = $holiday_array;
		$this->current_model_data = array();
		$this->days_of_week = array("sun" => "sunday","mon" => "monday", "tue" => "tuesday","wed" => "wednesday","thu" => "thursday",
					    "fri" => "friday","sat" => "saturday");

		$this->model_list = array("weekly_on_day" => array("day_string_one"),
					  "every_other_week_on_day" => array("day_string_one"),
					  "every_fourth_week_on_day" => array("day_string_one"),
					  "twice_per_month_on_days" => array("day_int_one","day_int_two"),
					  "twice_per_month_on_week_and_day" => array("week_one","week_two","day_string_one"),
					  "monthly_on_week_and_day" => array("week_one","day_string_one"),
					  "monthly_on_day" => array("day_int_one"),
					  "monthly_on_day_of_week_after_day" => array("day_int_one","day_string_one") );

		$this->old_name_map = array("dw" => "weekly_on_day",
					    "dwpd" => "every_other_week_on_day",
					    "dwpd_fw" => "every_fourth_week_on_day",
					    "dmdm" => "twice_per_month_on_days",
					    "wwdw" => "twice_per_month_on_week_and_day",
					    "dm" => "monthly_on_day",
					    "wdw" => "monthly_on_week_and_day",
					    "dwdm" => "monthly_on_day_of_week_after_day");
	}

	// Public functions

	/**
	 * Calculates pay dates into the future or past based upon model data.
	 *
	 * @param string $model_name
	 * @param array $model_data
	 * @param bool $direct_deposit
	 * @param int $num_dates
	 * @param string $start_date
	 * @param bool $forward
	 * @return array
	 */
	public function Calculate_Pay_Dates($model_name, $model_data, $direct_deposit = TRUE, $num_dates = 4, $start_date = "now", $forward = TRUE)
	{
		// Validate all the parameters first

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
			$start_timestamp = mktime(12,0,0,date("m", $start_timestamp), date("d", $start_timestamp), date("Y", $start_timestamp));
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

		// Main operation code

		// Initialize
		$this->start_timestamp = $start_timestamp;
		$this->model_data = (is_object($model_data)) ? (array) $model_data : $model_data;
		$this->model_name = $model_name;
		$this->pay_dates = array();

		// Do we have all required data for the module and is it valid?
		foreach($this->model_list[$model_name] as $field)
		{
			$this->Validate_Field($field);
		}

		// We'll start at whereever we're told to, but from here on all we need is
		// the beginning of the month
		$parts = getdate($start_timestamp);
		$limit_year = intval($parts['year']) + PDC_YEAR_LIMIT;
		$current_timestamp = mktime(12,0,0,$parts['mon'],1,$parts['year']);

		while (count($this->pay_dates) < $num_dates)
		{
			$parts = getdate($current_timestamp);
			if ($limit_year == intval($parts['year'])) throw new Exception("Date range limit exceeded ({$limit_year})");
			$monthly_paydates = $this->Get_Paydates_For_Month($parts['mon'], $parts['year']);

			if (!$forward)
				$monthly_paydates = array_reverse($monthly_paydates);

			foreach ($monthly_paydates as $mp)
			{
				// Direct deposit is faster
				if (!$direct_deposit) $mp = strtotime("+1 day", $mp);

				// Weekend/Holiday check
				while ( $this->Is_Holiday($mp) || $this->Is_Weekend($mp) )
				{
					$mp = $direct_deposit ? strtotime("-1 day", $mp): strtotime("+1 day", $mp);
				}

				// Did we get a non valid result?
				if( $mp === FALSE || $mp == -1 )
					throw new Exception("Strtotime failed, range of 1970-2038 exceeded or something bad happened");

				if (($forward && $mp > $this->start_timestamp) || (!$forward && $mp < $this->start_timestamp))
					$this->pay_dates[] = date("Y-m-d", $mp);
			}
			$current_timestamp = strtotime($forward ? '+1 month' : '-1 month', $current_timestamp);
		}

		return $this->pay_dates;
	}

	public function Shift_Dates($date_array, $direction = "forward")
	{
		if (!is_array($date_array)) throw new Exception("Shift_Dates not passed array as first argument");
		$shifted_dates = array();
		foreach($date_array as $date)
		{
			$shifted_dates[] = $this->Business_Days($date, 1, $direction);
		}
		return $shifted_dates;
	}

	// Returns the business date before the pay date for dates generated by the last Calculate_Pay_Dates call
	public function Get_Billing_Dates()
	{
		return $this->Shift_Dates($this->pay_dates, "backward");
	}

	/**
	 * Returns the current day if it is a business day, the next business day otherwise.
	 *
	 * @param string $date
	 * @return string
	 */
	public function Get_Closest_Business_Day_Forward($date)
	{
		$stamp = strtotime($date);
		if ($this->Is_Holiday($stamp) || $this->Is_Weekend($stamp))
		{
			return $this->Get_Next_Business_Day($date);
		}
		else
		{
			return $date;
		}
	}

	// Returrns the next business day
	public function Get_Next_Business_Day($date)
	{
		return $this->Business_Days($date, 1, "forward");
	}

	// Returns the previous business day
	public function Get_Last_Business_Day($date)
	{
		return $this->Business_Days($date, 1, "backward");
	}

	public function Get_Business_Days_Forward($date, $count)
	{
		return $this->Business_Days($date, $count, "forward");
	}

	public function Get_Calendar_Days_Forward($date, $count)
	{
		return $this->Calendar_Days($date, $count, "forward");
	}

	public function Get_Business_Days_Backward($date, $count)
	{
		return $this->Business_Days($date, $count, "backward");
	}

	public function Get_Calendar_Days_Backward($date, $count)
	{
		return $this->Calendar_Days($date, $count, "backward");
	}

	private function Business_Days($date, $count, $direction)
	{
		$stamp = strtotime($date);

		// All possible easily detected problem values
		if("" == $date || FALSE === $stamp || 0 == $stamp || -1 == $stamp)
		{
			throw(new Exception("Date is not valid or was not recognized by strtotime: ${date}"));
		}
		else
		{
			if ($count != 0)
			{
				// 86400 = Seconds in a day
				$adjustment = ($direction == "forward") ? "+86400" : "-86400";
				do
				{
					$stamp = $stamp + $adjustment;

					if (!(($this->Is_Weekend($stamp)) || ($this->Is_Holiday($stamp))))
					{
						$count--;
					}
				} while ($count > 0);
			}
		}

		return (date("Y-m-d", $stamp));
	}

	private function Calendar_Days($date, $count, $direction)
	{
		$stamp = strtotime($date);
		if( $stamp === FALSE || $stamp == -1) {
			throw new Exception("Date is not valid or was not recognized by strtotime: {$date}");
		}
		$adjustment = ($direction == "forward") ? "+1" : "-1";
		for ($i = 0; $i < $count; $i++) {
			$stamp = strtotime("{$adjustment} day", $stamp);
		}
		return (date("Y-m-d", $stamp));
	}

	// Returns true if the date is saturday or sunday
	public function Is_Weekend($timestamp)
	{
		if (in_array(date("w", $timestamp), array(0,6))) return TRUE;
		return FALSE;
	}

	// Returns true if the date is found in the holiday array
	public function Is_Holiday($timestamp)
	{
		$current_date = date("Y-m-d", $timestamp);

		if(@in_array($current_date, $this->holiday_array)) return TRUE;

		return FALSE;
	}

	/**
	 * Check if a given unix timestamp is a business day
	 *
	 * This simply calls Is_Holiday and Is_Weekend and return the combined result.
	 *
	 * @param integer Unix timestamp to check
	 * @return bool true if the date is not a holiday and not a weekend
	 */
	public function isBusinessDay($timestamp)
	{
		return (!$this->Is_Holiday($timestamp) && !$this->Is_Weekend($timestamp));
	}

	private function Get_Paydates_For_Month($imonth, $iyear)
	{
		$fom = mktime(12,0,0,$imonth,1,$iyear);
		//print("Using model function {$this->model_name}.\n");
		//print("First of month: " .date("Y-m-d H:i:s", $fom)."\n");
		$dates = $this->{$this->model_name}($fom);
		return $dates;
	}

	private function Weekly_On_Day($first_of_month)
	{
		$stamps = array();
		$reference = getdate($first_of_month);
		$stamp = strtotime("this {$this->model_data['day_string_one']}", $first_of_month);
		$this->checkstamp($stamp);
		if (intval(date("m", $stamp)) == $reference['mon'])
		{
			$stamps[] = $stamp;
		}
		$stamp = strtotime("next {$this->model_data['day_string_one']}", $stamp);
		$this->checkstamp($stamp);
		while (intval(date("m", $stamp) == $reference['mon']))
		{
			$stamps[] = $stamp;
			$stamp = strtotime("next {$this->model_data['day_string_one']}", $stamp);
			$this->checkstamp($stamp);
		}
		return $stamps;
	}

	private function Every_Other_Week_On_Day($first_of_month)
	{

		$day_of_week_map = array_flip(array_values($this->days_of_week));

		if ($this->model_data['last_paydate'])
		{
			$dow = strtotime($this->model_data['last_paydate']);
		}
		elseif ($this->model_data['next_pay_date'])
		{
			// Continue subtracting two weeks from next pay date until less than today.
			$dow = strtotime($this->model_data['next_pay_date']);
			$today = time();

			while ($dow > $today)
			{
				$dow = strtotime('-2 weeks', $dow);
			}
		}
		else
		{
			throw new Exception("'last_paydate' or 'next_pay_date' is required for every other week on day.");
		}

		$ref_month = date("m", $first_of_month);
		$ref_year = date("Y", $first_of_month);
		$date_info = getdate($dow);

		$difference = $day_of_week_map[$this->model_data['day_string_one']] - $date_info['wday'];

		if ($difference > 3) {
			$difference = $difference - 7;
		} elseif ($difference < -3) {
			$difference = 7 + $difference;
		}

		$this->checkstamp($dow);
		$dow = mktime(12,0,0,date("m", $dow), date("d", $dow) + $difference,
			      date("Y", $dow));

		while (($ref_year != (date("Y", $dow))) ||
		       ($ref_month != (date("m", $dow))))
		{
			if ($dow > $first_of_month)
			{
				$dow = strtotime("-2 weeks", $dow);
			}
			else
			{
				$dow = strtotime("+2 weeks", $dow);
			}
		}
		$this->checkstamp($dow);
		$stamps[] = $dow;
		// Look for dates before it still in the month
		$dowb = strtotime("-2 weeks", $dow);
		$this->checkstamp($dowb);
		while (date("m", $dowb) == date("m", $dow))
		{
			$stamps[] = $dowb;
			$dowb = strtotime("-2 weeks", $dowb);
			$this->checkstamp($dowb);
		}

		// Look for dates after it still in the month
		$dowf = strtotime("+2 weeks", $dow);
		$this->checkstamp($dowf);
		while (date("m", $dowf) == date("m", $dow))
		{
			$stamps[] = $dowf;
			$dowf = strtotime("+2 weeks", $dowf);
			$this->checkstamp($dowf);
		}

		sort($stamps);
		return $stamps;
	}

	private function Every_Fourth_Week_On_Day($first_of_month)
	{

		$day_of_week_map = array_flip(array_values($this->days_of_week));

		if ($this->model_data['last_paydate'])
		{
			$dow = strtotime($this->model_data['last_paydate']);
		}
		elseif ($this->model_data['next_pay_date'])
		{
			// Continue subtracting four weeks from next pay date until less than today.
			$dow = strtotime($this->model_data['next_pay_date']);
			$today = time();

			while ($dow > $today)
			{
				$dow = strtotime('-4 weeks', $dow);
			}
		}
		else
		{
			throw new Exception("'last_paydate' or 'next_pay_date' is required for every fourth week on day.");
		}

		$ref_month = date("m", $first_of_month);
		$ref_year = date("Y", $first_of_month);
		$date_info = getdate($dow);

		$difference = $day_of_week_map[$this->model_data['day_string_one']] - $date_info['wday'];

		if ($difference > 3) {
			$difference = $difference - 7;
		} elseif ($difference < -3) {
			$difference = 7 + $difference;
		}

		$this->checkstamp($dow);
		$dow = mktime(12,0,0,date("m", $dow), date("d", $dow) + $difference,
			      date("Y", $dow));

		while (($ref_year != (date("Y", $dow))) ||
		       ($ref_month != (date("m", $dow))))
		{
			if ($dow > $first_of_month)
			{
				$dow = strtotime("-4 weeks", $dow);
			}
			else
			{
				$dow = strtotime("+4 weeks", $dow);
			}
		}
		$this->checkstamp($dow);
		$stamps[] = $dow;
		// Look for dates before it still in the month
		$dowb = strtotime("-4 weeks", $dow);
		$this->checkstamp($dowb);
		while (date("m", $dowb) == date("m", $dow))
		{
			$stamps[] = $dowb;
			$dowb = strtotime("-4 weeks", $dowb);
			$this->checkstamp($dowb);
		}

		// Look for dates after it still in the month
		$dowf = strtotime("+4 weeks", $dow);
		$this->checkstamp($dowf);
		while (date("m", $dowf) == date("m", $dow))
		{
			$stamps[] = $dowf;
			$dowf = strtotime("+4 weeks", $dowf);
			$this->checkstamp($dowf);
		}

		sort($stamps);
		return $stamps;
	}

	private function Twice_Per_Month_On_Days($first_of_month)
	{
		$dom1 = min($this->model_data['day_int_one'], $this->model_data['day_int_two']);
		$dom2 = max($this->model_data['day_int_one'], $this->model_data['day_int_two']);

		$reference = getdate($first_of_month);
		$stamps = array();
		$stamps[] = mktime(12,0,0,$reference['mon'], $dom1, $reference['year']);
		$this->checkstamp($stamps[0]);
		$day2 = mktime(12,0,0,$reference['mon'], $dom2, $reference['year']);
		$this->checkstamp($day2);
		while (date("m", $day2) != $reference['mon'])
		{
			$day2 = strtotime("-1 day", $day2);
			$this->checkstamp($day2);
		}
		$stamps[] = $day2;
		return $stamps;
	}

	private function Twice_Per_Month_On_Week_And_Day($first_of_month)
	{
		//print("Day string one in use is: {$this->model_data['day_string_one']}\n");
		$stamps = array();
		$reference_month = date("m", $first_of_month);
		$dow = strtotime("this {$this->model_data['day_string_one']}", $first_of_month);
		if (date("m", $dow) != $reference_month)
			$dow = strtotime("next {$this->model_data['day_string_one']}", $dow);
		$this->checkstamp($dow);
		$count = 1;
		$week_1 = intval($this->model_data['week_one']);
		$week_2 = intval($this->model_data['week_two']);
		while (date("m", $dow) == $reference_month)
		{
			if ($count == $week_1) $stamps[] = $dow;
			elseif ($count == $week_2) $stamps[] = $dow;
			$dow = strtotime("next {$this->model_data['day_string_one']}", $dow);
			$this->checkstamp($dow);
			$count++;
		}
		return $stamps;
	}

	private function Monthly_On_Week_And_Day($first_of_month)
	{
		$reference_month = date("m", $first_of_month);
		$count = 1;
		$dow = strtotime("this {$this->model_data['day_string_one']}", $first_of_month);
		$this->checkstamp($dow);
		if (date("m", $dow) == $reference_month) $count = 1;
		else $count = 0;

		if ($this->model_data['week_one'] > 4) {
			$dow = strtotime("last {$this->model_data['day_string_one']}", strtotime("next month", $first_of_month));
			$this->checkstamp($dow);
		} else {
			for (;$count < $this->model_data['week_one']; $count++)
			{
				$dow = strtotime("next {$this->model_data['day_string_one']}", $dow);
				$this->checkstamp($dow);
			}
		}
		return (array($dow));
	}

	private function Monthly_On_Day($first_of_month)
	{
		$reference = getdate($first_of_month);
		$dom = mktime(12,0,0,$reference['mon'], $this->model_data['day_int_one'], $reference['year']);
		$this->checkstamp($dom);
		while (date("m", $dom) != $reference['mon'])
		{
			$dom = strtotime("-1 day", $dom);
			$this->checkstamp($dom);
		}
		return (array($dom));
	}

	private function Monthly_On_Day_Of_Week_After_Day($first_of_month)
	{
		$dom = mktime(12,0,0,date("m",$first_of_month),
			      $this->model_data['day_int_one'],
			      date("Y", $first_of_month));
		$this->checkstamp($dom);
		$dow = strtotime("this {$this->model_data['day_string_one']}", $dom);
		$this->checkstamp($dow);
		if ($dow < $dom)
		{
			$dow = strtotime("+1 week", $dow);
			$this->checkstamp($dow);
		}
		return (array($dow));
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

	private function checkstamp($stamp)
	{
		if (($stamp === FALSE) || ($stamp == -1))
		{
			throw new Exception("Invalid timestamp found");
		}
	}
}
?>
