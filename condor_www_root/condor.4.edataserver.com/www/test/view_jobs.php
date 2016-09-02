<?php
	
	include('../../lib/hylafax_job.php');
	
	class Hylafax_Jobs extends HylaFax_Job
	{
		
		public static function Fetch_All()
		{
			
			$jobs = array();
			
			foreach (self::$queues as $queue)
			{
				
				$files = glob($queue.'/q*');
				
				foreach ($files as $file)
				{
					
					$job_id = substr(basename($file), 1);
					
					try
					{
						$job = new HylaFax_Job($file);
						$jobs[$job_id] = $job;
					}
					catch (Exception $e)
					{
					}
					
				}
				
			}
			
			return $jobs;
			
		}
		
	}
	
	
	$jobs = HylaFax_Jobs::Fetch_All();
	
	echo '
		<table border="0" cellpadding="3" cellspacing="0">
		<tr>
			<th>Job ID</th>
			<th>Modem</th>
			<th>Recipient</th>
			<th>Pages</th>
			<th>Dials</th>
			<th>Tries</th>
		</tr>
	';
	
	foreach ($jobs as &$job)
	{
		
		echo '
			<tr>
				<td>'.$job->jobid.'</td>
				<td>'.$job->modem.'</td>
				<td>'.$job->external.'</td>
				<td>'.$job->totpages.'</td>
				<td>'.$job->totdials.'</td>
				<td>'.$job->tottries.'</td>
			</tr>
		';
		
	}
	
	echo '</table>';
	
?>