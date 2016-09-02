<?php

	interface ECash_Queues_IQueue
	{
		public function __construct(ECash_Models_Queue $queue_model, DB_IConnection_1 $db);
		public function remove(ECash_Queues_BasicQueueItem $item);
		public function insert(ECash_Queues_BasicQueueItem $item);
		public function contains(ECash_Queues_BasicQueueItem $item);
		public function dequeue();
		public function count();
	}
?>