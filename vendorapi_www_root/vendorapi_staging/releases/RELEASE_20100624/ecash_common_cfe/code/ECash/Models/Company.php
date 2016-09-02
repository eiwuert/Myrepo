<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Company extends ECash_Models_ObservableWritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Property;
		
		/**
		 * returns all rows that fit the where_args
		 *
		 * @param array $where_args
		 * @param array $override_dbs
		 * @return array of ECash_Models_Company
		 */
		public static function getAllBy(array $where_args, array $override_dbs = NULL)
		{
			$retval = NULL;
			$query = "SELECT * FROM company" . self::buildWhere($where_args);

			$base = new self();
			$base->setOverrideDatabases($override_dbs);

			if (($rs = $base->getDatabaseInstance(self::DB_INST_READ)->queryPrepared($query, $where_args)) !== FALSE)
			{
				$results = $rs->fetchAll();
				$retval = array();
				foreach($results as $result) {
					$temp = new self();
					$temp->fromDbRow($result);
					$retval[] = $temp;
				}
			}
			return $retval;
		}
		
		/**
		 * returns an array with the company_id as the key and the short name as the value
		 * ordered by company_id desc
		 *
		 * @param array $override_dbs
		 * @return array
		 */
		public static function getAllCompanyIds(array $override_dbs = NULL) {
			$companies = self::getAllBy(array());
			$retval = array();
			foreach($companies as $company) {
				$retval[$company->company_id] = $company->name_short;
			}
			return array_reverse($retval, true);
		}
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'company_id', 'name', 'name_short', 'co_entity_type',
				'ecash_process_type', 'property_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('company_id');
		}
		public function getAutoIncrement()
		{
			return 'company_id';
		}
		public function getTableName()
		{
			return 'company';
		}
	}
?>