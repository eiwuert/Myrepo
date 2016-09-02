<?php 

	/**
	 * The Agent Queue Reason table
	 *
	 * @package Models
	 * @author  Mike Lively <mike.lively@sellingsource.com>
	 */
	class ECash_Models_Reference_AgentQueueReason extends DB_Models_ReferenceModel_1 
	{
		/**
		 * The columns in the model
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'agent_queue_reason_id',
				'name', 'name_short', 'sort', 'handler_class'
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
			return array('agent_queue_reason_id');
		}
		/**
		 * The auto increment column
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'agent_queue_reason_id';
		}
		/**
		 * The name of the model table
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'n_agent_queue_reason';
		}

		/**
		 * Returns the column data.
		 *
		 * Overridden to automatically set the date_queued and date_available
		 *
		 * @return array
		 */
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
			return $column_data;
		}		

		/**
		 * Sets the column data.
		 *
		 * Overridden to automatically set the date_queued and date_available
		 *
		 * @param Array $column_data
		 * @return null
		 */
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}

		/**
		 * Returns the column that contains the table ID of each item
		 * @return string
		 */
		public function getColumnID()
		{
			return 'agent_queue_reason_id';
		}

		/**
		 * Returns the column that contains the name of each item
		 * @return string
		 */
		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>
