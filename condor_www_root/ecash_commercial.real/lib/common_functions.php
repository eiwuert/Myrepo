<?php
// Globally scoped variable
$debug_output = "";

function To_String($object)
{
	ob_start();
	if(!method_exists($object, '__toString'))
	{
		print_r($object);
	}
	else
	{
		echo $object;
	}
	return ob_get_clean();
}

// This may need moved
function Get_Readonly_Statuses()
{
	// We'll want custom read-only statuses only if READONLY_STATUSES is set
	// in the config file.
	$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

	if (!isset(eCash_Config::getInstance()->READONLY_STATUSES))
	{
		//Set the application as readonly for particular statuses
		$readonly_statuses[] = $asf->toId('pending::prospect::*root');
		$readonly_statuses[] = $asf->toId('confirmed::prospect::*root');
		$readonly_statuses[] = $asf->toId('confirm_declined::prospect::*root');
		$readonly_statuses[] = $asf->toId('declined::prospect::*root');
		$readonly_statuses[] = $asf->toId('disagree::prospect::*root');
		$readonly_statuses[] = $asf->toId('preact_confirmed::prospect::*root');
		$readonly_statuses[] = $asf->toId('preact_pending::prospect::*root');
	}
	else
	{
		if (!is_array(eCash_Config::getInstance()->READONLY_STATUSES) ||
				count(eCash_Config::getInstance()->READONLY_STATUSES) == 0)
		{
			throw new Exception('Read only statuses is set, but is not an array, or it is empty');
		}

		foreach (eCash_Config::getInstance()->READONLY_STATUSES as $readonly_status)
		{
			$readonly_statuses[] = $asf->toId($readonly_status);
		}
	}

	return $readonly_statuses;
}

// I'm sure this will want to be moved at some point [benb]
function Status_Chain_Needs_Resigned($status_chain)
{
	$resign_statuses = array(
			'agree::prospect::*root',      // Agree
			'soft_fax::pending::prospect::*root',          // Soft Fax
			'addl::verification::applicant::*root',        // Additional
			'dequeued::verification::applicant::*root',    // Confirmed
			'follow_up::verification::applicant::*root',   // Confirmed Follow Up
			'hotfile::verification::applicant::*root',     // Hotfile
			'queued::verification::applicant::*root',      // Confirmed
			'dequeued::underwriting::applicant::*root',    // Approved
			'follow_up::underwriting::applicant::*root',   // Approved Follow Up
			'pend_expire::underwriting::applicant::*root', // Pending Expiration
			'preact::underwriting::applicant::*root',      // Approved (Preact)
			'queued::underwriting::applicant::*root',      // Approved
			'approved::servicing::customer::*root'         // Pre-Fund
			);

	if (in_array($status_chain, $resign_statuses))
		return TRUE;

	return FALSE;
}

/**
 * Sets the application to it's previous status that is NOT a follow_up status.
 * If the app isn't currently in a follow up status then the status is not changed.
 *
 * @param int $application_id
 * @return unknown
 */
/* This function is a lie, it is not setting to previous status, previous status would
 * simply do a LIMIT 1 OFFSET 1, and also ordering by date_created in the status_history
 * is bad, as many status changes can happen in a single second. Using the status_history_id
 * is better, but I don't know who uses this POS, so I'm not going to fix it. [benb]
 */
function Return_To_Previous_Status($application_id) 
{
	require_once(SQL_LIB_DIR ."app_stat_id_from_chain.func.php");

	$query = "
			SELECT application_status_id
			  FROM status_history
			  WHERE application_id = $application_id AND
			  	application_status_id NOT IN (
			  		SELECT application_status_id
			  		FROM application_status
			  		WHERE name_short = 'follow_up'
			  		OR name_short = 'skip_trace'
			  		OR active_status = 'inactive'
			  		)
			  ORDER BY date_created DESC
			  LIMIT 1
	";
	$db = ECash_Config::getMasterDbConnection();
	$st = $db->query($query);

	if (!($row = $st->fetch(PDO::FETCH_OBJ)))
	{
		get_log()->Write("Unable to find a previous status for $application_id.");
		return false;

	}
	else
	{
		if (Update_Status(NULL, $application_id, $row->application_status_id, NULL, NULL, FALSE))
		{
			return $row->application_status_id;
		}
		else
		{
			return false;
		}
	}
}


/**
 * Updates the status of the application
 *
 * @param object $server Server object
 * @param integer $application_id ipp_id to update
 * @param array | string | int  $status contains the new status as array, string (status::status), or status_id
 * @param string[optional] $follow_up_time offset from now() for mysql dateadd() function
 * @param integer $agent_id The agent ID to associate with the account
 * @return boolean success or failure
 * @example Update_Status( $server, 500000, array( 'follow_up', 'fraud', 'applicant', '*root' ), '2 days' );
 */
function Update_Status($server, $application_id, $status, $follow_up_time = NULL, $agent_id = NULL, $queues=TRUE, $queue_name = NULL)
{
	$db = ECash_Config::getMasterDbConnection();

	/*
	 * @TODO: FIX TRANSACTION SAFETY
	 */
	if (FALSE)
	{
		get_log()->Write("IMPORTANT: Update_Status() called from within a transaction. This should be avoided at all costs. Committing the transaction.");

		$backtrace = debug_backtrace();
		foreach($backtrace as $bt)
		{
			get_log()->Write("Backtrace - Function: {$bt['function']} Line:{$bt['line']} File: {$bt['file']}");
		}
		$db->commit();
	}

	$success = doOnlyUpdateStatus($application_id, $status, $follow_up_time, $agent_id);

	if ($success && $queues)
	{
		//performQueueOperationsForStatusChange($application_id, $status,$queue_name);
	}

	return $success;
}

