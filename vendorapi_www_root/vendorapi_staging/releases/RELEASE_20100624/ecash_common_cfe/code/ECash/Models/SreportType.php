<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_SreportType extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'sreport_type_id','name_short','name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('sreport_type_id');
		}
		public function getAutoIncrement()
		{
			return 'sreport_type_id';
		}
		public function getTableName()
		{
			return 'sreport_type';
		}
		
		public function getTypeId($name_short)
		{
			$type = new self($this->getDatabaseInstance());
			$type->loadBy(array('name_short' => $name_short));
			if(empty($type->sreport_type_id))
			{
				$type->name_short = $name_short;
				$type->name = ucwords(str_replace('_',' ',$name_short));
				$type->insert();
			}
			return $type->sreport_type_id;
		}
	}
?>
