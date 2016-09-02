<?php 
set_include_path('.'.PATH_SEPARATOR . realpath(dirname(__FILE__).'/../lib/'). PATH_SEPARATOR . '/usr/share/php');

require_once 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath('../code/','../lib/blackbox/');

define('PID_FILE', '/var/run/apiscrubber.pid');
define('JOURNAL_PATH', '/var/state/vendor_api');
set_include_path(
    '.'
    . PATH_SEPARATOR . realpath(dirname(__FILE__).'/../lib/')
    . PATH_SEPARATOR . '/usr/share/php'
	. PATH_SEPARATOR . '/virtualhosts'
);

require_once 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath(
    '../code/',
    '../lib/blackbox/'
);
function removeLockFile($file)
{
	if (file_exists($file))
	{
		unlink($file);
	}
}

function findJournals()
{
	$files = glob('/var/state/vendor_api/*/*.db');
	$return = array();
	foreach ($files as $file)
	{
		$lock = str_replace('.db', '.lock', $file);
		if (!file_exists($lock))
		{
			touch($lock);
			// Basically if we lock it, we want to make
			// sure it's unlcoked
			register_shutdown_function('removeLockFile', $lock);
			$return[] = $file;
		}
	}
	return $return;
}
if ($argv[1] != 'imreallysure')
{
	die("You must be really sure before you can do this.\n");
}
if (posix_geteuid() != 0)
{
	die("You really must be root. And really sure.\n");
}
else
{
	echo "Okay now\n";
	$journals = findJournals();
	foreach ($journals as $journal)
	{
		echo("Processing $journal\n");
		$db = new DB_Database_1('sqlite:'.$journal);
		$stmt = $db->queryPrepared('SELECT * from state_object', array());
		while (($row = $stmt->fetch(PDO::FETCH_OBJ)))
		{
			echo("\tProcessing: ".$row->state_object_id."\n");
			$state = unserialize(gzuncompress($row->state_object));
			if (!is_numeric($state->application_id))
			{
				echo("\tDeleting: ".$row->state_object_id."\n");
				$db->queryPrepared('DELETE from state_object WHERE state_object_id = ?', array($row->state_object_id));
			}
		}
	}
}


