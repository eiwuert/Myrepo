<?php
/**
 * Abstract stuff for AJAX requests
 *
 */

abstract class Ajax_Request
{
	protected $collected_data;
	protected $olp_db;
	protected $config;
	protected $applog;
	
	
	public function __construct($collected_data, $olp_db, $config, $applog)
	{
		$this->collected_data = $collected_data;
		$this->olp_db = $olp_db;
		$this->config = $config;
		$this->applog = $applog;
	}
	
	abstract function Generate_Response();
}