<?php

/**
 * send out reminder letters to those who have made payment arrangements
 * 3 days prior to activation of said arrangement
 */

function Send_Arrangement_Reminder_Letters($server) 
{
	$company_id = $server->company_id;
	$db = ECash_Config::getMasterDbConnection();
	$holidays = Fetch_Holiday_List();
	$pdc = new Pay_Date_Calc_3($holidays);
	
	$accounts = array();

	// The following query pulls up all accounts that have a debit scheduled 3 days from now.
	$tbd = $pdc->Get_Business_Days_Forward(date("Y-m-d"), 3);
	
	$query = "
select  application_id
from event_schedule as es
join application as a using (application_id)
join application_status_flat as asf using (application_status_id)
where es.date_effective = '{$tbd}'
AND es.company_id = {$company_id}
and ( es.context = 'arrangement' OR es.context = 'partial' )
AND  ((asf.level1 = 'collections' and asf.level2 = 'customer' and asf.level3 = '*root')
   OR (asf.level2 = 'collections' and asf.level3 = 'customer' and asf.level4 = '*root'))
";
	
	$accounts = $db->querySingleColumn($query);

	foreach ($accounts as $account_id) {
		ECash::getLog()->Write("Sending Arrangement Reminder Letter for account {$account_id}");
//		eCash_Document_AutoEmail::Send($server, $account_id, 'APPROVAL_TERMS');
		eCash_Document_AutoEmail::Queue_For_Send($server, $account_id, 'ARRANGEMENTS_MADE');
	}
	
	eCash_Document_AutoEmail::Send_Queued_Documents($server);
	
}


/*                 MAIN processing code                */

function Main()
{
	global $server;
	
	require_once(COMMON_LIB_DIR."pay_date_calc.3.php");
	require_once(LIB_DIR."common_functions.php");
	require_once (LIB_DIR . "/Document/AutoEmail.class.php");

	Send_Arrangement_Reminder_Letters($server);

}