function doOnlyUpdateStatus($application_id, $status, $follow_up_time = NULL, $agent_id = NULL, $use_stats = TRUE)
{
	try
	{
		$application = ECash::getApplicationByID($application_id);

		$log     = get_log();
		$agent_id = ECash::getAgent()->getAgentId();

		if (!empty($follow_up_time))
		{
			$application->date_next_contact = $follow_up_time;
		}

		if (is_array($status)) $status = implode('::', $status);

		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		
		if(is_numeric($status))
		{
			$application->application_status_id = $status;
		}
		else 
		{
			$application->application_status_id = $status_list->toId($status);
		}

		$application->modifying_agent_id = $agent_id;
		$application->date_application_status_set = time();

		if($status_list->toId('approved::servicing::customer::*root') == $application->application_status_id)
		{

			//get biz rules
			$db = eCash_Config::getMasterDbConnection();
			$business_rules = new ECash_Business_Rules($db);
			$rules = $business_rules->Get_Rule_Set_Tree($application->rule_set_id);
	
			$application->date_fund_actual = time();
			$application->fund_actual = ($application->fund_actual === NULL) ? $application->fund_qualified : $application->fund_actual;

			//see what the FUNDUPD_CALL should be
			$datax_fundupd_type = isset($rules['FUNDUPD_CALL']) ? $rules['FUNDUPD_CALL'] : NULL;
			$company = $application->getCompany();
			//Make FUNDUPD call to DataX if applicable.
			if(!empty($datax_fundupd_type) && $datax_fundupd_type != 'NONE')
			{
				/**
				* String to launch the background process to start the fund
				* update call.
				*/
				$fund_call = CLI_EXE_PATH . "php -f " . CLI_SCRIPT_PATH . "idv_cli_wrapper.php " . EXECUTION_MODE .
					" {$company} {$application->application_id} >> /virtualhosts/log/applog/" .
					APPLOG_SUBDIRECTORY . "/fork_cli.log &";

				get_log()->Write("Forking DataX Fund Update: {$fund_call}");

				/**
				* Define the descriptor spec.  We're not worried about
				* stdin, stdout, or stderr since we're launching this
				* as a background process with a redirector on the
				* command line.
				*/
				$desc = array();
				
				/**
				* We're not using any pipes, so this is empty.
				*/
				$pipe = array();
				
				/**
				* Environment Variables that ecash_exec.php requires
				* to load the appripriate configurations for the
				* current company.
				*/
				$env = array(   
					'ECASH_CUSTOMER_DIR'    => getenv('ECASH_CUSTOMER_DIR'),
					'ECASH_CUSTOMER'                => getenv('ECASH_CUSTOMER'),
					'ECASH_EXEC_MODE'               => getenv('ECASH_EXEC_MODE'),
					'ECASH_COMMON_DIR'               => getenv('ECASH_COMMON_DIR'),
				);
				
				/**
				* Current working directory will be the cronjobs dir
				*/
				$cwd = "";
				
				/**
				* Execute the shell command and close the handle since
				* it's now running as a background process.
				*/
				$ph = proc_open($fund_call, $desc, $pipe, $cwd, $env);
				proc_close($ph);
				
			}
		}
		elseif($status_list->toId('funding_failed::servicing::customer::*root') == $application->application_status_id)
		{
			$application->date_fund_actual = NULL;
		}
		// $use_stats is meant as an override so we can disable
		// the hitting of stats during an Update_Status() call.
		if ($use_stats === TRUE &&
		   !ECash::getFactory()->getModel('StatusHistory')->getStatusExists($application_id, $application->application_status_id))
		{
			$hit_stat = true;
		}
		else
		{
			$hit_stat = false;
			get_log()->Write("Status History already contains the status: ('{$status}') for application $application_id; no stats call done.", LOG_INFO);
		}
		
		$application->save();
		$rows_affected = $application->getAffectedRowCount();

		if ($hit_stat)
		{
			$stats = new ECash_Stats();
			$stats->hitStatStatus($application);
		}

		$save_status = $rows_affected > 0 ? 'COMPLETE' : 'FAILED';
		$log->Write("[Agent:{$agent_id}] Update_Status: [App Id:{$application_id}] -> [Status: {$status}] {$save_status}");

		return $rows_affected > 0;

	} 
	catch (Exception $e) 
	{
		get_log('alert_errors')->Write("[Agent:{$agent_id}][AppID:{$application_id}][Status:{$status}] Failed to update status: ".$e->getMessage());
		throw $e;
	}

}

function Mqgcp_Escape($string)
{
	$string = get_magic_quotes_gpc()?$string:mysql_escape_string($string);
	return $string;
}
	
