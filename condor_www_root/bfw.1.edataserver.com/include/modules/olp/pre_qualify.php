<?php
	
	/**
		@publicsection
		@public
		@brief	Handles pre-qualification checks.
		
		This class handles the prequalification checks for OLP.
		
		@version
			0.1.0 2005-04-05 - Andrew Minerd
	
	*/
	class Pre_Qualify
	{
		
		protected $errors;
		
		/**
			@publicsection
			@public
			@fn return array Check($income_frequency, $income_net, $income_source, $date_hire)
			@brief
				
				Perform rudimentary error checks on the income data.
			
			@param $income_frequency string Frequency of pay
			@param $income_net int Net monthly income
			@param $income_source string Employment or Benefits
			@param $date_hire string Date the person was hired
		*/
		public function Check($income_frequency, $income_net, $income_source, $date_hire)
		{
			
			$errors = array();
			
			//Remove Min_Income check and rely on BB Limits [CB] 2006-03-23
			//$errors = $this->Min_Income($income_net, $income_source);
			
			if (!($paycheck_net = $this->Calculate_Paycheck_Net($income_net, $income_frequency)))
			{
				$errors[] = "income_frequency";
			}
			
			if (!$this->Job_Length($date_hire))
			{
				$errors[] = "employer_length";
			}
			
			$this->errors = $errors;
			return (count($errors)) ? $errors: TRUE;
			
		}
		
		/**
			@privatesection
			@private
			@fn return int Calculate_Paycheck_Net($monthly_net, $frequency)
			@brief
				
				Calculate the net income per check.
			
			@param $monthly_net int Net monthly income
			@param $frequency string Frequency of pay
		*/
		private function Calculate_Paycheck_Net($monthly_net, $frequency)
		{
			
			switch ($frequency)
			{
				case 'WEEKLY':
					$paycheck_net = ($monthly_net / 4);
				break;
				
				case 'BI_WEEKLY':
				case "TWICE_MONTHLY":
					$paycheck_net = ($monthly_net / 2);
				break;
						
				case 'MONTHLY':
					$paycheck_net = $monthly_net;
				break;
				
				default:
					$paycheck_net = FALSE;
				break;
			}
			
			return($paycheck_net);
			
		}
		
		/**
			@privatesection
			@private
			@fn return bool Job_Length($job_length)
			@brief
				
				Make sure they've been at their job for
				more than three months.
			
			@param $job_length timestamp Date of hire
			@param $job_length bool Worked for more than three months
		*/
		private function Job_Length($job_length)
		{
			
			//********************************************* 
			// The below was commented out because we changed
			// how the date of employment was determined
			// from radio button to drop down box with more 
			// options
			//********************************************* 
			//if (trim($job_length)=='1' || strtolower(trim($job_length))=='true') 
			//{
			//	$job_length = date("Y-m-d", strtotime("-4 months"));
			//}
			//********************************************* 
			// Oh in case you need some explanation
			// we return TRUE instead of FALSE in this 
			// case because if we don't we cause an error
			// that will not allow a user to say they are
			// not employed. 
			// - we don't care if they are
			// employed or not, just as long as they answer 
			// the question
			//********************************************* 
			if ($job_length == 'FALSE')
			{
				return TRUE;
			}

			$job_length = date("Y-m-d", strtotime($job_length));
			if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $job_length))
			{
				
				list($y, $m, $d) = explode('-', $job_length);
				//********************************************* 
				// Also since we changed how the date was determined
				// we don't want to reject just because they have been
				// employed for less than 3 months
				//********************************************* 
				//if (strtotime("+3 months", mktime(0, 0, 0, $m, $d, $y)) > strtotime("now"))
				//{
				//	$this->errors[] = 'employer_length';
				//	return FALSE;
				//}
				
			}
			else 
			{
				$this->errors[] = 'employer_length';
				return FALSE;
			}
			
			return TRUE;
			
		}
		
		/**
			@privatesection
			@private
			@fn return bool Min_Income($monthly_net, $income_source)
			@brief
				
				Rudimentary income check.
			
			@param $monthly_net int Net monthly income
			@param $income_source string Benefits or Employment
		*/
		private function Min_Income($monthly_net, $income_source)
		{
			
			$errors = array();
			
			if (is_numeric($monthly_net))
			{
				$monthly_net = (int)$monthly_net;
			}
			else
			{
				$monthly_net = 0;
			}

			switch ($income_source)
			{
				case "BENEFITS":
					if ($monthly_net < 800)
					{
						$errors['income_monthly_net'] = 'You must make at least $800 monthly in benefits income for a loan';
					}
				break;
				
				default:
				case "EMPLOYMENT":
					if ($monthly_net < 1000)
					{
						$errors['income_monthly_net'] = 'You must make at least $1000 monthly in job income for a loan';
					}
				break;
			}
			
			return($errors);
			
		}
				
	}
	
?>
