<?php

	class ECash_Models_CfeRule extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Event;
		
		public $Salience;
		
		/**
		 * array of CFE_ConditionDef, null to flag that it needs to be populated
		 */
		public $Conditions = null;
		
		/**
		 * array of CFE_ActionDef, null to flag that it needs to be populated
		 */
		public $Actions = null;

		/**
		 * getColumns - returns an array of the columns on this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'cfe_rule_id',
				'cfe_rule_set_id', 'name', 'cfe_event_id', 'salience'
			);
			return $columns;
		}

		/**
		 * returns the primary key in an array
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('cfe_rule_id');
		}

		/**
		 * gets the auto_increment field from the database
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'cfe_rule_id';
		}

		/**
		 * gets the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_rule';
		}
	}

?>