function performQueueOperationsForStatusChange($application_id, $status, $queue_name = NULL)
{
	$sort = "";
	$available = NULL;

	$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
	$application_status = $status_list->toName($status);

	$application = ECash::getApplicationByID($application);

	get_log()->Write("Performing Queue Operations for status change to : {$application_status}");

	$queue_manager = ECash::getFactory()->getQueueManager();
	$queue_item = new ECash_Queues_BasicQueueItem($application_id);
	$queue = NULL;

	switch ($application_status)

	{
		case 'queued::underwriting::applicant::*root':
			$react_status = ("no" == $application->is_react) ? "_non_react" : "_react";
			if($react_status == "react" && in_array($application->olp_process, array('online_confirmation')))
				$process_status = '_review';
			else
				$process_status = '';
			$queue = $queue_manager->getQueue("underwriting_{$react_status}{$process_status}");
			break;

		case 'skip_trace::collections::customer::*root':
			$queue = $queue_manager->getQueue("skip_trace");
			break;

		case 'queued::verification::applicant::*root':
			$react_status = ("no" == $application->is_react) ? "_non_react" : "_react" ;
			$queue = $queue_manager->getQueue("verification$react_status");
			break;

		case 'addl::verification::applicant::*root':
			$queue = $queue_manager->getQueue("additional_verification");
			break;

		case 'hotfile::verification::applicant::*root':
			$queue = $queue_manager->getQueue("hotfile");
			break;

		case 'queued::fraud::applicant::*root':
			$queue = $queue_manager->getQueue("fraud");
			break;

		case 'queued::high_risk::applicant::*root':
			$queue = $queue_manager->getQueue("high_risk");
			break;

		case 'new::collections::customer::*root':
			$queue_item->Priority = (!Has_Fatal_Failures($application_id) ? 200 : 100);
			$queue = $queue_manager->getQueue("collections_new");
			break;

		case 'queued::contact::collections::customer::*root':
		case 'followup::contact::collections::customer::*root':
			$queue_item->Priority = (!Has_Fatal_Failures($application_id) ? 200 : 100);
			$queue = $queue_manager->getQueue("collections_general");
			break;
	}
	$status_chain =$application_status->ApplicationStatusString;
	//mantis:7324 - moved before move_to_automated_queue to eliminate deleting from current_queue_status after creation a record of it
	switch ($application_status)
	{
		case "dequeued::collections::customer::*root":
		case "dequeued::fraud::applicant::*root":
		case "dequeued::contact::collections::customer::*root":
		case "dequeued::high_risk::applicant::*root":
		case "dequeued::cashline::*root":
		case "sent::quickcheck::collections::customer::*root":
		case "ready::quickcheck::collections::customer::*root":
			break;
		default:
			/**
			 * @todo: Why does current_queue_status need to be called?
			 */
			//current_queue_status_reset($application_id);
	}

	if ($queue !== NULL)
	{
		$queue_manager->getQueueGroup('automated')->remove($queue_item);
		$queue->insert($queue_item);
	}

	if(in_array($application_status, array(
		"paid::customer::*root",
		"sent::quickcheck::collections::customer::*root",
		"ready::quickcheck::collections::customer::*root",
		"sent::external_collections::*root",
		"dequeued::bankruptcy::collections::customer::*root",
		"queued::bankruptcy::collections::customer::*root",
		"unverified::bankruptcy::collections::customer::*root",
		"verified::bankruptcy::collections::customer::*root",
		"withdrawn::applicant::*root",
		"denied::applicant::*root",
		"active::servicing::customer::*root",
		"approved::servicing::customer::*root",
		"funding_failed::servicing::customer::*root",
		"ready::quickcheck::collections::customer::*root"
		)))
	{
		$queue_manager->getQueueGroup('automated')->remove($queue_item);
	}
}

 /**
 * Handles all the update queries for editing an application
 *
 * @param string $query the query to perform
 */
function Do_Update($db, $query, $args = NULL)
 {
 	$log = get_log();

	try
	{
		if ($args !== NULL)
		{
			$st = $db->queryPrepared($query, $args);
		}
		else
		{
			$st = $db->query($query);
		}

		return $st->rowCount();
	}
	catch(Exception $e)
	{
		$_SESSION['error_message'] = 'WARNING!  An error has occurred and your changes have not been submitted.';
		$log->Write("Error updating the following query: {$query}");
		throw $e;
	}
 }

Function IsIntegerValue( $input )
{
	if (is_integer($input))
	{
		return true;
	}
	elseif (strval(intval($input)) === $input)
	{
		return true;
	}
	elseif (str_pad(strval(intval($input)), strlen($input), "0", STR_PAD_LEFT) === $input)
	{
		return true;
	}
	else
	{
		return false;
	}

}

