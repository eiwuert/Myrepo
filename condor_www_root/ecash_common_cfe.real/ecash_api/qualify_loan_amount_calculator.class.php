<?php

require_once 'qualify.2.ecash.php';
require_once 'mysqli.1.php';

/**
 * Loan Amount Calculator using Qualify_2
 *
 * Uses the following business rules (required unless otherwise stated):
 *
 *   react_amount_increase => int
 *   max_react_loan_amount => array(int)
 *   minimum_loan_amount => array(
 *     min_react => int
 *     min_non_react => int
 *   )
 *   datax_increase (optional)
 *
 */
class QualifyLoanAmountCalculator extends LoanAmountCalculator
{
	private $mysqli;

	public function __construct($mysqli)
	{
		$this->mysqli = $mysqli;
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
		if(!is_object($data))
		{
			throw new InvalidArgumentException('$data must be an object');
		}
		if(!isset($data->business_rules))
		{
			throw new InvalidArgumentException('$data->business_rules is required');
		}
		if(!isset($data->income_monthly))
		{
			throw new InvalidArgumentException('$data->income_monthly is required');
		}
		if(!isset($data->is_react))
		{
			throw new InvalidArgumentException('$data->is_react is required');
		}
		if (!isset($data->payperiod))
		{
			throw new InvalidArgumentException('$data->payperiod is required');
		}

		// attempt to determine the reacted application ID if we're
		// not passed one, because qualify_2 requires it
		if ($data->is_react === 'yes'
			&& !isset($data->react_app_id))
		{
			// NOTE: it's possible that the application ID pulled here will NOT be the actual app
			// that was reacted (if they had multiple loans paid off and reacted an older one)
			if(! $data->react_app_id = $this->getReactAppID($data->application_list))
			{
				// If getReactAppId() returns false, it couldn't find a valid
				// previous app, so we don't want to call Qualify_2 with the react
				// flag set. [BR]
				$data->is_react = 'no';
			}
		}

		$rules = $data->business_rules;
		$gets_datax_increase = (isset($data->idv_increase_eligible)
			&& $data->idv_increase_eligible);

		// gay. ness.
		$db = ($this->mysqli instanceof DB_IConnection_1)
			? new ECash_Legacy_MySQLiAdapter($this->mysqli)
			: $this->mysqli;

		/* @var $qualify Qualify_2 */
		$qualify = new Qualify_2_ECash(null, null, $db);
		$qualify->setBusinessRules($rules);

		if ($data->is_react === 'yes')
		{
			$max_loan_amount = $qualify->Calculate_React_Loan_Amount(
				$data->income_monthly,
				NULL,
				$data->react_app_id,
				$data->payperiod
			);
		}
		else
		{
			$max_loan_amount = $qualify->Calculate_Loan_Amount($data->income_monthly);

			// #18905 -- increase max loan amount if they have a specific datax return
			if ($gets_datax_increase
				&& isset($rules['datax_amount_increase']))
			{
				$max_loan_amount += $rules['datax_amount_increase'];
			}
		}

		return $max_loan_amount;
	}

	/**
	 * Calculates the minimum loan amount for the given rules
	 *
	 * @param object $data
	 * @return int
	 */
	public function calculateMinLoanAmount($data)
	{
		if(!is_object($data))
		{
			throw new InvalidArgumentException("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
		}
		if(!isset($data->business_rules))
		{
			throw new InvalidArgumentException("\$data->business_rules must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}
		if(!isset($data->is_react))
		{
			throw new InvalidArgumentException("\$data->is_react must be set for " .__CLASS__ . "::" . __FUNCTION__);
		}

		$rules = $data->business_rules;

		$min_loan_amount = ($data->is_react == 'yes')
			? $rules['minimum_loan_amount']['min_react']
			: $rules['minimum_loan_amount']['min_non_react'];
		return $min_loan_amount;
	}

	/**
	 * Calculates the various loan amounts available to the applicant
	 *
	 * NOTE: This isn't used by OLP... And there are differences in how
	 * they generate the list of amounts -- they use the loan_amount_increment
	 * business rule regardless of whether it's a react or not.
	 *
	 * @param array $rules
	 * @param int $income
	 * @param string $is_react
	 * @return array
	 */
	public function calculateLoanAmountsArray($data)
	{
		// get max loan amount
		// this verifies the proper values are present in $data
		$max_loan_amount = $this->calculateMaxLoanAmount($data);
		$min_loan_amount = $this->calculateMinLoanAmount($data);

		$rules = $data->business_rules;

		// reacts calculate from min up, normal loans from max down;
		// this could potentially create different results if the min
		// or max isn't a multiple of the increment_amount
		if ($data->is_react === 'yes')
		{
			// The increment amount for the loan
			$increment_amount = $rules['react_amount_increase'];
			$loan_amounts = range($min_loan_amount, $max_loan_amount, $increment_amount);
		}
		else
		{
			// the increment here should be a business rule
			$loan_amounts = range($max_loan_amount, $min_loan_amount, 50);
		}

		return $loan_amounts;
	}

	protected function getReactAppID(array $list)
	{
		$list = array_reverse($list, TRUE);
		
		$applicable_status_strings = array(	'paid::customer::*root', 
											'recovered::external_collections::*root');

		foreach($list as $application)
		{
			if(in_array($application->status_chain, $applicable_status_strings) && ! empty($application->fund_actual))
				return $application->application_id;
		}

		return FALSE;

	}
}

?>
