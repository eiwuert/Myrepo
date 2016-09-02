<?php

	class ECash_Models_CfeVariable extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{

	
		/**
		 * getColumns - returns an array of the columns on this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'cfe_variable_id', 'name','name_short', 'type'
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
			return array('cfe_variable_id');
		}

		/**
		 * gets the auto_increment field from the database
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'cfe_variable_id';
		}

		/**
		 * gets the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_variable';
		}
		
	}

?>