// I know I'm cheating - and I don't care :)
function Get_Transactional_Data($application_id)
{
	require_once(SERVER_CODE_DIR . "paydate_info.class.php");

	$db = ECash_Config::getMasterDbConnection();

	$select_query = "
	SELECT ap.paydate_model as 'model_name',
       	ap.paydate_model,
       	ap.income_frequency as 'frequency_name',
       	ap.income_frequency,
       	ap.income_direct_deposit,
       	ap.day_of_week as 'day_string_one',
       	ap.day_of_week,
       	ap.day_of_month_1 as 'day_int_one',
       	ap.day_of_month_1,
       	ap.day_of_month_2 as 'day_int_two',
       	ap.day_of_month_2,
       	ap.week_1 as 'week_one',
       	ap.week_1,
       	ap.week_2 as 'week_two',
       	ap.week_2,
       	ap.last_paydate,
       	ap.rule_set_id,
       	ap.date_fund_actual as 'date_fund_stored',
       	ap.fund_actual,
       	ap.income_monthly,
       	ap.date_first_payment,
       	ap.is_react,
       	ap.is_watched,
       	ap.application_id,
       	ap.application_status_id,
       	(
            SELECT es.date_effective
            FROM event_schedule AS es
            JOIN event_type AS et USING (event_type_id)
            JOIN transaction_register AS tr USING (event_schedule_id)
            WHERE es.application_id = ap.application_id
            AND et.name_short = 'payment_service_chg'
            AND es.event_status = 'registered'
            AND es.origin_group_id > 0
            AND tr.transaction_status <> 'failed'
            ORDER BY es.date_effective DESC
            LIMIT 1
       	) as last_payment_date,
       	(
			SELECT es.date_effective
            FROM event_schedule AS es
            JOIN event_type AS et USING (event_type_id)
            JOIN transaction_register AS tr USING (event_schedule_id)
            WHERE es.application_id = ap.application_id
            AND es.event_status = 'registered'
            AND tr.transaction_status <> 'failed'
            ORDER BY es.date_effective DESC
            LIMIT 1	
       	) as last_debit_date,
        (
            SELECT es.date_effective
            FROM event_schedule AS es
            JOIN event_type AS et USING (event_type_id)
            JOIN transaction_register AS tr USING (event_schedule_id)
            WHERE es.application_id = ap.application_id
            AND et.name_short = 'assess_service_chg'
            AND es.event_status = 'registered'
            AND es.origin_group_id > 0
            AND tr.transaction_status <> 'failed'
            ORDER BY es.date_effective DESC
            LIMIT 1
        ) as last_assessment_date,
       	sm.name schedule_model
	FROM application ap
	LEFT JOIN schedule_model sm USING (schedule_model_id)
	WHERE application_id = {$application_id}";

	$st = $db->query($select_query);
	$info = $st->fetch(PDO::FETCH_OBJ);
	$obj = new stdClass();
	$obj->info = $info;
	$obj->info->model = $info;
	$obj->info->direct_deposit = $info->income_direct_deposit == 'yes' ? TRUE : FALSE;
	$biz_rules = new ECash_BusinessRulesCache($db);
	$ruleset = $biz_rules->Get_Rule_Set_Tree($info->rule_set_id);
	$obj->rules = $ruleset;
	$obj->amt = $info->fund_actual;
	$obj->fund_date = $info->date_fund_stored;

	return $obj;
}

function Get_System_ID_By_Name(DB_Database_1 $db, $sys_name)
{
	return $db->querySingleValue("SELECT system_id FROM system WHERE name_short='{$sys_name}'");
}

function Fetch_Agent_ID_by_Login(DB_Database_1 $db, $login)
{
	return $db->querySingleValue("SELECT agent_id FROM agent WHERE login='{$login}'");
}

function Fetch_Company_ID_by_Name(DB_Database_1 $db, $name)
{
	return $db->querySingleValue("SELECT company_id from company where name_short = lower('$name')");
}

function Fetch_Company_Map()
{
	$db = ECash_Config::getMasterDbConnection();
	$query = "SELECT company_id, name_short from company";
	$st = $db->query($query);
	$company_map = array();
	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$company_map[$row->company_id] = $row->name_short;
	}

	return $company_map;
}

// Testing this instead of a sub query to see if it helps a deadlock issue
function Load_Event_Type_Map($company_id = NULL)
{
	static $event_type_map;
	if($company_id === NULL) 
	{
		$company_id = ECash::getCompany()->company_id;
	}

	if(empty($company_id))
		throw new Exception("No valid company_id found!");

	if (empty($event_type_map[$company_id]))
	{
		$db = ECash_Config::getMasterDbConnection();

		$query = "
			SELECT 	event_type_id,
					name_short
			FROM 	event_type
			WHERE company_id = {$company_id}";

		$event_type_map = array();

		$st = $db->query($query);

		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$event_type_map[$company_id][$row->name_short] = $row->event_type_id;
		}
	}

	return $event_type_map[$company_id];
}

function Load_Event_Transaction_Map($company_id = null)
{
	static $transaction_maps = array();
	if (empty($company_id))
	{
		$company_id = ECash::getCompany()->company_id;
	}

	if (empty($transaction_maps[$company_id]))
	{
		$db = ECash_Config::getMasterDbConnection();

		$query = "
			SELECT
				et.name_short event, tt.*
			FROM
				event_type et
				JOIN event_transaction USING (event_type_id)
				JOIN transaction_type tt USING (transaction_type_id)
			WHERE
				company_id = {$company_id}
		";

		$st = $db->query($query);

		$transaction_maps[$company_id] = array();

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$transaction_maps[$company_id][$row['event']][$row['name_short']] = $row;
		}
	}
	return $transaction_maps[$company_id];
}

function Load_Transaction_Map($company_id = null)
{
	static $transaction_maps = array();
	if (empty($company_id))
	{
		$company_id = ECash::getCompany()->company_id;
	}

	if (empty($transaction_maps[$company_id]))
	{
		$db = ECash_Config::getMasterDbConnection();

		$query = "
			SELECT
				tt.*
			FROM
				transaction_type tt
			WHERE
				company_id = {$company_id}
		";

		$st = $db->query($query);

		$transaction_maps[$company_id] = array();

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$transaction_maps[$company_id][$row->name_short] = $row;
		}
	}
	return $transaction_maps[$company_id];
}

// Quietly outputs the passed variable (and optional name) at the end of parsed output.
// Encased in HTML comments, so it'll lamost never show up on the rendered page, but you can see it
// in the HTML source.
function dvar_dump($var, $varname = null)
{
  if (EXECUTION_MODE == 'LIVE') return;
  global $debug_output;

  ob_start();
  var_dump($var);
  if ($varname == null)
  {
    $out = "\n<!-- " . ob_get_clean() . " -->\n";
  }
  else
  {
    $out = "\n<!-- ${varname}: \n" . ob_get_clean() . " -->\n";
  }

  $debug_output .= $out;
}

