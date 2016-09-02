<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_AccessGroupControlOption extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{

		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'access_group_id', 'control_option_id' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('access_group_id','control_option_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'access_group_control_option';
		}
	}
?>