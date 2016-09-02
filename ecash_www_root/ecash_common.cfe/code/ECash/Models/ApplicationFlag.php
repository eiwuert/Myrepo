<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_ApplicationFlag extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_created','company_id','application_id','application_flag_id','flag_type_id',
				'modifying_agent_id','active_status'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_flag_id');
		}
		public function getAutoIncrement()
		{
			return 'application_flag_id';
		}
		public function getTableName()
		{
			return 'application_flag';
		}
		public function setFlagType($name_short)
		{
			$flag_type = ECash::getFactory()->getModel('FlagType');
			$flag_type->loadBy(array('name_short'=>$name_short));
			$this->flag_type_id = $flag_type->flag_type_id;
		}
	}
?>