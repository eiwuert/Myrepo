<?php

	/**
	 * A very simplistic API for the Configurable Front End Interface
	 *
	 */
	class ECash_CFE_API
	{
		
		public function fetchCompanies()
		{
			$search_types = array();
			$model = ECash::getFactory()->getModel('CompanyList');
			$list = $model->getCFECompanies();
			return $list;
		}
		
		public function fetchCompany($company_id)
		{
			if(!$company_id)
			{
				throw new Exception("Unable to locate company '$company_id'");
			}
			
			$company = ECash::getFactory()->getCompanyById($company_id);
			
			return $company;
		}
		/**
		 * Returns an array of ECash_Models_LoanType objects
		 * which contain information about all different loan types
		 *
		 * @param string $loan_type
		 * @return array of ECash_Models_LoanTypeList objects
		 */
		public function fetchAllLoanTypes($company_id = NULL)
		{	
			$search_types = array();
			if($company_id)
			{
				$search_types['company_id'] = $company_id;
			}
			
			/**
			 * This looks a bit silly, but I chose to use the ECash_Models_IterativeModel
			 * for the LoanTypeList, though for this use it's somewhat of a waste so
			 * instead I'm returning an array of ECash_Models_LoanType objects.
			 */
			
			$list = ECash::getFactory()->getModel('LoanTypeList');
			$list->loadBy($search_types);
			
			$return_array = array();
			foreach($list as $item)
			{
				$return_array[] = $item;
			}

			return $return_array;

		}

		/**
		 * Fetch a single loan type using it's name_short
		 * according to the database
		 *
		 * @param string $loan_type
		 * @return ECash_Models_LoanType object
		 */
		public function fetchLoanType($loan_type,$company_id=null)
		{	
			$search_types = array();
			if(!empty($loan_type))
			{
				$search_types['name_short'] = $loan_type;
			}
			if(!empty($company_id))
			{
				$search_types['company_id'] = $company_id;
			}
			$loan_type = ECash::getFactory()->getModel('LoanType');
			$loan_type->loadBy($search_types);
			return $loan_type;
		}

		
	/**
	 * Creates a copy of a loan_type
	 *
	 * @param int $loan_type_id - The loan_type_id of the loan_type you want to create a copy of
	 * @param String $loan_name - The name of your loan type copy.
	 * @param int $company_id - The company_id of the company you want to create the copy for
	 */
	public function copyLoanType($loan_type_id,$loan_name,$company_id)
	{
		$current_time = date('Y-m-d H:i:s');
		$loan_name_short = str_replace("'",'',str_replace(' ','_',strtolower($loan_name)));
		//get Loan_type to copy
		$original_loan_type = ECash::getFactory()->getModel('LoanType');
		$original_loan_type->loadBy(array('loan_type_id' => $loan_type_id));

		//Copy loan type
		$new_loan_type = ECash::getFactory()->getModel('LoanType');
		$new_loan_type->name = $loan_name;
		$new_loan_type->active_status = $original_loan_type->active_status;
		$new_loan_type->name_short = $loan_name_short;
		$new_loan_type->date_modified = $current_time;
		$new_loan_type->date_created = $current_time;
		$new_loan_type->company_id = $company_id;
		$new_loan_type->insert();
		$new_loan_type_id =  $new_loan_type->loan_type_id;

		//get CFE ruleset to copy
		
		$original_cfe_ruleset = ECash::getFactory()->getModel('CfeRuleSet')->getActiveByLoanType($loan_type_id);
		$original_cfe_ruleset_id = $original_cfe_ruleset->cfe_rule_set_id;
		
		//get eCash Business rule to copy
		$original_ecash_ruleset = ECash::getFactory()->getModel('RuleSet')->getActiveByLoanType($loan_type_id);
		$original_ecash_ruleset_id = $original_ecash_ruleset->rule_set_id;
		
		//Copy CFE ruleset
		$new_cfe_ruleset = ECash::getFactory()->getModel('CfeRuleSet');
		$new_cfe_ruleset->date_created = $current_time;
		$new_cfe_ruleset->date_modified = $current_time;
		$new_cfe_ruleset->loan_type_id = $new_loan_type_id;
		$new_cfe_ruleset->date_effective = $current_time;
		$new_cfe_ruleset->name = $loan_name." Rule Set";
		$new_cfe_ruleset->insert();
		
		$new_cfe_ruleset_id = $new_cfe_ruleset->cfe_rule_set_id;		
		
		//Copy eCash Business rule
		$new_ecash_ruleset = ECash::getFactory()->getModel('RuleSet');
		$new_ecash_ruleset->date_modified = $current_time;
		$new_ecash_ruleset->date_created = $current_time;
		$new_ecash_ruleset->name = $loan_name." Rule Set";
		$new_ecash_ruleset->loan_type_id = $new_loan_type_id;
		$new_ecash_ruleset->date_effective = $current_time;
		$new_ecash_ruleset->insert();
		$new_ecash_ruleset_id = $new_ecash_ruleset->rule_set_id;
		
		//get eCash Business Ruleset Components to copy
		$original_rule_set_component_list = ECash::getFactory()->getModel('RuleSetComponentList');
		$original_rule_set_component_list->loadBy(array('rule_set_id' => $original_ecash_ruleset_id));
		
		//Copy Ruleset Components
		foreach ($original_rule_set_component_list as  $original_rule_set_component) 
		{
			$new_rule_set_component = ECash::getFactory()->getModel('RuleSetComponent');
			$new_rule_set_component->rule_set_id = $new_ecash_ruleset_id;
			$new_rule_set_component->rule_component_id = $original_rule_set_component->rule_component_id;
			$new_rule_set_component->sequence_no = $original_rule_set_component->sequence_no;
			$new_rule_set_component->date_modified = $current_time;
			$new_rule_set_component->date_created = $current_time;
			$new_rule_set_component->insert();
			
			$new_rule_set_component_id = $new_rule_set_component->rule_component_id;
		}		
		//get eCash Ruleset Component Parm Values to copy
		$original_rscpv_list = ECash::getFactory()->getModel('RuleSetComponentParmValueList');
		$original_rscpv_list->loadBy(array('rule_set_id' => $original_ecash_ruleset_id));
		
		//Copy eCash Ruleset Component Parm Values
		foreach ($original_rscpv_list as $original_rscpv) 
		{
			$new_rscpv = ECash::getFactory()->getModel('RuleSetComponentParmValue');
			$new_rscpv->date_modified = $current_time;
			$new_rscpv->date_created = $current_time;
			$new_rscpv->rule_component_id = $original_rscpv->rule_component_id;
			$new_rscpv->rule_component_parm_id = $original_rscpv->rule_component_parm_id;
			$new_rscpv->parm_value	=	$original_rscpv->parm_value;
			$new_rscpv->rule_set_id = $new_ecash_ruleset_id;
			$new_rscpv->insert();
						
		}		
		
		
		//get CFE rules to copy
		$original_cfe_rule_list = ECash::getFactory()->getModel('CfeRuleList');
		$original_cfe_rule_list->loadBy(array("cfe_rule_set_id" => $original_cfe_ruleset_id));
		//Copy CFE rules
		foreach ($original_cfe_rule_list as $original_cfe_rule) 
		{
			$original_cfe_rule_id = $original_cfe_rule->cfe_rule_id;
			$new_cfe_rule = ECash::getFactory()->getModel('CfeRule');
			$new_cfe_rule->date_modified = $current_time;
			$new_cfe_rule->date_created = $current_time;
			$new_cfe_rule->name = $original_cfe_rule->name;
			$new_cfe_rule->cfe_event_id = $original_cfe_rule->cfe_event_id;
			$new_cfe_rule->salience = $original_cfe_rule->salience;
			$new_cfe_rule->cfe_rule_set_id = $new_cfe_ruleset_id;
			$new_cfe_rule->insert();
			$new_cfe_rule_id = $new_cfe_rule->cfe_rule_id;
			
			//get CFE rule actions to copy
			$original_cfe_rule_action_list = ECash::getFactory()->getModel('CfeRuleActionList');
			$original_cfe_rule_action_list->loadBy(array("cfe_rule_id" => $original_cfe_rule_id));
			//Copy CFE rule actions
			foreach ($original_cfe_rule_action_list as $original_cfe_rule_action) 
			{
				$original_cfe_rule_action_id = $original_cfe_rule_action->cfe_rule_action_id;
				$new_cfe_rule_action = ECash::getFactory()->getModel('CfeRuleAction');	
				$new_cfe_rule_action->date_modified = $current_time;
				$new_cfe_rule_action->date_created = $current_time;
				$new_cfe_rule_action->cfe_action_id = $original_cfe_rule_action->cfe_action_id;
				$new_cfe_rule_action->params = $original_cfe_rule_action->params;
				$new_cfe_rule_action->sequence_no = $original_cfe_rule_action->sequence_no;
				$new_cfe_rule_action->rule_action_type = $original_cfe_rule_action->rule_action_type;
				$new_cfe_rule_action->cfe_rule_id = $new_cfe_rule_id;
				$new_cfe_rule_action->insert();
			}
			
			//get CFE rule conditions to copy
			$original_cfe_rule_condition_list = ECash::getFactory()->getModel('CfeRuleConditionList');
			$original_cfe_rule_condition_list->loadBy(array('cfe_rule_id' => $original_cfe_rule_id));
			//Copy CFE rule conditions
			foreach ($original_cfe_rule_condition_list as $original_cfe_rule_condition) 
			{
				$new_cfe_rule_condition = ECash::getFactory()->getModel('CfeRuleCondition');
				$new_cfe_rule_condition->date_modified = $current_time;
				$new_cfe_rule_condition->date_created = $current_time;
				$new_cfe_rule_condition->cfe_rule_id = $new_cfe_rule_id;
				$new_cfe_rule_condition->operator = $original_cfe_rule_condition->operator;
				$new_cfe_rule_condition->operand1 = $original_cfe_rule_condition->operand1;
				$new_cfe_rule_condition->operand1_type = $original_cfe_rule_condition->operand1_type;
				$new_cfe_rule_condition->operand2 = $original_cfe_rule_condition->operand2;
				$new_cfe_rule_condition->operand2_type = $original_cfe_rule_condition->operand2_type;
				$new_cfe_rule_condition->sequence_no = $original_cfe_rule_condition->sequence_no;
				$new_cfe_rule_condition->insert();	
			}
		}
		
		return true;
	}
	
		
		/**
		 * Fetches all rule_sets for a given loan type
		 *
		 * @param string $loan_type name_short from the loan_type table
		 */
		public function fetchAllRulesets($loan_type = NULL) 
		{
			$search_types = array();
			if(!empty($loan_type))
			{
				$loan_type_object = $this->fetchLoanType($loan_type);
				
				$loan_type_id = $loan_type_object->loan_type_id;
				if(! ctype_digit((string) $loan_type_id))
				{
					throw new Exception("Unable to locate loan type '$loan_type'");
				}
				
				$search_types['loan_type_id'] = $loan_type_id;
			}

			$list = new ECash_CFE_API_RuleSetList(ECash::getFactory()->getDB());
			$list->loadBy($search_types);
			$return_array = array();
			foreach($list as $item)
			{
				$return_array[] = $item;
			}

			return $return_array;
		}
		
		public function fetchActiveRuleset($loan_type,$company_id)
		{
			$search_types = array();
	
			$loan_type_object = $this->fetchLoanType($loan_type,$company_id);
				
			$loan_type_id = $loan_type_object->loan_type_id;
			if(! ctype_digit((string) $loan_type_id))
			{
				throw new Exception("Unable to locate loan type '$loan_type' for Company $company_id");
			}
			
			$search_types['loan_type_id'] = $loan_type_id;
			$search_types['active_status'] = 'active';


			$list = new ECash_CFE_API_RuleSetList(ECash::getFactory()->getDB());
			$list->loadBy($search_types);
			$return_array = array();
			foreach($list as $item)
			{
				$return_array[] = $item;
			}
			
			if (count($return_array) === 1)
			{
				$active_rule_set = $return_array[0];
			}
			else if (count($return_array) < 1)
			{
				throw new Exception('No active ruleset found for '. $loan_type);
			}
			else if (count($return_array) > 1)
			{
				throw new Exception('Too many active rulesets found for '. $loan_type);
			}

			return $active_rule_set;
		}

		/**
		 * Fetches a Rule Set based on the rule_set_id
		 *
		 * @param integer $rule_set_id
		 * @return ECash_CFE_API_RuleSetDef
		 */
		public function fetchRuleset($rule_set_id) 
		{
			$model = new ECash_CFE_API_RuleSetDef(ECash::getFactory()->getDB());
			$model->loadBy(array('cfe_rule_set_id' => $rule_set_id));
			return $model;
		}

		/**
		 * Save a Rule Set
		 *
		 * @param integer $loan_type
		 * @param ECash_CFE_API_RuleSetDef $rs
		 */
		public function saveRuleset($loan_type, ECash_CFE_API_RuleSetDef $rs) 
		{
			if(empty($rs->loan_type_id) && ! empty($loan_type))
			{
				$loan_type_object = $this->fetchLoanType($loan_type);
				
				$loan_type_id = $loan_type_object->loan_type_id;
				if(! ctype_digit((string) $loan_type_id))
				{
					throw new Exception("Unable to locate loan type '$loan_type'");
				}
				
				$rs->loan_type_id = $loan_type_id;
			}
			else
			{
				throw new Exception("No loan type specified and ECash_CFE_API_RuleSetDef object does not contain an ID!");
			}
				
			$rs->save();
		}

		/**
		 * @return array of CFE_IAction
		 */
		public function getAvailableActions() 
		{
			$model = new ECash_CFE_API_DefinedActionDef(ECash::getFactory()->getDB());
			return $model->getAllActionsFromDirectory();
		}
		
		/**
		 * fetches all conditions which belong to the given rule set
		 *
		 * @param int $rule_id
		 * @return array of ECash_CFE_API_ConditionDef
		 */
		public function fetchAllConditions($rule_id) {
			$model = new ECash_CFE_API_ConditionDef(ECash::getFactory()->getDB());
			return $model->loadAllBy(array("cfe_rule_id" => $rule_id));
		}
		
		/**
		 * gets all actions that are of that action type
		 *
		 * @param int $type_id
		 * @return array
		 */
		public function fetchAllActionsByType($type_id) {
			$model = new ECash_CFE_API_DefinedActionTypeDef(ECash::getFactory()->getDB());
			return $model->loadAllBy(array("cfe_action_type_id" => $type_id));
		}
		
		/**
		 * gets all actions that are of that action type
		 *
		 * @param int $type_id
		 * @return array
		 */
		public function getAvailableEvents() {
			$model = new ECash_CFE_API_EventDef(ECash::getFactory()->getDB());
			return $model->loadAllBy(array());
		}
		
		/**
		 * fetches the condition with the given condition_id
		 *
		 * @param int $condition_id
		 * @return ECash_CFE_API_ConditionDef
		 */
		public function fetchCondition($condition_id) {
			$model = new ECash_CFE_API_ConditionDef(ECash::getFactory()->getDB());
			$model->loadBy(array("cfe_rule_condition_id" => $condition_id));
			return $model;
		}
		
		/**
		 * fetches all actions which belong to the same rule as the given condition
		 *
		 * @param int $rule_id
		 * @return array of ECash_CFE_API_ActionDef
		 */
		public function fetchAllActions($rule_id) {
			$model = new ECash_CFE_API_ActionDef(ECash::getFactory()->getDB());
			return $model->loadAllBy(array("cfe_rule_id" => $rule_id));
		}
		
		/**
		 * fetches all rules for the given rule_set_id
		 *
		 * @param int $rule_set_id
		 * @return array of CFE_API_RuleDef models
		 */
		public function fetchAllRules($rule_set_id) {
			$model =  new ECash_CFE_API_RuleDef(ECash::getFactory()->getDB());
			return $model->loadAllBy(array("cfe_rule_set_id"=>$rule_set_id));
		}
		
		/**
		 * fetches a single rule
		 *
		 * @param int $rule_id
		 * @return CFE_API_RuleDef
		 */
		public function fetchRule($rule_id) {
			$model = ECash_CFE_API_RuleDef(ECash::getFactory()->getDB());
			$model->loadBy(array("cfe_rule_id"=>$rule_id));
			return $model;
		}
		
		/**
		 * fetches a single action by the action_id
		 *
		 * @param int $action_id
		 * @return CFE_API_ActionDef
		 */
		public function fetchAction($action_id) {
			$model = ECash_CFE_API_DefinedActionDef(ECash::getFactory()->getDB());
			$model->loadBy(array("cfe_action_id"=>$action_id));
			return $model;
		}
		
		/**
		 * saves the given model
		 *
		 * @param ECash_Models_WritableModel $model
		 */
		public function save(ECash_Models_WritableModel $model) {
			$model->save();
		}
		
		/**
		 * inserts the given model
		 *
		 * @param ECash_Models_WritableModel $model
		 */
		public function create(ECash_Models_WritableModel $model) {
			$model->insert();
		}
		
		/**
		 * deletes the given model
		 *
		 * @param ECash_Models_WritableModel $model
		 */
		public function delete(ECash_Models_WritableModel $model) {
			$model->delete();
		}
		
		/**
		 * deletes a given loan type
		 *
		 * @param int $loan_type_id
		 */
		public function deleteLoanType($loan_type_id) {
			$temp = ECash::getFactory()->getModel('LoanType');
			$temp->loan_type_id=$loan_type_id;
			$temp->delete();
		}
		
		/**
		 * deletes a rule set
		 *
		 * @param int $rule_set_id
		 */
		public function deleteRuleSet($rule_set_id) {
			$temp = new ECash_CFE_API_RuleSetDef(ECash::getFactory()->getDB());
			$temp->cfe_rule_set_id=$rule_set_id;
			$temp->delete();
		}
		
		/**
		 * deletes a rule
		 *
		 * @param int $rule_id
		 */
		public function deleteRule($rule_id) {
			$temp = new ECash_CFE_API_RuleDef(ECash::getFactory()->getDB());
			$temp->cfe_rule_id=$rule_id;
			$temp->delete();
		}
		
		/**
		 * deletes an action
		 *
		 * @param int $action_id
		 */
		public function deleteAction($action_id) {
			$temp = new ECash_CFE_API_ActionDef(ECash::getFactory()->getDB());
			$temp->cfe_rule_action_id=$action_id;
			$temp->delete();
		}
		
		/**
		 * deletes a condition
		 *
		 * @param int $condition_id
		 */
		public function deleteCondition($condition_id) {
			$temp = new ECash_CFE_API_ConditionDef(ECash::getFactory()->getDB());
			$temp->cfe_rule_condition_id=$condition_id;
			$temp->delete();
		}
			
		/**
		 * gets the available variable types
		 *
		 * @return array, key is the constant name, value is the constant value
		 */
		public function getAvailableVariables() {
			$model = new ECash_CFE_API_VariableDef(null, null, ECash::getFactory()->getDB());
			return $model->loadAllBy(array());
		}
		
		public function fetchRuleByLoanTyleAndName($loan_type, $name) {
			$loan = $this->fetchLoanType($loan_type);
			$rule_set = new ECash_CFE_API_RuleSetDef(ECash::getFactory()->getDB());
			$rule_set->loadBy(array('loan_type_id' => $loand->loan_type_id));
			$model = ECash_CFE_API_RuleDef(ECash::getFactory()->getDB());
			$model->loadBy(array('cfe_rule_set_id' => $rule_set->cfe_rule_set_id, "name" => $name));
			return $model;
		}
	}
	
?>
