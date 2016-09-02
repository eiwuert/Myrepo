<?php

	class ECash_Data_Customer extends ECash_Data_DataRetriever
	{
		/**
		 * Do not loan flag object to be used for DNL actions
		 * 
		 * @var Do_Not_Loan
		 */
		protected $dnl;

		/**
		 * Gets / instantiates a Do_Not_Loan object for use with DNL actions
		 * @return Do_Not_Loan
		 */
		protected function getDNL()
		{
			if (empty($this->dnl))
			{
				$this->dnl = new Do_Not_Loan($this->db);
			}

			return $this->dnl;
		}

		public function getDoNotLoanSummary($ssn)
		{
			return $this->getDNL()->Get_DNL_Info($ssn);
		}

		public function getPaidCount($customer_id, $company_id)
		{
			$query = "select count(*)
						from application
						where customer_id = ?
						and company_id = ?
						and application_status_id = ?";

			$status_map = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			$paid_status = $status_map->toId('paid::customer::*root');

			return DB_Util_1::querySingleValue(
				$this->db,
				$query,
				array($customer_id, $company_id, $paid_status)
			);
		}

		public function getMaxLoan($customer_id, $company_id) {
            $query = "
            SELECT 	fund_qualified
            FROM application
            WHERE ssn = (SELECT ssn
                         FROM application
                         WHERE application_id = ?)
            AND company_id = ?
            AND fund_actual IS NOT NULL
            ORDER BY fund_qualified DESC";
    
            $max_qualified = DB_Util_1::querySingleValue($this->db, $query, array($application_id, $company_id));
    
            $query = "
            SELECT 	fund_actual
            FROM application
            WHERE ssn = (SELECT ssn
                         FROM application
                         WHERE application_id = ?)
            AND company_id = ?
            ORDER BY fund_actual DESC";
    
            $max_actual = DB_Util_1::querySingleValue($this->db, $query, array($application_id, $company_id));
            
            $max_funded = max($max_qualified*1,$max_actual*1);
            
            return $max_funded;
		}


		/**
		 * Returns whether or not the DNL flag is set for this customer.
		 *
		 * @param int $ssn 
		 * @return bool
		 */
		public function getDoNotLoan($ssn)
		{
			return $this->getDNL()->Does_SSN_In_Table($ssn);
		}

		/**
		 * Returns whether or not the DNL flag is set for this customer,
		 * only for the specified company_id
		 *
		 * @param int $ssn 
		 * @param int $company_id
		 * @return bool
		 */
		public function getDoNotLoanByCompany($ssn, $company_id)
		{
			return $this->getDNL()->Does_SSN_In_Table_For_Company($ssn, $company_id);
		}

		/**
		 * Returns whether or not the DNL flag is set for this customer,
		 * only for companys other than the company_id provided.
		 *
		 * @param int $company_id
		 */
		public function getDoNotLoanByCompanyExclusion($ssn, $company_id)
		{
			return $this->getDNL()->Does_SSN_In_Table_For_Other_Company($ssn, $company_id);
		}

		public function clearDoNotLoan($ssn)
		{
			return $this->getDNL()->clearDoNotLoan($ssn);
		}

		/**
		 * Determines whether or not this company has an "OK to fund"
		 * override
		 *
		 * @param int $company_id
		 * @return bool
		 */
		public function getDoNotLoanOverride($ssn, $company_id)
		{
			return $this->getDNL()->Does_Override_Exists_For_Company($ssn, $company_id);
		}

		/**
		 * Removes the override for this company.
		 *
		 * @param int $company_id
		 */
		public function clearDoNotLoanOverride($ssn, $company_id)
		{
			return $this->getDNL()->Remove_Override_DNL($ssn, $company_id);
		}

		public function Add_To_DNL_Audit($company_id, $ssn, $table_name, $column_name, $value_before, $value_after, $agent_id)
		{
			$dnla_model = ECash::getFactory()->getModel('DoNotLoanAudit');
			$dnla_model->company_id = $company_id;
			$dnla_model->ssn = $ssn;
			$dnla_model->table_name = $table_name;
			$dnla_model->column_name = $column_name;
			$dnla_model->value_before = $value_before;
			$dnla_model->value_after = $value_after;
			$dnla_model->agent_id = $agent_id;
			return $dnla_model->save();
		}

		/**
		 * Grabs the Do Not Loan Audit
		 * 
		 * This code is redundant -- MatthewJ is replacing this code in 
		 * ecash_commercial/sql/lib/do_not_loan.class.php
		 * 
		 * @param int $ssn
		 * @return array
		 */
		public function Get_DNL_Audit_Log($ssn)
		{

			$agent_model = ECash::getFactory()->getModel("Agent");
			
			$log_entries = ECash::getFactory()->getAppClient()->getDoNotLoanAudit($ssn);
			foreach($log_entries as $entry)
			{
				if(empty($entry)) continue;
				
				$loaded = $agent_model->loadBy(array('agent_id' => $entry->modifying_agent_id));
				$entry->date_created = date('Y-m-d H:i:s', strtotime($entry->date_created));
				$entry->table_name = $entry->table_name;
				$entry->value_before = $entry->old_value;
				$entry->value_after = $entry->new_value;
				$entry->agent_id = $entry->modifying_agent_id;
				$entry->name_first = ($loaded) ? $agent_model->name_first : NULL;
				$entry->name_last = ($loaded) ? $agent_model->name_last : NULL;
			}

			return $log_entries;

		}
		public function Get_DNL_Info($ssn)
		{
			return $this->getDNL()->Get_DNL_Info($ssn);
		}

		public function Get_DNL_Override_Info($ssn)
		{
			return $this->getDNL()->Get_DNL_Override_Info($ssn);
		}
	}
?>
