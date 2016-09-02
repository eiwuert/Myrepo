<?php

	/**
	 * The Agent Queue Entry table
	 *
	 * @package Models
	 * @author  Mike Lively <mike.lively@sellingsource.com>
	 */
	class ECash_Models_AgentQueueEntry extends ECash_Models_WritableModel
	{
		/**
		 * Returns the column data.
		 *
		 * Overridden to automatically set the date_queued and date_available
		 *
		 * @return array
		 */
		public function getColumnData()
		{
			$modified = $this->column_data;
			$modified['date_queued'] = date("Y-m-d H:i:s", $modified['date_queued']);
			$modified['date_available'] = date("Y-m-d H:i:s", $modified['date_available']);
			if(!empty($modified['date_expire']))
				$modified['date_expire'] = date("Y-m-d H:i:s", $modified['date_expire']);
			return $modified;
		}

		/**
		 * Sets the column data.
		 *
		 * Overridden to automatically set the date_queued and date_available
		 *
		 * @param Array $column_data
		 * @return null
		 */
		public function setColumnData($column_data)
		{
			$column_data['date_queued'] = strtotime($column_data['date_queued']);
			$column_data['date_available'] = strtotime($column_data['date_available']);
			if(!empty($column_data['date_expire']))
				$column_data['date_expire'] = strtotime($column_data['date_expire']);
			$this->column_data = $column_data;
		}

		/**
		 * The columns in the model
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'queue_entry_id', 'queue_id', 'agent_id', 'related_id',
				'date_queued', 'date_available', 'priority',
				'dequeue_count', 'start_hour', 'end_hour', 'owning_agent_id',
				'date_expire', 'agent_queue_reason_id'
			);
			return $columns;
		}

		/**
		 * The primary key columns
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('queue_entry_id');
		}

		/**
		 * The auto increment column
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'queue_entry_id';
		}

		/**
		 * The name of the model table
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'n_agent_queue_entry';
		}
		
		/**
		 * Reassigns all active affiliations of an agent to another agent. 
		 *
		 * @param int $from_agent_id
		 * @param int $to_agent_id
		 */
		public function reassign($from_agent_id, $to_agent_id)
		{
			$query = "
				UPDATE {$this->getTableName()}
				SET
					owning_agent_id = ?
				WHERE
					owning_agent_id = ?
			";
					
			return DB_Util_1::execPrepared(
				$this->getDatabaseInstance(),
				$query,
				array($to_agent_id, $from_agent_id)
			);
		}
	}
?>
