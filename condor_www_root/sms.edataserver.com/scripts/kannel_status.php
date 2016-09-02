<?php
	
	// hosts to monitor with this script
	$hosts = array(
		
		'kannel2' => array(
			'host' => 'kannel2.edataserver.com',
			'port' => 13000,
			'pass' => 'snailmail',
		),
		
		'kannel' => array(
			'host' => 'kannel.edataserver.com',
			'port' => 13000,
			'pass' => 'snailmail',
		),
		
	);
	
	// database information
	$server = array(
		'host' => 'ds38.tss',
		'user' => 'root',
		'pass' => '',
		'db' => 'sms',
		'table' => 'kannel_snapshot2'
	);
	
	$serverDLH = array(
		'host' => 'ds57.tss:/tmp/mysql.sock',
		'user' => 'dlh',
		'pass' => 'dlh',
		'db' => 'sms',
		'table' => 'kannel_snapshot2'
	);
	
	// email addresses to send alerts to
	$send_to = array(
		'mobile@dothedrew.net',
		'andrew.minerd@sellingsource.com',
		'don.adriano@sellingsource.com',
		'donadriano@gmail.com',
		'david.hickman@sellingsource.com'
	);
	
	// minimum interval between persistant alerts
	define('ALERT_INTERVAL', '5 minutes');
	
	// number of failures/interval before we send a failure alert
	define('ALERT_THRESHOLD', 2);
	
	// reset the modem after this many failures
	define('RESET_THRESHOLD', 5);
	
	// minimum interval between persistant resets
	define('RESET_INTERVAL', '2 minutes');
	
	require_once('mysql.4.php');
	
	// we'll use this to store alert information
	session_id('KANNEL-STATUS');
	session_start();
	
	// store the current time
	$now = time();
	$alert_interval = strtotime('-'.ALERT_INTERVAL, $now);
	$reset_interval = strtotime('-'.RESET_INTERVAL, $now);
	
	// connect to our database
	$sql = new MySQL_4($server['host'], $server['user'], $server['pass']);
	$sql->Connect();
	
	// an array of modems that need to be reset
	$reset = array();
	
	foreach ($hosts as $host_id=>$host)
	{
		
		if (is_array($host) && isset($host['host']) && isset($host['port']))
		{
			
			// get our status
			$xml = Get_Kannel_Status($host['host'], $host['port'], (isset($host['pass']) ? $host['pass'] : NULL));
			
			if ($xml !== FALSE)
			{
				
				if (Get_Flag($host_id, 'ALL', 'error') !== NULL)
				{
					// let 'em know it's back up
					Send_Alert($host['host'], 'RESOLVED: ONLINE', '');
					Set_Flag($host_id, 'ALL', 'error', NULL);
				}
				
				// get a list of modems
				$modems = $xml->xpath('//smscs/smsc');
				
				foreach ($modems as $modem)
				{
					
					// pull out modem data
					$modem_id = (string)$modem->id;
					$status = strtolower((string)$modem->status);
					$received = (int)$modem->received;
					$sent = (int)$modem->sent;
					$failed = (int)$modem->failed;
					$queued = (int)$modem->queued;
					
					// let us know what's going on
					echo('['.date('Y-m-d H:i:s')."] Checking {$host_id}.{$modem_id}: (received: {$received}, sent: {$sent}, failed: {$failed}, queued: {$queued})...\n");
					
					// build a short synopsis of the modem's status
					$synopsis = array();
					$synopsis[] = $modem_id;
					$synopsis[] = str_repeat('=', strlen($modem_id));
					$synopsis[] = '';
					
					$synopsis = implode("\n", $synopsis);
					
					// save these
					$total_sent = $sent;
					$total_received = $received;
					$total_failed = $failed;
					
					// get our uptime
					$online = preg_match('/^online (\d+)s$/', $status, $matches);
					$uptime = ($online ? (int)$matches[1] : 0);
					
					// pull the last record for this modem
					$query = "SELECT UNIX_TIMESTAMP(date_created) AS date_created, uptime, total_received,
						total_sent, total_failed FROM `".$server['table']."` WHERE host='{$host_id}' AND
						modem_id='{$modem_id}' AND date_created < '".date('Y-m-d H:i:s', $now)."'
						ORDER BY date_created DESC LIMIT 1";
					$result = $sql->Query($server['db'], $query);
					
					// we don't want cumulative data
					if ($rec = $sql->Fetch_Array_Row($result))
					{
						
						// attempt to determine if we've had a reset, etc. -- we can't just compare the
						// uptime values (which would be sooo easy!), since in a Kannel-instigated automatic
						// reset, the sent/recieved/failed counts don't get reset (but uptime does)
						if (($received > $rec['total_received']) || ($sent > $rec['total_sent']) || ($failed > $rec['total_failed']) ||
							(($received == $rec['total_received']) && ($sent == $rec['total_sent']) && ($failed == $rec['total_failed'])))
						{
							$received -= $rec['total_received'];
							$sent -= $rec['total_sent'];
							$failed -= $rec['total_failed'];
						}
						
					}
					
					if (!$online)
					{
						
						$last_alert = Get_Flag($host_id, $modem_id, 'offline');
						
						// see if this is a new alert, or if enough time has
						// passed to send a second alert again
						if (($last_alert === NULL) || ($last_alert < $alert_interval))
						{
							Send_Alert($modem_id, 'OFFLINE!', $synopsis);
							Set_Flag($host_id, $modem_id, 'offline', $now);
						}
						
						if ($status !== 'connecting')
						{
							// schedule us for a reset
							Schedule_Reset($host_id, $modem_id);
						}
						
					}
					else
					{
						
						if (Get_Flag($host_id, $modem_id, 'reset_at') !== NULL)
						{
							// we were reset successfully, clear some junk
							Send_Alert($modem_id, 'MODEM RESET SUCCESSFULLY', $synopsis);
							Set_Flag($host_id, $modem_id, 'reset_at', NULL);
						}
						
						if (Get_Flag($host_id, $modem_id, 'offline') !== NULL)
						{
							// let them know that the alert has been resolved
							Send_Alert($modem_id, 'RESOLVED: MODEM IS ONLINE', $synopsis);
							Set_Flag($host_id, $modem_id, 'offline', NULL);
						}
						
					}
					
					if ($failed > ALERT_THRESHOLD)
					{
						
						$last_alert = Get_Flag($host_id, $modem_id, 'failed');
						
						// see if this is a new alert, or if enough time has
						// passed to send a second alert again
						if (($last_alert === NULL) || ($last_alert < $alert_interval))
						{
							Send_Alert($modem_id, 'FAILURE!', $synopsis);
							Set_Flag($host_id, $modem_id, 'failed', $now);
						}
						
						// schedule us for a reset
						Schedule_Reset($host_id, $modem_id);
						
					}
					
					if ($total_failed > ALERT_THRESHOLD)
					{
						// schedule us for a reset
						Schedule_Reset($host_id, $modem_id);
					}
					
					// get the time of our last reset
					$reset_time = Get_Flag($host_id, $modem_id, 'reset_at');
					
					// are we scheduled for a reset? make sure that we either don't have a
					// processing reset, or that our processing reset is taking too long
					if (((Get_Flag($host_id, $modem_id, 'reset') !== NULL) && ($reset_time === NULL)) || (($reset_time !== NULL) && ($reset_time < $reset_interval)))
					{
						
						// if the modem is still online, then give
						// it a chance to try and clear its queue
						if (($queued <= 0) || (!$online))
						{
							// schedule for an immediate reset
							$reset[] = array($host_id, $modem_id);
						}
						
					}
					
					// save space: only insert a record if something has changed
					if (($received > 0) || ($sent > 0) || ($failed > 0) || ($queued > 0) || ($rec['uptime'] > $uptime))
					{
						
						// insert this "snapshot"
						$query = "INSERT INTO `".$server['table']."` (host, modem_id, date_created, uptime, received, sent, failed, queued, total_sent,
							total_received, total_failed) VALUES ('{$host_id}', '{$modem_id}', '".date('Y-m-d H:i:s', $now)."', '{$uptime}',
							'{$received}', '{$sent}', '{$failed}', '{$queued}', '{$total_sent}', '{$total_received}', '{$total_failed}')";
						$sql->Query($server['db'], $query);
						
					}
					
				}
				
			}
			else
			{
				
				$last_alert = Get_Flag($host_id, 'ALL', 'error');
				
				// see if we don't have an error set, or if our last alert
				// was more than ALERT_INTERVAL ago
				if (($last_alert === NULL) || ($last_alert < $alert_interval))
				{
					Send_Alert($host['host'], 'UNREACHABLE', '');
					Set_Flag($host_id, 'ALL', 'error', $now);
				}
				
			}
			
		}
		
	}
	
	// do we have modems that we need to reset?
	if (count($reset))
	{
		
		// for some reason, kannel's reset-smsc command didn't
		// seem to work: the modem would stop and never come
		// back up... so we start/stop it manually
		foreach ($reset as $info)
		{
			
			if (is_array($info) && (list($host_id, $modem_id) = $info) && isset($hosts[$host_id]))
			{
				
				// get host info
				$host = $hosts[$host_id];
				
				// stop the modem
				$result = Set_Modem_Status($host['host'], $host['port'], $host['pass'], $modem_id, 'STOP');
				
			}
			
		}
		
		// again, for some reason, kannel can't stop/start
		// modems right away -- so give it some time
		sleep(1);
		
		foreach ($reset as $info)
		{
			
			if (is_array($info) && (list($host_id, $modem_id) = $info) && isset($hosts[$host_id]))
			{
				
				// get host info
				$host = $hosts[$host_id];
				
				// start the modem
				$result = Set_Modem_Status($host['host'], $host['port'], $host['pass'], $modem_id, 'START');
				Send_Alert($modem_id, 'RESET', $result);
				
				// clear our scheduled reset and save when this modem was reset
				Set_Flag($host_id, $modem_id, 'reset', NULL);
				Set_Flag($host_id, $modem_id, 'reset_at', $now);
				
			}
			
		}
		
	}
	
	function Set_Flag($host, $modem, $flag, $value)
	{
		
		if ($value !== NULL)
		{
			
			if (!isset($_SESSION[$flag])) $_SESSION[$flag] = array();
			if (!isset($_SESSION[$flag][$host])) $_SESSION[$flag][$host] = array();
			
			// set the flag
			$_SESSION[$flag][$host][$modem] = $value;
			
			// let us know what's going on
			if (is_bool($value)) $value = ($value ? '[TRUE]' : '[FALSE]');
			if ($value === NULL) $value = '[NULL]';
			echo('['.date('Y-m-d H:i:s')."] SET FLAG '{$flag}' FOR {$host}.{$modem} TO '{$value}'\n");
			
		}
		else
		{
			unset($_SESSION[$flag][$host][$modem]);
			echo('['.date('Y-m-d H:i:s')."] CLEARED FLAG '{$flag}' FOR {$host}.{$modem}\n");
		}
		
		return;
		
	}
	
	function Get_Flag($host, $modem, $flag)
	{
		
		$value = isset($_SESSION[$flag][$host][$modem]) ? $_SESSION[$flag][$host][$modem] : NULL;
		return $value;
		
	}
	
	function Schedule_Reset($host, $modem)
	{
		
		// schedule for a reset
		Set_Flag($host, $modem, 'reset', TRUE);
		return;
		
	}
	
	function Send_Alert($modem_id, $message, $data)
	{
		
		global $send_to;
		
		// build our subject
		if ($modem_id !== NULL)
		{
			$subject = '['.$modem_id.']: '.$message;
		}
		else
		{
			$subject = $message;
		}
		
		if (is_array($send_to))
		{
			
			// send to all of our addresses
			foreach ($send_to as $address)
			{
				mail($address, $subject, $data);
			}
			
		}
		
		echo("*** [".date('Y-m-d H:i:s')."] ALERT: [{$modem_id}]: {$message} ***\n");
		
		return;
		
	}
	
	function Get_Kannel_Status($server, $port, $password = NULL)
	{
		
		// build our url
		$url = 'http://'.$server.':'.$port.'/status.xml'.(($password !== NULL) ? '?password='.$password : '');
		
		// setup our curl object
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		// send the request
		$response = @curl_exec($curl);
		$response = ($response ? @simplexml_load_string($response) : FALSE);
		
		return $response;
		
	}
	
	function Set_Modem_Status($server, $port, $password, $modem_id, $status = 'START')
	{
		
		// start the modem
		$url = 'http://'.$server.':'.$port.'/'.strtolower($status).'-smsc?password='.$password.'&smsc='.$modem_id;
		
		// call the url to start the modem
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		
		// execute the call and clean up
		$result = curl_exec($curl);
		curl_close($curl);
		
		return $result;
		
	}
	
?>