// When dumping into the parsed output isn't an option (some parse-ending errors occurs)
// use this diddy to just pipe it straight to the screen. Encases the output in "preformat"
// tags to make it readable.
function dvar_echo($var, $varname = NULL)
{
  if (EXECUTION_MODE == 'LIVE') return;
  $out = "<pre>";
  if (!is_null($varname)) $out .= "${varname}:\n\n";
  ob_start();
  var_dump($var);
  $out .= ob_get_clean() . "</pre>";
  echo $out;
}

// This simply chops a string off the right.
// Created because built-in function chop() was being used incorrectly.
// No it wasn't...
// $result = chop_string_right( 'denied and', ' and' ) returns 'denied' (CORRECT)
// $result = chop( 'denied and', ' and' ) returns 'denie' (INCORRECT)
// Ah ha, but:
// $result = chop_string_right( "'denied' and ", " and" ) returns "'denied'" (CORRECT)
// Which is what was being done
function chop_string_right( $str_in, $str_to_remove, $case_sensitive = false )
{
	$result = isset($str_in) ? $str_in : '';
	$str_to_remove = isset($str_to_remove) ? (!$case_sensitive ? strtoupper($str_to_remove) : $str_to_remove) : '';

	if ( $str_to_remove == '' || $result == '' ) return $result;

	$str_in_len = strlen( $result );
	$str_to_remove_len = strlen( $str_to_remove );

	if ( $str_to_remove_len > $str_in_len ) return $result;

	$tail =  substr( $result, -$str_to_remove_len );

	if ( !$case_sensitive ) $tail = strtoupper($tail);

	if ( $tail == $str_to_remove ) $result = substr( $result, 0, $str_in_len - $str_to_remove_len );

	return $result;
}

function Set_Process_Status($db = null, $company_id, $step, $state, $date = NULL, $pid = NULL)
{
	if ($db == NULL) $db = ECash_Config::getMasterDbConnection();

		$step  = trim($step);
		$state = strtolower(trim($state));

		if ( !in_array($state, array('started','completed','failed')) )
		{
			throw new General_Exception("Set_Process_Status: called with invalid processing state ('$state').");
		}

		if ( strlen($step) == 0 || strlen($step) > 50 )
		{
			throw new General_Exception("Set_Process_Status: invalid string length for processing step.");
		}

		if ( $date == null )
		{
			$date = date("Y-m-d");
		}

		// If this step is started, it's going to be a new entry..
		if($state == 'started')
		{

			$query = "
				INSERT INTO process_log
					(
						business_day,
						company_id,
						step,
						state,
						date_started,
						date_modified
					)
				VALUES
					(
						'{$date}',
						{$company_id},
						'$step',
						'$state',
						current_timestamp,
						current_timestamp
					)
				ON DUPLICATE KEY UPDATE
					state		= '$state',
					date_modified	= current_timestamp
			";

			$st = $db->query($query);
			return $db->lastInsertId();
		}
		// Otherwise it'll be an update.  If we don't have the pid already, try to get it.
		elseif ($pid === null)
		{
			$pid = Get_Process_Log_Id($db, $company_id, $step, $date);
		}

		// If we have the pid do the update, if not return false.
		if(!empty($pid))
		{
			$query = "
				UPDATE process_log
					SET state		= '{$state}',
					date_modified	= current_timestamp
				WHERE process_log_id = '{$pid}'
				AND   step = '{$step}'
			";
			$db->query($query);
			return $pid;
		}
		else
		{
			return false;
		}
}

/*  Check for the state of a particular process
 *  Specify a date or else the most recent state
 *  will be returned.
 */
function Check_Process_State(DB_Database_1 $db, $company_id, $process, $business_day = false)
{
	$query = "
			SELECT 	state
			FROM	process_log
			WHERE	company_id = {$company_id}
			AND     step = '{$process}'
	";

	if($business_day != false) 
	{
		$query .= "
			AND		business_day = '{$business_day}'";
	}
	$query .= "
			ORDER BY date_started desc
			LIMIT 1
	";

	return $db->querySingleValue($query);
}

function Get_Process_Log_Id(DB_Database_1 $db, $company_id, $process, $business_day = false)
{
	$query = "
			SELECT 	process_log_id
			FROM	process_log
			WHERE	company_id = {$company_id}
			AND     step = '{$process}'
	";

	if($business_day != false) 
	{
		$query .= "
			AND		business_day = '{$business_day}'";
	}
	$query .= "
			ORDER BY date_started desc
			LIMIT 1
	";
	return $db->querySingleValue($query);
}

/**
 * Returns the most recent [$step] Process time as a string in 'YmdHis' format.
 *
 * If [$state] is specified, this function will return the most recent [$step]
 * Process time in [$state] state.
 *
  * @param string $step The step value
 * @param string $state Optional state value
 * @return string|NULL The date_started value for the most recent record
 * matching the specified criteria, or NULL if none are found
 */
function getLastProcessTime(DB_Database_1 $db, $step, $state = NULL)
{
	$step = $db->quote($step);

	$company_id = ECash::getCompany()->company_id;

	$query = "
			SELECT
			 MAX(date_started) as last_process_time
			FROM  process_log
			WHERE step = {$step}
			AND   company_id = {$company_id}
			";

	if (NULL !== $state)
	{
		$state = $db->quote($state);
		$query .= "AND state = {$state}";
	}

	$st = $db->query($query);
	$row = $st->fetch(PDO::FETCH_OBJ);

	if (NULL !== $row->last_process_time)
	{
		return date('YmdHis', strtotime($row->last_process_time) );
	}

	return NULL;
}

