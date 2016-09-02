<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_DocumentListPackage extends ECash_Models_ObservableWritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Company;
		public $System;
		public function getColumns()
		{
			static $columns = array(
			'date_modified', 'date_created', 
			'company_id', 'document_package_id' , 'document_list_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('document_list_id', 'document_package_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'document_list_package';
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
