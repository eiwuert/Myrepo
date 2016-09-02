<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EscalatedQueueEntry extends ECash_Models_WritableModel
	{
		public function getColumnData()
		{
			$modified = $this->column_data;
			$modified['date_queued'] = date("Y-m-d H:i:s", $modified['date_queued']);
			$modified['date_available'] = date("Y-m-d H:i:s", $modified['date_available']);
			return $modified;
		}
		public function setColumnData($column_data)
		{
			$column_data['date_queued'] = strtotime($column_data['date_queued']);
			$column_data['date_available'] = strtotime($column_data['date_available']);
			$this->column_data = $column_data;
		}
		public function getColumns()
		{
			static $columns = array(
				'queue_entry_id', 'queue_id', 'agent_id', 'source_queue_id', 'related_id',
				'priority', 'date_queued', 'date_available', 'date_expire', 'dequeue_count'
			);
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
			return 'n_escalated_queue_entry';
		}
	}
?>