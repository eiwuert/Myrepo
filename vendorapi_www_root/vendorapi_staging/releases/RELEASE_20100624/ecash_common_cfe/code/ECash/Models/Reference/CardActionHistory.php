<?php
/**
 * @package Ecash.Models
 */
class ECash_Models_Reference_CardActionHistory extends DB_Models_ReferenceModel_1
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'card_action_history_id',
				'agent_id'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('card_action_history_id');
	}

	public function getAutoIncrement()
	{
		return 'card_action_history_id';
	}

	public function getTableName()
	{
		return 'card_action_history';
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
		return 'card_action_history_id';
	}

	public function getColumnName()
	{
		return 'card_action_history_id';
	}

}
?>
