<?php

class ECash_Models_ReactAffiliation extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'company_id', 'application_id', 'react_application_id', 'agent_id'
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('react_application_id');
	}
	public function getAutoIncrement()
	{
		return NULL;
	}
	public function getTableName()
	{
		return 'react_affiliation';
	}
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
		$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
		return $column_data;
	}
	public function setColumnData($data)
	{
		$data['date_modified'] = strtotime($data['date_modified']);
		$data['date_created'] = strtotime($data['date_created']);
		parent::setColumnData($data);
	}
}
?>
