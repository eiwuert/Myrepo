<?php

class ECash_Display_LegacyApplication extends ECash_Display_LegacyHandler
{	
	public static function loadAll($db_row, &$response)
	{
		foreach($db_row as $column => $value)
		{
			//a hack to not use numerically indexed columns
			if(!is_numeric($column))
			{
				$name_short = str_replace('_', '', $column);
				if(method_exists(__CLASS__, 'set' . $name_short))
				{
					call_user_func_array(array(__CLASS__, 'set' . $name_short), array($value, $response));
				}
				else
				{
					$response->$column = $value;
				}
			}
		}
	}

	public static function setDateModified($value, &$response)
	{
		//re-alias to different name
		$response->lock_chk_date = $value;
	}

	public static function setDateCreated($value, &$response)
	{		
		self::setFormattedDate('date_created', $value, $response);
	}

	public static function setOLPProcess($value, &$response)
	{
		//set the original too
		$response->olp_process = $value;
		
		$display_value = NULL;
		
		switch($value)
		{
			case 'email_confirmation':
				$display_value = 'Email Confirmation';
				break;

			case 'online_confirmation':
				$display_value = 'Online Confirmation';
				break;

			case 'email_react':
				$display_value = 'Marketing Email Re-Act';
				break;

			case 'cs_react':
				$display_value = 'User Initiated Re-Act';
				break;

			case 'ecashapp_react':
				$display_value = 'Agent Initiated Re-Act';
				break;

			default:
				$display_value = $value;
				break;
		}

		$response->olp_process_display = $display_value;		
	}

	public static function setDateFirstPayment($value, &$response)
	{
		self::setFormattedDate('date_first_payment', $value, $response);
	}

	public static function setDateFundActual($value, &$response)
	{
		$fund_actual_ts = NULL;
		if(empty($value)) // CASE WHEN ap.date_fund_actual is null 
		{
			//THEN DATE_FORMAT(current_date(),'%m-%d-%Y')
			$fund_actual_ts = time();
		}
		else
		{
			//( ELSE DATE_FORMAT(date_fund_actual,'%m-%d-%Y') END ) as date_fund_actual,
			$fund_actual_ts = strtotime($value);
		}

		self::setFormattedDateFromTS('date_fund_actual', $fund_actual_ts, $response);
		self::setMDYFromTS('date_fund_actual', $fund_actual_ts, $response);
		
		//also change the name
		self::setFormattedDateFromTS('date_fund_stored', $fund_actual_ts, $response);
	}
	
	public static function setLastPaydate($value, &$response)
	{
		//don't use the regular format
        //DATE_FORMAT(ap.last_paydate, '%Y-%m-%d') as last_paydate,
		//format the date (remembering it's mysql format)
		if(! empty($value))
		{
			$response->last_paydate = date('Y-m-d', strtotime($value));
		}
		else
		{
			$response->last_paydate = NULL;
		}
	}

	//the next two functions are to accomplish this:
	//IF(ap.fund_actual > 0, ap.fund_actual, ap.fund_qualified) as fund_amount
	public static function setFundActual($value, &$response)
	{
		$response->fund_actual = $value;
		
		if($value > 0)
			$response->fund_amount = $value;
		else
			//this NULL is a breadcrumb for the next function
			$response->fund_amount = NULL;
	}

	public static function setFundQualified($value, &$response)
	{
		if(empty($response->fund_amount) || is_null($response->fund_amount))
			$response->fund_amount = $value;

		$response->fund_qualified = $value;
	}

	public static function setDateFundEstimated($value, &$response)
	{
		$ts = strtotime($value);
		self::setFormattedDateFromTS('date_fund_estimated', $ts, $response);
		self::setMDYFromTS('date_fund_estimated', $ts, $response);
	}

	public static function setZipCode($value, &$response)
	{
		$response->zip = $value;
	}

	public static function setDOB($value, &$response)
	{
		$ts = strtotime($value);
		self::setFormattedDateFromTS('dob', $ts, $response);
		self::setMDYFromTS('dob', $ts, $response);
	}
	
	public static function setEmail($value, &$response)
	{
		$response->customer_email = $value;
	}
	
	//these next two go hand in hand and must appear in this order in the query
	public static function setDateHire($value, &$response)
	{

		if ($value)
		{
			list($date, $time) = explode(' ', $value);

			// Since a person can theoretically have worked at the same job
			// since before the epoch (maybe they have excellent fringe benefits)
			// I need to treat this like it's not a UNIX timestamp. [benb]
			list($year, $month, $day) = explode('-', $date);

			$yrs     = date('Y') - $year;

			$tmonths = date('m');

			if ($tmonths < $month)
			{
				$tmonths += 12;
				$yrs--;
			}

			$mos = $tmonths - $month;

			$response->date_hire           = $date;
			$response->employment_duration = "{$yrs}yrs {$mos}mos";
		}
		else
		{	 
			$response->date_hire           = 'n/a';
			//this NULL is a breadcrumb for the next function
	       	$response->employment_duration = NULL;                        
		}
	}

	public static function setJobTenure($value, &$response)
	{
		if(is_null($response->employment_duration))
		{		
			switch ($value)
			{
				case 1:
					$response->employment_duration="0 to 6 Months";
					break;
				case 2:
					$response->employment_duration="6 to 12 Months";
					break;
				case 3:
					$response->employment_duration="12+ Months";
					break;
				case 4:
					$response->employment_duration="Not Presently Employed";
					break;
				case 5:
					$response->employment_duration="Retired";
					break;
				default:
					$response->employment_duration = "n/a";
					break;		
			}
		}
	}	
}

?>