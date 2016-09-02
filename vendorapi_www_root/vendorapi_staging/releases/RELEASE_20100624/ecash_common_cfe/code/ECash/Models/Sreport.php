<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_Sreport extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'date_created','sreport_type_id','sreport_id','company_id','sreport_date','sreport_start_date','sreport_end_date','sreport_send_status_id','sreport_status_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('sreport_id');
		}
		public function getAutoIncrement()
		{
			return 'sreport_id';
		}
		public function getTableName()
		{
			return 'sreport';
		}
	}
?>
