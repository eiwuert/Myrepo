<?php

require_once("/virtualhosts/lib/timer.1.php");

// This script uses innodb hot backup to do a full backup of all databases listed (assumes per db table space setting is on).
// http://www.innodb.com

// CONFIGURATION - DO NOT GET THE CONF FILES BACKWARD, IT MAY CAUSE A CATASTROPHIC DELETE!
$databases = array("ldb","condor","sync_cashline_ca","sync_cashline_d1","sync_cashline_pcl","sync_cashline_ucl","sync_cashline_ufc","mysql");
$mysql_conf_location = "/etc/mysql/my.cnf";  // MySQL conf
$backup_conf_location = "/etc/mysql/my.backup.cnf";  // Backup conf (see www.innodb.com for more information)
$ibbackup_location = "/bin/ibbackupA03300";  // Location of the innodb hot backup script
$mysqlhotcopy_location = "/usr/bin/mysqlhotcopy";
$tarball_save_location = "/data/backup-staging";
$backup_log_file = "/var/log/mysql/mysql.backup";
$mysql_user = "mysql";
$mysql_group = "mysql";

// Host to transfer backup to
$backup_scp_host = "71.4.57.133";
$backup_scp_user = "backup";
$backup_scp_location = "/home/backup";

$live_mode = FALSE;

// This could take a very long time, do not time out.
set_time_limit(0);

file_put_contents($backup_log_file,"Creating backup for " . date("Y-m-d") . "\n", FILE_APPEND);

$timer = new Code_Timer();

if( !file_exists($mysql_conf_location) )
{
	echo "Mysql conf file not found at location {$mysql_conf_location} \n";
	echo "Script stopped.\n";
	exit;
}

if( !file_exists($backup_conf_location) )
{
	echo "Mysql backup conf file not found at location {$backup_conf_location} \n";
	echo "Script stopped.\n";
	exit;
}

$backup_data_dir = Read_MySQL_Conf_Option($backup_conf_location, "innodb_data_home_dir");
$backup_log_dir = Read_MySQL_Conf_Option($backup_conf_location, "innodb_log_group_home_dir");
$live_data_dir = Read_MySQL_Conf_Option($mysql_conf_location, "innodb_data_home_dir");

// Did we find a backup data dir?
if( !isset($backup_data_dir) || $backup_data_dir == FALSE )
{
	echo "Could not find a innodb_data_home_dir setting in the backup conf.  Script stopped.\n";
	exit;
}

// Did we find a backup data dir?
if( !isset($live_data_dir) || $live_data_dir == FALSE )
{
	echo "Could not find a innodb_data_home_dir setting in the live conf.  Script stopped.\n";
	exit;
}

// Did we find a backup log dir?
if( !isset($backup_log_dir) || $backup_log_dir == FALSE )
{
	echo "Could not find a innodb_log_group_home_dir setting in the backup conf.  Script stopped.\n";
	exit;
}

// Cheesy safety check - having backup in the name of the backup dir is a requirement
if( strpos( strtolower($backup_data_dir), "backup") === FALSE || strpos( strtolower($backup_log_dir), "backup") === FALSE  )
{
	echo "The backup directory does not contain the word 'backup' for the log and datadirs.\n";
	echo "Safety check failed.  Script stopped. \n";
	exit;
}

if( $live_mode )
{
	// Clear out old backup data
	exec("rm -rf {$backup_data_dir}/*");
	exec("rm -rf {$backup_log_dir}/*");
}
else
{
	echo "rm -rf {$backup_data_dir}/*\n";
	echo "rm -rf {$backup_log_dir}/*\n";
}

$db_list = "";

// Build the regex for the databases to include in the backup
for($i = 0; $i < count($databases); $i++)
{
	$db = $databases[$i];
	
	$db_list .= $i > 0 ? "|{$db}": "{$db}";	
}

$regex = "({$db_list})\..*";


if( $live_mode )
{
	// Do the backup
	$ibbackup_res = shell_exec("{$ibbackup_location} --include \"{$regex}\" {$mysql_conf_location} {$backup_conf_location}\n");
	
	// Apply the log file
	shell_exec("{$ibbackup_location} --apply-log {$backup_conf_location}\n");
}
else
{
	echo "{$ibbackup_location} --include \"{$regex}\" {$mysql_conf_location} {$backup_conf_location}\n";
}

// elapsed time spent for innodb hot backup
$timer->Stop_Timer();
$elapsed_ibbackup_time = $timer->Get_Time();

file_put_contents($backup_log_file,"Innodb hot backup finished. Elapsed time for Innodb hot backup {$elapsed_ibbackup_time} seconds.\n", FILE_APPEND);

$timer->Start_Timer();

foreach($databases as $database)
{
	if( $live_mode )
	{
		// Run MySQL hot copy for MyISAM tables
		shell_exec("{$mysqlhotcopy_location} --addtodest {$database} {$backup_data_dir}");
		
		// Copy FRM files over, slightly redundant for MyISAM tables but these files are small
		shell_exec("cp -p {$live_data_dir}/{$database}/*.frm {$backup_data_dir}/{$database}");
		
		// Fix permissions
		shell_exec("chmod 660 {$backup_data_dir}/{$database}/*");
	}
	else
	{
		echo "{$mysqlhotcopy_location} --addtodest {$database} {$backup_data_dir}\n";
		echo "cp -p {$live_data_dir}/{$database}/*.frm {$backup_data_dir}/{$database}\n";
		echo "chmod 660 {$backup_data_dir}/{$database}/*\n";
	}
}

