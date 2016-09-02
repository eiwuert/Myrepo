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
		self::setFormattedDate('date_fund_stored', $value, $response);
	}
	
	public static function setLastPaydate($value, &$response)
	{
		//don't use the regular format
        //DATE_FORMAT(ap.last_paydate, '%Y-%m-%d') as last_paydate,
		//format the date (remembering it's mysql format)
		$response->last_paydate = date('Y-m-d', strtotime($value));		
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
		if(is_null($response->fund_amount))
			$response->fund_amount = $value;
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
	
	public static function setDateHire($value, &$response)
	{
		$ts = strtotime($value);
		self::setFormattedDateFromTS('date_hire', $ts, $response);
		self::setMDYFromTS('date_hire', $ts, $response);
	}

	public static function setEmail($value, &$response)
	{
		$response->customer_email = $value;
	}
	
}

?>