<?php
class Pay_Date_Validation
{
	public $errors;
	public $pay_stamp_1;
	public $pay_stamp_2;
	public $pay_stamp_3;
	public $pay_stamp_4;
	public $checks;
	public $pay_dates;
	public $validate_4_paydates;
	private $is_it = 0;
   private $record_bad_paydate;
   private $stats_info;
   private $stats_session;
   private $stats_event;
   private $stats_applog;
   private $stats_application_id;
	
	
	public function __construct( $form_data, $holiday_array, $limits = NULL) 
	{
		//$this->errors = array(); so that error checking doesn't fail below
		$this->holiday_array = $holiday_array;
		$this->form_data = $form_data;
		$this->pay_dates = array();
		$this->checks = array();
		$this->validate_4_paydates = FALSE;
		$this->limits = $limits;

      /*
         $this->stats_info - This value is set to FALSE by default
         and then when the values for the necessary to hit stats is
         there then we will set this to TRUE
      */
      $this->stats_info = FALSE;

      $this->stats_session = NULL;
      $this->stats_event = NULL;
      $this->stats_applog = NULL;
      $this->stats_application_id = NULL;


      /* 
      This code was set to true so we would accept the leads that were coming
      through and not send back an error because we would lose the chance
      to actually purchase the lead - the vendor would move on to the next
      person that was willing to accept the lead

      -- We are going to update the code so that we do not bypass the error
      checking, we will instead, record a stat, accept the lead and if we
      get approval from CLK we will prompt the user to redo his pay dates 
      so we have "correct" information --
      */
		$this->bypass_error_checking = TRUE;
      $this->record_bad_paydate = TRUE;
		
		// We use this flag to bypass some of the validations based on accept_level.
		//   We can change this to work from webadmin2 by adding the flag into the limit object, 
		//   and changing bypass_error_checking to reflect that flag.
		//if ($this->limits->accept_level >= 2 && isset($this->limits->accept_level))
		//	$this->bypass_error_checking = TRUE;
	}
		
   public function setStatsInformation($stats_session,$stats_event,$stats_applog,$stats_application_id)
   {
   // Stats::Hit_Stats("bad_twice_monthly", $this->stats_session, $this->stats_event, $this->stats_applog, $this->stats_application_id );
      $this->stats_session = $stats_session;
      $this->stats_event = $stats_event;
      $this->stats_applog = $stats_applog;
      $this->stats_application_id = $stats_application_id;

      $this->stats_info = TRUE;
   }

