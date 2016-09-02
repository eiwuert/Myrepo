<?php
/*
 * This DFA is meant to handle failures for transactions that are
 * non-ACH and not QuickCheck related.
 *
 */

require_once('dfa.php');

require_once(SQL_LIB_DIR."comment.func.php");

class Other_Transaction_DFA extends DFA
{
	const NUM_STATES = 13;

	function __construct($server)
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(2,3,4,6,11,12);
		$this->initial_state = 0;
		$this->tr_functions = array( 
				0 => 'is_in_holding_status',
				1 => 'has_2nd_tier_status',
				7 => 'most_recent_failure_is_arrangement',
				9 => 'is_debt_consolidation',
				10 => 'is_at_max_debt_consolidation_failure_limit',
			);
				
		$this->transitions = array(
				 0 => array( 	0 => 1, 1 => 3),
				 1 => array( 	0 => 9, 1 => 4),
				 7 => array( 	0 => 2, 1 => 6),
				 9 => array(	0 => 7, 1 => 10),
				 10 => array(	0 => 11, 1 => 12)
				);
		parent::__construct();
		$this->server = $server;
	}
	
	/* Helper Functions go here */

	// If the application is in a Hold Status (Watch Flag, Hold,
	// Bankruptcy, etc... Then we want to postpone rescheduling
	// the account.
	function is_in_holding_status($parameters) 
	{
		$application_id = $parameters->application_id;
		if(In_Holding_Status($application_id)) 
		{
			return 1;
		}
		return 0;
	}

	function has_2nd_tier_status($parameters) 
	{
		if ($parameters->application_status_chain == 'sent::external_collections::*root') 
			return 1;
			
		return 0;
	}
	
	function most_recent_failure_is_arrangement($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		
		return (bool)($e->context == 'arrangement' || $e->context == 'partial');
	}
	
	function is_debt_consolidation($parameters) 
	{
		if ($this->has_type($parameters, 'payment_debt_fees') || 
		  $this->has_type($parameters, 'payment_debt_principal')) {
			
		  	return 1;
		} 
		else 
		{
			return 0;
		}
	}
	
	function is_at_max_debt_consolidation_failure_limit($parameters) 
	{
		$count = 0;
		foreach ($parameters->schedule as $e) 
		{
			if (in_array($e->type, array('payment_debt_fees', 'payment_debt_principal')) &&
			  $e->status == 'failed') {
				$count++;
			}
		}
		
		return $count < 2 ? 0 : 1;
	}
	
	function has_type($parameters, $comparison_type, $checklist='failures') 
	{
		if ($checklist == 'failures') $list = $parameters->status->fail_set;
		else $list = $parameters->schedule;
		foreach ($list as $e) 
		{
			if ($e->type == $comparison_type) return 1;
		}
		return 0;
	}


	/* These are the end states for the state machine */
	
	// The applicant is in neither a Hold Status or 2nd Tier.  The failure was most likely
	// an arrangement.  Add the Application to the Arrangements Failed Queue for now.
	function State_2 ($parameters) 
	{
		// Write code to put the customer in the arrangements failed queue.

		$cs = Fetch_Application_Status($parameters->application_id);
		if("new" == $cs["level0"] && "collections" == $cs["level1"] && "customer" == $cs["level2"] && "*root" == $cs["level3"])
		{
			$qm = ECash::getFactory()->getQueueManager();
			
			$qi = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
			if (Has_Fatal_Failures($parameters->application_id))
			{
				$qi->Priority = 200;
			}
			$qm->moveToQueue($qi, 'collections_general');

			Update_Status(NULL,$parameters->application_id,"queued::contact::collections::customer::*root",NULL,NULL,FALSE);
		}
		else
		{
			$this->Log("Exception State_2: Failure on manual payment for {$parameters->application_id}, can't determine where to put this app!");
		}
	}
	
	// The applicant is in Hold Status - Defer the rescheduling until
	// after the status has changed.
	function State_3 ($parameters) 
	{
		
	}
	
	// The applicant is in Second Tier.  Do nothing.
	function State_4 ($parameters) 
	{
		
	}
	
	// We are in made arrangements status, but the most recent failure is NOT 
	// part of the arrangements.
	// We should add a followup to the application.
	function State_6($parameters) 
	{
		require_once(CUSTOMER_LIB."/arrangements_dfa.php");
		
		if (!isset($dfas['arrangements'])) 
		{
			$dfa = new Arrangement_DFA($this->server);
			$dfa->SetLog($parameters->log);
			$dfas['arrangements'] = $dfa;
		} 
		else 
		{
			$dfa = $dfas['arrangements'];
		}

		$dfa->run($parameters);
	}
	
	function State_11($parameters) 
	{
		$agent_id = Fetch_Default_Agent_ID();
		$application = ECash::getApplicationById($parameters->application_id);
		$affiliations = $application->getAffiliations();
		$comments = $application->getComments();
		$affiliations->expireAll();
		$comments->add("Scheduled Debt Consolidation payment failed. Follow up with customer / debt consolidation company", $agent_id);
		$qm = ECash::getFactory()->getQueueManager();
			
		$qi = $qm->getQueue('action_queue')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($qi, 'action_queue');
	}
	
	function State_12($parameters) 
	{
		$db = ECash::getMasterDb();
		try 
		{
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$application = ECash::getApplicationById($parameters->application_id);
			$affiliations = $application->getAffiliations();
			$affiliations->expireAll();
			
			if (!$this->has_type($parameters, 'full_balance', 'schedule')) 
			{
				Update_Status(null, $parameters->application_id,
					array('queued','contact','collections','customer','*root'));
				// Removed for escalation #26062
				Schedule_Full_Pull($parameters->application_id);
			} 
			else 
			{
				if ($parameters->status->num_qc < 2 && ECash::getConfig()->USE_QUICKCHECKS === TRUE) 
				{
					Update_Status(null, $parameters->application_id,
						array('ready','quickcheck','collections','customer','*root'));
				} 
				else 
				{
					Update_Status(null, $parameters->application_id,
									array('pending','external_collections','*root'));
				}
			}
			$db->commit();
		} 
		catch (Exception $e) 
		{

			$this->Log(__METHOD__.": Unable to process final debt consolidation failure.");
			$db->rollBack();
			throw $e;
		}
	}
}
?>
