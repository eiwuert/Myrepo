<?php

/**
 * Loan Amount Calculator using CLK Business Logic & Rules
 *
 * @requires Business Rule new_loan_amount
 * @requires Business Rule max_react_loan_amount
 */
Class LoanAmountCalculator
{
	private $mysqli;

	public function __construct($mysqli)
	{
		$this->mysqli = $mysqli;
	}

	/**
	 * Factory method for returning an Enterprise specific API
	 *
	 * @param strng $company_short
	 * @return LoanAmountCalculator
	 */
	static function Get_Instance($mysqli, $company_short = NULL)
	{
		// Default to CLK
		if($company_short === NULL) $company_short = 'pcl';

		switch(strtolower($company_short))
		{
			case 'generic':
			default:
				$api_name = 'LoanAmountCalculator';
		}

		return new $api_name($mysqli);

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

		$loan_amounts = $rules['new_loan_amount'];
		ksort($loan_amounts);
		$new_amounts = array();

		$next_entry = 0;
		foreach($loan_amounts as $max_income => $loan_amount)
		{
			$new_amounts[$next_entry] = $loan_amount;
			$next_entry = $max_income;
		}
		$loan_amounts = $new_amounts;

		if (count($loan_amounts) > 0)
		{
			foreach($loan_amounts as $min_income => $loan_amount)
			{
				if($income >= (int)$min_income)
				{
					$max_loan_amount = (int)$loan_amount;
				}
			}
		}

		if($is_react === 'yes')
		{
			$num_paid = $this->countNumberPaidApplications($data);
			$max_react_amount = max($rules['max_react_loan_amount']);
			$react_increase = max($rules['react_amount_increase'] * $num_paid, $rules['react_amount_increase']);
			if($max_loan_amount + $react_increase <= $max_react_amount)
			{
				$max_loan_amount = $max_loan_amount + $react_increase;
			}
			else
			{
				$max_loan_amount = $max_react_amount;
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

		//echo "<pre>"; print_r($data); die();

		$loan_amounts = array();
		$min_loan_amount = ($is_react == 'yes') ? $rules['minimum_loan_amount']['min_react'] : $rules['minimum_loan_amount']['min_non_react'];
		// Get max loan amount
		$max_loan_amount = self::calculateMaxLoanAmount($data);
		if($is_react === 'yes')
		{

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

			$loan_amounts[]    = $max_loan_amount;
			$valid_loan_amount = $max_loan_amount;

			while (($valid_loan_amount = ($valid_loan_amount - 50)) >= $min_loan_amount)
			{
				$loan_amounts[] = $valid_loan_amount; // This should be a business rule
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
