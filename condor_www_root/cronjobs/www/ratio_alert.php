#!/usr/lib/php5/bin/php
<?php
/**
 * Cron job that alerts us when datax pass/fail ratio gets above some ratio for any site.
 * 
 * Command Line Parameters:
 * 	-d Do the datax check
 * 	-r Do the ratio check
 * 
 * NOTE:
 * 	Currently this only runs the 
 * 
 * @author Brian Feaver
 */

require_once('mysql.4.php');
require_once('mysqli.1.php');
require_once('prpc/client.php');
require_once('reported_exception.1.php');

global $argv;

define('OLE_PROPERTY_ID', 17176);

define('RATIO_FILENAME', '/tmp/%s_ratios.gz');

define('TYPE_DATAX_IDV', 'DataX ID Verification');
define('TYPE_DATAX_PERF', 'DataX Performance');
define('TYPE_AGREE_CONFIRM', 'Agree:Confirm Ratio');
define('TYPE_PREQUAL_SUBMIT', 'Prequal:Submit Ratio');
define('TYPE_DATAX_ERROR', 'TYPE_DATAX_ERROR');

define('AGREE_CONFIRM_DAY_RATIO', 40);		// Default is 40
define('AGREE_CONFIRM_NIGHT_RATIO', 5);
define('PREQUAL_SUBMIT_DAY_RATIO', 75);		// Default is 75
define('PREQUAL_SUBMIT_NIGHT_RATIO', 65);
define('DATAX_ERROR_DAY_RATIO', 95);
define('DATAX_ERROR_NIGHT_RATIO', 95);
define('DATAX_TOTAL', 50);					// Total events needed to check the DataX ratio
define('DATAX_IDV_RATIO', 30);				// Default is 30
define('DATAX_PERF_RATIO', 30);				// Default is 30

// Header spacing
define('HEADER_TYPE', 25);
define('HEADER_SITE', 30);
define('HEADER_CURRENT', 10);
define('HEADER_TRIGGER', 9);
define('HEADER_PASS', 7);
define('HEADER_FAIL', 7);
define('HEADER_RATIO_1', 10);
define('HEADER_RATIO_2', 10);
define('HEADER_TOTAL', 7);
define('HEADER_DATE', 20);

$server = array(
	'db' => 'rc_olp',
	'host' => 'db101.clkonline.com',
	'user' => 'sellingsource',
	'password' => 'password'
);

// Setup the MySQL connection
try
{
//	$sql = new MySQL_4($server['host'], $server['user'], $server['password']);
//	$sql->Connect();
}
catch(Exception $e)
{
	echo $e->getMessage();
	die();
}

$event_log_table = 'event_log_' . date('Ym');

/**
 * -d : Run the DataX trigger
 * -r : Run the ratio trigger
 */
