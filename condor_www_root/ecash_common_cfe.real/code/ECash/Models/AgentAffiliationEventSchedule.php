<?php 
	class ECash_Models_AgentAffiliationEventSchedule extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'agent_affiliation_id', 'event_schedule_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('agent_affiliation_id', 'event_schedule_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'agent_affiliation_event_schedule';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			
			return $column_data;
		}		
		public function setColumnData($data)
		{
			$this->column_data = $data;
		}
		
		/**
		 * Deletes all rows that match the given event schedule ids.
		 *
		 * @param mixed $event_schedule_ids a single ID or an array of ids.
		 */
		public function deleteByEventIds($event_schedule_ids)
		{
			$event_schedule_ids = (array)$event_schedule_ids;
			
			if (!count($event_schedule_ids))
			{
				throw new RuntimeException("No IDs passed.");
			}
			
			$placeholders = substr(str_repeat('?,', count($event_schedule_ids)), 0, -1);
			$query = "
				DELETE FROM agent_affiliation_event_schedule
				WHERE
					event_schedule_id IN ({$placeholders})
			";
			
			DB_Util_1::execPrepared(
				$this->getDatabaseInstance(),
				$query,
				$event_schedule_ids
			);
		}
	}
