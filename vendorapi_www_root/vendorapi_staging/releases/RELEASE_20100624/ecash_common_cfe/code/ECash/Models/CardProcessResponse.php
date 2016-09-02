<?php
/**
 * @package Ecash.Models
 */
class ECash_Models_CardProcessResponse extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData
{
	public function getColumns()
	{
		static $columns = array(
			'response_code','reason_code','response_text','reason_text','response','process_fail','fatal_fail'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('card_process_response_id');
	}

	public function getAutoIncrement()
	{
		return 'card_process_response_id';
	}

	public function getTableName()
	{
		return 'card_process_response';
	}

	public function getColumnData()
	{
		$column_data = $this->column_data;
		return $column_data;
	}

	public function setColumnData($data)
	{
		$this->column_data = $data;
	}
}
?>