switch($argv[1])
{
	case '-d':
		//die("Not running DataX checks.");
		
		// Run DataX check		
		$alarms = array();
		
		$ratio_info_list = array(
			TYPE_DATAX_IDV => array(
				'event' => 'DATAX_IDV',
				'ratio' => DATAX_IDV_RATIO
			),
			TYPE_DATAX_PERF => array(
				'event' => 'DATAX_PERF',
				'ratio' => DATAX_PERF_RATIO
			)
		);
		
		foreach($ratio_info_list as $type => $ratio_info)
		{
			Fetch_Datax_Alarms($sql, $alarms, $type, $ratio_info);
		}
		
		// If there are any alarms, send an email alert
		if(!empty($alarms))
		{
			$email_alarms = "The trigger alerts below have gone below their pass percentage.\r\n";
			$email_alarms .= "This means the percentage of passes to the total number of attempts\r\n";
			$email_alarms .= "is lower than our trigger point for the specified site.\r\n";
			$email_alarms .= "\r\n";
			
			$email_alarms .= str_pad('Type', HEADER_TYPE, ' ', STR_PAD_BOTH);
			$email_alarms .= str_pad('Site', HEADER_SITE, ' ', STR_PAD_BOTH);
			$email_alarms .= str_pad('Current', HEADER_CURRENT);
			$email_alarms .= str_pad('Trigger', HEADER_TRIGGER);
			$email_alarms .= str_pad('Pass', HEADER_PASS);
			$email_alarms .= str_pad('Fail', HEADER_FAIL);
			$email_alarms .= str_pad('Total', HEADER_TOTAL);
			$email_alarms .= str_pad('Date', HEADER_DATE, ' ', STR_PAD_BOTH);
			$email_alarms .= "\r\n";
			
			$email_alarms .= Create_Header_Underline(HEADER_TYPE);
			$email_alarms .= Create_Header_Underline(HEADER_SITE);
			$email_alarms .= Create_Header_Underline(HEADER_CURRENT);
			$email_alarms .= Create_Header_Underline(HEADER_TRIGGER);
			$email_alarms .= Create_Header_Underline(HEADER_PASS);
			$email_alarms .= Create_Header_Underline(HEADER_FAIL);
			$email_alarms .= Create_Header_Underline(HEADER_TOTAL);
			$email_alarms .= Create_Header_Underline(HEADER_DATE);
			$email_alarms .= "\r\n";
			
			$subject_type = '';
			$used_types = array();
			
			foreach($alarms as $alarm)
			{
				switch($alarm['type'])
				{
					case TYPE_DATAX_PERF:
						if(!in_array(TYPE_DATAX_PERF, $used_types)) {
							if(!empty($subject_type)) {
								$subject_type .= ' & ' . TYPE_DATAX_PERF;
								$used_types[] = TYPE_DATAX_PERF;
							}
							else {
								$subject_type = TYPE_DATAX_PERF;
								$used_types[] = TYPE_DATAX_PERF;
							}
						}
						break;
					case TYPE_DATAX_IDV:
						if(!in_array(TYPE_DATAX_IDV, $used_types)) {
							if(!empty($subject_type)) {
								$subject_type .= ' & ' . TYPE_DATAX_IDV;
								$used_types[] = TYPE_DATAX_IDV;
							}
							else {
								$subject_type = TYPE_DATAX_IDV;
								$used_types[] = TYPE_DATAX_IDV;
							}
						}
						break;
				}
				
				$email_alarms .= str_pad($alarm['type'], HEADER_TYPE);
				$email_alarms .= str_pad($alarm['site'], HEADER_SITE);
				$email_alarms .= str_pad(round($alarm['percent'], 2).'%', HEADER_CURRENT);
				$email_alarms .= str_pad($alarm['ratio'].'%', HEADER_TRIGGER);
				$email_alarms .= str_pad($alarm['pass'], HEADER_PASS);
				$email_alarms .= str_pad($alarm['fail'], HEADER_FAIL);
				$email_alarms .= str_pad($alarm['total'], HEADER_TOTAL);
				$email_alarms .= $alarm['date'];
				$email_alarms .= "\r\n";
			}
			
			$subject = "DataX Pass Alerts for $subject_type";
			
			Mail_Alarms($email_alarms, $subject);
		}
		break;
		
	case '-r':
		// Run the ratio trigger, this will be run every 5 minutes
		
		$current_time = getdate();
		$daytime = ($current_time['hours'] > 3 && $current_time['hours'] < 19);
		
		$alarms = array();
		$ratio_info_list = array(
			TYPE_AGREE_CONFIRM => array(
				'ratio' => $daytime ? AGREE_CONFIRM_DAY_RATIO : AGREE_CONFIRM_NIGHT_RATIO,
				'stat_2' => 'STAT_ACCEPTED', // 71 db100
				'stat_2_id' => 71,
				'stat_1' => 'STAT_CONFIRMED', // 93 db100
				'stat_1_id' => 93,
				'type' => 'AGR_CON'
			),
			TYPE_PREQUAL_SUBMIT => array(
				'ratio' =>  $daytime ? PREQUAL_SUBMIT_DAY_RATIO : PREQUAL_SUBMIT_NIGHT_RATIO,
				'stat_1' => 'STAT_BASE', // 64 db100
				'stat_1_id' => 64,
				'stat_2' => 'STAT_INCOME', // 62 db100
				'stat_2_id' => 62,
				'type' => 'PRE_SUB'
			)
		);
		
		foreach($ratio_info_list as $type => $ratio_info)
		{
			if($type == TYPE_AGREE_CONFIRM)
			{
				Fetch_Confirm_Agree($alarms, $type, $ratio_info);
			}
			else
			{
				// Currently turned off (event_log table problems)
//				Fetch_Ratio_Alarms($sql, $alarms, $type, $ratio_info);
			}
		}
		
		// Include the DataX Error
		// Currently turned off (event_log table problems)
//		Fetch_Datax_Error_Alarms($sql, $alarms);
		
		// If there are alarms, email users
		if(!empty($alarms))
		{
			$email_alarms = "The trigger alerts below have gone below their acceptable ratio percentage.\r\n";
			$email_alarms .= "This means the percentage generated by dividing the second statistic by the first statistic,\r\n";
			$email_alarms .= "is lower than is acceptable.\r\n";
			$email_alarms .= "\r\n";
			
			$subject_type = '';
				
			foreach($alarms as $alarm)
			{
				$email_alarms .= str_pad('Type', HEADER_TYPE, ' ', STR_PAD_BOTH);
				$email_alarms .= str_pad('Current', HEADER_CURRENT);
				$email_alarms .= str_pad('Trigger', HEADER_TRIGGER);
				switch($alarm['type'])
				{
					case TYPE_AGREE_CONFIRM:
						$email_alarms .= str_pad('Agree', HEADER_RATIO_1);
						$email_alarms .= str_pad('Confirm', HEADER_RATIO_2);
						if(!empty($subject_type)) {
							$subject_type .= ' & '.TYPE_AGREE_CONFIRM;
						}
						else {
							$subject_type = TYPE_AGREE_CONFIRM;
						}
						break;
					case TYPE_PREQUAL_SUBMIT:
						$email_alarms .= str_pad('PreQual', HEADER_RATIO_1);
						$email_alarms .= str_pad('Submit', HEADER_RATIO_2);
						if(!empty($subject_type)) {
							$subject_type .= ' & '.TYPE_PREQUAL_SUBMIT;
						}
						else {
							$subject_type = TYPE_PREQUAL_SUBMIT;
						}
						break;
				}
				$email_alarms .= str_pad('Date', HEADER_DATE, ' ', STR_PAD_BOTH);
				$email_alarms .= "\r\n";

				$email_alarms .= Create_Header_Underline(HEADER_TYPE);
				$email_alarms .= Create_Header_Underline(HEADER_CURRENT);
				$email_alarms .= Create_Header_Underline(HEADER_TRIGGER);
				$email_alarms .= Create_Header_Underline(HEADER_RATIO_1);
				$email_alarms .= Create_Header_Underline(HEADER_RATIO_2);
				$email_alarms .= Create_Header_Underline(HEADER_DATE);
				$email_alarms .= "\r\n";
			
				$email_alarms .= str_pad($alarm['type'], HEADER_TYPE);
				$email_alarms .= str_pad(round($alarm['percent'], 2).'%', HEADER_CURRENT);
				$email_alarms .= str_pad($alarm['ratio'].'%', HEADER_TRIGGER);
				$email_alarms .= str_pad($alarm['stat_1'], HEADER_RATIO_1);
				$email_alarms .= str_pad($alarm['stat_2'], HEADER_RATIO_2);
				$email_alarms .= str_pad($alarm['date'], HEADER_DATE);
				$email_alarms .= "\r\n";
				$email_alarms .= "\r\n";
			}
			
			$subject = "Ratio Alerts for $subject_type";
			
			Mail_Alarms($email_alarms, $subject);
			
			Send_SMS_Alerts($alarms);
		}
		
		break;
}

