<?php
/**
 * @package Ecash.Models
 */
class ECash_Models_Reference_EventType extends DB_Models_ReferenceModel_1
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'event_type_id',
				'name', 'name_short', 'active_status', 'company_id',
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('event_amount_type_id');
	}

	public function getAutoIncrement()
	{
		return 'event_type_id';
	}

	public function getTableName()
	{
		return 'event_type';
	}

	public function getColumnData()
	{
		$column_data = $this->column_data;
		$column_data['date_created'] = date("Y-m-d H:i:s", $this->column_data['date_created']);
		return $column_data;
	}

	public function setColumnData($data)
	{
		$this->column_data = $data;
		$this->column_data['date_created'] = strtotime($data['date_created']);
	}

	public function getColumnID()
	{
		return 'event_type_id';
	}

	public function getColumnName()
	{
		return 'name_short';
	}


}
?>
