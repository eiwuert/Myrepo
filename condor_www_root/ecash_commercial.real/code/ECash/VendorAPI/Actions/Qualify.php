<?php

require_once ECASH_COMMON_DIR.'/ecash_api/loan_amount_calculator.class.php';

/**
 * Commercial version of the qualify API call
 *
 * The qualify call is responsible for setting up the state
 * object, running the first step in the CFE Asynch Engine,
 * and calculating the qualified loan amount.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_VendorAPI_Actions_Qualify extends VendorAPI_Actions_Qualify
{
	/**
	 * @var ECash_CFE_AsynchResult
	 */
	protected $result;

	/**
	 * Retrieves Response Data for Execute
	 *
	 * @param int $amount
	 * @param Blackbox_IWinner $winner The blackbox winner
	 * @return array
	 */
	public function getResponseData($amount, Blackbox_IWinner $winner)
	{
		$response_data = parent::getResponseData($amount, $winner);
		$response_data['async_result'] = $this->result;
		return $response_data;
	}

	/**
	 * Determines whether a customer qualifies for a loan
	 *
	 * For commercial, this runs CFE to see if there's a matching loan type.
	 *
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return bool
	 */
	protected function isValid(array $data, VendorAPI_StateObject $state)
	{
		$valid = FALSE;
		
		$engine = $this->getEngine();
		$result = $engine->beginExecution($data, FALSE);

		if ($result->getIsValid())
		{
			// @todo put this in $state
			$this->result = $result;
			$valid = TRUE;
		}
		
		return $valid;
	}

	/**
	 * @param VendorAPI_StateObject $state
	 * @return VendorAPI_ILoanAmountCalculator
	 */
	protected function getLoanAmountCalculator(VendorAPI_StateObject $state)
	{
		$rules = $this->getBusinessRules($this->result->getLoanTypeId());

		return new ECash_VendorAPI_LoanAmountCalculator(
			$this->getECashCalculator(),
			$rules,
			$this->result->getLoanTypeName()
		);
	}

	/**
	 * Instantiates and returns an AsynchEngine
	 * @return ECash_CFE_AsynchEngine
	 */
	protected function getEngine()
	{
		return new ECash_CFE_AsynchEngine(
			$this->driver->getDatabase(),
			$this->driver->getCompanyId()
		);
	}

	/**
	 * Loads ECash business rules
	 *
	 * @param int $loan_type_id
	 * @return array
	 */
	protected function getBusinessRules($loan_type_id)
	{
		$br = new ECash_BusinessRules($this->driver->getDatabase());
		return $br->Get_Latest_Rule_Set($loan_type_id);
	}

	/**
	 * Instantiates and returns an instance of Commercial's LoanAmountCalculator
	 * @return LoanAmountCalculator
	 */
	protected function getECashCalculator()
	{
		// impact used qualify_2 on the OLP side, but the LoanAmountCalculator
		// on the Ecash side -- these DON'T calculate react amounts the same...
		// please resolve this at your earliest convenience!
		if (strcasecmp($this->driver->getEnterprise(), 'impact') === 0)
		{
			require_once ECASH_COMMON_DIR.'/ecash_api/qualify_loan_amount_calculator.class.php';
			return new QualifyLoanAmountCalculator($this->driver->getDatabase());
		}
		return LoanAmountCalculator::Get_Instance(
			$this->driver->getDatabase(),
			$this->driver->getCompany()
		);
	}
}

?>