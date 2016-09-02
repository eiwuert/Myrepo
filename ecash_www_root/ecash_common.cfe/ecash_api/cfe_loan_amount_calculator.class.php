<?php
/**
 * Loan Amount Calculator using CFE logic, rules, and PDO!
 *
 * @requires Business Rule new_loan_amount
 * @requires Business Rule max_react_loan_amount
 */
Class CFE_LoanAmountCalculator extends LoanAmountCalculator
{

	private $db;
	
	public function __construct(DB_Database_1 $db)
	{
		$this->db = $db;
	}
	
	
	
	/**
	 * Calculates the Maximum loan amount for an application
	 *
	 * @param array $rules
	 * @param int $income
	 * @param string $is_react
	 * @return int
	 */
	public function calculateMaxLoanAmount($data)
	{
		/**
		 * There's an excessive amount of dependancy checking here... But it will 
		 * help if there are ever any bugs.
		 */
		if(! is_object($data))
		{
			throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
		}
		
		if(! isset($data->business_rules))
		{
			throw new Exception("\$data->business_rules must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		else
		{
			$rules = $data->business_rules;
		}
		
		if(! isset($data->income_monthly))
		{
			throw new Exception("\$data->income_monthly must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		else
		{
			$income = $data->income_monthly;
		}

		if(! isset($data->is_react))
		{
			throw new Exception("\$data->is_react must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		else
		{
			$is_react = $data->is_react;
		}
		
		$loan_amounts = array();
		$max_loan_amount = 0;
			
		if($is_react === 'yes')
		{
			$loan_amounts = empty($rules['max_react_loan_amount']) ? array() : $rules['max_react_loan_amount'];	
			//die;
		}
		else
		{
			$loan_amounts = $rules['new_loan_amount'];
		}
		//var_dump($loan_amounts);die;
		if (count($loan_amounts) > 0)
		{
			foreach($loan_amounts as $min_income => $loan_amount)
			{
				if($income >= (int)$min_income)	$max_loan_amount = (int)$loan_amount;
			}
		}

		return $max_loan_amount;
	}
	
	/**
	 * Calculates the various loan amounts available to the applicant 
	 *
	 * @param array $rules
	 * @param int $income
	 * @param string $is_react
	 * @return array
	 */
	public function calculateLoanAmountsArray($data)
	{
		
		if(! is_object($data))
		{
			throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
		}
		
		if(! isset($data->business_rules))
		{
			throw new Exception("\$data->business_rules must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		else
		{
			$rules = $data->business_rules;
		}
		
		if(! isset($data->income_monthly))
		{
			throw new Exception("\$data->income_monthly must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		else
		{
			$income = $data->income_monthly;
		}

		if(! isset($data->is_react))
		{
			throw new Exception("\$data->is_react must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		else
		{
			$is_react = $data->is_react;
		}
		
		$loan_amounts = array();

		$min_loan_amount = ($is_react == 'yes') ? $rules['minimum_loan_amount']['min_react'] : $rules['minimum_loan_amount']['min_non_react'];
		
		
		if($is_react === 'yes')
		{
			// Get max loan amount
			$max_loan_amount = self::calculateMaxLoanAmount($data);
			
			// The increment amount for the loan
			$increment_amount = $rules['react_amount_increase'];

			for ($x = 1; $x < ($max_loan_amount / $increment_amount) + 1; $x++)
			{
				$loan_amount = $increment_amount * $x;
				if($loan_amount <= $max_loan_amount && $loan_amount >= $min_loan_amount)
				{
					$loan_amounts[] = $loan_amount;
				}
			}
		}
		else
		{
			foreach($rules['new_loan_amount'] as $min_income => $loan_amount)
			{
				if($income >= (int)$min_income) 
				{
					if(((int)$loan_amount - 50)  >= $min_loan_amount)
					{
						$loan_amounts[] = (int)$loan_amount - 50; // This should be a business rule
					}
					
					$loan_amounts[] = (int)$loan_amount;
				}
			}
		}
		return array_unique($loan_amounts);
	}
	
	public function countNumberPaidApplications($data)
	{
		if(! is_object($data))
		{
			throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
		}
		
		if(! isset($data->application_list))
		{
			throw new Exception("\$data->application_list must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		else
		{
			$application_list = $data->application_list;
		}

		$num_paid = 0;
		$paid_status = Status_Utility::Get_Status_ID_By_Chain('paid::customer::*root');
		
		foreach($application_list as $application)
		{
			if($application->application_status_id == $paid_status) $num_paid++;
		}
		
		return $num_paid;
	}
	
}
