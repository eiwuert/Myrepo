<?php
require_once 'applog.1.php';

	/* TODO:
		-- MASSIVE AMOUNTS OF TESTING WITH DIFFERENT SCENERIOS FOR DATES, INCLUDING LEAP YEAR, MULTI-YEAR, AND ODD WEEKEND STUFF
	*/


	// Some testing code.  Remove before release?  All tests push out 1 year of dates

//	$holiday_array = array ("2004-09-06"=>TRUE,"2004-10-11"=>TRUE,"2004-11-11"=>TRUE,"2004-11-25"=>TRUE,"2004-12-24"=>TRUE,"2004-12-25"=>TRUE,"2005-01-01"=>TRUE,"2005-07-04"=>TRUE,"2004-07-20"=>TRUE,"2004-07-19"=>TRUE,"2004-07-18"=>TRUE);
//	$holiday_array = array ("2005-02-21"=>TRUE,"2005-05-30"=>TRUE,"2005-07-04"=>TRUE,"2005-09-05"=>TRUE,"2005-10-10"=>TRUE,"2005-11-11"=>TRUE,"2005-11-24"=>TRUE,"2005-12-26"=>TRUE);
//	$pd = new Pay_Date_Calc_1 ($holiday_array);
//	echo "DW\n"; // Good
//	print_r ($pd->Calculate_Payday ("DW", "2004-09-24", array ("day_string_one" => "MON"), 52));
//	echo "DW No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DW", "2004-09-24", array ("day_string_one" => "MON"), 52, FALSE));
//	echo "DW from eCash\n";
//	print_r ($pd->Calculate_Payday ("DW", "2004-09-27", array ("day_string_one" => "MON"), 4, 1));
//	echo "DW\n"; // Good
//	print_r ($pd->Calculate_Payday ("DW", "2004-09-24", array ("day_string_one" => "MON"), 52));
//	echo "DW No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DW", "2004-09-24", array ("day_string_one" => "MON"), 52, FALSE));
//	$holiday_array = array ("2004-09-06"=>TRUE,"2004-12-24"=>TRUE,"2004-12-25"=>TRUE,"2005-01-01"=>TRUE,"2005-07-04"=>TRUE,"2004-07-20"=>TRUE,"2004-07-19"=>TRUE,"2004-07-18"=>TRUE);
//	$pd = new Pay_Date_Calc_1 ($holiday_array);
//	echo "DW\n"; // Good
//	print_r ($pd->Calculate_Payday ("DW", "2004-07-22", array ("day_string_one" => "FRI"), 52));
//	echo "DW No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DW", date("Y-m-d"), array ("day_string_one" => "FRI"), 4, FALSE));
//	print_r ($pd->Generate_Paid_On_Dates( "DWPD", array("day_string_one" => "FRI", "next_pay_date"=>"2004-09-03"), TRUE ));
//	echo "DWPD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DWPD", "2004-09-13", array("day_string_one" => "MON", "next_pay_date"=>"2004-09-27"), 4));
//	echo "DWPD No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DWPD", "2004-09-20", array("day_string_one" => "FRI", "next_pay_date"=>"2004-10-15"), 4, FALSE));
//	print_r ($pd->Generate_Paid_On_Dates( "DWPD", array("day_string_one" => "WED", "next_pay_date"=>"2004-09-03"), FALSE ));
//	echo "DMDM\n"; // Good
//	print_r ($pd->Calculate_Payday ("DMDM", date("Y-m-d"), array("day_int_one" => "1", "day_int_two" => "15"), 24));
//	echo "DMDM\n"; // Good
//	print_r ($pd->Calculate_Payday ("DMDM", date("Y-m-d"), array("day_int_one" => "15", "day_int_two" => "28"), 4));
//	echo "DMDM No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DMDM", date("Y-m-d"), array("day_int_one" => "1", "day_int_two" => "15"), 24, FALSE));
//	print_r ($pd->Generate_Paid_On_Dates( "DMDM", array("day_int_one" => "15", "day_int_two" => "31"), FALSE ));
//	echo "WWDW\n";  // Good
//	print_r ($pd->Calculate_Payday ("WWDW", "2004-08-06", array("week_one" => 1, "week_two" => 3, "day_string_one" => "MON"), 24));
//	echo "WWDW No DD\n";  // Good
//	print_r ($pd->Calculate_Payday ("WWDW", date("Y-m-d"), array("week_one" => 1, "week_two" => 3, "day_string_one" => "MON"), 4, FALSE));
//	print_r ($pd->Generate_Paid_On_Dates( "WWDW", array("week_one" => 1, "week_two" => 3, "day_string_one" => "MON"), FALSE ));
//	echo "DM\n"; // Good
//	print_r ($pd->Calculate_Payday ("DM", "2004-08-06", array("day_int_one" => "32"), 12));
//	echo "DM No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DM", date("Y-m-d"), array("day_int_one" => "1"), 12, FALSE));
//	print_r ($pd->Generate_Paid_On_Dates( "DM", array("day_int_one" => "1"), FALSE ));
//	echo "WDW\n"; // Good
//	print_r ($pd->Calculate_Payday ("WDW", "2004-08-06", array("week_one" => 1, "day_string_one" => "MON"), 12));
//	echo "WDW No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("WDW", date("Y-m-d"), array("week_one" => '1', "day_string_one" => "WED"), 12, FALSE));
//	print_r($pd->Generate_Paydate_Model(20040920000000, 20040927000000, 'TWICE_MONTHLY'));
//	print_r ($pd->Generate_Paid_On_Dates( "WDW", array("week_one" => 3, "day_string_one" => "FRI"), FALSE ));
//	echo "DWDM\n"; // Good
//	print_r ($pd->Calculate_Payday ("DWDM", "2004-09-15", array("day_string_one" => "FRI", "day_int_one" => "1"), 12));
//	echo "DWDM No DD\n"; // Good
//	print_r ($pd->Calculate_Payday ("DWDM", "2004-09-15", array("day_string_one" => "FRI", "day_int_one" => "1"), 12, FALSE));
//	print_r ($pd->Generate_Paid_On_Dates( "DWDM", array("day_string_one" => "MON", "day_int_one" => "15"), FALSE ));



