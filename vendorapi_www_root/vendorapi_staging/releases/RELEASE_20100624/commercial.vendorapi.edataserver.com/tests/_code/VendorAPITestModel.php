<?php
class VendorAPITestModel extends DB_Models_WritableModel_1
{
	public function __construct() 
	{
		
	}
	public function getTableName()
	{
		return "test";
	}
	public function getColumns()
	{
		return array('test_id','col1','col2','col3','col4', 'other_model_id');
	}
	
	public function getPrimaryKey()
	{
		return array($this->getAutoIncrement());
	}
	public function getAutoIncrement()
	{
		return 'test_id';
	}
}