/**
 * Peforms the query and fills alarms with any triggers we find for DataX.
 *
 * @param object $sql
 * @param array $alarms
 * @param string $type
 * @param array $ratio_info
 */
function Fetch_Datax_Alarms(&$sql, &$alarms, $type, $ratio_info)
{
	global $server;
	global $event_log_table;
	
	$query = "
		/* File: ".__FILE__.", Line: ".__LINE__." */
		SELECT
			IF(event_log.response_id = 1, 'stat_1', 'stat_2') AS stat,
			COUNT(DISTINCT event_log.application_id) AS count,
			campaign_info.url AS site
		FROM
			events
			JOIN $event_log_table AS event_log ON events.event_id = event_log.event_id
			JOIN campaign_info ON event_log.application_id = campaign_info.application_id
		WHERE
			event_log.date_created BETWEEN DATE_SUB(NOW(), INTERVAL 12 HOUR) AND NOW()
			AND events.event = '{$ratio_info['event']}' AND event_log.response_id IN (1, 2)
			AND campaign_info.url != ''
		GROUP BY site, response_id";
	
	try
	{
		$result = $sql->Query($server['db'], $query);
		
		while(($row = $sql->Fetch_Array_Row($result)))
		{
			$stat_list[strtolower($row['site'])][$row['stat']] = $row['count'];
		}
	}
	catch(Exception $e)
	{
		echo $e->getMessage();
		die();
	}
	
	// Store any possible alarms for the DataX IDV check
	foreach($stat_list as $site => $stats)
	{
		$stat_1_value = $stats['stat_1'] ? $stats['stat_1'] : 0;
		$stat_2_value = $stats['stat_2'] ? $stats['stat_2'] : 0;
		$total = $stat_1_value + $stat_2_value;
		
		if($total >= DATAX_TOTAL)
		{
			$percent = $stat_1_value / $total * 100;
			
			if($percent < $ratio_info['ratio'])
			{
				$alarms[] = array(
					'site' => $site,
					'percent' => $percent,
					'type' => $type,
					'ratio' => $ratio_info['ratio'],
					'pass' => $stat_1_value,
					'fail' => $stat_2_value,
					'total' => $total,
					'date' => date('m/d/Y h:i:s'));
			}
		}
	}
}

