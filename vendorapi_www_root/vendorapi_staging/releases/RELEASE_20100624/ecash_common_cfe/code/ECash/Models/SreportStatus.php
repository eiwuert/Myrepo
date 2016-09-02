<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_SreportStatus extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'sreport_status_id','name_short','name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('sreport_status_id');
		}
		public function getAutoIncrement()
		{
			return 'sreport_status_id';
		}
		public function getTableName()
		{
			return 'sreport_status';
		}
		public function getStatusId($name_short)
		{
			$status = new self($this->getDatabaseInstance());
			$status->loadBy(array('name_short' => $name_short));
			if(empty($status->sreport_status_id))
			{
				$status->name_short = $name_short;
				$status->name = ucwords(str_replace('_',' ',$name_short));
				$status->insert();
			}
			return $status->sreport_status_id;
		}
	}
?>
