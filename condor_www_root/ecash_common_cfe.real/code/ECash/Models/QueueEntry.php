<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_QueueEntry extends ECash_Models_WritableModel
	{
		public $Queue;
		public $Application;

		public function getColumnData()
		{
			$modified = $this->column_data;
			$modified['date_queued'] = date("Y-m-d H:i:s", $modified['date_queued']);
			$modified['date_available'] = date("Y-m-d H:i:s", $modified['date_available']);
			$modified['date_expire'] = date("Y-m-d H:i:s", $modified['date_expire']);
		
			return $modified;
		}
		
		public function setColumnData($column_data) 
		{
			$column_data['date_queued'] = strtotime($column_data['date_queued']);
			$column_data['date_available'] = strtotime($column_data['date_available']);
			$modified['date_expire'] = date("Y-m-d H:i:s", $modified['date_expire']);
			$this->column_data = $column_data;
		}
		public function getColumns()
		{
			static $columns = array('queue_entry_id', 'queue_id', 'related_id', 
									'agent_id', 'priority', 'date_queued', 'date_available', 
									'date_expire', 'dequeue_count');
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('queue_entry_id');
		}
		public function getAutoIncrement()
		{
			return 'queue_entry_id';
		}
		public function getTableName()
		{
			return 'n_queue_entry';
		}
	}
?>