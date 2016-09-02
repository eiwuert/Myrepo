<?php
//require_once('/virtualhosts/ecash_common/ecash_api/ecash_api.2.php');
require_once(dirname(__FILE__) . '/business_rules.class.php');
/**
 * Enterprise-level eCash API extension
 * 
 * An enterprise specific extension to the eCash API for Impact.
 *  
 * 
 * 
 * 
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * 
*/

Class Impact_eCash_API_2 extends eCash_API_2
{
	
	public function __construct($db, $application_id, $company_id = NULL)
	{
		parent::__construct($db, $application_id, $company_id);

	}
	
		/**
	 * Returns the APR
	 *
	 * @param string $loan_type_short - shortname for loan type
	 * @param string $company_short - shortname for company
	 * @param int $start_stamp - start timestamp for time period
	 * @param int $end_stamp - end timestamp for time period
	 * @return  float - APR rounded to two decimal places
	 */
	public function getAPR($loan_type_short, $company_short, $start_stamp=NULL, $end_stamp=NULL)
	{
		//Normalize timestamps
		$start_stamp = ($start_stamp) ? strtotime(date("m/d/Y", $start_stamp)) : NULL;
		$end_stamp = ($end_stamp) ? strtotime(date("m/d/Y", $end_stamp)) : NULL;
	
		$rule_name = 'service_charge';
		$svc_chg = $this->getCurrentRuleValue($loan_type_short, $company_short, $rule_name);
		$svc_chg_pct = $svc_chg['svc_charge_percentage'];

		switch($loan_type_short)
		{
		
			case 'standard':
			default:
				if($start_stamp && $end_stamp)
				{
					$num_days = round(($end_stamp - $start_stamp) / 60 / 60 / 24 );
				}
				else 
				{
					throw new Exception ("{$loan_type_short} applications require starting and ending timestamps for the relevant time period.");
				}
				break;	
		}
		$num_days = ($num_days < 1) ? 1 : $num_days;
		
		$apr = round( (($svc_chg_pct / $num_days) * 365), 2);
		
		return $apr;
	}
	/**
	 * Returns the value for the current rule set for a given loan_type
	 *
	 * @param string $loan_type - Example: delaware_title
	 * @param string $company_short - Example: pcal
	 * @param string $rule_name - Example: moneygram_fee
	 * @return string or array depending on if the rule has one or multiple rule component parameters
	 */
	protected function getCurrentRuleValue($loan_type, $company_short, $rule_name)
	{
		$rules = new Legacy_Business_Rules($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($company_short, $loan_type);
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);

		return $rule_set[$rule_name];
	}
}

?>
