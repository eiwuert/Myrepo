<?php
/**
 * @package Ecash.Models
 */
class ECash_Models_Reference_CardType extends DB_Models_ReferenceModel_1
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'card_type_id',
				'name', 'name_short', 'card_digits',
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('card_type_id');
	}

	public function getAutoIncrement()
	{
		return 'card_type_id';
	}

	public function getTableName()
	{
		return 'card_type';
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
		return 'card_type_id';
	}

	public function getColumnName()
	{
		return 'name_short';
	}


}
?>
