<?php

/**
 * Returns tokens for creating Condor documents
 *
 * If fund amount is sent in data, this must recalculate qualify information.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Actions_GetTokens extends VendorAPI_Actions_Base
{
	/**
	 * Token provider
	 *
	 * @var VendorAPI_ITokenProvider
	 */
	protected $provider;

	/**
	 * @var VendorAPI_IQualify
	 */
	protected $qualify;

	/**
	 *
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $application_factory;

	/**
	 * construct
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_ITokenProvider $provider
	 */
	public function __construct(
		VendorAPI_IDriver $driver,
		VendorAPI_ITokenProvider $provider,
		VendorAPI_IApplicationFactory $application_factory)
	{
		parent::__construct($driver);
		$this->provider = $provider;
		$this->application_factory = $application_factory;
	}

	/**
	 * Execute the action
	 *
	 * @param Integer $application_id
	 * @param array $data
	 * @param string $serialized_state
	 * @return VendorAPI_Response
	 */
	public function execute($application_id, $is_preview, array $data = NULL, $serialized_state = NULL)
	{
		$this->call_context->setApplicationId($application_id);

		if ($serialized_state == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = $this->getStateObject($serialized_state);
		}

		$loan_amount = isset($data['loan_amount'])
			? $data['loan_amount']
			: NULL;

		$persistor = new VendorAPI_StateObjectPersistor($state);

		$application = $this->application_factory->getApplication($application_id, $persistor, $state);
		$tokens = $this->provider->getTokens($application, $is_preview, $loan_amount);
		$tokens = $this->matchTokensToOLP($tokens);

		if (!$is_preview)
		{
			// this is essentially agree, so we have to update the finance
			// info... and we might not have received a state object
			if (!$state->application_id)
			{
				$state->application_id = $application_id;
				$state->application->application_id = $application_id;
			}
			$this->saveState($state);
		}

		return new VendorAPI_Response(
			$state,
			VendorAPI_Response::SUCCESS,
			array(
				'tokens' => $tokens,
			)
		);
	}

	/**
	 * Does a bunch of crap to make tokens match _EXACTLY_ what OLP used to generated
	 *
	 * @param array $tokens
	 * @return array
	 */
	protected function matchTokensToOLP(array $tokens)
	{
		$tokens['LoginId'] = '';
		//$tokens['CompanyNameLegal'] = $condor_tokens->Get_Site_Config()->legal_entity;
		$tokens['CompanyNameShort'] = $tokens['CompanyInit'];
		$tokens['IncomeDD'] = ($tokens['IncomeDD'] == 'TRUE' ? 'Yes' : 'No');
		$tokens['IncomeMonthlyNet'] = str_replace(',', '', $tokens['IncomeMonthlyNet']);
		$tokens['IncomeNetPay'] = str_replace(',', '', $tokens['IncomeNetPay']);
		$tokens['IncomeType'] = strtolower($tokens['IncomeType']);
		$tokens['IncomeFrequency'] = strtolower($tokens['IncomeFrequency']);
		$tokens['EmployerLength'] = '0 Yrs&nbsp;&nbsp;&nbsp;&nbsp;3+ Mths&nbsp;&nbsp;&nbsp;&nbsp;';
		$tokens['CustomerCity'] = strtoupper($tokens['CustomerCity']);
		if (!$tokens['CustomerResidenceLength']) $tokens['CustomerResidenceLength'] = 'Unspecified';
		if ($tokens['CustomerResidenceType'] === 'Unspecified') $tokens['CustomerResidenceType'] = 'NA';
		$tokens['LoanPayoffDate'] = date('Y/m/d', strtotime($tokens['LoanPayoffDate']));
		$tokens['LoanFundDate'] = preg_replace('#(\d{2})-(\d{2})-(\d{4})#', '$3/$1/$2', $tokens['LoanFundDate']);
		$tokens['CompanyFax'] = preg_replace('#1-(\d{3})-(\d{3})-(\d{4})#', '$1$2$3', $tokens['CompanyFax']);
		$tokens['CompanySupportFax'] = preg_replace('#1-(\d{3})-(\d{3})-(\d{4})#', '($1) $2-$3', $tokens['CompanySupportFax']);

		// OLP never populated these tokens...
		$tokens['CompanyLogoLarge'] = '';
		$tokens['CompanyLogoSmall'] = '';
		$tokens['CompanyEmail'] = '';

		for ($i = 1, $t = 'IncomePaydate1'; isset($tokens[$t]); $i++, $t = 'IncomePaydate'.$i)
		{
			$tokens[$t] = date('m/d/Y', strtotime(str_replace('-', '/', $tokens[$t])));
		}
		$tokens['CustomerDOB'] = str_replace('-', '/', $tokens['CustomerDOB']);
		$tokens['LoanFundDate2'] = $tokens['LoanFundDate'];
		return $tokens;
	}

	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->application_factory;

	}
}

?>
