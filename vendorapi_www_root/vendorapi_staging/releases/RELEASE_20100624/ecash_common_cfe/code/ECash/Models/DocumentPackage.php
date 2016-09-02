<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_DocumentPackage extends ECash_Models_ObservableWritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Company;
		public $System;
		public function getColumns()
		{
			static $columns = array(
			'date_modified', 'date_created', 
			'company_id', 'loan_type_id', 'document_package_id' , 'document_list_id', 'name', 'name_short', 'active_status'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array( 'document_package_id');
		}
		public function getAutoIncrement()
		{
			return 'document_package_id';
		}
		public function getTableName()
		{
			return 'document_package';
		}
		public function getColumnID()
		{
			return 'document_package_id';
		}

		public function getColumnName()
		{
			return 'name';
		}

	}
?>
