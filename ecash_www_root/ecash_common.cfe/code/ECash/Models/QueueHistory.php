<?php
require_once 'WritableModel.php';

/**
 * @package Ecash.Models
 */

class ECash_Models_QueueHistory extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
				'queue_history_id', 'date_queued', 'date_removed', 'queue_entry_id', 'queue_id',
				'related_id', 'original_agent_id', 'removal_agent_id', 'dequeue_count', 'removal_reason',
				);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('queue_history_id');
	}
	public function getAutoIncrement()
	{
		return 'queue_history_id';
	}
	public function getTableName()
	{
		return 'n_queue_history';
	}
}
?>
