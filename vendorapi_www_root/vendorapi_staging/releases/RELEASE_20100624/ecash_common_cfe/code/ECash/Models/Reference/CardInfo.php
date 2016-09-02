<?php
/**
 * @package Ecash.Models
 */
class ECash_Models_Reference_CardInfo extends DB_Models_ReferenceModel_1
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'card_info_id',
				'application_id', 'card_number', 'expiration_date', 'active_status',
				'cardholder_name', 'card_street', 'card_zip', 'card_type_id'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('card_info_id');
	}

	public function getAutoIncrement()
	{
		return 'card_info_id';
	}

	public function getTableName()
	{
		return 'card_info';
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
		return 'card_info_id';
	}

	public function getColumnName()
	{
		return 'card_info_id';
	}

}
?>
