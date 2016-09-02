<?php

require_once('dfa.php');
require_once(COMMON_LIB_DIR."pay_date_calc.3.php");

class QuickCheckDFA extends DFA
{
	const NUM_STATES = 9;

	function __construct()
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(5,6,7,8);
		$this->initial_state = 0;
		$this->tr_functions = array( 
				0 => 'has_watch_flag',
				1 => 'has_bankruptcy_status',
				2 => 'has_2nd_tier_status',
				3 => 'has_fatal_code', 
				4 => 'is_first_qc',
				9 => 'in_qc_arrangements');
				// Add checks for 2nd Tier Sent, Watch, Hold, and Bankruptcy
				
		$this->transitions = array(
				0 => array( 	0 => 1, 1 => 7),
				1 => array( 	0 => 2, 1 => 7),
				2 => array( 	0 => 9, 1 => 8),
				3 => array(		0 => 4, 1 => 5),
				4 => array(		0 => 5, 1 => 6),
				9 => array(		0 => 3, 1 => 7)
				);
		parent::__construct();
	}

	function has_watch_flag($parameters) 
	{
		if ($parameters->is_watched == 'yes')
			return 1;

		return 0;
	}
	
	function has_bankruptcy_status($parameters) 
	{
		if (($parameters->application_status_chain == 'verified::bankruptcy::collections::customer::*root') 
			|| ($parameters->application_status_chain == 'unverified::bankruptcy::collections::customer::*root'))
			return 1;
			
		return 0;
	}

	function has_2nd_tier_status($parameters) 
	{
		if ($parameters->application_status_chain == 'sent::external_collections::*root') 
			return 1;
			
		return 0;
	}
	
	// true if there are fatal returns in any QC items
	function has_fatal_code($parameters) 
	{
		$f = $parameters->most_recent_failure;

		//$this->Log("Most Recent Failure:" .var_export($f,true));
		//$this->Log("ARC Map:" .var_export($parameters->arc_map,true));

		if ($parameters->arc_map[$f->ach_return_code_id]) 
		{
			if ($parameters->arc_map[$f->ach_return_code_id]['is_fatal'] == 'yes')
			return 1;
		}
		else
		{
			throw new Exception ("Can't find ACH Return Code in map!");
		}
		return 0;
	}
		
	// Counts the number of QC's.  If it's less than the maximum allowable,
	// then true.
	function is_first_qc($parameters) 
	{
		return(($parameters->status->num_qc == 1)?1:0);
	}
	
	function in_qc_arrangements($parameters) 
	{
		if ($parameters->level0 == 'arrangements' && $parameters->level1 == 'quickcheck') 
		{
			return 1;
		} 
		else 
		{
			return 0;
		}
	}
	
	// The customer has either a fatal return or more than one QC, go directly to collections
	function State_5($parameters) 
	{

		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		require_once(SQL_LIB_DIR."util.func.php");
		$queue_log = get_log("queues");
		$queue_log->Write(__FILE__.":".'$Revision: 247 $'.":".__LINE__.":".__METHOD__."()",LOG_NOTICE);

		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();
			Remove_Standby($parameters->application_id);
			Set_Standby($parameters->application_id, 'qc_return');
			Update_Status(null, $parameters->application_id, "return::quickcheck::collections::customer::*root", NULL, FALSE);
			
		//	$qm = ECash::getFactory()->getQueueManager();
		//	$qi = $qm->getQueue('collections_returned_qc')->getNewQueueItem($parameters->application_id);
		//	$qm->moveToQueue($qi, 'collections_returned_qc');
			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to Send to collections.");
			$db->rollBack();
			throw $e;
		}
	}
	
	// The customer is ready for another QC
	function State_6($parameters) 
	{

		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		require_once(SQL_LIB_DIR."util.func.php");
		$queue_log = get_log("queues");
		$queue_log->Write(__FILE__.":".'$Revision: 247 $'.":".__LINE__.":".__METHOD__."()",LOG_NOTICE);

		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();
			Remove_Standby($parameters->application_id);
			Set_Standby($parameters->application_id, 'qc_ready');
			Update_Status(null, $parameters->application_id, "return::quickcheck::collections::customer::*root", NULL, FALSE);
			
		//	$qm = ECash::getFactory()->getQueueManager();
		//	$qi = $qm->getQueue('collections_returned_qc')->getNewQueueItem($parameters->application_id);
		//	$qm->moveToQueue($qi, 'collections_returned_qc');
			
			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to Send to collections.");
			$db->rollBack();
			throw $e;
		}
	}
	
	// This is an empty function because the customer must be in the watch or bankruptcy status,
	// therefore they should not change status when there is a return
	// mantis:6213 - now we need to update status
	function State_7($parameters) 
	{
		require_once(SQL_LIB_DIR."util.func.php");

		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();
			
			Update_Status(null, $parameters->application_id, "return::quickcheck::collections::customer::*root", NULL, FALSE);
			
			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to Update status.");
			$db->rollBack();
			throw $e;
		}	 
	}
	
	// The customer is in external collections
	function State_8($parameters) 
	{
		// Tally up the amount of the last return set
		$amount = 0;
		foreach($parameters->status->fail_set as $f) 
		{
			// Only look at the returns for today, otherwise we risk adding
			// too many past returns
			if(date("Y-m-d", strtotime($f->date_modified)) == date("Y-m-d")) 
			{
				if($f->principal_amount < 0)
					$amount += $f->principal_amount;
				
				if($f->fee_amount < 0)
					$amount += $f->fee_amount;
			}
		}
		
		$ec = new External_Collections($parameters->server);
		$ec->Create_EC_Delta_From( $parameters->application_id, $amount );
	}
	
	
}
?>
