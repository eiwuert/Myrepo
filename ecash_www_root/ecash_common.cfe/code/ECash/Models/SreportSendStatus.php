<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_SreportSendStatus extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'sreport_send_status_id','name_short','name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('sreport_send_status_id');
		}
		public function getAutoIncrement()
		{
			return 'sreport_send_status_id';
		}
		public function getTableName()
		{
			return 'sreport_send_status';
		}
		public function getStatusId($name_short)
		{
			$send_status = new ECash_Models_SreportSendStatus($this->getDatabaseInstance());
			$send_status->loadBy(array('name_short' => $name_short));
			
			if(empty($send_status->sreport_send_status_id))
			{
				$send_status->name_short = $name_short;
				$send_status->name = ucwords(str_replace('_',' ',$name_short));
				$send_status->insert();
			}
			return $send_status->sreport_send_status_id;
		}
	}
?>
