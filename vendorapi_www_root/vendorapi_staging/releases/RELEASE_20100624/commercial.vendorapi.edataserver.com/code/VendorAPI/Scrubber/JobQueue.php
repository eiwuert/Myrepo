<?php
class VendorAPI_Scrubber_JobQueue
{
	protected $enterprise, $company, $mode, $journals;

	public function __construct($enterprise, $company, $mode, array $journals)
	{
		$this->enterprise = $enterprise;
		$this->company = $company;
		$this->mode = $mode;
		$this->journals = $journals;
	}

	public function getNextJob()
	{
		if (!count($this->journals))
		{
			return FALSE;
		}

		$file = array_shift($this->journals);

		$job = new VendorAPI_Scrubber_Job();
		$job->enterprise = $this->enterprise;
		$job->company = $this->company;
		$job->mode = $this->mode;
		$job->journal = $file;
		return $job;
	}
}