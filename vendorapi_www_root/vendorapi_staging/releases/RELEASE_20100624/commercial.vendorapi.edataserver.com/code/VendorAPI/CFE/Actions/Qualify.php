<?php

/**
 * Runs qualify on the application
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_CFE_Actions_Qualify extends ECash_CFE_Base_BaseAction
{
	public function getType() {}
	public function getParameters() {}

	/**
	 * Run the action. The abstract requires one thing, but
	 * the actual functionality requires something else.
	 * @see code/ECash/CFE/ECash_CFE_IAction#execute()
	 */
	public function execute(ECash_CFE_IContext $c)
	{

		$params = $this->evalParameters($c);
		$fund_amount = !empty($params['fund_amount']) && is_numeric($params['fund_amount']) ? $params['fund_amount'] : NULL;
		$idv_el = $c->getAttribute('idv_increase_eligible');
		$history = $c->getAttribute('customer_history');
		if ($history instanceof ECash_CustomerHistory)
		{
			$paid_apps = $history->getCountPaid();	
		}
		$extra = array(
			'idv_increase_eligible' => $idv_el,
			'num_paid_applications' => $paid_apps,
			'loan_amount_desired'   => $c->getAttribute('loan_amount_desired'),
		);
		$info = $c->getAttribute('application')->calculateQualifyInfo(TRUE, $fund_amount, $extra);
		$c->setAttribute('qualify_info', $info);
		$fa = $info->getMaximumLoanAmount();
		
		return (!empty($fa));
	}
}
