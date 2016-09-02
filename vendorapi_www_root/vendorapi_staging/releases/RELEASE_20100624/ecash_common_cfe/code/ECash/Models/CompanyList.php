<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CompanyList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_Company';
		}

		public function getTableName()
		{
			return 'company';
		}

		/**
		 * getCFECompanies
		 * Gets a list of active companies for the CFE rule editor.
		 * If $default company is defined, it includes the default company in the results as well (as its named).
		 *
		 * @param string $default_company
		 * @param array $override_dbs
		 * @return CompanyList $list
		 */
		public function getCFECompanies($default_company = null, $override_dbs= NULL)
		{
			
			$where_args = array();
			$where_args['active_status'] = 'active';
			
			$query = "SELECT * FROM company WHERE active_status = :active_status ";
			if($default_company)
			{
				$query.= "OR name_short = :name_short";
				$where_args['name_short'] = $default_company;
			}
			$db = $this->getDatabaseInstance();
			$this->statement = $db->queryPrepared($query, $where_args);


		}
	}
?>
