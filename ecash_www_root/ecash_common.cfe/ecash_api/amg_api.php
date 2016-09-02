<?php

require_once(dirname(__FILE__) . '/ecash_api.2.php');

/**
 * Enterprise-level eCash API extension
 * 
 * An enterprise specific extension to the eCash API for AMG.
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 * 
*/

class AMG_eCash_API_2 extends eCash_API_2
{
	/**
	 * Overridden to exclude the rate_override column
	 */
	public function _Get_Application_Info($application_id = NULL, $set_class_members = TRUE)
	{
		if(! is_array($this->status_map))
		{
			$this->status_map = $this->_Fetch_Status_Map($this->db);
		}

		if($application_id === NULL) {
			$application_id = $this->application_id;
		}

		$query = "
		SELECT  
			app.date_fund_actual,
			app.fund_actual,
			app.date_fund_estimated,
			app.company_id,
			c.name_short as company_short,
			app.income_monthly,
			app.income_direct_deposit,
			app.application_status_id,
			lt.name_short as loan_type,
			lt.name as loan_type_description,
			app.rule_set_id,
			app.is_react,
			IF(rf.regulatory_flag_id IS NULL, FALSE, TRUE) as regulatory_flag
		FROM 
			application AS app
		JOIN 
			loan_type AS lt USING (loan_type_id)
		JOIN 
			company AS c ON (c.company_id = app.company_id)
		LEFT JOIN 
			regulatory_flag AS rf ON (rf.customer_id = app.customer_id AND rf.active_status = 'active')
		WHERE 
			app.application_id = '{$application_id}' ";
		$result = $this->db->query($query);
		if(! $row = $result->fetch(PDO::FETCH_OBJ))
		{
			// Set this to false in case the result returned 
			// is something unexpected
			return array(FALSE, FALSE, FALSE, FALSE, FALSE);
		}
		$return_array = array (	$row->date_fund_actual, 
								$this->status_map[$row->application_status_id]['name_short'],
								$row->application_status_id,
								$row->loan_type,
								(boolean)$row->regulatory_flag);

		if($set_class_members)
		{
			$this->income_direct_deposit = $row->income_direct_deposit;
			$this->company_short 		 = $row->company_short;
			$this->date_funded 			 = $row->date_fund_actual;
			$this->is_react 			 = $row->is_react;
			$this->income_monthly 		 = $row->income_monthly;
			$this->loan_status 			 = $this->status_map[$row->application_status_id]['name_short'];
			$this->application_status_id = $row->application_status_id;
			$this->loan_type 			 = $row->loan_type;
			$this->loan_type_description = $row->loan_type_description;
			$this->rule_set_id 			 = $row->rule_set_id;
			$this->regulatory_flag 		 = (boolean)$row->regulatory_flag;
			$this->fund_amount			 = $row->fund_actual;
			$this->date_fund_estimated	 = $row->date_fund_estimated;
		}

		return $return_array;		
	}
	
	/**
	 * Overridden to exclude num_paid_applications
	 * 
	 * @return integer $max_loan_amount
	 */
	public function calculateMaxLoanAmount()
	{
		if(! is_a($this->biz_rules, 'Business_Rules'))
		{
			require_once('/virtualhosts/lib/business_rules.class.php');
			$this->biz_rules = new Business_Rules($this->db);
		}
		
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}
		
		$data = new stdClass;
		$data->business_rules = $this->rule_set;
		$data->income_monthly = $this->income_monthly;
		$data->is_react       = $this->is_react;
		
		require_once('loan_amount_calculator.class.php');

		$loan_amount_calc = LoanAmountCalculator::Get_Instance();
		return $loan_amount_calc->calculateMaxLoanAmount($data);	
	}

	/**
	 * Overridden to exclude num_paid_applications
	 *
	 * @return array
	 */
	public function calculateLoanAmountsArray()
	{
		if(! is_a($this->biz_rules, 'Business_Rules'))
		{
			require_once('/virtualhosts/lib/business_rules.class.php');
			$this->biz_rules = new Business_Rules($this->db);
		}
		
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}
		
		$data = new stdClass;
		$data->business_rules = $this->rule_set;
		$data->income_monthly = $this->income_monthly;
		$data->is_react       = $this->is_react;
		
		require_once('loan_amount_calculator.class.php');

		$loan_amount_calc = LoanAmountCalculator::Get_Instance($this->company_short);
		return $loan_amount_calc->calculateLoanAmountsArray($data);
		
	}	
}

?>