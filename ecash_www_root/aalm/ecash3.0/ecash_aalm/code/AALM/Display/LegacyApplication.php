<?php

class AALM_Display_LegacyApplication extends ECash_Display_LegacyApplication
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

	public static function setBankingStartDate($value, &$response)
	{
		$response->banking_start_date = $value;
		if($value)
		{
			$secs = time() - strtotime($value); // Get the difference in seconds
			$yrs = date("Y", $secs) - 1970; // Subtract the epoch date      
			$mos = date("n", $secs);
                        
			$response->banking_duration = "{$yrs}yrs {$mos}mos";
		}
		else
		{	
			$response->banking_duration = "n/a";
		}
	}

	public static function setResidenceStartDate($value, &$response)
	{
		$response->residence_start_date = $value;
		
		if ($value)
        {
			$secs = time() - strtotime($value); // Get the difference in seconds
			$yrs = date("Y", $secs) - 1970; // Subtract the epoch date      
			$mos = date("n", $secs);
                        
			$response->residence_duration = "{$yrs}yrs {$mos}mos";
		}
		else
		{	
			$response->residence_duration = "n/a";
		}
	}

	//these next two go hand in hand and must appear in this order in the query
	public static function setDateHire($value, &$response)
	{

		if ($value)
		{
			if (strpos($value,' ')) list($date, $time) = explode(' ', $value);
			else $date = $value;

			// Since a person can theoretically have worked at the same job
			// since before the epoch (maybe that have excellent fringe benefits)
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
		  	 //this NULL is a breadcrumb for the next function
			$response->date_hire           = 'n/a';
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
