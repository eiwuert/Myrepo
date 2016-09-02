<?php
/**
 * Quick cron job that basically just create graph 
 * caches for all the cool stuff in condor_admin.
 * The crontab stuff I used
 
 0,15,30,45 * * * * (cd /virtualhosts/condor_admin/cronjobs;php transport_graphs.php live)
 01 0 * * * (cd /virtualhosts/condor_admin/cronjobs.php; php transport_graphs.php live yesterday clean)
 
 *
 */
define('CACHE_DIR','/data/graph_cache/');

require_once('mysqli.1.php');
require_once('../lib/transport_graph.php');

//$mode = array_shift($argv);
if(in_array(strtoupper($argv[1]),array('LIVE','RC','LOCAL')))
{
	define('MODE',strtoupper($argv[1]));
}
else 
{
	die("Invalid mode ({$argv[1]})\n");
}
if(!is_dir(CACHE_DIR))
{
	if(!mkdir(CACHE_DIR))
	{
		die("Could not create cache dir ".CACHE_DIR."\n");
	}
}

switch (strtoupper(MODE))
{
	case "RC": // The rc server, for cron/command line testing use
		define ("EXECUTION_MODE", 'RC');
		define ("DB_HOST", 'db101.clkonline.com');
		define ("DB_NAME", 'condor_admin');
		define ("CONDOR_DB_NAME", 'condor');
		define ("DB_USER", 'condor');
		define ("DB_PASS", 'andean');
		define ("DB_PORT", '3313');
	break;

	case "LOCAL": // The ds locations
		define ("EXECUTION_MODE", 'LOCAL');
		define('DB_HOST','localhost');
		define('DB_NAME','condor_admin');
		define('CONDOR_DB_NAME','condor');
		define('DB_USER','root');
		define('DB_PASS','');
		define('DB_PORT',3306);
	break;

	case "LIVE":
	default: // It must be live
		define("DB_HOST",'writer.condor2.ept.tss');
		define ("DB_NAME", 'condor_admin');
		define ("CONDOR_DB_NAME", 'condor');
		define ("DB_USER", 'condor');
		define ("DB_PASS", 'flyaway');
		define ("DB_PORT", 3308);


	break;
}

//Remove all the graphs older than 30 days
//that we've saved
function Clean_Old_Graphs()
{
	$files = glob(CACHE_DIR.'*.png');
	clearstatcache();
	$rem_files = array();
	foreach($files as $val)
	{
   		$thirty_days_ago = time() - 2592000;	
     	if(filemtime($val) <= $thirty_days_ago)
       	{
       		unlink($val);
       	}
    }
}

//Basically just a curl the image with a few getOpts that creates/saves the url
function Build_Graphs($start_date,$company_id)
{
		
	$plots = array(
		'Sent Emails' => array(
			array(
				'EMAIL',
				'SENT',
				'seagreen',
				'Sent Emails',
			),
		),
		'Failed Emails' => array(
			array(
				'EMAIL',
				'FAIL',
				'red',
				'Failed Emails',
			),
		),
		'Sent Faxes' => array(
			array(
				'FAX',
				'SENT',
				'seagreen',
				'Sent Faxes',
			),
		),
		'Failed Faxes' => array(
			array(
				'FAX',
				'FAIL',
				'red',
				'Failed Faxes',
			)
		)
	);
	$start_timestamp = strtotime($start_date);
	$start_date = date('Y-m-d',$start_timestamp);
	$end_date = date('Y-m-d',$start_timestamp + 86400);
	foreach($plots as $key => $plot)
	{
		$this_plot_array = array();
		foreach($plot as $p2)
		{
			$this_plot_array[] = join(',',$p2);
		}

		$file_name = CACHE_DIR.md5(serialize($this_plot_array)."_{$start_date}_{$end_date}_{$company_id}_".strtolower(MODE)).".png";
		$graph = new Transport_Graph(MODE);
		$graph->setTitle($key);
		$graph->setCompanyId($company_id);
		$graph->setStartDate($start_date);
		$graph->setEndDate($end_date);
		foreach($plot as $myBar)
		{
			call_user_func_array(array($graph,'Add_Plot'),$myBar);
		}
		$graph->Graph($file_name);
	}

}

//loop through and build all graphs for 
//all companies yesterday
function Build_Everyones_Stuff($build_yesterday)
{
	$db = new MySQLi_1(DB_HOST,DB_USER,DB_PASS,DB_NAME,DB_PORT);
	$query = '
		SELECT 
			company_id
		FROM
			company
	';
	$res = $db->Query($query);
	$yesterday = date('Y-m-d',strtotime('Yesterday'));
	$now = date('Y-m-d');
	while($row = $res->Fetch_Object_Row())
	{
		if($build_yesterday === TRUE)
		{
			Build_Graphs($yesterday,$row->company_id);
		}
		Build_Graphs($now,$row->company_id);
	}
}

$build_yesterday = FALSE;
$clean_old = FALSE;
foreach($argv as $arg)
{
	if(strcasecmp($arg,'yesterday') == 0)
	{
		$build_yesterday = TRUE;
	}
	elseif(strcasecmp($arg,'clean') == 0)
	{
		$clean_old = TRUE;
	}
}

//Clean the old ones if 
//we're supposed to
if($clean_old === TRUE)
{
	Clean_Old_Graphs();
}
//Build the current graphs, and possibly yesterdays
//depending on command line arguments
Build_Everyones_Stuff($build_yesterday);
