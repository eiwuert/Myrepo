<?php

/**
 * The DFA implemented here is based on the following logic from Mantis issue
 * 2995:
 *
 * "If an account is in Bankruptcy Notification and the 30 days has expired, eCash needs to change the status 
 *  to Collection Contact and schedule a full pull ONLY if there were no quick check present in the transactions. 
 *  If the transactions have one non fatal failed quick check, the account should be placed in QCReady and the 
 *  second quick check would be attempted. 
 *
 *  State 1 is the result state if the accounts has no QCs at all.
 *
 *  State_7: If the transactions have one fatally failed quick check or two failed 
 *  quick check (fatal or not), the account should be placed in Collection Contact for the 7 days. After that 7 days, 
 *  State_6: if no other action is taken, the account will need to be moved to Second Tier Pending. "
 *
 *  State_8: If the account has a fatal ACH (verify Angi/Natalie)
 *
 *  State_9: If the account has no fatal ACHs. (verify w/ Angi/Natalie)
 * 
 */

require_once('dfa.php');

class BToCDFA extends DFA
{
	const NUM_STATES = 10;
	
	function __construct($server) 
	{
		for ($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(3,6,7,8,9);
		$this->tr_functions = array(
				  0 => 'qc_count',
				  1 => 'has_fatal_ach',
				  2 => 'has_pending_qc',
				  4 => 'qc_count',
				  5 => 'has_fatal_qc');

		$this->transitions = array (
			    0 => array(0 => 1, 1 => 2, 2 => 2),
			    1 => array(0 => 8, 1 => 9),
			    2 => array(1 => 3, 0 => 4),
			    4 => array(1 => 5, 2 => 7),
			    5 => array(0 => 6, 1 => 7));

		$this->server = $server;
		parent::__construct();
	}

	function qc_count($parameters) {
		return ($parameters->status->num_qc);
	}
	
	function has_fatal_qc($parameters) {
		foreach ($parameters->schedule as $e) {
			if ($e->status == 'scheduled') continue;
			if (in_array($e->return_code, $parameters->fatal_qc_codes))
				return 1;
		}
		return 0;
	}
	
	function has_pending_qc($parameters) {
		foreach ($parameters->schedule as $e) {
			if (($e->status == 'pending') &&
			    ($e->type == 'quickcheck')) return 1;
		}
		return 0;
	}
	
	/**
	 *  @todo This should grab the fatal ACH types from database 
	 */
	function has_fatal_ach($parameters) {
		foreach ($parameters->schedule as $e) {
			if (in_array($e->return_code, array('R02', 'R05', 'R07', 'R08', 'R10',
							    'R16', 'R29', 'R38', 'R51',
							    'R52')))
				return 1;
		}
		
		return 0;
	}
	
	/**
	 * Scenario: Bankruptcy Notification has timed out, and they have a pending QC.
	 *
	 * Action: Just put the account in QC Sent.
	 */
	function State_3($parameters) {
		$this->Log("Application {$row->application_id}: Bankruptcy -> Quickcheck Sent.");
		Update_Status(null, $parameters->application_id, array('sent','quickcheck','collections','customer','*root'));
	}

	/**
	 * Situation: Account has no pending Quickchecks, there is only one returned QC, and it came
	 *            back with a 'non-fatal' return code.
	 *
	 * Action: Place the account in QC Ready and attempt another QC
	 *
	 */
	function State_6($parameters) 
	{
		// If we're not using QuickChecks, disable QC Related activities
		if(ECash::getConfig()->USE_QUICKCHECKS === TRUE)
		{
			$this->Log("Application {$row->application_id}: Bankruptcy -> Quickcheck.");
			Update_Status(null, $parameters->application_id, "ready::quickcheck::collections::customer::*root");
		}
		else
		{
			$this->Log("Application {$row->application_id}: Bankruptcy -> 2nd Tier Pending.");
			Update_Status(null, $parameters->application_id, "pending::external_collections::*root");
		}
	}
	
	/**
	 * Situation: Account either has 2 returned QuickChecks or a fatal QuickCheck return
 	 *  
 	 *  Action: The account should be placed in Collection Contact for the 7 days. 
 	 *  After that 7 days, if no other action is taken, the account will need to 
 	 *  be moved to Second Tier Pending.  This is done by Status_Dequeued_Collections_Move_To_QC_Ready
 	 *  in the Nightly cronjob.
 	 * 
	 */
	function State_7($parameters)
	{
		Set_Standby($parameters->application_id, $parameters->company_id, 'qc_return');
		$this->Log("Application {$row->application_id}: Bankruptcy -> Quickcheck.");
		Update_Status(null, $parameters->application_id, "queued::contact::collections::customer::*root", null, null, false);
		
		/**
		 * @todo: Fix date_unavailable
		 */
	}
	
	/**
	 * Situation: Account has no QC's and no fatal ACH's
	 *
	 * Action: Schedule Full Pull and move to Collections Contact Queue
	 */
	
	function State_8($parameters)
	{
		$this->Log("Application {$parameters->application_id}: Bankruptcy -> Collections/Contact/Queued.");
		
		Update_Status(null, $parameters->application_id,  "queued::contact::collections::customer::*root", null, null, false);

		// Per #16663, The customer does not want any Full Pulls to run.
		// If the customer were to expire from Bankruptcy Notified, this 
		// would be to hit.
		//
		//Schedule_Full_Pull($parameters->application_id);
	}

	/**
	 * Situation: Account has no QC's and one or more fatal ACH's
	 *
	 * Action: Send to Collections
	 */
	function State_9($parameters) 
	{
		$this->Log("Application {$row->application_id}: Bankruptcy -> Collections");
		Update_Status(null, $parameters->application_id, "queued::contact::collections::customer::*root", null, null, false);
		
		}
}

?>
