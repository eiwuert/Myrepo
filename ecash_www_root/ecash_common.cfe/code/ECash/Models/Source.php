<?
	class ECash_Models_Source extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'source_ref_id', 'name',
				'name_short'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('source_ref_id');
		}
		public function getAutoIncrement()
		{
			return 'source_ref_id';
		}
		public function getTableName()
		{
			return 'source';
		}
	}
?>