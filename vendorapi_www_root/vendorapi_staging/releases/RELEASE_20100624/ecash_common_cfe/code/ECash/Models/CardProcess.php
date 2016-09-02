<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_CardProcess extends ECash_Models_WritableModel
	{
		public $Company;
		public $Login;
		public $CardRef;
		public function getColumns()
		{
			static $columns = array(
				'card_process_id', 'date_created', 'card_info_id',
				'application_id', 'amount', 'process_status', 
				'transaction_id', 'result_code', 'result_subcode', 
				'reason_code', 'authorization_code', 'avs_response',
				'card_code_response', 'cavv_response'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('login_id');
		}
		public function getAutoIncrement()
		{
			return 'card_process_id';
		}
		public function getTableName()
		{
			return 'card_process';
		}
	
		public function getColumnData()
		{
			$column_data = $this->column_data;
	
			$column_data['date_created']    = date("Y-m-d H:i:s", strtotime($this->column_data['date_created']));
	
			return $column_data;
		}
	
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created']    = date("Y-m-d H:i:s", strtotime($data['date_created']));
		}
		
		public function GetOpenProcID($info_id){
			$db = $this->getDatabaseInstance(self::DB_INST_READ);
	
			//application_contact
			$sql = "SELECT card_process_id FROM card_process".
				" WHERE card_info_id = ".$info_id.
				" AND process_status = 'sent'".
				" ORDER BY date_modified DESC LIMIT 1";
			$stmt = $db->query($sql);
			$ret = $stmt->fetch(PDO::FETCH_OBJ);
			return $ret->card_process_id;

		}
	}
?>
