<?php
/**
 * @package Ecash.Models
 */
class ECash_Models_CardActionHistory extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'card_action_history_id',
				'card_info_id', 'card_action_id', 'application_id', 'agent_id', 'changed_fields'
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

}
?>
