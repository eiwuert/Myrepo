<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Customer extends ECash_Models_ObservableWritableModel
	{
		public $Company;
		public $ModifyingAgent;
		

		public function loadByCompanyExisting($company_name_short, $app_id, $ssn, $override_dbs = NULL)
		{
			//Check for existing logins
			//not sure I like the 'OR' [JustinF]
			$query = "SELECT cust.*
				  FROM customer as cust
				  inner join application as app on (cust.customer_id = app.customer_id)
				  inner join company as co on (cust.company_id = co.company_id)
				  WHERE co.name_short = ?
					AND (app.application_id = ?
					  OR app.ssn = ?)
				  LIMIT 1";

			if (($row = $this->getDatabaseInstance()->querySingleRow($query, array(strtolower($company_name_short), $app_id, $ssn))) !== FALSE)
			{
				$this->fromDbRow($row);
				return TRUE;
			}
			return FALSE;
		}

		public function getUsernameCount($username, $override_dbs = NULL)
		{
			//Underscore is a wildcard character, so we should escape it in the query.
			$query_username = str_replace('_', '\_', $username);

			$query = "
				SELECT LPAD(SUBSTRING_INDEX(login, '_', -1), 10, ' ') AS num
				FROM customer
				WHERE login LIKE '{$query_username}%'
				ORDER BY num DESC
				LIMIT 1";

			if (($num = $this->getDatabaseInstance()->querySingleValue($query)) !== FALSE)
			{
				return $num;
			}
			return 0;                       
		}
		
		public function loadBySSN($company_id, $ssn, $override_dbs = NULL)
		{
			//Check for existing SSNs
			$query = "
			SELECT 	customer.*
			FROM customer
			WHERE ssn = ?
			AND company_id = ?";
			
			if (($row = $this->getDatabaseInstance()->querySingleRow($query, array($ssn, $company_id))) !== FALSE)
			{
				$this->fromDbRow($row);
				return TRUE;
			}
			return FALSE;
		}

		public function getColumns()
		{
			static $columns = array(
				'customer_id', 'company_id', 'ssn', 'login', 'password',
				'modifying_agent_id', 'date_modified', 'date_created'
			);
			return $columns;
		}

		public function getColumnData()
		{
			$modified 	= $this->column_data;
			$modified['date_created'] = date('Y-m-d H:i:s', $modified['date_created']);

			return $modified;
		}		
		public function getPrimaryKey()
		{
			return array('customer_id');
		}
		public function getAutoIncrement()
		{
			return 'customer_id';
		}
		public function getTableName()
		{
			return 'customer';
		}
	}
?>