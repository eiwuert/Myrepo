<?php
	class ECash_Models_QueueDisplay extends ECash_Models_WritableModel
	{
		
		public function deleteByQueueId($queue_id, array $override_dbs = NULL)
		{
			$query = "delete        
			FROM    
				n_queue_display
			WHERE
				queue_id = {$queue_id}";
			
			return $this->getDatabaseInstance()->exec($query);                      
		}
		
		public function getColumns()
		{
			static $columns = array(
				'queue_display_id', 'queue_id', 'section_id'
			);
			return $columns;
		}
		
		public function getPrimaryKey()
		{
			return array('queue_display_id');
		}
		
		public function getAutoIncrement()
		{
			return 'queue_display_id';
		}
		
		public function getTableName()
		{
			return 'n_queue_display';
		}
	}
?>