	public function Validate_Paydates( ) 
	{
		$twice['00'] = 0;
		$twice['01'] = 0; 
		$twice['02'] = 0;
		$twice['03'] = 0;
		$twice['04'] = 0;
		$twice['05'] = 0;
		$twice['06'] = 0;
		$twice['07'] = 0;
		$twice['08'] = 0;
		$twice['09'] = 0;
		$twice['10'] = 0;
		$twice['11'] = 0;
		
		// create dates
		if (isset($this->form_data['pay_date1'])) $this->pay_dates["pay_date1"] = $this->form_data['pay_date1'];
		if (isset($this->form_data['pay_date2'])) $this->pay_dates["pay_date2"] = $this->form_data['pay_date2'];

		// create paydates if we need to otherwise put the into the correct format for use	
		if(!isset($this->form_data["pay_date3"]))
		{
			$this->_Create_Paydates();
			
			$this->validate_4_paydates = FALSE;
		} 
		else 
		{
			$this->validate_4_paydates = TRUE;
			if (isset($this->form_data['pay_date3']))
				$this->pay_dates["pay_date3"] = $this->form_data['pay_date3'];
			if (isset($this->form_data['pay_date4']))
				$this->pay_dates["pay_date4"] = $this->form_data['pay_date4'];
		}
		
		//echo "<pre>";print_r($this->pay_dates);exit;
		
		//convert to timestamps for easy date calculationpay_date1_weekly_far
		if (isset($this->pay_dates['pay_date1']))
			$this->pay_stamp_1 = strtotime($this->pay_dates["pay_date1"]);
		if (isset($this->pay_dates['pay_date2']))
			$this->pay_stamp_2 = strtotime($this->pay_dates["pay_date2"]);
		if (isset($this->pay_dates['pay_date3']))
			$this->pay_stamp_3 = strtotime($this->pay_dates["pay_date3"]);
		if (isset($this->pay_dates['pay_date4']))
			$this->pay_stamp_4 = strtotime($this->pay_dates["pay_date4"]);
		
		// make sure no dates are in the past
		$ts_current = mktime(0,0,0);
		if (isset($this->pay_stamp_1) && ($this->pay_stamp_1 < $ts_current))
			$this->errors[] = "pay_date1_past";
		if (isset($this->pay_stamp_2) && ($this->pay_stamp_2 < $ts_current) && !$this->bypass_error_checking )
			$this->errors[] = "pay_date2_past";
		
		//check that the first paydate is within a sane amount of time from now
		$this->_Advanced_Payday();
		
		//make sure no dates fall on a weekend or holiday
		if (isset($this->pay_stamp_1)
		&& ($this->_Is_Holiday ($this->pay_stamp_1)
		|| $this->_Is_Weekend ($this->pay_stamp_1)))
		{
			$this->errors[] = "pay_date1_weekend_holiday";
		}
			
		if (!$this->bypass_error_checking)
		{
			if (isset($this->pay_stamp_2)
			&& ($this->_Is_Holiday ($this->pay_stamp_2)
			|| $this->_Is_Weekend ($this->pay_stamp_2)))
			{
				$this->errors[] = "pay_date2_weekend_holiday";
			}
		}
		
		if (isset($this->validate_4_paydates) && $this->validate_4_paydates)
		{	
			if (isset($this->pay_stamp_3) && ($this->pay_stamp_3 < $ts_current))
				$this->errors[] = "pay_date3_past";
			if (isset($this->pay_stamp_4) && ($this->pay_stamp_4 < $ts_current))
				$this->errors[] = "pay_date4_past";
			if (isset($this->pay_stamp_3)
					&& ($this->_Is_Holiday ($this->pay_stamp_3)
						|| $this->_Is_Weekend ($this->pay_stamp_3)))
				$this->errors[] = "pay_date3_weekend_holiday";
			if (isset($this->pay_stamp_4)
					&& ($this->_Is_Holiday ($this->pay_stamp_4)
						|| $this->_Is_Weekend ($this->pay_stamp_4)))
				$this->errors[] = "pay_date4_weekend_holiday";
		}
			
		if (!count($this->errors))
		{
			if (isset($this->form_data["income_frequency"]))
			{
				switch ($this->form_data["income_frequency"])
				{
					case "WEEKLY":
						//generate the date checks
						$this->_Gen_Date_Checks();
					
						//check the dates input against the available good dates
						$this->_Check_vs_Input();
					break;
			
					case "BI_WEEKLY":
						//generate the date checks
						$this->_Gen_Date_Checks();
							$this->validate_4_paydates = FALSE;			
						//check the dates input against the available good dates
						$this->_Check_vs_Input();
					break;
			
					case "TWICE_MONTHLY":
						if (isset($this->pay_stamp_1))
						{
							$twice[date("m", $this->pay_stamp_1)]++;
							$d1 = date("d", $this->pay_stamp_1);
						}
						if (isset($this->pay_stamp_2))
						{
							$twice[date("m", $this->pay_stamp_2)]++;
							$d2 = date("d", $this->pay_stamp_2);
						}
						if (isset($this->pay_stamp_3))
							$twice[date("m", $this->pay_stamp_3)]++;
						if (isset($this->pay_stamp_4))
							$twice[date("m", $this->pay_stamp_4)]++;
							
						if($d1 == $d2 && $this->record_bad_paydate)
						{
							//$this->errors[] = "invalid_twice_monthly";
                     Stats::Hit_Stats("bad_twice_monthly", 
                        $this->stats_session, 
                        $this->stats_event, 
                        $this->stats_applog,
                        $this->stats_application_id );
;
						}
						foreach($twice as $month => $num_dates)
						{
							if ($num_dates > 2 && !$this->bypass_error_checking)
								$this->errors[] = "too_many_twice_monthly";
						}
					break;
				
					case "MONTHLY":
						if (isset($this->pay_stamp_1))
							$twice[date("m", $this->pay_stamp_1)]++;
						if (isset($this->pay_stamp_2))
							$twice[date("m", $this->pay_stamp_2)]++;
						/*if (isset($this->pay_stamp_3))
							$twice[date("m", $this->pay_stamp_3)]++;
						if (isset($this->pay_stamp_4))
							$twice[date("m", $this->pay_stamp_4)]++;
						*/
						foreach($twice as $num_dates)
						{
							if ($num_dates > 1 && !$this->bypass_error_checking)
								$this->errors[] = "too_many_monthly";
						}
					break;
				}
			}
		}
		
		return array("errors" => $this->errors, "pay_dates" => $this->pay_dates );
	}
	
