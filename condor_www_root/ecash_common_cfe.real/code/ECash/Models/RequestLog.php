<?php

require_once 'WritableModel.php';

/**
 * @package Ecash.Models
 */

class ECash_Models_RequestLog extends ECash_Models_WritableModel
{
	public $RequestLog;
	public function getColumns()
	{
		static $columns = array(
			'request_log_id', 'agent_id', 'module', 'action',
			'levels', 'start_time', 'stop_time' , 'elapsed_time',
			'memory_usage', 'user_time', 'system_time' 
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('request_log_id');
	}
	public function getAutoIncrement()
	{
		return 'request_log_id';
	}
	public function getTableName()
	{
		return 'request_log';
	}
}
?>