<?php
require_once('hylafax_job.php');
require_once('hylafax_db.php');
class HylaFax_JobControl
{
	protected $db;
	public $fax_job;
	protected $company_data;
	
	public function __construct($mode)
	{
		$this->db = HylaFax_DB::Get_DB($mode);
	}
	
	public function loadFromQueueFile($job_id)
	{
		$file = HylaFax_Job::Find($job_id);
		if($file !== false)
		{
			$this->fax_job = new HylaFax_Job($file);
		}
	}
	public function setFaxJob(HylaFax_Job $job)
	{
		$this->fax_job = $job;
	}
	public function setCompanyData($company_data)
	{
		$this->company_data = $company_data;
	}
	
	public function setJobId($job_id)
	{
		$this->job_id = $job_id;
	}
	
	
	public function registerInfo($job_id, $owner, $company_data = NULL)
	{
		if(is_object($company_data))
		{
			$this->setCompanyData($company_data);
		}
		$from_string = $this->company_data->name;
		$company_id = $this->company_data->company_id;
		
		//Default the from_string to be the owner
		if(empty($from_string))
		{
			if(preg_match('/^(.+)[\s]\[(\d+)\]*$/', $owner, $matches))
			{
				$from_string = $matches[1];
				$company_id = $matches[2];
			}
		}
				
		if(is_numeric($job_id) && is_numeric($company_id) && is_string($from_string))
		{
			$query = "INSERT INTO 
				job_control (
					job_id,
					from_string,
					company_id
				) VALUES (
					$job_id,
					'$from_string',
					$company_id
				)
			";
			$result = $this->db->queryExec($query);
			if($result !== false)
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
		else 
		{
			throw new Exception("Invalid job data. Could not register.");
		}
	}
	
	public function getInfo()
	{
		$job_id = $this->fax_job->jobid;
		$return = false;
		if(is_numeric($job_id))
		{
			$query = "SELECT * FROM job_control WHERE job_id=$job_id";
			$result = $this->db->arrayQuery($query);
			$return = reset($result);
		}
		return $return;
	}
	
	public function deleteInfo()
	{
		$job_id = $this->fax_job->jobid;
		if(is_numeric($job_id))
		{
			$query = "DELETE FROM job_control WHERE job_id=$job_id";
			$result = $this->db->queryExec($query);
		}
	}
}