	// will create the extra paydate for the forms with only two pay dates
	public function _Create_Paydates( ) 
	{
		
		if (isset($this->form_data["income_frequency"]))
		{
			switch ($this->form_data["income_frequency"] )
			{
				case "WEEKLY":
					if ( isset($this->pay_dates['pay_date2']) )
					{
						$this->pay_dates["pay_date3"]
							= date("Y-m-d",
										strtotime($this->pay_dates["pay_date2"]) + 604800); //+1 Week
						$this->pay_dates["pay_date4"]
							= date("Y-m-d",
										strtotime($this->pay_dates["pay_date3"]) + 604800); //+1 Week
					}
				break;
				case "BI_WEEKLY":
					if (isset($this->pay_dates['pay_date2']))
					{
						$this->pay_dates["pay_date3"]
							= date("Y-m-d",
									strtotime($this->pay_dates["pay_date2"]) + 1209600); //+2 Week
						$this->pay_dates["pay_date4"]
							= date("Y-m-d",
									strtotime($this->pay_dates["pay_date2"]) + 2419200); //+4 Week
					}
				break;
				case "TWICE_MONTHLY":
					if (isset($this->pay_dates['pay_date1'])
							&& isset($this->pay_dates['pay_date2']))
					{								
						$this->pay_dates["pay_date3"]
							= date("Y-m-d",
									strtotime("+1 month", strtotime($this->pay_dates["pay_date1"])));
						$this->pay_dates["pay_date4"]
							= date("Y-m-d",
									strtotime("+1 month", strtotime($this->pay_dates["pay_date2"])));
					}
				break;
				case "MONTHLY":
					if ( isset($this->pay_dates['pay_date2']) )
					{
						
						$this->pay_dates["pay_date3"]
							= date("Y-m-d",
									strtotime("+1 month", strtotime($this->pay_dates["pay_date2"])));
						$this->pay_dates["pay_date4"]
							= date("Y-m-d",
									strtotime("+1 month", strtotime($this->pay_dates["pay_date3"])));
					}
				break;
			}
		}
		
		if (isset($this->pay_dates['pay_date3']) && strlen($this->pay_dates['pay_date3'])==10)
		{
			while ($this->_Is_Weekend(strtotime($this->pay_dates["pay_date3"]))
						|| $this->_Is_Holiday(strtotime($this->pay_dates["pay_date3"])))
			{
				$this->pay_dates["pay_date3"]
					= date("Y-m-d",strtotime("+1 day",strtotime($this->pay_dates["pay_date3"])));
					//= date("Y-m-d",strtotime($this->pay_dates["pay_date3"]) + 86400);
			}
		}

		if (isset($this->pay_dates['pay_date4']) && strlen($this->pay_dates['pay_date4'])==10)
		{
			while ($this->_Is_Weekend(strtotime($this->pay_dates["pay_date4"]))
						|| $this->_Is_Holiday(strtotime($this->pay_dates["pay_date4"])))
			{
				$this->pay_dates["pay_date4"]
					= date("Y-m-d",strtotime("+1 day",strtotime($this->pay_dates["pay_date4"])));
					//= date("Y-m-d",strtotime($this->pay_dates["pay_date4"]) + 86400);
			}	
		}
		
		return TRUE;
	}
	
	public function _Gen_Date_Checks()
	{
		$test = $this->_Near_Holiday($this->pay_stamp_1);

		if (isset($this->form_data["income_frequency"]))
		{
			switch ($this->form_data["income_frequency"])
			{	
				case "WEEKLY":
					if($test && $this->pay_stamp_2 != $this->pay_stamp_1 + 604800)
					{
						$this->pay_stamp_1 = $test;
					}
					
					for ($i=1; $i<=3; $i++)
					{
						$this->checks[$i] = strtotime ("+$i weeks", $this->pay_stamp_1);	
					}
				
					foreach ($this->checks as $key => $date)
					{
						if ($this->_Is_Weekend($date) || $this->_Is_Holiday($date))
						{
							$this->_Is_Valid($date);
							unset ($this->checks[$key]);
						}
					}
				break;
			
				case "BI_WEEKLY":
					if($test && $this->pay_stamp_2 != $this->pay_stamp_1 + 1209600)
					{
						$this->pay_stamp_1 = $test;
					}
				
					for ($i=1; $i<=3; $i++)
					{
						$ix2 = $i*2;
						$this->checks[$i] = strtotime ("+$ix2 weeks", $this->pay_stamp_1);
					}
					foreach ($this->checks as $key => $date)
					{
						if ($this->_Is_Weekend($date) || $this->_Is_Holiday($date))
						{
							$this->_Is_Valid($date);
							unset ($this->checks[$key]);
						}
					}
				
				break;
			}
		}
	}
	
