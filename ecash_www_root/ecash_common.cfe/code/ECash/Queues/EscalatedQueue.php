<?php
	/**
	 * Represents a queue which contains only entries from another queue,
	 * that have been escalated to their 'escalate_queue_id'.  An escalated
	 * queue entry can be continually escalated following the trail of escalated_queue_id's
	 * while maintaining the originating queue's rules, via a queue_id stored with the queue_entry
	 *
	 * @author John Hargrove
	 * @todo Basically everything
	 */
	class ECash_Queues_EscalatedQueue extends ECash_Queues_BasicQueue
	{
		protected function queueEntryFactory()
		{
			return new ECash_Models_EscalatedQueueEntry($this->db);
		}
		protected function queueItemFactory(ECash_Models_WritableModel $model)
		{
			return ECash_Queues_EscalatedQueueItem::fromModel($model);
		}
		public function getQueueEntryTableName()
		{
			return 'n_escalated_queue_entry';
		}
		public function getNewQueueItem($related_id, $queue_item)
		{
			return new ECash_Queues_EscalatedQueueItem($related_id, $queue_item);
		}
	}
?>