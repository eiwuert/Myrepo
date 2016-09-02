<?php
	
	require_once 'WritableModel.php';

	
	class ECash_Models_DocumentListBody extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'document_list_id',
				'document_list_body_id',
				'send_method'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('document_list_id', 'document_list_body_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'document_list_body';
		}
	
	}
?>
