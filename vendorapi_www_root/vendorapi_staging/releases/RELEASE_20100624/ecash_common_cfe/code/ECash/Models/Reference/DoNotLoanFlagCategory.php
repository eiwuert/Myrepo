<?php 
	class ECash_Models_Reference_DoNotLoanFlagCategory extends DB_Models_ReferenceModel_1
	{
		public $Category;
		public function getColumns()
		{
			static $columns = array(
				'category_id', 'name', 'active_status', 'date_modified',
				'date_created'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('category_id');
		}
		public function getAutoIncrement()
		{
			return 'category_id';
		}
		public function getTableName()
		{
			return 'do_not_loan_flag_category';
		}
		public function getColumnId()
		{
			return 'category_id';
		}
		public function getColumnName()
		{
			return 'name';
		}
	}
