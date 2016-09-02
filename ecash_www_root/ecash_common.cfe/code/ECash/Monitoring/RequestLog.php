<?php


abstract class ECash_Monitoring_RequestLog extends Object_1
{

	//static public $last_error;
	public $db;
	public $company_id;
	public $data;
	
	/*
	 *  Create Reques_Lgo object
	 * 	$db = database
	 * 	$company_id = company_id
	 */
	public function __construct()
	{
		$this->db 			= ECash::getMasterDb();
		$this->company_id 	= ECash::getCompany()->company_id;
		$this->data 		= ECash::getFactory()->getData('Monitoring');
	}
	
	/*
	 *  getRequests - Get Request Log Requests
	 * 
	 * 	$start_boundary
	 * 	$end_boundary
	 * 	$agent_id
	 * 
	 * 	return $results
	 */
	protected function getRequests($start_boundary, $end_boundary, $agent_id = null)
	{
		$results = $this->data->getRequests($start_boundary, $end_boundary, $this->company_id, $agent_id);
		return $results;

	}
	
	/**
	 * setRequests - Inserts row into the request_log table matching cols
	 * $values = Reques Log values
	 *  
	 * return $request_log_ig
	 */
	protected function setRequests($values)
	{
		$model = ECash::getFactory()->getModel('RequestLog');
		foreach($model->getColumns() as $key)
		{
			if(isset($values[$key]))
			{
				$model->{$key} = $values[$key];
			}
		}
		$model->save();
		return $model->request_log_id;

	}	
}
?>
