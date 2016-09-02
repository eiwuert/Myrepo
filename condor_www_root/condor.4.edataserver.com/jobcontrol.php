<?php
/**
 * HylaFAX Job Controls. HylaFAX will pass us a job id
 * as an argument. We'll take that job id and load up
 * any configuration data that's job specific via 
 * the HylaFAX_JobControl. Then, we just echo that out
 */
define('MODE_LIVE', 'LIVE');
define('MODE_RC', 'RC');
define('MODE_DEV', 'LOCAL');
require_once(CONDOR_DIR.'/lib/hylafax_db.php');
require_once(CONDOR_DIR.'/lib/hylafax_job.php');
require_once(CONDOR_DIR.'/lib/hylafax_jobcontrol.php');

$mode = 'LIVE';

list($script, $jobid) = $argv;

$job_control = new HylaFax_JobControl($mode);
$job_control->loadFromQueueFile($jobid);

$data = $job_control->getInfo();

//Maps a HylaFAX config command to 
//a field returned from HylaFax_JobControl
$job_control_map = array(
	'LocalIdentifier' => 'from_string',
);

foreach($job_control_map as $config_command => $job_control_key)
{
	if(!empty($data[$job_control_key]))
	{
		echo($config_command.': "'.$data[$job_control_key]."\"\n");
	}
}