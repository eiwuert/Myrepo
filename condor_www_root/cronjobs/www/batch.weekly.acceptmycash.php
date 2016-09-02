<?php

/** @file
 * Batch reports for specific application statuses for specific websites.
 *
 * This batch report is an automated tool that will query OLP to find all
 * applications that are in a certain status. It will then dump this data into
 * a CSV file and SCP it to a remote server.
 *
 * The tool works by reading it's own filename for specific data. This
 * data tells it how often it is ran. The specific format that the filename
 * needs to be in is:
 *   batch.(nightly|weekly).XXXX.php
 *
 * The file must start with "batch." The first dot separated section should be
 * how often it is ran, which will tell the script to look either over a one
 * day span, or over a full week.
 *
 * Defines:
 *  SCP_HOST            => The hostname of the server to SCP the file to.
 *  SCP_USER            => What username on the server do we log in under.
 *  SCP_DIR             => The full location on the server to upload file to.
 *  BATCH_MODE          => 
 *  APPLICATION_STATUS  => An comma separated list of statuses.
 *  SITE_URLS           => An comma separated list of site urls.
 *  OUTPUT_FILENAME     => What the output filename should look like.
 *
 * The OUTPUT_FILENAME can contain tokens to better describe it. The tokens
 * are:
 *  %%DATE_START%%      => The date that the range is starting over.
 *  %%DATE_END%%        => The date that the range finished over (ie: today).
 */

define('SCP_HOST', 'drop1.sellingsource.com');
define('SCP_USER', 'aroi');
define('SCP_DIR', '/home/aroi/livefeed');

define('BATCH_MODE', 'REPORT');
define('APPLICATION_STATUS', 'FAILED');
define('SITE_URLS', 'acceptmycash.com, cashloannetwork.com');
define('OUTPUT_FILENAME', 'acceptmycash_%%DATE_END%%.csv');


define('BFW_OLP_DIR', '/virtualhosts/bfw.1.edataserver.com/include/modules/olp/');
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

ini_set('include_path', '.' . ini_get('include_path') . ':/virtualhosts:');

require_once('mysql.4.php');
require_once('mysqli.1.php');
require_once(BFW_CODE_DIR . 'server.php');
require_once(BFW_CODE_DIR . 'setup_db.php');

/** SCPs a file
 *
 * @param $file_name The file to send.
 * @param $scp_host Hostname to send to.
 * @param $scp_user The user to log in as.
 * @param $directory What directory to send file to.
 */
function SCPFile($file_name, $scp_host, $scp_user, $directory)
{
	// Check if we have a host
	if (!$scp_host)
	{
		throw new Exception("No SCP host defined.");
	}
	
	// Check if the file exists
	if (file_exists($file_name) != 1)
	{
		throw new Exception("Can not scp '{$file_name}', as it does not exist.");
	}
	
	// SCP the file
	$result = @exec("scp $file_name {$scp_user}@{$scp_host}:{$directory}/.");
}

/** Displays the help menu
 */
function PrintHelpMenu()
{
	echo "Could not run report. Report must be named in the following format:\n";
	echo "  batch.[range].XXXX\n\n";
	echo "Example for a weekly report for CLK:\n";
	echo "  batch.weekly.clk.php\n\n";
	echo "Possible date ranges:\n";
	echo "  nightly, weekly\n";
	exit(1);
}

/** Escapes MySQL strings with quotes also.
 *
 * @param $str String to run mysql_real_escape_string over.
 * @return A MySQL safe string
 */
function QuoteMySQLRealEscapeString($str)
{
	return "'" . mysql_real_escape_string($str) . "'";
}

/** Grabs applications from database and stores to CSV file.
 *
 * @param $dbi The database interface.
 * @param $handle An open file handle to write to.
 * @param $site_url The URL to grab applications from.
 * @param $date_start The starting date.
 * @param $date_end The ending date.
 */
function DumpApplicationsToCSV($dbi, $handle, $site_urls, $application_statuses, $date_start, $date_end)
{
	$field_names = array(
		'First Name',
		'Last Name',
		'Email',
		'Address',
		'City',
		'State',
		'Zip',
		'Home Phone',
		'Date Created',
		'IP Address',
		'Source',
		'Promo ID',
	);
	
	$sites = array_map(QuoteMySQLRealEscapeString, $site_urls);
	$statuses = array_map(QuoteMySQLRealEscapeString, $application_statuses);
	
	$query = "
		SELECT
			p.first_name,
			p.last_name,
			p.email,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			p.home_phone,
			a.created_date,
			c.url,
			c.ip_address,
			c.promo_id
		FROM
			application AS a
		JOIN
			campaign_info AS c USING(application_id)
		JOIN
			personal_encrypted AS p USING(application_id)
		JOIN
			residence AS r USING(application_id)
		WHERE
			a.created_date BETWEEN " . QuoteMySQLRealEscapeString($date_start) . " AND " . QuoteMySQLRealEscapeString($date_end) . "
			AND a.application_type IN (" . implode(',', $statuses) . ")
			AND c.url IN (" . implode(',', $sites) . ")
			AND c.active = 'TRUE'
		ORDER BY
			a.created_date ASC";
	
	// Normally would wrap in exception handling, instead let PHP report it back
	$result = $dbi->Query($dbi->db_info['db'], $query);
	
	fputcsv($handle, $field_names);
	
	while ($row = $dbi->Fetch_Array_Row($result))
	{
		fputcsv($handle, $row);
	}
}

// Auto-attempt to determine information
if (preg_match('/batch.([^.]+)./i', $_SERVER['SCRIPT_NAME'], $matches))
{
	$range = strtolower($matches[1]);
}
else
{
	PrintHelpMenu();
}

// Grab mode from ARGV
$mode = ($_SERVER['argc'] > 1) ? $_SERVER['argv'][1] : BATCH_MODE;

// Setup date range
switch ($range)
{
	case 'nightly':
		$date_start = date('Y-m-d', strtotime('-1 day'));
		break;
	
	case 'weekly':
		$date_start = date('Y-m-d', strtotime('-1 week'));
		break;
	
	default:
		PrintHelpMenu();
		break;
}
$date_end = date('Y-m-d');

// Write CSV to temporary file
$file_name = str_replace(
	array('%%DATE_START%%', '%%DATE_END%%'),
	array($date_start, $date_end),
	(function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : '/tmp') . '/' . OUTPUT_FILENAME
);
$handle = fopen($file_name, 'w');
if (!$handle)
{
	throw new Exception("Could not open tmp file: {$file_name}");
}

// Grab application data and store in CSV file
$sites = array_map(trim, explode(',', SITE_URLS));
$statuses = array_map(trim, explode(',', APPLICATION_STATUS));
$dbi = Setup_DB::Get_Instance('BLACKBOX', $mode);
DumpApplicationsToCSV($dbi, $handle, $sites, $statuses, $date_start, $date_end);

fclose($handle);

// SCP file
SCPFile($file_name, SCP_HOST, SCP_USER, SCP_DIR);

// Delete our local copy
unlink($file_name);

?>