/**
 * Adds alarms if the Confirm:Agree ratio is below its level.
 *
 * @param array $alarms Existing alarms.
 * @param string $type The ratio type.
 * @param array $ratio_info The ratio information.
 */
function Fetch_Confirm_Agree(&$alarms, $type, $ratio_info)
{
	$ldb = new MySQLi_1('writer.ecashclk.ept.tss', 'olp', 'dicr9dJA', 'ldb');
	
	$query = "
		/* File: ".__FILE__.", Line: ".__LINE__." */
		SELECT
			SUM(asf.name = 'confirmed') AS confirms,
			SUM(asf.name = 'agree') AS agrees
		FROM
			status_history sh
			JOIN application_status asf ON sh.application_status_id = asf.application_status_id
		WHERE
			sh.date_created BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND NOW()
			AND asf.name IN ('agree','confirmed')";
	
	$result = $ldb->Query($query);
	$ratios = $result->Fetch_Object_Row();
	$ldb->Close();
	
	$percent = ($ratios->confirms > 0) ? ($ratios->agrees / $ratios->confirms * 100) : 0;
	
	if($percent < $ratio_info['ratio'])
	{
		$alarms[] = array(
			'percent' => $percent,
			'type' => $type,
			'ratio' => $ratio_info['ratio'],
			'stat_1' => $ratios->confirms,
			'stat_2' => $ratios->agrees,
			'date' => date('m/d/Y h:i:s')
		);
	}
}

/**
 * Determines if given ratios are low enough to trigger an alarm.
 *
 * @param object $sql
 * @param array $alarms
 * @param string $type
 * @param array $ratio_info
 */