$timer->Stop_Timer();
$elapsed_mysqlhotcopy_time = $timer->Get_Time();

file_put_contents($backup_log_file,"MySQL Hot Copy finished. Elapsed time for MyISAM backup {$elapsed_mysqlhotcopy_time} seconds.\n", FILE_APPEND);

// Fix permissions so we can restore without having to chmod/chown
if( $live_mode )
{
	shell_exec("chmod 660 {$backup_log_dir}/*");
	shell_exec("chown {$mysql_user}:{$mysql_group} {$backup_log_dir}");
	shell_exec("chmod 660 {$backup_data_dir}/ibdata*");
	shell_exec("chown {$mysql_user}:{$mysql_group} {$backup_data_dir}/ibdata*");
}
else 
{
	echo "chmod 660 {$backup_log_dir}/*\n";
	echo "chown {$mysql_user}:{$mysql_group} {$backup_log_dir}\n";
	echo "chmod 660 {$backup_data_dir}/ibdata*\n";
	echo "chown {$mysql_user}:{$mysql_group} {$backup_data_dir}/ibdata*\n";
}

$timer->Start_Timer();

$backup_date_string = date("Y_m_d");

$backup_data_dir_pieces = explode("/",$backup_data_dir);
$backup_log_dir_pieces = explode("/",$backup_log_dir);

$mismatch = FALSE;

// Figure out if the log and data dirs are sub directories of the same directory
if( count($backup_log_dir_pieces) == count($backup_data_dir_pieces) )
{
	for($i = 0; $i < count($backup_data_dir_pieces)-1; $i++)
	{
		if( $backup_data_dir_pieces[$i] != $backup_log_dir_pieces[$i] )
		{
			$mismatch = TRUE;
			break;
		}
	}
}
else
{
	$mismatch = TRUE;	
}

$final_data_dir = array_pop($backup_data_dir_pieces);
$final_log_dir = array_pop($backup_log_dir_pieces);

$full_gzip_file_path = "{$tarball_save_location}/mysql_innodb_backup_{$backup_date_string}.tar.gz";

// If the logs and data dirs are in the same root dir lets tar it in a way that doesn't have the whole path
if( !$mismatch )
{
	$command ="cd {$backup_data_dir} && cd .. && tar -czf $full_gzip_file_path -p {$final_data_dir} -p {$final_log_dir}";
	
	if( $live_mode )
	{
		$gzip_res = shell_exec($command);
	}
	else
	{
		echo $command . "\n";		
	}
}
else 
{
	$command = "cd {$backup_data_dir} && cd .. && tar -czf $full_gzip_file_path {$backup_data_dir} {$backup_log_dir}";
	
	if( $live_mode )
	{
		$gzip_res = shell_exec($command);
	}
	else 
	{
		echo $command . "\n";
	}
}

// elapsed time for gzip
$timer->Stop_Timer();
$gzip_time = $timer->Get_Time();

file_put_contents($backup_log_file,"Elapsed time for taring and gziping file {$gzip_time} seconds. Gziped file stored at {$full_gzip_file_path} \n", FILE_APPEND);

$timer->Stop_Timer();
$timer->Start_Timer();

$scp_command = "scp {$full_gzip_file_path} {$backup_scp_user}@{$backup_scp_host}:{$backup_scp_location}";

if( $live_mode )
{
	$scp_res = shell_exec($scp_command);
}
else 
{
	echo $scp_command . "\n";
}

$timer->Stop_Timer();
$scp_time = $timer->Get_Time();

file_put_contents($backup_log_file,"File transferred to {$backup_scp_host} in {$scp_time} seconds.\n", FILE_APPEND);

$yesterdays_date = date("Y_m_d", strtotime("yesterday") );

$yesterdays_backup = "mysql_innodb_backup_{$yesterdays_date}.tar.gz";

if( $live_mode )
{

	// Delete yesterdays remote backup - (Should be archived to tape)
	shell_exec("ssh {$backup_scp_user}@{$backup_scp_host} rm {$yesterdays_backup}");
	//echo "ssh {$backup_scp_user}@{$backup_scp_host} rm {$yesterdays_backup}\n";
	
	// Delete yesterdays local backup
	shell_exec("rm {$tarball_save_location}/{$yesterdays_backup}");
}
else
{
	echo "ssh {$backup_scp_user}@{$backup_scp_host} rm {$yesterdays_backup}\n";
	echo "rm {$tarball_save_location}/{$yesterdays_backup}\n";	
}

file_put_contents($backup_log_file,"Deleted yesterdays backup {$yesterdays_backup}\n", FILE_APPEND);

file_put_contents($backup_log_file,"MySQL backup complete. File name is mysql_innodb_backup_{$backup_date_string}.tar.gz\n\n", FILE_APPEND);

// Grabs an option from a conf file
function Read_MySQL_Conf_Option($conf_location, $option)
{
	// Open the backup conf and parse the backup data dir
	$conf_lines = file($conf_location);
	
	// Parse thru the backup conf lines and search for datadir
	foreach($conf_lines as $line)
	{
		// Does this line contain the datadir setting?
		if( strpos($line, $option) !== FALSE )
		{
			// Grab everything right of the equals
			return trim( substr($line, strpos($line, "=")+1) );
		}		
	}
	return FALSE;
}

?>