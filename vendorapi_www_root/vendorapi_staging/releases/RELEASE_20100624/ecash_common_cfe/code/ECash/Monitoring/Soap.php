<?php

class ECash_Monitoring_Soap extends ECash_Monitoring_RequestLog
{
	public $soap_log_id;

	public function insertRequest($application_id, $agent_id, $soap_data, $type = "soap_call")
	{
		return $this->data->setSoapLog($this->company_id, $application_id, $agent_id, $soap_data, $type);
	}
	
	public function setSent($soap_response = null,$soap_log_id = null)
	{
		$this->soap_log_id = is_null($soap_log_id) ? $this->soap_log_id : $soap_log_id;
		$this->data->setSoapLogResponse($soap_response,"sent");
	}
	
	public function setFailed($soap_response = null,$soap_log_id = null)
	{
		$this->soap_log_id = is_null($soap_log_id) ? $this->soap_log_id : $soap_log_id;
		$this->data->setSoapLogResponse($soap_response,"failed");
	}
	
	public function setSuccess($soap_response = null,$soap_log_id = null)
	{
		$this->soap_log_id = is_null($soap_log_id) ? $this->soap_log_id : $soap_log_id;
		$this->data->setSoapLogResponse($soap_response,"success");
	}

	

}
?>