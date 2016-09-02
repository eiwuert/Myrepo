<?php

	class ECash_Data_Customer extends ECash_Data_DataRetriever
	{
		public function getDoNotLoanSummary($ssn)
		{
			$query = "
				SELECT
					cat.name,
					dnl.explanation,
					dnl.other_reason,
					dnl.agent_id,
					if(ag.name_last is null, 'Unknown', ag.name_last) AS name_last,
					if(ag.name_first is null, 'Unknown', ag.name_first) AS name_first,
					dnl.date_created,
					dnl.company_id,
					com.name as company_name,
					com.name_short,
					dnl.active_status
				FROM do_not_loan_flag dnl
				JOIN do_not_loan_flag_category cat ON (dnl.category_id = cat.category_id)
				JOIN company com ON (dnl.company_id = com.company_id)
				LEFT JOIN agent ag ON (dnl.agent_id = ag.agent_id)
				WHERE ssn = ?
					AND dnl.active_status = 'active'
				ORDER BY
					dnl.date_created DESC
			";
			$st = DB_Util_1::queryPrepared($this->db, $query, array($ssn));
			return $st->fetchAll(PDO::FETCH_OBJ);
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
		
		public function getCustomerIDBySSN($ssn,$company_id)
		{
			$query = "
				SELECT 	customer_id
				FROM customer
				WHERE ssn = ?
				AND company_id = ?
			";			
			$st = DB_Util_1::queryPrepared($this->db, $query, array($ssn,$company_id));
			return $st->fetchAll(PDO::FETCH_OBJ);			
		}
		
		/**
		 * Checks to see if a login name exists in the customer table
		 *
		 * @param string $login
		 * @return boolean
		 */
		public function getCustomerIDByLogin($login,$company_id)
		{
			$query = "
				SELECT customer_id
				FROM customer
				WHERE login = ?
				AND company_id = ?
			";
			$st = DB_Util_1::queryPrepared($this->db, $query, array($login, $company_id));
			return $st->fetchAll(PDO::FETCH_OBJ);
		}		
		
		public function setApplicationsCustomerID($applications, $customer_id, $company_id)
		{
			// get a string of comma-delimited question marks
			$value_str = array();
			$value_str = array_pad($value_str, count($applications), '?');
			$value_str = implode(', ', $value_str);
						
			$query = "
				UPDATE application
				SET customer_id = $customer_id
				WHERE application_id IN ({$value_str})
				AND company_id = ?	
			";
	
			$st = DB_Util_1::queryPrepared($this->db, $query, array($customer_id, $company_id));
			return $st->fetchAll(PDO::FETCH_OBJ);		

		}
		
		/**
		 * Set's a new SSN number for all of the applications in the array
		 *
		 * @param array  $applications array(123234,234235,3245234)
		 * @param string $ssn (example: 123121234)
		 */
		public function setApplicationSSN($ssn, $applications, $customer_id, $company_id)
		{

			// get a string of comma-delimited question marks
			$value_str = array();
			$value_str = array_pad($value_str, count($applications), '?');
			$value_str = implode(', ', $value_str);
						
			$query = "
				UPDATE application
				SET ssn = ?
				WHERE customer_id = ?
					AND company_id = ?
					AND application_id IN ({$value_str})
			";
			$st = DB_Util_1::queryPrepared($this->db, $query, array($ssn, $customer_id, $company_id));		
			
			return $st->fetchAll(PDO::FETCH_OBJ);	
		}
		
		/**
		 * Returns whether or not the DNL flag is set for this customer.
		 *
		 * @param int $ssn 
		 * @return bool
		 */
		public function getDoNotLoan($ssn)
		{
			$query = "
				SELECT COUNT(*) AS count
				FROM do_not_loan_flag
				WHERE
					ssn = ?
					AND active_status = 'active'
			";

			return (DB_Util_1::querySingleValue(
				$this->db,
				$query,
				array($ssn)) > 0
			);
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
			$query = "
				SELECT COUNT(*) AS count
				FROM do_not_loan_flag
				WHERE
					ssn = ?
					AND company_id = ?
					AND active_status = 'active'
			";

			return (DB_Util_1::querySingleValue(
				$this->db,
				$query,
				array(
					$ssn,
					$company_id
				)) > 0
			);
		}
			
		/**
		 * Returns whether or not the DNL flag is set for this customer,
		 * only for companys other than the company_id provided.
		 *
		 * @param int $company_id
		 */
		public function getDoNotLoanByCompanyExclusion($ssn, $company_id)
		{
			$query = "
				SELECT COUNT(*) AS count
				FROM do_not_loan_flag
				WHERE
					ssn = ?
					AND company_id != ?
					AND active_status = 'active'
			";

			return (DB_Util_1::querySingleValue(
				$this->db,
				$query,
				array(
					$ssn,
					$company_id
				)) > 0
			);
		}	

		public function clearDoNotLoan($ssn)
		{
			$query = "
				DELETE FROM do_not_loan_flag
				WHERE
					ssn = ?
			";

			DB_Util_1::execPrepared(
				$this->db,
				$query,
				array($ssn)
			);
		}
		
		public function deactivateDoNotLoan($ssn, $agent_id, $company_id)
		{
			$query = "
				UPDATE do_not_loan_flag
				SET
					active_status = 'inactive',
					agent_id = ?,
					date_modified = now()
				WHERE
					ssn = ?
					AND active_status = 'active'
					AND company_id = ?
			";

			DB_Util_1::execPrepared($this->db, $query, array($agent_id, $ssn, $company_id));
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
			$query = "
				SELECT count(*) as 'count'
				FROM  do_not_loan_flag_override
				WHERE
					ssn = ?
					AND company_id = ?";

			return (DB_Util_1::querySingleValue(
				$this->db,
				$query,
				array(
					$ssn,
					$company_id
				)) > 0
			);
		}		
		
			/**
		 * Removes the override for this company.
		 *
		 * @param int $company_id
		 */
		public function clearDoNotLoanOverride($ssn, $company_id)
		{
			$query = "
				DELETE FROM do_not_loan_flag_override
				WHERE
					ssn = ?
					and company_id = ?
			";

			DB_Util_1::execPrepared(
				$this->db,
				$query,
				array($ssn, $company_id)
			);
		}
		public function Add_To_DNL_Audit($company_id, $ssn, $table_name, $column_name, $value_before, $value_after, $agent_id)
		{
			$query = "
				INSERT INTO do_not_loan_audit 
				(date_created, company_id, ssn, table_name, column_name, value_before, value_after, agent_id)
				VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)
			";
			$args = array($company_id, $ssn, $table_name, $column_name, $value_before, $value_after, $agent_id);
			
			$this->db->queryPrepared($query, $args);
			return TRUE;
		}
	
		public function Get_DNL_Audit_Log($ssn)
		{
			$query = "
				SELECT
					dnla.date_created,
					dnla.table_name,
					dnla.column_name,
					dnla.value_before,
					dnla.value_after,
					dnla.agent_id,
					a.name_first,
					a.name_last
				FROM do_not_loan_audit as dnla
					LEFT JOIN agent a ON dnla.agent_id = a.agent_id
				WHERE dnla.ssn = ? 
				ORDER BY
					date_created
			";
			$st = $this->db->queryPrepared($query, array($ssn));
			return $st->fetchAll(PDO::FETCH_OBJ);
		}			
		public function Override_Do_Not_Loan($company_id, $agent_id, $ssn)
		{
			$query = "
				INSERT INTO do_not_loan_flag_override
				(ssn, company_id, agent_id, date_modified, date_created)
				VALUES (?, ?, ?, NOW(), NOW())
			";
			$this->db->queryPrepared($query, array($ssn, $company_id, $agent_id));
			
			return TRUE;
		}
		
		public function Get_DNL_Info($ssn)
		{
			$query = "
				SELECT
					cat.name,
					dnl.explanation,
					dnl.other_reason,
					dnl.agent_id,
					if(ag.name_last is null, 'Unknown', ag.name_last) AS name_last,
					if(ag.name_first is null, 'Unknown', ag.name_first) AS name_first,
					dnl.date_created,
					dnl.company_id,
					com.name as company_name,
					com.name_short,
					dnl.active_status 
				FROM do_not_loan_flag dnl
					JOIN do_not_loan_flag_category cat ON (dnl.category_id = cat.category_id)
					JOIN company com ON (dnl.company_id = com.company_id)
					LEFT JOIN agent ag ON (dnl.agent_id = ag.agent_id)
				WHERE ssn = ?
					AND dnl.active_status = 'active' 
				ORDER BY 
					dnl.date_created DESC 
			";
			$st = $this->db->queryPrepared($query, array($ssn));
			return $st->fetchAll(PDO::FETCH_OBJ);
		}
	
		public function Get_DNL_Override_Info($ssn)
		{
			$query = "
				SELECT
					com.name_short,
					com.name 
				FROM do_not_loan_flag_override ovr
					JOIN company com ON (ovr.company_id = com.company_id) 
				WHERE ssn = ? 
				ORDER BY ovr.date_created DESC 
			";
			$st = $this->db->queryPrepared($query, array($ssn));
			return $st->fetchAll(PDO::FETCH_OBJ);
		}
	}
?>