/**
 * Writes to the SYS V IPC Message Queue for a particular facility
 *
 * This is meant for writing out messages from a background process for a
 * front-end client to read.  Currently this is used for the ACH and QC Batch
 * Send methods.
 *
 * @param string $facility - Name of the facility
 * @param string $string - Message to send
 * @param integer $percentage - Optional, percentage from 0 to 100
 */
function Update_Progress($facility, $string, $percentage = null)
{
	// Add new facilities to the array...
	if(in_array($facility, array('qc','ach')))
	{
		$msg = new stdClass();
		if($percentage != null) 
		{
			$msg->percentage = $percentage;
		}

		if($string != null)	
		{
			$msg->message = $string;
		}

		$queue_name = CUST_DIR . "temp_data/$facility." . ECash::getCompany()->company_id . ".queue";

		// If the file doesn't exist, create it.
		if(! file_exists($queue_name)) 
		{
			$fp = fopen($queue_name, 'w+');
			if($fp === FALSE)
				die("Could not create queue file $queue_name!");

			fclose($fp);
		}

		$queue = msg_get_queue(ftok($queue_name, 'A'),0666 | IPC_CREAT);
		msg_send($queue, 1, $msg);
	}
}

/**
 * Removes the SYS V IPC Message Queue handle
 *
 * This simply removes the file that is used for IPC communications as a means
 * to wipe out any old messages in the queue.  When Update_Progress() is run,
 * the file for the IPC handle is automatically created if it does not exist.
 *
 * @param string $facility - Facility name (ach, qc)
 * @return bool
 */
function Remove_Progress_Facility($facility)
{
	if(in_array($facility, array('qc','ach')))
	{
		$queue_name = CUST_DIR . "temp_data/$facility.{$_SESSION['company_id']}.queue";

		// If the file doesn't exist, create it.
		if(file_exists($queue_name)) 
		{
			unlink($queue_name);
		}
		return true;
	}

	return false;
}

/**
 * Insert application in standby table
 *
 * @param integer $application_id
 * @param string process type
 * @param string old process type to replace, if exists.
 * @param string date to set if old process type is null, or to compare against create date if updating
 */
function Set_Standby($application_id, $process_type, $old_pt=null, $date=null)
{

	$db = ECash_Config::getMasterDbConnection();

	if ($old_pt == null && $date == null) 
	{
		$sql = "
			INSERT INTO standby (application_id, process_type) VALUES ({$application_id}, '{$process_type}')";

	}
	else if ($old_pt == null && $date != null) 
	{
		$sql = "
			INSERT INTO standby (date_created, application_id, process_type)
			VALUES (DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 minute),{$application_id}, '{$process_type}')";
	}
	else if ($date != null) 
	{
		// Yeah, I know we're passing a date, but due to timezone issues it's causing problems
		// on my testing machine and all we really care about is that the date isn't the future.
		$sql = "
			UPDATE standby
			SET process_type = '{$process_type}'
			WHERE application_id = {$application_id}
			AND date_created    <= CURRENT_TIMESTAMP
			AND process_type     = '{$old_pt}'";
	} 
	else 
	{
		$sql = "
			UPDATE standby
			SET process_type = '{$process_type}', date_created = CURRENT_TIMESTAMP
			WHERE application_id = {$application_id}
			AND process_type = '{$old_pt}'";
	}
	$db->exec($sql);
}

function Remove_Standby($application_id, $process_type=NULL)
{
	if(!is_numeric($application_id))
	{
		get_log()->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."($application_id,$process_type) Not an application_id passed to Remove_Standby!",LOG_NOTICE);
		throw new Exception ("Not an application_id passed to Remove_Standby!");
	}

	$db = ECash_Config::getMasterDbConnection();

	$sql = "
		DELETE FROM standby WHERE application_id = {$application_id}
		";
	if ($process_type !== NULL)
	{
		$sql .= " AND process_type = '{$process_type}'";
	}
	$st = $db->query($sql);

	get_log()->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."($application_id,$process_type): ".$st->rowCount(),LOG_NOTICE);

	return $st->rowCount();
}

function Get_Standby_List($type, $date=NULL)
{
	$db = ECash_Config::getMasterDbConnection();

	if ($date === NULL) $date = date("Y-m-d");

	//mantis:7357 - filter company
	$sql = "
			SELECT st.*
			FROM standby st
			JOIN application ap ON (st.application_id = ap.application_id)
			WHERE 	st.process_type='{$type}'
			     AND
                       		st.date_created < '{$date}'
			     AND
				ap.company_id = " . ECash::getCompany()->company_id;
	$st = $db->query($sql);
	return $st->fetchAll(PDO::FETCH_OBJ);
}

function anti_mqgpc($input)
{
	if(get_magic_quotes_gpc())
	{
		return(stripslashes($input));
	}
	return($input);
}

function Get_Current_Balance($application_id, $include_future = FALSE)
{
	$db = ECash_Config::getMasterDbConnection();

	if(empty($application_id))
		throw new Exception("No Application ID passed to " . __METHOD__);

	if($include_future)
	{
		$query = "
			SELECT SUM(IFNULL(tr1.amount,es1.amount_non_principal + es1.amount_principal)) AS balance
			FROM event_schedule es1
			LEFT JOIN transaction_register tr1 ON ( tr1.event_schedule_id = es1.event_schedule_id )
			WHERE es1.application_id = $application_id
			AND ( tr1.transaction_status != 'failed' OR tr1.transaction_register_id IS NULL ) ";
	}
	else
	{
		$query = "
			SELECT SUM(tr1.amount) AS balance
			FROM transaction_register tr1
			WHERE tr1.application_id = $application_id
			AND tr1.transaction_status != 'failed' 	";
	}

	return $db->querySingleValue($query);
}

