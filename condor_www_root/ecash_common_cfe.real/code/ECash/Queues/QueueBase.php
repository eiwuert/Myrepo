<?php

	/**
	 * base class for all ecash queue classes. Mainly provides
	 * common functionality and constants for enums in the database.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	abstract class ECash_Queues_QueueBase extends Object_1
	{
		/**
		 * removal type: removed from queue directly.
		 */
		const REMOVE_QUEUE = 'queue';

		/**
		 * removal type: removed from queue as part of a group removal.
		 */
		const REMOVE_GROUP = 'group';

		/**
		 * removal type: removed in a cleanup script or manual process.
		 * note: this should only be used for deletions that are correcting
		 * system errors. it is not considered part of the standard queue flow.
		 */
		const REMOVE_MANUAL = 'manual';
		
		const REMOVE_EXPIRED = 'expired';

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;
		
		/**
		 * Default constructor
		 */
		public function __construct(DB_IConnection_1 $db)
		{
			$this->db = $db;
		}
		
		/**
		 * Moves items for a specific related_id in a specific queue
		 *
		 * @param string $reason
		 */
		protected function archiveQueueEntries(array $args, $reason = self::REMOVE_QUEUE)
		{
			$queue_table = $this->getQueueEntryTableName();
			
			$query = "
				insert into n_queue_history
				select
					null,
					date_available,
					:curr_time,
					queue_entry_id,
					queue_id,
					related_id,
					agent_id,
					:removal_agent_id,
					dequeue_count,
					:reason
				from $queue_table" . DB_Database_1::buildWhereClause($args) . " and date_available < :curr_time";

			$args['reason'] = $reason;
			$args['curr_time'] = date("Y-m-d H:i:s");
			/**
			 * @todo: CUTTING CORNERS. Make this not retarded.
			 */
			$args['removal_agent_id'] = ECash::getAgent()->getAgentId();

			$this->db->queryPrepared($query, $args);
		}

		/**
		 * Deletes queue items
		 */
		protected function deleteQueueEntries(array $args)
		{
			$queue_table = $this->getQueueEntryTableName();
			$query = "delete from $queue_table" . DB_Database_1::buildWhereClause($args);
			$this->db->queryPrepared($query, $args);
		}

		/**
		 * override this method to specify what table is to be used in queue operations
		 * @return string
		 */
		public function getQueueEntryTableName()
		{
			return 'n_queue_entry';
		}

	}

?>
