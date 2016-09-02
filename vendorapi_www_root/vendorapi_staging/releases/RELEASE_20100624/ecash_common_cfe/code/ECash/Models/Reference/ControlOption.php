<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_ControlOption extends ECash_Models_Reference_Model
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'control_option_id',
				'name_short', 'name', 'description', 'type'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('control_option_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'control_option';
		}
		
		public function getColumnID()
		{
			return 'control_option_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>
