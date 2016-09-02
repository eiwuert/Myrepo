<?php

/**
 * Runs qualification checks for OLP enterprise companies to see if/how much a customer qualifies to borrow.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_Enterprise_Generic_Rule_LegacyQualifiesForAmount extends OLPBlackbox_Rule
{
	/**
	 * The event log name that this object will use.
	 * 
	 * @var string
	 */
	protected $event_name = 'QUALIFY';

	/**
	 * Determines whether there is enough state data to run this rule.
	 * 
	 * @param Blackbox_Data $data State data about the application.
	 * @param Blackbox_IStateData $state_data State data about the target.
	 *
	 * @return bool Whether or not the rule can run.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		
		// income_frequency is used in Calculate_React_Loan_Amount if it's a react
		if (($state_data->is_react || $config->ecash_react) &&
			!isset($data->income_frequency))
		{
			return FALSE;
		}
		
		// monthly net must be 
		return (isset($data->income_monthly_net) && isset($data->income_direct_deposit));
	}
	
	/**
	 * Gets a qualify object which is the API Object for determining how much a customer qualifies for on a loan.
	 * 
	 * @param Blackbox_IStateData $state_data State information about the ITarget calling this rule.
	 *
	 * @return OLP_Qualify_2 Object used to determine how much a user qualifies for on a loan, old API.
	 */
	protected function getQualify(Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		$property_short = EnterpriseData::resolveAlias($state_data->campaign_name);
		
		$db = Setup_DB::Get_Instance('mysql', $config->mode . '_READONLY', $property_short);
		
		$qualify = new OLP_Qualify_2(
			$property_short, 
			array(), 
			$config->olp_db, 
			$db, 
			$config->applog, 
			$config->mode, 
			$config->title_loan
		);
		
		if ($config->ecash_react)
		{
			$qualify->setIsEcashReact(TRUE);
		}
		
		return $qualify;
	}
	
	/**
	 * Determine if the customer qualifies for an amount of money. (And as a side effect, store that amount.)
	 *
	 * @param Blackbox_Data $data containing data about the application being processed
	 * @param Blackbox_IStateData $state_data containing data about the state of the ITarget running this rule.
	 *
	 * @post the amount of the loan will be entered into the Blackbox_IStateData
	 *
	 * @return bool whether this rule is valid
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		
		$fund_amount = NULL;
		
		try
		{
			$qualify = $this->getQualify($state_data);
		} 
		catch (Exception $e)
		{
			throw new Blackbox_Exception($e->getMessage());
		}
		
		// Use React Loan if React or an Ecash React
		if ($state_data->is_react || $config->ecash_react)
		{
			$app_id = $data->application_id;
			if (!empty($data->react_app_id))
			{
				$app_id = $data->react_app_id;
			}
			
			$fund_amount = $qualify->Calculate_React_Loan_Amount(
				$data->income_monthly_net, 
				$data->income_direct_deposit, 
				$app_id,
				strtolower($data->income_frequency)
			);
		
		} 
		else
		{
			$fund_amount = $qualify->Calculate_Loan_Amount(
				$data->income_monthly_net, 
				$data->income_direct_deposit
			);
		}
		
		if ($fund_amount > 0)
		{
			$state_data->qualified_loan_amount = $fund_amount;
		}
		
		return ($fund_amount > 0);
	}
	
	/**
	 * Runs when there is an exception thrown by the rule.
	 * 
	 * Write out an applog entry with the exception and hit an error event log entry.
	 *
	 * @param Blackbox_Exception $e exception thrown by the rule
	 * @param Blackbox_Data $data the data passed to the rule
	 * @param Blackbox_IStateData $state_data the state data passed to the rule
	 * @return bool
	 */
	protected function onError(Blackbox_Exception $e, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->getConfig()->applog->Write(
			'Error while qualifying customer: ' . $e->getMessage()
		);
		
		// Show it as an error, not as a failure like we did before
		$this->hitRuleEvent(
			OLPBlackbox_Config::EVENT_RESULT_ERROR, $data, $state_data
		);
		
		return FALSE;
	}
}
?>