/*
 		Some variables explained
 		$prev_start_date: This value is for recursion use only.  DO NOT PASS A VALUE, OR IT WILL BREAK!!!!!

 		$model_name: Can be one of the following: (If you add another one, please update here)
 			DW - Day of Week (WEEKLY)
				model_data:  day_string_one
			DWPD - Day of Week, Next Pay Day (EVERY OTHER WEEK)
				model_data: day_string_one, next_pay_date
			DWPD_FW - Day of Week, Next Pay Day (EVERY FOUR WEEKS)
				model_data: day_string_one, next_pay_date
 			DMDM - Day of Month, Day of Month (TWICE A MONTH)
				model_data: day_int_one, day_int_two
 			WWDW - Week #, Week #, Day of Week (TWICE A MONTH)
				model_data: week_one, week_two, day_string_one
 			DM - Day of Month (MONTHLY)
				model_data: day_int_one
 			WDW - Week #, Day of Week (MONTHLY)
				model_data: week_one, day_string_one
 			DWDM - Day of Week, Day of Month (MONTHLY)
				model_data: day_int_one, day_string_one

 		$model_data: The data for the model.  Will contain one or more of the following elements: (If more are added, please update here)
 			day_string_one - The day of the week as a three letter char in upper case e.g.: MON
 			next_pay_date - The "reference" pay date as a string (YYYY-MM-DD).  This date can be in the past or the future.
 			day_int_one - The day of the month as an integer (1-32) where 32 is the last day of the month
 			day_int_two - The day of the month as an integer (1-32) where 32 is the last day of the month, the integer should be larger than day_int_one
 			week_one - The week number as an integer (1-4)
 			week_two - The week number as an integer (1-4), The integer should be larger than week_one
*/




	// A class to handle the paydate calculation
	class Pay_Date_Calc_1
	{
		

		
		var $holiday_array; // An array of holidays as dates

		/**
		* @return Pay_Date_Calc_1
		* @param array $holiday_array
		* @desc The Constructor
		*/
		function Pay_Date_Calc_1 ($holiday_array)
		{
			// Set up the holidays
			$this->holiday_array = $holiday_array;

			if (!is_array ($this->holiday_array))
			{
				$this->holiday_array = array ();
			}

			return TRUE;
		}

		
		
		/**
		* @return array on success FALSE on failure
		* @param string $model_name - The name of the model to use (see above for explaination)
		* @param string $start_date - The date to start projecting from
		* @param array $model_data  - The data needed for the model (see above for explaination)
		* @param integer $num_dates - The number of paydates to calculate
		* @param boolean $direct_deposit - OPTIONAL, send FALSE if adjusting for paper check rules (assumes TRUE)
		* @desc Calculate the num_dates paydays starting at start_date.
		*/
		function Calculate_Previous_Payday ($model_name, $start_date, $model_data,  $direct_deposit = TRUE, $holiday_weekend_check = TRUE)
		{

			$time_start_date = strtotime($start_date);
            // Grab the month and the year
            $month = (int)date ("m", $time_start_date);
            $year = (int)date ("Y", $time_start_date);
			
			// Get the date
			$start_date_minus_one_month = mktime(0,0,0,$month-1,1,$year); // start at the beginning of last month

			 
			// Calculate the paydates and take the closest one to today's date
			$my_dates = $this->Calculate_Payday($model_name, date("Y-m-d", $start_date_minus_one_month), $model_data, 9, $direct_deposit , $holiday_weekend_check );
			$previous_pay_day = "12/31/1969";
			
			// loop over the dates to find the last paydate
			for($i = count($my_dates) - 1; $i >= 0 ; $i--)
			{
				$time_mydate = strtotime($my_dates[$i]);
				// is the date less than or equal to $start_date?
				if($time_mydate <= $time_start_date)
				{
						return $my_dates[$i];
				}
			}
			// Return what we found if we didn't find anything
			return $previous_pay_day;
		}

		
		/**
		* @return array on success FALSE on failure
		* @param string $model_name - The name of the model to use (see above for explaination)
		* @param string $start_date - The date to start projecting from
		* @param array $model_data  - The data needed for the model (see above for explaination)
		* @param integer $num_dates - The number of paydates to calculate
		* @param boolean $direct_deposit - OPTIONAL, send FALSE if adjusting for paper check rules (assumes TRUE)
		* @desc Calculate the num_dates paydays starting at start_date.
		*/
		function Calculate_Payday ($model_name, $start_date, $model_data, $num_dates, $direct_deposit = TRUE, $holiday_weekend_check = TRUE)
		{
	
	
			if (empty($model_name))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			$model_name = strtoupper($model_name);
			
			if (empty($start_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Initialize the variable
			$future_dates = array ();

			// holiday / weekend check?
			$this->holiday_weekend_check = $holiday_weekend_check;

			// Run through the loop
			switch ($model_name)
			{
				case "DW":
					if (empty($model_data["day_string_one"]))
					{
						// Vegas, we have a problem
						return FALSE;
					}

					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$future_dates [$counter] = $this->DW ($start_date, $model_data ["day_string_one"]);
						}
						else
						{
							$future_dates [$counter] = $this->DW ($future_dates [($counter - 1)], $model_data ["day_string_one"]);
						}

						// Did we get a failure?
						if ($future_dates [$counter] === FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;

				case "DWPD":
					if ( empty($model_data["day_string_one"]) || empty($model_data["next_pay_date"]) )
					{
						// Vegas, we have a problem
						return FALSE;
					}

					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$pay_dates = $this->DWPD ($start_date, $model_data ["next_pay_date"], $model_data ["day_string_one"]);
							if ($pay_dates === FALSE)
							{
								// Vegas, we have a problem
								return FALSE;
							}
							$future_dates [$counter] = $pay_dates["pay_date"];
							$pre_holiday = $pay_dates["pre_holiday"]; //save this for the next time we call DWPD
						}
						else
						{
							$pay_dates = $this->DWPD ($pre_holiday, $pre_holiday, $model_data ["day_string_one"]);
							if ($pay_dates === FALSE)
							{
								// Vegas, we have a problem
								return FALSE;
							}
							$future_dates [$counter] = $pay_dates["pay_date"];
							$pre_holiday = $pay_dates["pre_holiday"]; //save this for the next time we call DWPD
						}

						// Did we get a problem?
						if (FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;

				case "DWPD_FW":
					if ( empty($model_data["day_string_one"]) || empty($model_data["next_pay_date"]) )
					{
						// Vegas, we have a problem
						return FALSE;
					}

					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$pay_dates = $this->DWPD_FW ($start_date, $model_data ["next_pay_date"], $model_data ["day_string_one"]);
							if ($pay_dates === FALSE)
							{
								// Vegas, we have a problem
								return FALSE;
							}
							$future_dates [$counter] = $pay_dates["pay_date"];
							$pre_holiday = $pay_dates["pre_holiday"]; //save this for the next time we call DWPD_FW
						}
						else
						{
							$pay_dates = $this->DWPD_FW ($pre_holiday, $pre_holiday, $model_data ["day_string_one"]);
							if ($pay_dates === FALSE)
							{
								// Vegas, we have a problem
								return FALSE;
							}
							$future_dates [$counter] = $pay_dates["pay_date"];
							$pre_holiday = $pay_dates["pre_holiday"]; //save this for the next time we call DWPD_FW
						}

						// Did we get a problem?
						if (FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;
				
				case "DMDM":
					if ( empty($model_data["day_int_one"]) || empty($model_data["day_int_two"]) )
					{
						// Vegas, we have a problem
						return FALSE;
					}

					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$future_dates [$counter] = $this->DMDM ($start_date, $model_data ["day_int_one"], $model_data ["day_int_two"]);
						}
						else
						{
							$future_dates [$counter] = $this->DMDM ($future_dates [($counter - 1)], $model_data ["day_int_one"], $model_data ["day_int_two"]);
						}

						// Did we get a problem?
						if ($future_dates [$counter] === FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;

				case "WWDW":
					if ( empty($model_data["day_string_one"]) || empty($model_data ["week_one"]) || empty($model_data ["week_two"]) )
					{
						// Vegas, we have a problem
						return FALSE;
					}

					// The WWDW method allows for a "prev_start_date" to be passed as optional.  DO NOT PASS THAT PARAMETER FROM HERE, IT IS ONLY FOR RECURSION USE!!!!!
					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$future_dates [$counter] = $this->WWDW ($start_date, $model_data ["week_one"], $model_data ["week_two"], $model_data ["day_string_one"]);
						}
						else
						{
							$future_dates [$counter] = $this->WWDW ($future_dates [($counter - 1)], $model_data ["week_one"], $model_data ["week_two"], $model_data ["day_string_one"]);
						}

						// Did we get a problem?
						if ($future_dates [$counter] === FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;

				case "DM":
					if (empty($model_data["day_int_one"]))
					{
						// Vegas, we have a problem
						return FALSE;
					}

					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$future_dates [$counter] = $this->DM ($start_date, $model_data ["day_int_one"]);
						}
						else
						{
							$future_dates [$counter] = $this->DM ($future_dates [($counter - 1)], $model_data ["day_int_one"]);
						}

						// Did we get a problem?
						if ($future_dates [$counter] === FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;

				case "WDW":
					if ( empty($model_data["day_string_one"]) || empty($model_data["week_one"]) )
					{
						// Vegas, we have a problem
						return FALSE;
					}

					// The WDW method allows for a "prev_start_date" to be passed as optional.  DO NOT PASS THAT PARAMETER FROM HERE, IT IS ONLY FOR RECURSION USE!!!!!
					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$future_dates [$counter] = $this->WDW ($start_date, $model_data ["week_one"], $model_data ["day_string_one"]);
						}
						else
						{
							$future_dates [$counter] = $this->WDW ($future_dates [($counter - 1)], $model_data ["week_one"], $model_data ["day_string_one"]);
						}

						// Did we get a problem?
						if ($future_dates [$counter] === FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;

				case "DWDM":
					if ( empty($model_data["day_string_one"]) || empty($model_data["day_int_one"]) )
					{
						// Vegas, we have a problem
						return FALSE;
					}

					for ($counter = 0; $counter < $num_dates; $counter++)
					{
						// Determine if this is the first or not
						if ($counter ==0)
						{
							$future_dates [$counter] = $this->DWDM ($start_date, $model_data ["day_string_one"], $model_data ["day_int_one"]);
						}
						else
						{
							$future_dates [$counter] = $this->DWDM ($future_dates [($counter - 1)], $model_data ["day_string_one"], $model_data ["day_int_one"]);
						}

						// Did we get a problem?
						if ($future_dates [$counter] === FALSE)
						{
							// Houston, we have a problem, bail out
							return FALSE;
						}
					}
				break;
			}

			//adjust for no direct deposit
			if($direct_deposit == FALSE)
			{
				foreach ($future_dates as $index => $date)
				{
					$future_dates[$index] = $this->Adjust_For_No_Direct_Deposit($date);
				}
			}

			// Return what we found
			return $future_dates;
		}
		
		/**
		* @return array on success FALSE on failure
		* @param string $model_name - The name of the model to use (see above for explaination)
		* @param array $model_data  - The data needed for the model (see above for explaination)
		* @param integer $num_dates - The number of paydates to calculate
		* @param boolean $direct_deposit - OPTIONAL, send FALSE if adjusting for paper check rules (assumes TRUE)
		* @desc Calculate the num_dates paydays starting at start_date.
		*/
		function Generate_Paid_On_Dates( $model_name, $model_data, $direct_deposit = TRUE )
		{
			$model_name = strtoupper($model_name);
			
			// Initialize the variable
			$paid_on_dates = array ();

			// Run through the loop
			switch ($model_name)
			{

				case "DW":
				case "DWPD":
				case "DWPD_FW": //TODO: I don't know if it works or not [DY]
					// set day of week + 1 since the day of week starts with 0
					$day_int = $this->Day_String_to_Int ($model_data["day_string_one"]);
					if (!$direct_deposit)
					{
						$paid_on_dates[0] = ($day_int<5) ? $day_int + 1 : 1;
					}
					else
					{
						$paid_on_dates[0] = $day_int;
					}
					// cashline currently handles wey of weeks starting 51+
					$paid_on_dates[0] = '5'.$paid_on_dates[0];
				break;


				case "DMDM":
						//  set day int one
						$paid_on_dates[0] = ($direct_deposit) ? $model_data["day_int_one"] : $model_data["day_int_one"] + 1;

						// if day int 2 is 31+ and there is no direct deposit
						if ($model_data["day_int_two"]>=31 && !$direct_deposit)
						{
								// move the first paid_on_date as the second
								$paid_on_dates[1] = $paid_on_dates[0];
								// set the first paid_on_date as the first of the month
								$paid_on_dates[0] = 1;
						}
						else
						{
							// set second paid on date
							$paid_on_dates[1] = ($direct_deposit) ? $model_data["day_int_two"] : $model_data["day_int_two"] + 1 ;
						}
				break;

				case "WWDW":

					// calculate next 6 paydates without holiday_weekend validation
					$pay_dates = $this->Calculate_Payday ($model_name, date("Y-m-d"), $model_data, 6, $direct_deposit, FALSE);

					$i = 0; // counter for pay_dates key
					$d = 0; // counter for paid_on_dates

					while (count($paid_on_dates)<2)
					{
						if (empty($pay_dates[$i]))
						{
							// Vegas, we have a problem
							return FALSE;
						}
						// get current day
						$day = date( "d", strtotime($pay_dates[$i]));
						//echo $day."\n";
						// if there is a previous date and the previous date is greater than the current day
						// overwrite the previous paid_on_date
						if ( $paid_on_dates[$i-1] && $paid_on_dates[$i-1] > $day)
						{
							$paid_on_dates[$d-1] = $day;
						}
						else
						{
							//  add day to array
							$paid_on_dates[$d] = $day;
							++$d;
						}
						++$i;
					}
				break;

				case "DM":
					if ($model_data["day_int_one"]>=31 && !$direct_deposit)
					{
						$paid_on_dates[0] = 1;
					}
					else
					{
						$paid_on_dates[0] = ($direct_deposit) ? $model_data["day_int_one"] : $model_data["day_int_one"] + 1;
					}
				break;

				case "WDW":
				case "DWDM":
					// calculate next paydate without holiday_weekend validation
					$pay_dates = $this->Calculate_Payday ($model_name, date("Y-m-d"), $model_data, 1, $direct_deposit, FALSE);
					if (empty($pay_dates[0]))
					{
						// Vegas, we have a problem
						return FALSE;
					}
					$paid_on_dates[0] = date("d", strtotime($pay_dates[0]));
				break;
			}

			return $paid_on_dates;

		}


		/**
		* @return array
		* @param string $pay_date1 - The first/second paydate, can be passed in a format that is able to be calculated by strtotime
		* @param string $pay_date2 - Mysql dates are also formatted to the date only
		* @param string $frequency - pay frequency
		*/
		function Generate_Paydate_Model($pay_date1, $pay_date2, $frequency)
		{
			//  remove seconds from mysql date
			$pay_date1 = (strlen($pay_date1)>10) ? substr($pay_date1, 0, 8) : $pay_date1;
			$pay_date2 = (strlen($pay_date2)>10) ? substr($pay_date2, 0, 8) : $pay_date2;

			// get nix time
			if (empty($pay_date1))
			{
				// Vegas, we have a problem
				return FALSE;
			}
			$pay_date1_unix = strtotime($pay_date1);

			if (empty($pay_date2))
			{
				// Vegas, we have a problem
				return FALSE;
			}
			$pay_date2_unix = strtotime($pay_date2);

			// get day of week
			$pay_date1_dow = strtoupper(date("D", $pay_date1_unix));
			$pay_date2_dow = strtoupper(date("D", $pay_date2_unix));

			// get day of month
			$pay_date1_day = date("d", $pay_date1_unix);
			$pay_date2_day = date("d", $pay_date2_unix);
			//echo $pay_date1_day ." - ".$pay_date2_day."\n";
			/*
			 * 2004.11.13 tomr: it's saturday morning and i'm working on a 911 showstopper, so you know what kind of mood i'm in...
			 * judging from the comment below, the week of the month should never exceed 4, which is wierd because some months
			 * have 5 weeks.  that logic would lump up to 9 days in the 5th week, if week one has only 1 day.  does this
			 * this wreak havoc elsewhere if there are two mondays in a week?  who knows.  it's a moot point because
			 * the comment doesn't accurately reflect the code: the code allows week of the month to range up to 5.
			 * one way to fix the problem is with the commented out code added below to calculate pay_date_week.
			 * the other way is further down and i've chosen that fix, but it may break something else, so you've been
			 * warned.
			 */
			//  guestimate week of month and limit it only up to 4
			$pay_date1_week = ceil($pay_date1_day / 7) ;
			$pay_date2_week = ceil($pay_date2_day / 7) ;
			#$pay_date1_week = min(4,ceil($pay_date1_day/7)) ;
			#$pay_date2_week = min(4,ceil($pay_date2_day/7)) ;

			//  add frequency to the return
			$return_val['frequency'] = strtoupper($frequency);

			switch ($return_val['frequency'])
			{
				case 'WEEKLY':
					$return_val['model_name'] = 'DW';
					$return_val['day_string_one'] = $pay_date1_dow;
					$return_val['next_pay_date'] = $pay_date1;
				break;

				case 'MONTHLY':

					// if days of week are the same.. default to DWDM
					if ($pay_date1_dow == $pay_date2_dow && $pay_date1_week == $pay_date2_week)
					{
						$return_val['model_name'] = 'DWDM';
						$return_val['day_string_one'] = $pay_date1_dow;
						$return_val['day_int_one'] = $pay_date1_day;
						$return_val['week_one'] = ($pay_date1_week<$pay_date2_week) ? $pay_date1_week : $pay_date2_week;
					}
					// if days of week are the same default to WDW
					elseif($pay_date1_dow == $pay_date2_dow )
					{
						/*
						 * 2004.11.13 tomr: originally $d = 31 which means if a paydate falls on the 31st, this loop fails
						 * to set a model_name.  i changed $d to start at 32 and so we now have a model.  but is it the right
						 * model?  maybe not.  maybe the model should be DWDM instead of WDW.  who knows.
						 */
						$d = 32;
						while($d >= 0)
						{
							if ($pay_date1_day < $d && $pay_date2_day < $d)
							{
								$return_val['model_name'] = 'WDW';
								$return_val['day_int_one'] = $d;
								$return_val['day_string_one'] = $pay_date1_dow;
								$return_val['week_one'] = ceil($return_val['day_int_one'] / 7);
								$return_val['week_one'] = ($return_val['week_one']>4) ? 4 : $return_val['week_one'];
							}

							--$d;
						}
					}
					//  default to DM
					else
					{
						$return_val['model_name'] = 'DM';
						$return_val['day_int_one'] = $pay_date1_day;
					}

					/*
					 * 2004.11.13 tomr: i'm leaving this debug code just to watch for other occurences of null model_names
					 */
#					if ( empty($return_val['model_name']) ) {
#						$log = new Applog();
#						$log->Write("\$return_val: ".print_r($return_val,TRUE));
#						$log->Write("$pay_date1_dow <1:dow:2> $pay_date2_dow && $pay_date1_week <1:week:2> $pay_date2_week");
#						$log->Write("$pay_date1_day <1:day:2> $pay_date2_day");
#						$log->Close();
#					}

				break;

				case 'BI_WEEKLY':
					$return_val['model_name'] = 'DWPD';
					$return_val['day_string_one'] = $pay_date1_dow;
					$return_val['day_int_one'] = $pay_date1_day;
					$return_val['next_pay_date'] = date("Y-m-d", $pay_date1_unix);
				break;

				case 'FOUR_WEEKLY':
					$return_val['model_name'] = 'DWPD_FW';
					$return_val['day_string_one'] = $pay_date1_dow;
					$return_val['day_int_one'] = $pay_date1_day;
					$return_val['next_pay_date'] = date("Y-m-d", $pay_date1_unix);
				break;

				case 'TWICE_MONTHLY':
					// assume the model to be WWDW if the week days are the same or the second date == 1st date + 2 weeks
					if (
						(
							$pay_date1_dow == $pay_date2_dow
							|| date("Ymd", $pay_date2_unix) == date("Ymd", strtotime("+ 2 weeks", $pay_date1_unix))
						)
						&& $pay_date1_unix != $pay_date2_unix
						&& $pay_date1_week < 5
						&& $pay_date2_week < 5
					)
					{ //echo $pay_date1_week ."=-=-". $pay_date2_week;
						$return_val['model_name'] = 'WWDW';
						$return_val['day_int_one'] = $pay_date1_day;
						$return_val['day_string_one'] = $pay_date1_dow;
						if ($pay_date1_week >= 5 || $pay_date2_week >= 5)
						{
							// default to 2/4 since it is in the fifth week
							$return_val['week_one'] = 2;
						}
						else
						{
							if ($pay_date1_week >= 3 && $pay_date2_week >= 3)
							{
								// default to 2/4 since it is in the fifth week
								$return_val['week_one'] = 2;
							}
							else
							{
								$return_val['week_one'] = ($pay_date1_week < $pay_date2_week) ? $pay_date1_week : $pay_date2_week;
							}
						}
						$return_val['week_two'] = $return_val['week_one'] + 2;
					}
					else
					{
						// default to DMDM
						$return_val['model_name'] = 'DMDM';
						$return_val['day_int_one'] = ($pay_date1_day < $pay_date2_day) ? $pay_date1_day : $pay_date2_day;
						$return_val['day_int_two'] = ($return_val['day_int_one'] == $pay_date2_day) ? $pay_date1_day : $pay_date2_day;
					}

					$return_val['next_pay_date'] = date("Y-m-d", $pay_date1_unix);
				break;
			}
			return $return_val;
		}

		/*******************************************
		PRIVATE METHODS START HERE
		*******************************************/


		/**
		* @return date
		* @param date $calculated_pay_date - The calculated pay date (w/o paper check adjustment)
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function Adjust_For_No_Direct_Deposit($calculated_pay_date)
		{
			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// add one day
			$calculated_pay_date = date ("Y-m-d", strtotime ("1 day", strtotime ($calculated_pay_date)));

			// Account for weekends.
			$calculated_pay_date = $this->Fix_Weekend ($calculated_pay_date, FALSE);

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date, FALSE);
			}

			//return the new pay date
			return $calculated_pay_date;
		}


		/**
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param string $day_string_one - SUN, MON, TUE, WED, THU, FRI, SAT
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function DW ($start_date, $day_string_one)
		{
			// Convert the day string to an integer
			$day_int = $this->Day_String_to_Int($day_string_one);

			// Get the difference
			$day_diff = $day_int - (int)date ("w", strtotime ($start_date));

			// Test for positive
			if ($day_diff <= 0)
			{
				$day_diff += 7;
			}

			// Calculate the date
			$calculated_pay_date = date ("Y-m-d", strtotime ($day_diff." day", strtotime ($start_date)));
			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Get the value we found before holidays messed it up
			$pre_holiday = $calculated_pay_date;

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
			}

			// Did we pass the start date?
			if (strtotime ($calculated_pay_date) <= strtotime ($start_date))
			{
				// Grab the next future paydate because we cannot be in the past using what this calculated paydate would have been
				$calculated_pay_date = $this->DW ($pre_holiday, $day_string_one);
			}

			// Return the date
			return $calculated_pay_date;
		}

		/**
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param date $next_pay_date - The date used to start the every other week cycle
		* @param string $day_string_one - SUN, MON, TUE, WED, THU, FRI, SAT
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function DWPD ($start_date, $next_pay_date, $day_string_one)
		{
			// Clugy, but accurate (I think)

			if (empty($next_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

		// Check the next paydate to determine if it is on a $day_string_one
			if (strtoupper (substr ($day_string_one, 0, 3)) != strtoupper (date ("D", strtotime ($next_pay_date))))
			{
				// The dates do not match, bail
				return FALSE;
			}

			// Convert dates to integers
			$start_int = strtotime ($start_date);
			$next_int = strtotime ($next_pay_date);
			
			if ($start_int == -1 || $next_int == -1)
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Test if they are different
			if ($start_int != $next_int)
			{
				// The dates are different, find a date that is within reason and on cycle
				if ($start_int > $next_int)
				{
					// The future
					$temp_int = $next_int;
					// <= to make sure we get a date in the FUTURE, not today
					while ($temp_int <= $start_int)
					{
						$temp_int = strtotime ("+14 day", $temp_int);
						if ($temp_int == -1) return FALSE; // Vegas, we have a problem
					}

					// Set the date
					$calculated_pay_date = date ("Y-m-d", $temp_int);
				}
				else
				{
					// The past
					$temp_int = $next_int;
					while ($temp_int > $start_int)
					{
						$temp_int = strtotime ("-14 day", $temp_int);
						if ($temp_int == -1) return FALSE; // Vegas, we have a problem
					}

					// We end up one pay day in the past, move it forward then set the date
					$calculated_pay_date = date ("Y-m-d", strtotime ("+14 day", $temp_int));
				}
			}
			else
			{
				// The dates are the same
				$calculated_pay_date = date ("Y-m-d", strtotime ("+14 day", strtotime ($start_date)));
			}

			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Get the value we found before holidays messed it up
			$pre_holiday = $calculated_pay_date;

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
			}

			// Did we pass the start date?
			if (strtotime ($calculated_pay_date) <= strtotime ($start_date))
			{
				// Grab the next future paydate because we cannot be in the past using what this calculated paydate would have been
				$pay_dates = $this->DWPD ($pre_holiday, $pre_holiday, $day_string_one);
				if ($pay_dates === FALSE)
				{
					// Vegas, we have a problem
					return FALSE;
				}
				$calculated_pay_date = $pay_dates["pay_date"];
			}

			return array ("pre_holiday" => $pre_holiday, "pay_date" => $calculated_pay_date);
		}

		/**
		* Calculate paydates for FOUR_WEEKLY.
		* 
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param date $next_pay_date - The date used to start the every other week cycle
		* @param string $day_string_one - SUN, MON, TUE, WED, THU, FRI, SAT
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function DWPD_FW ($start_date, $next_pay_date, $day_string_one)
		{
			// Clugy, but accurate (I think)

			if (empty($next_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

		// Check the next paydate to determine if it is on a $day_string_one
			if (strtoupper (substr ($day_string_one, 0, 3)) != strtoupper (date ("D", strtotime ($next_pay_date))))
			{
				// The dates do not match, bail
				return FALSE;
			}

			// Convert dates to integers
			$start_int = strtotime ($start_date);
			$next_int = strtotime ($next_pay_date);
			
			if ($start_int == -1 || $next_int == -1)
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Test if they are different
			if ($start_int != $next_int)
			{
				// The dates are different, find a date that is within reason and on cycle
				if ($start_int > $next_int)
				{
					// The future
					$temp_int = $next_int;
					// <= to make sure we get a date in the FUTURE, not today
					while ($temp_int <= $start_int)
					{
						$temp_int = strtotime ("+28 day", $temp_int);
						if ($temp_int == -1) return FALSE; // Vegas, we have a problem
					}

					// Set the date
					$calculated_pay_date = date ("Y-m-d", $temp_int);
				}
				else
				{
					// The past
					$temp_int = $next_int;
					while ($temp_int > $start_int)
					{
						$temp_int = strtotime ("-28 day", $temp_int);
						if ($temp_int == -1) return FALSE; // Vegas, we have a problem
					}

					// We end up one pay day in the past, move it forward then set the date
					$calculated_pay_date = date ("Y-m-d", strtotime ("+28 day", $temp_int));
				}
			}
			else
			{
				// The dates are the same
				$calculated_pay_date = date ("Y-m-d", strtotime ("+28 day", strtotime ($start_date)));
			}

			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Get the value we found before holidays messed it up
			$pre_holiday = $calculated_pay_date;

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
			}

			// Did we pass the start date?
			if (strtotime ($calculated_pay_date) <= strtotime ($start_date))
			{
				// Grab the next future paydate because we cannot be in the past using what this calculated paydate would have been
				$pay_dates = $this->DWPD_FW ($pre_holiday, $pre_holiday, $day_string_one);
				if ($pay_dates === FALSE)
				{
					// Vegas, we have a problem
					return FALSE;
				}
				$calculated_pay_date = $pay_dates["pay_date"];
			}

			return array ("pre_holiday" => $pre_holiday, "pay_date" => $calculated_pay_date);
		}
				
		/**
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param int $day_int_one - The day of the month for the first payday (1-32)
		* @param int $day_int_two - The day of the monthe for the second payday (1-32).  Should be AFTER $day_int_one.
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function DMDM ($start_date, $day_int_one, $day_int_two)
		{
			if ($day_int_one >= $day_int_two)
			{
				// The first day after or on the second day.  Bad, punt
				return FALSE;
			}

			// convert the start date to int
			$start_int = strtotime ($start_date);

			// Grab the month and the year
			$month = (int)date ("m", $start_int);
			$year = (int)date ("Y", $start_int);

			// Is day two the last day of the month?
			$last_day = 32;
			
			// Find the last day of the month (for February weirdness)
			while(!checkdate($month, --$last_day, $year));
			
			if ($day_int_two == 32 || ($day_int_two > $last_day))
			{
				// We need to use the last day of the month
				while (!checkdate($month, --$day_int_two, $year));
			}

			// Get the one and two dates
			$one_int = strtotime ($year."-".$month."-".$day_int_one);
			$two_int = strtotime ($year."-".$month."-".$day_int_two);

			// Where is the start date in relation to the monthly dates?
			if ($start_int < $one_int)
			{
				// Before the first date

				// Use pre holiday so we have a "pure" start point for recursion
				$pre_holiday = date ("Y-m-d", $one_int);
			}
			else if ($start_int < $two_int)
			{
				// Before the second date

				// Use pre holiday so we have a "pure" start point for recursion
				$pre_holiday = date ("Y-m-d", $two_int);
			}
			else
			{
				// After both dates

				// Use pre holiday so we have a "pure" start point for recursion			
				$pre_holiday = date ("Y-m-d",  
					strtotime ("+1 month", strtotime("{$year}-{$month}-{$day_int_one}"))
					//	strtotime ($year."-".++$month."-".$day_int_one)
				);
			}

			// Account for weekends.
			$calculated_pay_date = $this->Fix_Weekend ($pre_holiday);
			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
			}

			// Did we pass the start date?
			if (strtotime ($calculated_pay_date) <= strtotime ($start_date))
			{
				// Grab the next future paydate because we cannot be in the past using what this calculated paydate would have been
				$calculated_pay_date = $this->DMDM ($pre_holiday, $day_int_one, $day_int_two);
			}

			return $calculated_pay_date;
		}

		/**
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param int $week_one - The week number for the first paycheck (1-4)
		* @param int $week_two - The week number for the second paycheck (1-4) should be after $week_one
		* @param string $day_string_one - MON, TUE, WED, THU, FRI, SAT, SUN
		* @param date prev_start_date - ONLY FOR RECURSION USE, DO NOT USE EXTERNALLY
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function WWDW ($start_date, $week_one, $week_two, $day_string_one, $prev_start_date = NULL)
		{
			// convert the start date to int
			$start_int = strtotime ($start_date);

			// Grab the month and the year
			$month = (int)date ("m", $start_int);
			$year = (int)date ("Y", $start_int);

			// Set the day string to proper format
			$day_to_use = strtoupper (substr ($day_string_one, 0, 3));

			$days = $this->Get_Weeks($month, $year, $day_to_use);

			// Where is the start date in relation to the monthly dates?
			if ($start_int < $days [$week_one] || ($start_int == $days [$week_one] && !is_null ($prev_start_date) && $days [$week_one] > strtotime ($prev_start_date)))
			{
				// Before the first date
				$calculated_pay_date = date ("Y-m-d", $days [$week_one]);
			}
			else if ($start_int < $days [$week_two])
			{
				// Before the second date
				$calculated_pay_date = date ("Y-m-d", $days [$week_two]);
			}
			else
			{
				// After both dates, recurse to find
				$new_time = mktime(0, 0, 0, $month + 1, 1, $year);
				$calculated_pay_date = $this->WWDW(
					date('Y-m-d', $new_time),
					$week_one,
					$week_two,
					$day_string_one,
					$start_date
				);
			}
			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Get the value we found before holidays messed it up
			$pre_holiday = $calculated_pay_date;

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
			}

			// Reduce the number of times this is called
			$calc_int = strtotime ($calculated_pay_date);

			// Did we pass the start date?
			if
			(
				// Start date cannot be after the calculated pay date
				($calc_int < $start_int) ||

				// If the calc and start are equal AND check calc against prev AND prev exits (Should be used when date is on the first of the month
				(!is_null ($prev_start_date) && $calc_int == $start_int && $calc_int < strtotime ($prev_start_date)) ||

				// The calc and start are equal AND no previous (Should be used when date did not advance
				($calc_int == $start_int && is_null ($prev_start_date))
			)
			{
				// Try again via recursion
				$calculated_pay_date = $this->WWDW ($pre_holiday, $week_one, $week_two, $day_string_one, $start_date);
			}

			return $calculated_pay_date;
		}

		/**
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param int $day_int_one - The day of the month the pay day is on (1-32)
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function DM ($start_date, $day_int_one)
        {
            // convert the start date to int
            $start_int = strtotime ($start_date);

            // Grab the month and the year
            $month = (int)date ("m", $start_int);
            $year = (int)date ("Y", $start_int);

            // Assign a variable for local use so I can preserve the original value
            $day_int = $day_int_one;

            // Is day one the last day of the month?
            $last_day = 32;
           
            // Find the last day of the month (for wierdness in feb)
            while (!checkdate($month, --$last_day, $year));
           
            if (($day_int == 32) || ($day_int > $last_day))
            {
                $day_int = $last_day;
            }
           
            // Get the date
            $one_int = strtotime ($year."-".$month."-".$day_int);

            // Where is the start date in relation to the monthly dates?
            if ($start_int < $one_int)
            {
                // Before the first date, test for weekend and run
                $pre_holiday = date ("Y-m-d", $one_int);
            }
            else
            {
                // Into the next month

                // Check for the last day of the month
                $day_int = $day_int_one;
               
                // If we are going beyond the end of the year, move the year
                if (($month + 1) == 13)
                {
                    // Change the year
                    $year ++;

                    // 1 will be added to the month, set to 0
                    $next_month = 1;
                }
                else
                {
                    $next_month = $month + 1;
                }

                // Is day one the last day of the month?
                $last_day = 32;
               
                // Find the last day of the month (for wierdness in feb)
                while (!checkdate($next_month, --$last_day, $year));
           
                if ($day_int == 32 || $day_int > $last_day)
                {
                    $day_int = $last_day;
                }

                // Get the date
                $one_int = strtotime ($year."-".$next_month."-".$day_int);

                $pre_holiday = date ("Y-m-d", $one_int);
            }

            $calculated_pay_date = $this->Fix_Weekend ($pre_holiday);
            // Get the value we found before holidays messed it up
            //$pre_holiday = $calculated_pay_date;

			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

            // Test for holiday
            if (array_key_exists ($calculated_pay_date, $this->holiday_array))
            {
                // We have a holiday
                $calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
            }

            // Reduce the number of times this is called
            $calc_int = strtotime ($calculated_pay_date);

            // Did we pass the start date?
            if ($calc_int <= $start_int)
            {
                // Grab the next future paydate because we cannot be in the past using what this calculated paydate would have been
                $calculated_pay_date = $this->DM ($pre_holiday, $day_int_one);
            }

            return $calculated_pay_date;
        }

		/**
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param int $week_one - The week number for the first paycheck (1-4)
		* @param string $day_string_one - MON, TUE, WED, THU, FRI, SAT, SUN
		* @param date prev_start_date - ONLY FOR RECURSION USE, DO NOT USE EXTERNALLY
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function WDW ($start_date, $week_one, $day_string_one, $prev_start_date = NULL)
		{
			// convert the start date to int
			$start_int = strtotime ($start_date);

			// Grab the month and the year
			$month = (int)date ("m", $start_int);
			$year = (int)date ("Y", $start_int);

			// Set the day string to proper format
			$day_to_use = strtoupper (substr ($day_string_one, 0, 3));

			$days = $this->Get_Weeks($month, $year, $day_to_use);

			// Where is the start date in relation to the monthly dates?
			if ($start_int < $days [$week_one])
			{
				// Before the first date
				$calculated_pay_date = date ("Y-m-d", $days [$week_one]);
			}
			// Are they equal AND there is something in the previous date
			else if ($start_int == $days [$week_one] && !is_null ($prev_start_date))
			{
				// The dates are equal

				// Explode the start date into parts
				list ($prev_year, $prev_month, $prev_day) = explode ("-", $prev_start_date);

				// The current start month != the previous start month AND  the last start date is a real date
				if (date ("m", $start_int) != date ("m", strtotime ($prev_start_date)) && checkdate ($prev_month, $prev_day, $prev_year))
				{
					// Allow this date to be used
					$calculated_pay_date = date ("Y-m-d", $days [$week_one]);
				}
				else
				{
					// The date is bad, try again a month later
					//$calculated_pay_date = $this->WDW (date ("Y-m-d", strtotime ($year."-".($month + 1)."-1")), $week_one, $day_string_one, $start_date);		// The date is bad, try again a month later
					$calculated_pay_date = $this->WDW (date ("Y-m-d", strtotime ("+1 month", strtotime("{$year}-{$month}-1"))), $week_one, $day_string_one, $start_date);
		
				}
			}
			else
			{
				// The date is bad, try again a month later
				$calculated_pay_date = $this->WDW (date ("Y-m-d", strtotime ("+1 month", strtotime("{$year}-{$month}-1"))), $week_one, $day_string_one, $start_date);
			}
			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Get the value we found before holidays messed it up
			$pre_holiday = $calculated_pay_date;

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
			}

			// Convert once for all the if stuff
			$calc_int = strtotime ($calculated_pay_date);

			// Did we pass the start date OR the dates are equal and there is no previous date to compare
			// I had to add a check for the prev_start_date only when the dates were equal to handle some wierdness.  CHANGE THIS AT YOUR OWN RISK!!!!
			if ($calc_int < $start_int || ($calc_int == $start_int && is_null($prev_start_date)))
			{
				// Grab the next future paydate because we cannot be in the past using what this calculated paydate would have been
				$calculated_pay_date = $this->WDW ($pre_holiday, $week_one, $day_string_one, $start_date);
			}

			return $calculated_pay_date;
		}

		/**
		* @return date
		* @param date $start_date - The date to start calculating from
		* @param string $day_string_one - SUN, MON, TUE, WED, THU, FRI, SAT
		* @param int $day_int_one - The day of the month the pay date is after
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function DWDM ($start_date, $day_string_one, $day_int_one)
		{
			// convert the start date to int
			$start_int = strtotime ($start_date);

			// Grab the month and the year
			$month = (int)date ("m", $start_int);
			$year = (int)date ("Y", $start_int);

			// Convert the day string to an integer
			$day_int = $this->Day_String_to_Int($day_string_one);

			// Convert the point in time date
			$point_int = strtotime ($year."-".$month."-".$day_int_one);

			// Get the difference
			$day_diff = $day_int - (int)date ("w", $point_int);

			// Test for positive
			if ($day_diff <= 0)
			{
				$day_diff += 7;
			}

			// Calculate the one_date
			$one_int = strtotime ($day_diff." day", $point_int);

			// Where is the start date in relation to the monthly dates?
			if ($start_int < $one_int)
			{
				// Before the first date, test for weekend and run
				$calculated_pay_date = date ("Y-m-d", $one_int);
			}
			else
			{
				// Bounce to the next month and try again
				$calculated_pay_date = $this->DWDM (date ("Y-m-d", 
					//strtotime ($year."-".($month + 1)."-1")
					strtotime ("+1 month", strtotime("{$year}-{$month}-1"))
				), $day_string_one, $day_int_one);
			}
			if (empty($calculated_pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			// Get the value we found before holidays messed it up
			$pre_holiday = $calculated_pay_date;

			// Test for holiday
			if (array_key_exists ($calculated_pay_date, $this->holiday_array))
			{
				// We have a holiday
				$calculated_pay_date = $this->Fix_Holiday ($calculated_pay_date);
			}

			// Did we pass the start date?
			if (strtotime ($calculated_pay_date) <= strtotime ($start_date))
			{
				// Grab the next future paydate because we cannot be in the past using what this calculated paydate would have been
				$calculated_pay_date = $this->DWDM ($pre_holiday, $day_string_one, $day_int_one);
			}

			return $calculated_pay_date;
		}

		/**
		* @return int
		* @param string $day_string - The string to convert to an integer.  The interger matches the integers from date ("w")
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function Day_String_to_Int ($day_string)
		{
			// Initialize to a bad day
			$day_int = FALSE;

			switch (strtoupper (substr ($day_string, 0, 3)))
			{
				case "SUN":
					$day_int = 0;
				break;

				case "MON":
					$day_int = 1;
				break;

				case "TUE":
					$day_int = 2;
				break;

				case "WED":
					$day_int = 3;
				break;

				case "THU":
					$day_int = 4;
				break;

				case "FRI":
					$day_int = 5;
				break;

				case "SAT":
					$day_int = 6;
				break;

				default: // Just to make sure
					$day_int = FALSE;
				break;

			}

			return $day_int;
		}

		/**
		* @return array
		* @param int $month - The month as an integer
		* @param int $year - The year as an integer
		* @param string $day - The day of the week as a string
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function Get_Weeks ($month, $year, $day)
		{
			// Find the first day string.  It is critical this is the first week of the month with that "DAY" in it
			for ($day_count = 1; $day_count <= 7; $day_count++)
			{
				// Test for day match
				if ($day == strtoupper (date ("D", strtotime ($year."-".$month."-".$day_count))))
				{
					// we have a match, set the first week of the month
					$days [1] = strtotime ($year."-".$month."-".$day_count);
					break;
				}
			}

			// Find the rest
			for ($week_count = 2; $week_count <= 5; $week_count++)
			{
				$days [$week_count] = strtotime ("+7 day", $days[($week_count-1)]);
			}

			return $days;
		}

		/**
		* @return date
		* @param date $pay_date - The date to check for weekend
		* @param boolean $direct_deposit - OPTIONAL, send FALSE if adjusting for paper check rules (assumes TRUE)
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function Fix_Holiday ($pay_date, $direct_deposit = TRUE)
		{
			if (empty($pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			if ($this->holiday_weekend_check)
			{
				$pay_date_time = strtotime ($pay_date);

				// Test for holiday (A little recursion should do it)
				if (array_key_exists ($pay_date, $this->holiday_array))
				{
					//echo "Fixing Holiday for date: {$pay_date}\n";
					// Move the date one day
					if($direct_deposit)
					{
						$pay_date_time = strtotime ("-1 day", $pay_date_time);
					}
					else
					{
						$pay_date_time = strtotime ("1 day", $pay_date_time);
					}

					$pay_date_time = strtotime ($this->Fix_Holiday (date ("Y-m-d", $pay_date_time), $direct_deposit));
				}

				$pay_date_time = strtotime ($this->Fix_Weekend(date ("Y-m-d", $pay_date_time), $direct_deposit));

				return date ("Y-m-d", $pay_date_time);
			}
			return $pay_date;
		}

		/**
		* @return date
		* @param date $pay_date - The date to check for holiday
		* @param boolean $direct_deposit - OPTIONAL, send FALSE if adjusting for paper check rules (assumes TRUE)
		* @desc PRIVATE METHOD -- DO NOT USE!!!!
		*/
		function Fix_Weekend ($pay_date, $direct_deposit = TRUE)
		{
			if (empty($pay_date))
			{
				// Vegas, we have a problem
				return FALSE;
			}

			if ($this->holiday_weekend_check)
			{
				//echo "Fixing Weekend for date: {$pay_date}\n";
				$date_int = strtotime ($pay_date);
				// Grab the day name
				$day_string = strtoupper (date ("D", $date_int));

				// Test if day is sat or sun
				while ($day_string == "SAT" || $day_string == "SUN")
				{
					if($direct_deposit)
					{
						$date_int = strtotime ("-1 day", $date_int);
					}
					else
					{
						$date_int = strtotime ("1 day", $date_int);
					}
					$day_string = strtoupper (date ("D", $date_int));
				}

				return date ("Y-m-d", $date_int);
			}
			return $pay_date;
		}




	}

?>