function Get_Other_Active_Loans(DB_Database_1 $db, $application_id, $ssn = NULL)
{
	if(NULL === $ssn)
	{
		$query = "
			SELECT ssn FROM application WHERE application_id = $application_id
			";
		$ssn = $db->querySingleValue($query);
	}

	// These statuses are the "OK" ones.  Anything else
	// should trigger an "active loan" warning.
	$inert_application_statuses = array();
	$query = "
		SELECT * FROM application_status_flat
		";
	$st = $db->query($query);

	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$app_stat_id = $row->application_status_id;
		if("funding_failed" == $row->level0
				|| "paid" == $row->level0
				|| "applicant" == $row->level1
				|| "prospect" == $row->level1
				)
		{
			$inert_application_statuses[] = $app_stat_id;
		}
	}
	$inert_application_statuses = join(",",$inert_application_statuses);

	/**
	 * To prevent a possible case of mixed company id's, I'm pulling the Application
	 * up and getting the company_id off of it rather than fetching it from the
	 * ECash Object. [GF#17150][BR]
	 */
	$application = ECash::getApplicationById($application_id);
	$company_id = $application->getCompanyId();

	// A simple query using SSN to determine uniqueness
	// which looks for how many "not OK" loans there are
	// not including the one we're currently looking at.
	$query = "
		SELECT application_id
		FROM   application
		WHERE  company_id = {$company_id}
		AND    ssn = '{$ssn}'
		AND    application_id != {$application_id}
		AND    application_status_id NOT IN ($inert_application_statuses)
		";
	$st = $db->query($query);
	return $st->fetchAll(PDO::FETCH_ASSOC);
}

function Has_Batch_Closed($company_id = null)
{
	if($company_id === NULL) 
	{
		$company_id = ECash::getCompany()->company_id;
	}

	if(empty($company_id))
		throw new Exception("No valid company_id found!");

	static $count;

	if(isset($count)) 
	{
		return $count;
	}

	$db = ECash_Config::getMasterDbConnection();
	$query = "
		SELECT COUNT(*) AS `count`
		FROM process_log
		WHERE business_day = CURDATE()
		AND company_id = {$company_id}
		AND step = 'ach_batchclose'";

	return ($db->querySingleValue($query) > 0);
}

function Start_of_Previous_Business_Day($num_days)
{
    require_once(SQL_LIB_DIR."util.func.php");

    $today = date("Y-m-d");
    $today_unixstamp = time();

    $pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
    $period = $pdc->Get_Business_Days_Backward($today, $num_days);

    $old_tz = ini_get("date.timezone");
    ini_set("date.timezone", ECash_Config::getInstance()->TIME_ZONE);
    $period_unixstamp = strtotime($period . "235959");
    ini_set("date.timezone",$old_tz);

    $next_day = intval(($period_unixstamp - $today_unixstamp) /60);
    return($next_day);
}

function Get_Source_Map() 
{
	static $map;
	$db = ECash_Config::getMasterDbConnection();

	if (!isset($map)) 
	{
		$query = "SELECT * FROM source";
		$st = $db->query($query);

		$map = array();
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$map[$row->name_short] = $row->source_ref_id;
		}
	}

	return $map;
}

function Set_Schedule_Model($application_id, $model)
{
	settype($application_id, 'int');
	$db = ECash_Config::getMasterDbConnection();

	/**
	 * To prevent a possible case of mixed company id's, I'm pulling the Application
	 * up and getting the company_id off of it rather than fetching it from the
	 * ECash Object. [GF#17150][BR]
	 */
	$application = ECash::getApplicationById($application_id);
	$company_id = $application->getCompanyId();
	
	if (!is_int($model))
	{
		$model = "(SELECT schedule_model_id FROM schedule_model WHERE company_id = {$company_id} AND active_status = 'active' AND name = ".$db->quote($model).")";
		$exists = " AND EXISTS $model";
	}

	$query = "UPDATE application SET schedule_model_id = $model WHERE application_id = {$application_id} {$exists} ";

	$st = $db->query($query);
	return ($st->rowCount > 0);

}

/**
 * Determines whether or not the supplied company
 * is an eCash 3.0 company or not by checking
 * for the exitence of a company property called
 * SYSTEM_NAME to have the value 'ecash3_0'.
 *
 * @todo REPLACE THIS!
 * @param integer $company_id
 * @return bool
 */
function Is_ECash_3_Company($company_id)
{
	// HACK - ALL eCash Commercial companies will be 3.x
	return true;
}

/**
 * Retrieves default AgentID
 *
 * @param int $company_id
 * @return int
 */
function Fetch_Default_Agent_ID()
{
	if (eCash_Config::getInstance()->DEFAULT_AGENT_ID !== NULL)
	{
		$agent_id = eCash_Config::getInstance()->DEFAULT_AGENT_ID;
	}
	else
	{
		$agent_id = 1; // The ultimate default
	}
}

/**
 * Get the current agent_id.  If one isn't available, try
 * one set via the define(), else use 1.  This should always
 * return some value.
 *
 * @param Server $server (optional)
 */
