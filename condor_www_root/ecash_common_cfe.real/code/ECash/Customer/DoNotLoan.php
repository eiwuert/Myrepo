<?php

	class ECash_Customer_DoNotLoan extends ECash_Customer_Component
	{
		const CATEGORY_FRAUD = 'fraud';
		const CATEGORY_RISK_ACTIVITY = 'risk_activity';
		const CATEGORY_BANKRUPTCY = 'bankruptcy';
		const CATEGORY_COMPLIANCE = 'compliance';
		const CATEGORY_OTHER = 'other';

		/**
		 * Flags this customer (by ssn) as Do Not Loan.
		 *
		 * @param int $agent_id Agent_ID of the agent responsible for the flag.
		 * @param string $reason Agent-entered reason or details about this flag.
		 * @param string $category Category string. Use one of the class constants (CATEGORY_*)
		 * @param string $category_details if the category is CATEGORY_OTHER, further details go here.
		 */
		public function set($agent_id, $reason, $category, $category_details = NULL)
		{
			$dnl_info = ECash::getFactory()->getData('Customer')->Get_DNL_Info($this->customer->Model->ssn);;
			$dnl_before = array();
			foreach($dnl_info as $current_dnl)
			{
				$dnl_before[] = $current_dnl->name . ' ' . str_replace("'", "\'", $current_dnl->other_reason) . ' ' .
						str_replace("'", "\'", $current_dnl->explanation) . ' ' . strtoupper($current_dnl->name_short);
			}
			
			$flag = ECash::getFactory()->getModel('DoNotLoanFlag');
			$flagtypes = ECash::getFactory()->getReferenceList('DoNotLoanFlagCategory');

			$flag->ssn = $this->customer->getModel()->ssn;
			$flag->company_id = $this->company_id;
			$flag->category_id = $flagtypes->toId($category);
			$flag->other_reason = $category_details;
			$flag->explanation = $reason;
			$flag->active_status = 'active';
			$flag->agent_id = $agent_id;
			$flag->date_modified = time();
			$flag->date_created = time();

			$flag->save();
		
			$dnl_info = ECash::getFactory()->getData('Customer')->Get_DNL_Info($this->customer->Model->ssn);;
			$dnl_after  = array();
			foreach($dnl_info as $current_dnl)
			{
				$dnl_after[] = $current_dnl->name . ' ' . str_replace("'", "\'", $current_dnl->other_reason) . ' ' .
						str_replace("'", "\'", $current_dnl->explanation) . ' ' . strtoupper($current_dnl->name_short);
			}
			
			ECash::getFactory()->getData('Customer')->Add_To_DNL_Audit($this->customer->Model->company_id, $this->customer->Model->ssn, 'do_not_loan_flag', '', $dnl_before != null ? implode("; ", $dnl_before) : 'not set', implode("; ", $dnl_after), $agent_id);
		}

		/**
		 * Returns whether or not the DNL flag is set for this customer.
		 *
		 * @return bool
		 */
		public function get()
		{
			return ECash::getFactory()->getData('Customer')->getDoNotLoan($this->customer->Model->ssn);

		}

		/**
		 * Returns whether or not the DNL flag is set for this customer,
		 * only for the specified company_id
		 *
		 * @param int $company_id
		 * @return bool
		 */
		public function getByCompany($company_id)
		{
			return ECash::getFactory()->getData('Customer')->getDoNotLoanByCompany($this->customer->Model->ssn, $company_id);
		}

		/**
		 * Returns whether or not the DNL flag is set for this customer,
		 * only for companys other than the company_id provided.
		 *
		 * @param int $company_id
		 */
		public function getByCompanyExclusion($company_id)
		{
			return ECash::getFactory()->getData('Customer')->getDoNotLoanByCompanyExclusion($company_id, $this->customer->Model->ssn);
		}

		/**
		 * Removes the DNL flag for this customer.
		 *
		 */
		public function clear()
		{
			$dnl_info = ECash::getFactory()->getData('Customer')->Get_DNL_Info($this->customer->Model->ssn);;
			$dnl_before = array();
			foreach($dnl_info as $current_dnl)
			{
				$dnl_before[] = $current_dnl->name . ' ' . str_replace("'", "\'", $current_dnl->other_reason) . ' ' .
						str_replace("'", "\'", $current_dnl->explanation) . ' ' . strtoupper($current_dnl->name_short);
			}
			
			ECash::getFactory()->getData('Customer')->clearDoNotLoan($this->customer->Model->ssn);

			$dnl_info = ECash::getFactory()->getData('Customer')->Get_DNL_Info($this->customer->Model->ssn);;
			$dnl_after  = array();
			foreach($dnl_info as $current_dnl)
			{
				$dnl_after[] = $current_dnl->name . ' ' . str_replace("'", "\'", $current_dnl->other_reason) . ' ' .
						str_replace("'", "\'", $current_dnl->explanation) . ' ' . strtoupper($current_dnl->name_short);
			}
			
			ECash::getFactory()->getData('Customer')->Add_To_DNL_Audit($this->customer->Model->company_id, $this->customer->Model->ssn, 'do_not_loan_flag', '', implode("; ", $dnl_before), $dnl_after != null ? implode("; ", $dnl_after) : 'not set', ECash::getAgent()->getAgentId());
		}

		public function deactivate($agent_id, $company_id)
		{
			$dnl_info = ECash::getFactory()->getData('Customer')->Get_DNL_Info($this->customer->Model->ssn);;
			$dnl_before = array();
			foreach($dnl_info as $current_dnl)
			{
				$dnl_before[] = $current_dnl->name . ' ' . str_replace("'", "\'", $current_dnl->other_reason) . ' ' .
						str_replace("'", "\'", $current_dnl->explanation) . ' ' . strtoupper($current_dnl->name_short);
			}
	
			ECash::getFactory()->getData('Customer')->deactivateDoNotLoan($this->customer->Model->ssn,$agent_id, $company_id);
	
			$dnl_info = ECash::getFactory()->getData('Customer')->Get_DNL_Info($this->customer->Model->ssn);;
			$dnl_after  = array();
			foreach($dnl_info as $current_dnl)
			{
				$dnl_after[] = $current_dnl->name . ' ' . str_replace("'", "\'", $current_dnl->other_reason) . ' ' .
						str_replace("'", "\'", $current_dnl->explanation) . ' ' . strtoupper($current_dnl->name_short);
			}
			
			ECash::getFactory()->getData('Customer')->Add_To_DNL_Audit($this->customer->Model->company_id, $this->customer->Model->ssn, 'do_not_loan_flag', '', implode("; ", $dnl_before), $dnl_after != null ? implode("; ", $dnl_after) : 'not set', ECash::getAgent()->getAgentId());
	
		}

		/**
		 * Sets a DoNotLoan override flag.
		 *
		 * @param int $agent_id
		 * @param int $company_id
		 */
		public function setOverride($agent_id, $company_id)
		{
			$dnl_override_info = ECash::getFactory()->getData('Customer')->Get_DNL_Override_Info($this->customer->Model->ssn);
			$dnl_override_before = array();
			foreach($dnl_override_info as $current_ovr)
			{
				$dnl_override_before[] = strtoupper($current_ovr->name_short);
			}
		
			ECash::getFactory()->getData('Customer')->Override_Do_Not_Loan($company_id, $agent_id, $this->customer->Model->ssn);
			
			$dnl_override_info = ECash::getFactory()->getData('Customer')->Get_DNL_Override_Info($this->customer->Model->ssn);
			$dnl_override_after = array();
			foreach($dnl_override_info as $current_ovr)
			{
				$dnl_override_after[] = strtoupper($current_ovr->name_short);
			}

			ECash::getFactory()->getData('Customer')->Add_To_DNL_Audit($company_id, $this->customer->Model->ssn, 'do_not_loan_flag_override', '', $dnl_override_before != null ? implode("; ", $dnl_override_before) : 'not set', implode("; ", $dnl_override_after), $agent_id);
		}


		/**
		 * Determines whether or not this company has an "OK to fund"
		 * override
		 *
		 * @param int $company_id
		 * @return bool
		 */
		public function getOverride($company_id)
		{
			return ECash::getFactory()->getData('Customer')->getDoNotLoanOverride($this->customer->Model->ssn, $company_id);
		}

		/**
		 * Removes the override for this company.
		 *
		 * @param int $company_id
		 */
		public function clearOverride($company_id)
		{
			$dnl_override_info = ECash::getFactory()->getData('Customer')->Get_DNL_Override_Info($this->customer->Model->ssn);
			$dnl_override_before = array();
			foreach($dnl_override_info as $current_ovr)
			{
				$dnl_override_before[] = strtoupper($current_ovr->name_short);
			}
			
			ECash::getFactory()->getData('Customer')->clearDoNotLoanOverride($this->customer->Model->ssn, $company_id);
			
			$dnl_override_info = ECash::getFactory()->getData('Customer')->Get_DNL_Override_Info($this->customer->Model->ssn);
			$dnl_override_after = array();
			foreach($dnl_override_info as $current_ovr)
			{
				$dnl_override_after[] = strtoupper($current_ovr->name_short);
			}
			ECash::getFactory()->getData('Customer')->Add_To_DNL_Audit($company_id, $this->customer->Model->ssn, 'do_not_loan_flag_override', '', implode("; ", $dnl_override_before),$dnl_override_after != null ? implode("; ", $dnl_override_after) : 'not set', ECash::getAgent()->getAgentId());
		}
		/**
		 * Returns Do not Loan Audit Log.
		 *
		 * 
		 */
		public function getLog()
		{
			return ECash::getFactory()->getData('Customer')->Get_DNL_Audit_Log($this->customer->Model->ssn);
		}
		
	}
?>
