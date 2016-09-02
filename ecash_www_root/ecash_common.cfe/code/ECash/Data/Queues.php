<?php

	class ECash_Data_Queues extends ECash_Data_DataRetriever
	{
		public function getHistory($application_id)
		{
			$query = "
				SELECT
					DATE_FORMAT(qh.date_queued, '%m/%d/%Y %r') AS date_inserted,
					DATE_FORMAT(qh.date_removed, '%m/%d/%Y %r') AS date_removed,
					qh.original_agent_id AS create_agent_id,
					qh.removal_agent_id AS remove_agent_id,
					n_queue.name queue_name,
					a.name_first AS create_agent_name_first,
					a.name_last AS create_agent_name_last,
					b.name_first AS remove_agent_name_first,
					b.name_last AS remove_agent_name_last
				FROM n_queue_history qh
				JOIN n_queue ON (n_queue.queue_id = qh.queue_id)
				LEFT JOIN agent a ON qh.original_agent_id = a.agent_id
				LEFT JOIN agent b ON qh.removal_agent_id = b.agent_id
				WHERE
					qh.related_id = ?
				ORDER BY
					qh.date_removed";

			$st = DB_Util_1::queryPrepared($this->db, $query, array($application_id));

			$history_array = array();
			while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$c = new StdClass;
				$d = new StdClass;

				$c->action = "in";
				$d->action = "out";

				$c->queue = $row->queue_name;
				$d->queue = $row->queue_name;
				$c->application_id = $application_id;
				$d->application_id = $application_id;
				$c->date_created = $row->date_inserted;
				$d->date_created = $row->date_removed;

				$c->agent_id = $row->create_agent_id;
				$c->name_first = $row->create_agent_name_first;
				$c->name_last = $row->create_agent_name_last;

				$d->agent_id = $row->remove_agent_id;
				$d->name_first = $row->remove_agent_name_first;
				$d->name_last = $row->remove_agent_name_last;

				$history_array[] = $c;
				$history_array[] = $d;
			}
			foreach(ECash::getFactory()->getQueueManager()->findInAllQueues(new ECash_Queues_BasicQueueItem($application_id)) as $name_short => $item)
			{
				if($item->date_available < time() && (!$item->date_expire || $item->date_expire > time()))
				{
					$record = new stdClass();
					$record->action = "in";
					$record->queue = ECash::getFactory()->getQueueManager()->getQueue($name_short)->getModel()->name;
					$record->applciation_id = $application_id;
					$record->date_created = date('m/d/Y h:i:s A', $item->date_available); 
					$agent = ECash::getFactory()->getModel('Agent');
					$agent->loadBy(array('agent_id' => $item->agent_id));
					$record->agent_id = $agent->agent_id;
					$record->name_first = $agent->name_first;
					$record->name_last = $agent->name_last;
					$history_array[] = $record;
				}
			}
			return $history_array;
		}
	}

?>