function Fetch_Ratio_Alarms(&$sql, &$alarms, $type, $ratio_info)
{
	global $server;
	global $event_log_table;
	
	// Retrieve the previoius ratios from file
	$filename = sprintf(RATIO_FILENAME, $ratio_info['type']);
	$ratio_list = Load_Previous_Ratios($filename);
	
	if(!is_array($ratio_list))
	{
		// This is probably our first time running. We don't want to do more
		// than store the values.
		$ratio_list = array();
	}
	
	$query = "
		/* File: ".__FILE__.", Line: ".__LINE__." */
		SELECT
			COUNT(DISTINCT event_log.application_id) AS stat_count
		FROM
			$event_log_table AS event_log
			JOIN campaign_info ON event_log.application_id = campaign_info.application_id
		WHERE
			event_log.date_created BETWEEN SUBDATE(NOW(), INTERVAL 5 MINUTE) AND NOW()
			AND event_log.event_id = {$ratio_info['stat_1_id']}
			AND event_log.response_id = 1
			AND campaign_info.url != ''";
	
	try
	{
		$result = $sql->Query($server['db'], $query);
		
		if(($row = $sql->Fetch_Array_Row($result)))
		{
			$stat_1 = $row['stat_count'];
		}
	}
	catch(Exception $e)
	{
		die($e->getMessage());
	}
	
	$query = "
		/* File: ".__FILE__.", Line: ".__LINE__." */
		SELECT
			COUNT(DISTINCT event_log.application_id) AS stat_count
		FROM
			$event_log_table AS event_log
			JOIN campaign_info ON event_log.application_id = campaign_info.application_id
		WHERE
			event_log.date_created BETWEEN SUBDATE(NOW(), INTERVAL 5 MINUTE) AND NOW()
			AND event_log.event_id = {$ratio_info['stat_2_id']}
			AND event_log.response_id = 1
			AND campaign_info.url != ''";
	
	try
	{
		$result = $sql->Query($server['db'], $query);
		
		if(($row = $sql->Fetch_Array_Row($result)))
		{
			$stat_2 = $row['stat_count'];
		}
	}
	catch(Exception $e)
	{
		die($e->getMessage());
	}
	
	// Add the new ratios to the end of the list
	$ratio_list['stat_1'][] = $stat_1;
	$ratio_list['stat_2'][] = $stat_2;
	
	// We only want to keep 60 minutes worth of information.
	if(count($ratio_list['stat_1']) > 12 && count($ratio_list['stat_2']) > 12)
	{
		array_shift($ratio_list['stat_1']);
		array_shift($ratio_list['stat_2']);
	}
	
	if(!Save_Ratio_List($filename, $ratio_list))
	{
		die("Was unable to save the ratio list (".RATIO_FILENAME."). Check file permissions.");
	}
	
	$stat_1_value = array_sum($ratio_list['stat_1']) ? array_sum($ratio_list['stat_1']) : 0;
	$stat_2_value = array_sum($ratio_list['stat_2']) ? array_sum($ratio_list['stat_2']) : 0;
	
	$percent = ($stat_1_value > 0) ? ($stat_2_value / $stat_1_value * 100) : 0;
	
	if(count($ratio_list['stat_1']) == 12 && $percent < $ratio_info['ratio'])
	{
		$alarms[] = array(
			'percent' => $percent,
			'type' => $type,
			'ratio' => $ratio_info['ratio'],
			'stat_1' => $stat_1_value,
			'stat_2' => $stat_2_value,
			'date' => date('m/d/Y h:i:s')
		);
	}
}

/**
 * Detemines if the DataX error ratio is high enough to trigger an alarm.
 *
 * @param object $sql
 * @param array $alarms
 */
function Fetch_Datax_Error_Alarms(&$sql, &$alarms)
{
	global $server;
	global $event_log_table;
		
	$query = "
		/* File: ".__FILE__.", Line: ".__LINE__." */
		SELECT
			IF(event_log.response_id = 1, 'pass', IF(event_log.response_id = 2, 'fail', 'error')) AS stat,
			COUNT(DISTINCT event_log.application_id) AS count
		FROM
			$event_log_table AS event_log
		WHERE
			event_log.date_created BETWEEN DATE_SUB(NOW(), INTERVAL 12 HOUR) AND NOW()
			AND event_log.event_id IN (28, 29) AND event_log.response_id IN (1, 2, 9)
		GROUP BY response_id";
	
	try
	{
		$result = $sql->Query($server['db'], $query);
		
		while(($row = $sql->Fetch_Array_Row($result)))
		{
			$stats[$row['stat']] = $row['stat_count'];
		}
	}
	catch(Exception $e)
	{
		echo $e->getMessage();
	}
	
	$pass = $stats['pass'] ? $stats['pass'] : 0;
	$fail = $stats['fail'] ? $stats['fail'] : 0;
	$error = $stats['error'] ? $stats['error'] : 0;
	$total = $pass + $fail + $error;
	
	$percent = 0;
	if($total > 0)
	{
		$percent = $error / $total * 100;
	}
	
	if($percent > DATAX_ERROR_DAY_RATIO)
	{
		$alarms[] = array('percent' => $percent, 'type' => 'DataX Error');
	}
}

