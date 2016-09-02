<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_DocumentList extends ECash_Models_Reference_Model
	{
		public $Company;
		public $System;
		public function getColumns()
		{
			static $columns = array(
			'date_modified', 'date_created', 'active_status',
			'company_id', 'document_list_id', 'name', 'name_short',
			'required', 'esig_capable', 'system_id', 'send_method',
			'document_api', 'doc_send_order', 'doc_receive_order',
			'only_receivable'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('document_list_id');
		}
		public function getAutoIncrement()
		{
			return 'document_list_id';
		}
		public function getTableName()
		{
			return 'document_list';
		}
		public function getColumnID()
		{
			return 'document_list_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>
