<?php
class VendorAPI_Scrubber_Master
{
	const MAX_PROCESS_PER_COMPANY = 3;

	protected $queue = array();
	protected $workers = array();

	public function __construct($mode, array $companies)
	{
		foreach ($this->filterCompanies($companies) as $enterprise=>$companies)
		{
			foreach ($companies as $company)
			{
				$queue = new VendorAPI_Scrubber_JobQueue(
					$enterprise,
					$company,
					$mode,
					$this->findJournals($company, $mode)
				);
				$this->queue[$company] = $queue;
			}
		}
	}

	public function execute()
	{
		// seed the workers with jobs
		foreach ($this->queue as $company=>$queue)
		{
			$processes = self::MAX_PROCESS_PER_COMPANY;
			while ($processes--
				&& ($job = $queue->getNextJob()) !== FALSE)
			{
				$this->executeJob($job);
			}
		}

		// wait until all jobs have been finished
		// reaping a job launches the next in the queue,
		// so this loop processes the remaining queue
		while (count($this->workers))
		{
			$pid = pcntl_wait($status);
			$this->reapJob($pid);
		}
	}

	protected function executeNextJob($company)
	{
		$job = $this->queue[$company]->getNextJob();
		if ($job !== FALSE)
		{
			$this->executeJob($job);
			return TRUE;
		}
		return FALSE;
	}

	protected function executeJob(VendorAPI_Scrubber_Job $j)
	{
		$pid = pcntl_fork();
		if ($pid === 0)
		{
			$worker = new VendorAPI_Scrubber_Worker();
			$worker->execute($j);
			exit();
		}

		$this->workers[$pid] = $j;
	}

	protected function reapJob($pid)
	{
		$job = $this->workers[$pid];
		unset($this->workers[$pid]);

		$this->executeNextJob($job->company);
	}

	protected function filterCompanies(array $companies)
	{
		$filtered = array();
		foreach ($companies as $enterprise=>$c)
		{
			$filtered[$enterprise] = array_keys(array_filter($c));
		}
		return $filtered;
	}

	protected function findJournals($company, $mode)
	{
		$path = JOURNAL_PATH.DIRECTORY_SEPARATOR.strtoupper($mode).DIRECTORY_SEPARATOR.strtolower($company);
		return glob($path.DIRECTORY_SEPARATOR.'*.db');
	}
}
