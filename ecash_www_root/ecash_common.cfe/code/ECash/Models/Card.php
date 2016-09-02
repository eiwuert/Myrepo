<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Card extends ECash_Models_WritableModel
	{
		public $Company;
		public $Login;
		public $CardRef;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'company_id', 'login_id', 'card_number',
				'card_account_number', 'card_bin', 'card_stock',
				'date_expiration', 'card_ref_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('login_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'card';
		}
	}
?>