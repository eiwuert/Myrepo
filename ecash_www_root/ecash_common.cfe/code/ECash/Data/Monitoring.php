<?php


class ECash_Data_Monitoring extends ECash_Data_DataRetriever
{
	public function getRequests($start_boundary, $end_boundary, $company_id, $agent_id = null)
	{
		$agent_id = (isset($agent_id) && !is_array($agent_id)) ? array($agent_id) : $agent_id; 
		if(!is_null($agent_id))
		{
			$agent_id = array_pad($agent_id, count($agent_id), '?');
			$agent_id = "AND  agent_id IN ".implode(', ', $agent_id);			
		}
		
		$query = "
			SELECT 
				*
			FROM 
				request_log 
			WHERE
				company_id = ?
				$agent_id
				AND	start_time BETWEEN ? AND ?
		";
		return DB_Util_1::queryPrepared($this->db, $query, array($company_id,$start_boundary,$end_boundary))->fetchAll(PDO::FETCH_OBJ);		

	}
	
	public function setSoapLog($company_id, $application_id, $agent_id, $soap_data, $type)
	{

		$query = 'INSERT INTO soap_log
				  (
					date_created,
					company_id,
					application_id,
					agent_id,	
					soap_data,
					type,			  
					status
				  )
				  VALUES
				  (
					now(),
					?,
					?,
					?,
					compress(?),
					?,
					"created"					
				  )
				  ';

		return DB_Util_1::execPrepared($this->db, $query, array($company_id,$application_id,$agent_id,$soap_data,$type));
		
	}
	
	private function setSoapLogResponse($soap_response = null,$action)
	{
		$soap_response = is_null($soap_response) ? "" : "soap_response = compress(\"".$this->db->quote($soap_response)."\"),";
		
		$query = '
		 UPDATE 
			soap_log
		 SET
			'.$soap_response.'
			status = ?
		 WHERE
			soap_log_id = ?';	
		return DB_Util_1::execPrepared($this->db, $query, array($action,$this->soap_log_id))->fetchAll(PDO::FETCH_OBJ);
	}		
}
?>