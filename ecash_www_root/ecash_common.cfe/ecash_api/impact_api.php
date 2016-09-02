<?php
//require_once('/virtualhosts/ecash_common/ecash_api/ecash_api.2.php');
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
	 * Returns the value for the current rule set for a given loan_type
	 *
	 * @param string $loan_type - Example: delaware_title
	 * @param string $company_short - Example: pcal
	 * @param string $rule_name - Example: moneygram_fee
	 * @return string or array depending on if the rule has one or multiple rule component parameters
	 */
	protected function getCurrentRuleValue($loan_type, $company_short, $rule_name)
	{
		$rules = new ECash_BusinessRulesCache($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($company_short, $loan_type);
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);

		return $rule_set[$rule_name];
	}
}

?>
