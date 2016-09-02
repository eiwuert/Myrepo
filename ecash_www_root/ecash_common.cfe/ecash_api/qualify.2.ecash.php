<?php
require_once(COMMON_LIB_DIR."qualify.2.php");

class Qualify_2_Ecash extends Qualify_2
{
	private $loan_type;
	public function __construct($company_short=NULL, $loan_type=NULL, $mysql=NULL, $income_monthly_net=NULL, $application_id=NULL, &$log=NULL, $holidays=NULL)
	{
		if (isset($loan_type) && !empty($loan_type))
		{
			$this->loan_type = $loan_type;
		}
		else
		{
			$this->loan_type = 'standard';
		}

		//Qualify_2's parameters are references and I cannot pass in NULL, so this works
		$nonval = NULL;
		$empty_obj = new stdClass();

		parent::Qualify_2($company_short, $holidays, $empty_obj, $empty_obj, $log);
		$this->ldb = $mysql;
	}

	protected function Get_Rule_Config_Loan_Type()
	{
		return $this->loan_type;
	}

	/**
	 * Backdoor to set business rules
	 *
	 * Required for the transitionary Qualify_2 -> LoanAmountCalculator adapter.
	 * DO NOT USE EVER AGAIN.
	 *
	 * @deprecated
	 *
	 * @param array $rules
	 * @return void
	 */
	public function setBusinessRules(array $rules)
	{
		$this->config = (object)$rules;
	}
}
?>