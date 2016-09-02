<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_CardInfo extends ECash_Models_WritableModel
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

	public function getTableName()
	{
		return 'card_info';
	}

	public function getAutoIncrement()
	{
		return 'card_info_id';
	}

	public function getColumnData()
	{
		$column_data = $this->column_data;

		$column_data['date_created']    = date("Y-m-d H:i:s", strtotime($this->column_data['date_created']));
		$column_data['expiration_date'] = date("Y-m-d",       strtotime($this->column_data['expiration_date']));

		return $column_data;
	}

	public function setColumnData($data)
	{
		$this->column_data = $data;
		$this->column_data['date_created']    = date("Y-m-d H:i:s", strtotime($data['date_created']));
		$this->column_data['expiration_date'] = date("Y-m-d", strtotime($data['expiration_date']));
	}
	
	public function getCardInfoByApId($ap_id)
	{
		$query = "
			SELECT card_info_id
			FROM card_info ci
			WHERE application_id = ?
			AND active_status = 'active'
			ORDER BY date_modified DESC
			LIMIT 1";

			$prep_args   = array($ap_id);

			$st = DB_Util_1::querySingleValue($this->db, $query, $prep_args);

		if(!empty($st))
		{
			$this->setColumnData($st[0]);
		}
	}
}
?>