function Fetch_Current_Agent($server = null)
{
	if($server instanceof Server)
	{
		$agent_id = $server->agent_id;
	}
	else
	{
		if(empty($agent_id) && isset($_SESSION["agent_id"]))
		{
			$agent_id = $_SESSION["agent_id"];
		}
		else
		{
			$agent_id = Fetch_Default_Agent_ID();
		}
	}

	return $agent_id;
}

/**
 * Inert/Update Loan Snapshot Table.
 */
function Set_Loan_Snapshot($trid,$tr_status)
{
	return;
	$ls_query 		= null;
	$application_id = null;
	$prev_tr_status	= null;
    $amount 		= null;
    $amount_type 	= null;
	$db = ECash_Config::getMasterDbConnection();

	$query = "
		select
			tr.application_id,
			tr.transaction_status,
			tr.amount,
			eat.name_short as amount_type
		from
			transaction_register tr
			join event_amount ea using (transaction_register_id)
			JOIN event_amount_type eat USING (event_amount_type_id)
		where
			transaction_register_id = $trid";

    $st = $db->query($query);

    while ($row = $st->fetch(PDO::FETCH_OBJ))
    {
			$application_id = $row->application_id;
			$prev_tr_status	= $row->transaction_status;
            $amount 		= $row->amount;
            $amount_type 	= $row->amount_type;
    }


	if(is_null($application_id))
	{
		get_log()->Write("Loan Snapshot Error: Transaction: {$trid} New Status: {$tr_status}");
	}
	else
	{

		switch($amount_type)
		{
			case "principal":
			case "service_charge":
			case "fee":
			case "irrecoverable":
				$type_suffix = 	"_{$amount_type}";
				break;
			default:
				$type_suffix = "";
		}
		$query = "
		INSERT IGNORE INTO loan_snapshot (date_created,application_id) VALUES (NOW(),$application_id)";
		$db->exec($query);

		switch($tr_status)
		{
			case "pending":
				switch($prev_tr_status)
				{
					case "new":
					case "pending":
						$ls_query = "
										UPDATE
											loan_snapshot
										SET
											balance_pending{$type_suffix} = balance_pending{$type_suffix}+{$amount}
										WHERE
											application_id = $application_id
									";
						break;
					case "failed":
						$ls_query = "
										UPDATE
											loan_snapshot
										SET
											balance_pending{$type_suffix} = balance_pending{$type_suffix}-{$amount}
										WHERE
											application_id = $application_id
									";
						break;
				}
				break;
			case "complete":
				switch($prev_tr_status)
				{
					case "pending":
						$ls_query = "
										UPDATE
											loan_snapshot
										SET
											balance_complete{$type_suffix} = balance_complete{$type_suffix}+{$amount}
										WHERE
											application_id = $application_id
									";
						break;
					case "failed":
						$ls_query = "
										UPDATE
											loan_snapshot
										SET
											balance_pending{$type_suffix} = balance_pending{$type_suffix}-{$amount},
											balance_complete{$type_suffix} = balance_complete{$type_suffix}-{$amount}
										WHERE
											application_id = $application_id
									";
						break;
				}
				break;
			case "failed":
				switch($prev_tr_status)
				{
					case "complete":
						$ls_query = "
										UPDATE
											loan_snapshot
										SET
											balance_pending{$type_suffix} = balance_pending{$type_suffix}+{$amount},
											balance_complete{$type_suffix} = balance_complete{$type_suffix}+{$amount}
										WHERE
											application_id = $application_id
									";
						break;
				}
				break;

		}
		if(!is_null($ls_query))
		{
			try
			{
			 $db->exec($ls_query);
			}
			catch (Exception $e)
			{
				get_log()->Write("Loan Snapshot Query Error: ".var_export($e,TRUE));
			}
		}
		else
		{
			get_log()->Write("Loan Snapshot Error: Transaction: {$trid} Prev/New Status: {$prev_tr_status}/{$tr_status}");
		}
	}


}

/**
 * Takes the results of a debug_backtrace(),
 * formats it nicely, and logs it to the debug log.
 *
 * @param array $backtrace
 * @usage logBackTrace(debug_backtrace());
 */
function logBackTrace(array $backtrace)
{
	$log = get_log('debug');

	$index = count($backtrace) -1;
	foreach($backtrace as $call)
	{
		$log_entry = "";

		foreach($call as $key => $value)
		{
			if($key == 'object' || $key == 'type') continue;
			if(empty($value)) continue;

			if(is_array($value))
			{
				$values = array();
				foreach($value as $cell)
				{
					if(is_array($cell))
					{
						$values[] = "Array";
					}
					else if (is_object($cell))
					{
						$name = get_class($cell);
						$values[] = "Object($name)";
					}
					else if(is_null($cell))
					{
						$values[] = "NULL";
					}
					else
					{
						$values[] = $cell;
					}
				}

				$value = implode(',', $values);
			}

			$log_entry .= "[$key: $value] ";
		}

		$log->Write("Trace[$index]: $log_entry");
		$index--;
	}
}

function Fetch_Intercept_Card($company_id)
{
	$db = ECash_Config::getMasterDbConnection();

	$query = "
	            -- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
	          	SELECT
				intercept_serialized
			FROM 
				intercept_login
			WHERE 	
				company_id = {$company_id}
			   AND
				active_status = 'active'
			ORDER BY
				date_created DESC
			LIMIT 1
	   	";
	
	$result = $db->query($query);
    $row = $result->fetch(PDO::FETCH_OBJ);
	$card_array = unserialize($row->intercept_serialized);

	return $card_array;
}
?>
