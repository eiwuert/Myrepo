<?php

	class ECash_Models_CfeAction extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{

				/**
		 * returns an array of the columns in this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'cfe_action_id',
				'name'
			);
			return $columns;
		}

		/**
		 * returns an array of the primary key
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('cfe_action_id');
		}

		/**
		 * returns the auto_increment field
		 *
		 * @return int
		 */
		public function getAutoIncrement()
		{
			return 'cfe_action_id';
		}

		/**
		 * returns the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_action';
		}
		
		
	}

?>