	public function _Is_Holiday($date)
	{
		if(empty($date) || $date===-1 || empty($this->holiday_array)) return FALSE; 
		
		//Check how many iterations we've run
		$this->is_it++;
		if($this->is_it>=100) throw new Exception("Stop infinite loop " . $date . " " . print_r($this->holiday_array,true));
		
		if(in_array(date("Y-m-d", $date), $this->holiday_array, TRUE))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public function _Is_Weekend($date)
	{
		if(empty($date) || $date===-1) return FALSE; 
		
		//Check how many iterations we've run
		$this->is_it++;
		if($this->is_it>=100) throw new Exception("Stop infinite loop " . $date . " " . print_r($this->holiday_array,true));
		
		if (date ("w", $date) === "0" || date ("w", $date) === "6")
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public function _Is_Valid($date)
	{
		$date_forward = $date;
		$date_backward = $date;
		do
		{
			//$date_forward += 86400;
			
			$date_forward =strtotime("+1 day",strtotime($date_forward));
		} while( $this->_Is_Weekend($date_forward) || $this->_Is_Holiday($date_forward));
		
		do
		{
			//$date_backward -= 86400;
			$date_backward =strtotime("-1 day",strtotime($date_backward));
		} while( $this->_Is_Weekend($date_backward) || $this->_Is_Holiday($date_backward));
		
		$this->checks[] = $date_forward;
		$this->checks[] = $date_backward;
		
	}
	
	public function _Near_Holiday($date)
	{
		//$date_forward = $date + 86400;
		//$date_backward = $date - 86400;
		
		$date_forward =strtotime("+1 day",strtotime($date));
		$date_backward =strtotime("-1 day",strtotime($date));
		
		while($this->_Is_Weekend($date_forward))
		{
			//$date_forward += 86400;
			$date_forward =strtotime("+1 day",strtotime($date_forward));
		}
		while( $this->_Is_Weekend($date_backward))
		{
			//$date_backward -= 86400;
			$date_backward =strtotime("-1 day",strtotime($date_backward));
		}
		
		if($this->_Is_Holiday($date_forward))
		{
			return $date_forward;
		}
		if($this->_Is_Holiday($date_backward))
		{
			return $date_backward;
		}
		
		return FALSE;
	}
	
	public function _Check_vs_Input()
	{
		$this->validate_4_paydates = FALSE;
		switch ($this->form_data["income_frequency"])
		{
			case "WEEKLY":
				if (!in_array ($this->pay_stamp_2, $this->checks) && !$this->bypass_error_checking )
					$this->errors[] = "pay_date2_weekly";
				
				if( $this->validate_4_paydates )
				{	
					if (!in_array ($this->pay_stamp_3, $this->checks))
						$this->errors[] = "pay_date3_weekly";
					if (!in_array ($this->pay_stamp_4, $this->checks))
						$this->errors[] = "pay_date4_weekly";
				}
			break;
		
			case "BI_WEEKLY":
				if (!in_array ($this->pay_stamp_2, $this->checks) && !$this->bypass_error_checking )
					$this->errors[] = "pay_date2_biweekly";
				
				if( $this->validate_4_paydates )
				{
					if (!in_array ($this->pay_stamp_3, $this->checks))
						$this->errors[] = "pay_date4_biweekly";
					if (!in_array ($this->pay_stamp_4, $this->checks))
						$this->errors[] = "pay_date4_biweekly";
				}
			break;
		
			case "TWICE_MONTHLY":
			
			break;
		
			case "MONTHLY":
			
			break;
		}
	}
	
	public function _Advanced_Payday( )
	{
		if (isset($this->form_data["income_frequency"]) && !$this->bypass_error_checking)
		{
			switch ( $this->form_data["income_frequency"] )
			{
				case "WEEKLY":
					if($this->pay_stamp_1 > mktime(0,0,0)+691200) //+ 8 Days
						$this->errors[] = "pay_date1_weekly_far";
				break;
				case "BI_WEEKLY":
					if($this->pay_stamp_1 > mktime(0,0,0)+1382400) //+16 Days
						$this->errors[] = "pay_date1_weekly_far";
				break;
			}
		}
	}
}
?>