/**
 * Emails to the RATIO_ALERT template in OLE.
 *
 * @param string $email_alarms
 */
function Mail_Alarms($email_alarms, $subject)
{
	$mail = new Prpc_Client('prpc://smtp.2.soapdataserver.com/ole_smtp.1.php');
	
	$recipients = array(
		array(	'email_primary_name' => 'Brian Feaver',
				'email_primary' => 'brian.feaver@sellingsource.com'),
		array(	'email_primary_name' => 'Mike Genatempo',
				'email_primary' => 'mike.genatempo@sellingsource.com'),
		array(	'email_primary_name' => 'Andy Roberts',
				'email_primary' => 'andy.roberts@sellingsource.com'),
		array(	'email_primary_name' => 'phoneofdeath@sellingsource.com',
				'email_primary' => 'phoneofdeath@sellingsource.com')
	);
	
	$email_alarms .= "\r\nEmails were sent to:\r\n";
	foreach($recipients as $recipient)
	{
		$email_alarms .= '* '.$recipient['email_primary_name']."\r\n";
	}
	
	$data = array(
		'site_name' => 'sellingsource.com',
		'subject' => $subject,
		'alarms' => $email_alarms
	);
	
//	echo $email_alarms;
	
	foreach($recipients as $recipient)
	{
		$send_data = array_merge($recipient, $data);

		$mail->Ole_Send_Mail('RATIO_ALERT', OLE_PROPERTY_ID, $send_data);
	}
}

function Send_SMS_Alerts($alarms)
{
	Reported_Exception::Add_Recipient('SMS', '6613191881'); // BrianF
	Reported_Exception::Add_Recipient('SMS', '6032641231'); // AndrewM
	Reported_Exception::Add_Recipient('SMS', '7023278177'); // MikeG

	$message = "TSS: Ratio Alert\n";
	foreach($alarms as $alarm)
	{
		$message .= $alarm['type'] . " is low.\n";
	}
	$reporter = Reported_Exception::Send($message);
}

/**
 * Returns the event ID for the given event.
 *
 * @param object $sql
 * @param string $stat_1
 * @return int The event ID of the event given.
 */
function Get_Event_ID(&$sql, $stat_1)
{
	global $server;
	$event_id = FALSE;
	
	$query = "
		/* File: ".__FILE__.", Line: ".__LINE__." */
		SELECT
			event_id
		FROM
			events
		WHERE
			event = '$stat_1'";
	
	try
	{
		$result = $sql->Query($server['db'], $query);
		
		if(($row = $sql->Fetch_Array_Row($result)))
		{
			$event_id = intval($row['event_id']);
		}
	}
	catch(Exception $e)
	{
		echo $e->getMessage();
		die();
	}
	
	return $event_id;
}

/**
 * Loads previous ratios from the temporary file.
 *
 * @param string $filename
 * @return array The array of ratios from the past 5 minutes
 */
function Load_Previous_Ratios($filename)
{
	$ratios = @file_get_contents($filename);
	
	if($ratios !== FALSE)
	{
		$ratios = unserialize(gzuncompress($ratios));
	}
	
	return $ratios;
}

/**
 * Saves the ratio list to the temporary file.
 *
 * @param string $filename
 * @param array $ratios
 * @return boolean
 */
function Save_Ratio_List($filename, $ratios)
{
	$ret_val = TRUE;
	
	$compressed_ratios = gzcompress(serialize($ratios));
	
	if(!file_put_contents($filename, $compressed_ratios))
	{
		$ret_val = FALSE;
	}
	
	return $ret_val;
}

/**
 * Generates a header based on the size.
 *
 * @param int $size
 * @return string
 */
function Create_Header_Underline($size)
{
	return str_pad(str_pad('', $size - 1, '='), $size);
}

?>
