<?php
	
	/*
		
		PHP replacement for the HylaFax notification script,
		used to return fax results via callbacks registered
		when the job was submitted.
		
		This script is called by HylaFax with the following
		arguments:
		
		- queue-file
		- why
		- jobtime
		- [next]
		
		Author: Andrew Minerd
		Date: 2006-02-20
		
	*/

	define('MODE_LIVE', 'LIVE');
	define('MODE_RC', 'RC');
	define('MODE_DEV', 'LOCAL');
	require_once(DIR_CONDOR.'/lib/hylafax_job.php');
	require_once(DIR_CONDOR.'/lib/hylafax_callback.php');
	require_once(DIR_CONDOR.'/lib/hylafax_jobcontrol.php');
	
	$script = array_shift($argv);
	$arg_count = count($argv);
	
	// make sure we have the correct number of arguments
	if (($arg_count !== 3) && ($arg_count !== 4))
	{
		die("Usage: {$script} queue-file why jobtime [next]\n");
	}
	
	// pull out our arguments
	list($queue_file, $why, $job_time) = $argv;
	$next = ($arg_count === 4) ? $argv[3] : NULL;
	
	// basic information about what's going on
	$class = Notification_Class($why);
	$final = (($class === 'FAILED') || ($class === 'COMPLETE'));
	
	try
	{
		
		// load our queue file
		$job = new HylaFax_Job($queue_file);
		
		// if a callback was registered for this job,
		// this will contain id@callback
		$id = explode('@', $job->mailaddr);
		
		if ((count($id) == 2) && ($id[1] == 'callback'))
		{
			
			// find our callback information
			$callback = HylaFax_Callback::Find_By_ID($id[0]);
			
			if ($callback instanceof HylaFax_Callback)
			{
				
				// just do it
				$callback->Process($job, $why);
				
				// if this job has reached a "final" status (i.e., the job completed or
				// failed and will not be reattempted), delete the callback and any
				// job control information we have
				if ($final === TRUE)
				{
					$callback->Delete();
					$job_control = new HylaFax_JobControl('LIVE');
					$job_control->setFaxJob($job);
					$job_control->deleteInfo();
				}
				
			}
			
		}
		
	}
	catch (Exception $e)
	{
		die("Couldn't load queue file: ".$e->getMessage()."\n");
	}
	
	function Notification_Class($reason)
	{
		
		$class = FALSE;
		
		switch (strtolower($reason))
		{
			
			case 'timedout':
			case 'rejected':
			case 'format_failed':
			case 'no_formatter':
			case 'poll_rejected':
			case 'poll_no_document':
			case 'poll_failed':
			case 'failed':
			case 'removed':
			case 'killed':
				$class = 'FAILED';
				break;
				
			case 'done':
				$class = 'COMPLETE';
				break;
				
			case 'blocked':
			case 'requeued':
				$class = 'RETRY';
				break;
			
		}
		
		return $class;
		
	}
	
?>
