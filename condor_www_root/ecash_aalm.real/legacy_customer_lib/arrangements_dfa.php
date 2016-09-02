<?php
/*
 * This DFA is meant to handle failures for transactions that are
 * non-ACH and not QuickCheck related.
 *
 */

require_once('dfa.php');

require_once(SQL_LIB_DIR."comment.func.php");
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(LIB_DIR . "/business_time.class.php");

class Arrangement_DFA extends DFA
{
	const NUM_STATES = 18;

	function __construct()
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(4,5,6,7,10,11,12,16,17);
		$this->initial_state = 0;
		$this->tr_functions = array( 
				 0 => 'most_recent_failure_is_arrangement',
				 1 => 'most_recent_failure_last_status',
				 2 => 'arrangements_include_pending',
				 3 => 'has_fatal_ach',
				 8 => 'in_qc_arrangements',
				 9 => 'number_of_qcs',
				13 => 'last_quickcheck_fatal',
				14 => 'quickcheck_pending',
				15 => 'has_pending_transactions'
			);

		$this->transitions = array(
				  0 => array(0 =>  1, 1 =>  8),
				  1 => array('complete' =>  4, 'pending' => 2, 'failed' => 5),
				  2 => array(0 =>  4, 1 =>  5),
				  8 => array(0 =>  3, 1 => 14),
				  3 => array(0 => 15, 1 =>  7),
				 14 => array(0 =>  9, 1 => 12),
				  9 => array(0 => 17, 1 => 13, 2 => 11),
				 13 => array(0 => 10, 1 => 11),
				 15 => array(0 =>  6, 1 => 16)
				);
		parent::__construct();
	}

	/* Helper Functions go here */

	function number_of_qcs($parameters) 
	{
		if ($parameters->status->num_qc > 2) return 2;
		if ($parameters->status->num_qc < 0) return 0;

		return $parameters->status->num_qc;
	}

	function last_quickcheck_fatal($parameters) 
	{
		$return_code = $parameters->status->quickchecks[$parameters->status->num_qc - 1]->ach_return_code_id;
		
		if ($parameters->arc_map[$return_code]['is_fatal'] == 'yes') 
		{
			return 1;
		} 
		else 
		{
			return 0;
		}
	}
	
	function quickcheck_pending($parameters) 
	{
		$status = $parameters->status->quickchecks[$parameters->status->num_qc - 1]->status;
		
		if (in_array($status, array('new', 'pending'))) 
		{
			return 1;
		} 
		else 
		{
			return 0;
		}
	}
	
	function in_qc_arrangements($parameters) 
	{
		// If we're not using QuickChecks, disable QC Related activities
		if(eCash_Config::getInstance()->USE_QUICKCHECKS === FALSE) return 0;
		
		if (($parameters->level1 == 'quickcheck' && $parameters->level0 == 'arrangements') 
			|| ($this->number_of_qcs($parameters) > 0))
		{
			return 1;
		} 
		else 
		{
			return 0;
		}
	}

	function most_recent_failure_is_arrangement($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		
		return (bool)($e->context == 'arrangement' || $e->context == 'partial');
	}
	
	function in_arrangements($parameters) 
	{
		$cs = Fetch_Application_Status($parameters->application_id);
		
		return (bool)("current" == $cs["level0"] && "arrangements" == $cs["level1"] && "collections" == $cs["level2"] && "customer" == $cs["level3"] && "*root" == $cs["level4"]);
	}

	function has_fatal_ach($parameters) 
	{
		foreach ($parameters->status->fail_set as $f) 
		{
			//TODO: Replace with method call to get fatals
			if (in_array($f->return_code, array('R02', 'R05', 'R07', 'R08', 'R10', 'R16', 'R29', 'R38', 'R51', 'R52')))
				return 1;
		}
		
		return 0;
	}

	function num_ach_failures($parameters) 
	{
		$failed_events = array();
		foreach ($parameters->schedule as $e) 
		{
			if($e->clearing_type === 'ach' && $e->status === 'failed')
			{
				$failed_events[] = $e->event_schedule_id;
			}
		}
		
		$failed_events = array_unique($failed_events);
		
		return count($failed_events);
	}
	
	function most_recent_failure_last_status($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		$status = Grab_Transactions_Previous_Status($e->transaction_register_id);
		
		switch ($status) 
		{
			case 'complete':
				return 'complete';
			case 'new':
			case 'pending':
				return 'pending';
			default:
		}
	}
	
	function arrangements_include_pending($parameters) 
	{
		return Application_Flag_Exists($parameters->application_id, 'arr_incl_pend');
	}
	
	function fail_arrangement_discount($parameters) 
	{
		$discounts = array();
		//get_log('scheduling')->Write(print_r($parameters->schedule, true));
		foreach ($parameters->schedule as $e) 
		{
			if (($e->context == 'arrangement' || $e->context == 'partial') && 
			  (in_array($e->type, array('adjustment_internal', 'adjustment_internal_fees', 'adjustment_internal_princ')))) {
			  	if ($e->status == 'scheduled') 
				{
			  		Record_Scheduled_Event_To_Register_Pending($e->date_event, $parameters->application_id, $e->event_schedule_id);
			  		Record_Event_Failure($parameters->application_id, $e->event_schedule_id);
			  	} 
				elseif ($e->status != 'failed') 
				{
					Record_Transaction_Failure($parameters->application_id, $e->transaction_register_id);
			  	}
			}
		}
	}
	
	function has_pending_transactions($parameters) 
	{
		foreach($parameters->schedule as $e) 
		{
			if($e->status == 'pending')
				return 1;
		}
		return 0;
	}

	/* These are the end states for the state machine */
	
	// We are in made arrangements status, but the most recent failure is NOT 
	// part of the arrangements.
	// We should add a followup to the application.
	function State_4($parameters) 
	{
		//Add followup comment to indicate that the account balance has changed
		$agent_id = Fetch_Default_Agent_ID();
		Update_Status(null, $parameters->application_id, 'current::arrangements::collections::customer::*root', NULL, NULL, FALSE);
		
		$comment = "This customer has had an ach fail that was not part of the arrangement. The outstanding balance has been increased and arrangements must be renegotiated.";
		$this->createNewAgentAffiliation($parameters, $comment);
	}

	// We are in made arrangements status, the most recent failure is NOT 
	// part of the arrangements, the transaction failure is from a manual fail,
	// and the arrangements set include pending. We do absolutely nothing.
	function State_5($parameters) 
	{
	}
	
	// Situation: The customer is in the 'Made Arrangements' status, and none of the
	//            returns has a fatal code.
	function State_6($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		try 
		{
			$db->beginTransaction();
			
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$this->fail_arrangement_discount($parameters);

			$db->commit();

			// 0 - 1 Failures, Collections New, 1+ Failures, Collections Contact
			if($this->num_ach_failures($parameters) <= 1)
			{
				Update_Status(null, $parameters->application_id, array('new','collections','customer','*root'), NULL, NULL, false);
			}
			else
			{
				Update_Status(null, $parameters->application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, false);
			}
			
			$db->beginTransaction();
					   
			$qm = ECash::getFactory()->getQueueManager();
			$qm->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($parameters->application_id));
			$this->createNewAgentAffiliation($parameters);

			$db->commit();
			
			Remove_Standby($parameters->application_id);
			
			if (!$this->in_qc_arrangements($parameters)) 
			{
				// Per #16663, The customer does not want any Full Pulls to go out.
				// This standby is read in by the CompleteAgentAffiliationExpirationActions
				// nightly event and will execute a full pull after the agent affiliation expires.
				//
				//Set_Standby($parameters->application_id, 'arrangement_failed');
			}

		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to add set failed arrangement processing.");
			if ($db->InTransaction)
			{
				$db->rollBack();
			}
			throw $e;
		}
	}
	
	// Situation: One of the returns came back with a fatal return code.
	// Action:    Immediately move the customer to "Collections/Contact" for their
	//            'one shot' contact try.
	function State_7($parameters) 
	{
		$application_id = $parameters->application_id;
		
		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($application_id);
			$this->fail_arrangement_discount($parameters);

			$db->commit();
			Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
			
		 	/**
			 * Add the account to the Collections General Queue
			 * Ordered first by Fatal, then Non-Fatal
			 * 1 - Fatal
			 * 0 - Non-Fatal
			 */
			$qm = ECash::getFactory()->getQueueManager();
			$qi = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
			$qi->Priority = 200;
			$qm->moveToQueue($qi, 'collections_general');

			Remove_Standby($application_id);
			
			// If we're not using QuickChecks, disable QC Related activities
			if(eCash_Config::getInstance()->USE_QUICKCHECKS === TRUE)
			{
				Set_Standby($application_id, 'qc_ready');
			}

		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to queue app and standby it for qcready.");
			if ($db->InTransaction)
			{
				$db->rollBack();
			}
			throw $e;
		}
	}
	
	// Situation: Arrangement failed with a non-fatal return on the 1st quick check
	function  State_10($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();

			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$this->fail_arrangement_discount($parameters);

			$db->commit();

			Update_Status(null, $parameters->application_id,
					   array('return','quickcheck','collections','customer','*root'), NULL, NULL, false);

			$db->beginTransaction();

			$qm = ECash::getFactory()->getQueueManager();
			$qm->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($parameters->application_id));
			$this->createNewAgentAffiliation($parameters);
			
			$db->commit();

			Remove_Standby($parameters->application_id);
			Set_Standby($parameters->application_id, '3_day_return_queue');

		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to queue app and standby it for qc return processing." . $e->getMessage() );
			if ($db->InTransaction)
			{
				$db->rollBack();
			}
			throw $e;
		}
	}
	
	// Situation: Arrangement failed with a fatal return on the 1st quick check or a 2nd quick check
	function State_11($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();

			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$this->fail_arrangement_discount($parameters);

			$db->commit();

			Update_Status(null, $parameters->application_id,
					   array('return','quickcheck','collections','customer','*root'), NULL, NULL, false);

			$db->beginTransaction();

			$qm = ECash::getFactory()->getQueueManager();
			$qm->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($parameters->application_id));
			$this->createNewAgentAffiliation($parameters);

			$db->commit();

			Remove_Standby($parameters->application_id);
			Set_Standby($parameters->application_id, '5_day_return_queue');

		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to queue app and standby it for qc return processing." . $e->getMessage() );
			if ($db->InTransaction)
			{
				$db->rollBack();
			}
			throw $e;
		}
	}
	
	// Situation: Arrangement failed with a pending quick check.
	function State_12($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		try 
		{
			$db->beginTransaction();
			
			Update_Status(null, $parameters->application_id,
					   array('sent','quickcheck','collections','customer','*root'), NULL, NULL, false);
			
			$qm = ECash::getFactory()->getQueueManager();
			$qm->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($parameters->application_id));
			
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			
			$this->fail_arrangement_discount($parameters);
	
			Remove_All_Agent_Affiliations($parameters->application_id);
			$this->createNewAgentAffiliation($parameters);
			
			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to dequeue app and add agent affiliation.");
			$db->rollBack();
			throw $e;
		}
	}
	
	// Situation: The customer is in the 'Made Arrangements' status, and none of the
	//            returns has a fatal code, but there are still pending transactions
	// Action: Notify the Agent by setting the date next contact and let the pending 
	//         transactions run their course before any actions are to occur.
	function State_16($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$this->fail_arrangement_discount($parameters);

			$db->beginTransaction();
			
			$this->createNewAgentAffiliation($parameters);

			$db->commit();

			Remove_Standby($parameters->application_id);

		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to set agent affiliation." . $e->getMessage() );
			if ($db->InTransaction)
			{
				$db->rollBack();
			}
			throw $e;
		}
	}

	function State_17($parameters)
	{
	
		Update_Status(null, $parameters->application_id,
					   array('ready','quickcheck','collections','customer','*root'), NULL, NULL, false);

		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();
			
			$qm = ECash::getFactory()->getQueueManager();
			$qm->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($parameters->application_id));
			
			$this->fail_arrangement_discount($parameters);
			
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$this->createNewAgentAffiliation($parameters);

			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to dequeue app and add agent affiliation.");
			$db->rollBack();
			throw $e;
		}		
	}

	private function createNewAgentAffiliation($parameters, $comment = NULL)
	{
		$application = ECash::getApplicationById($parameters->application_id);
		$affiliations = $application->getAffiliations();
		$currentAffiliation = $affiliations->getCurrentAffiliation('collections', 'owner');
		
		if(!empty($currentAffiliation))
		{
			$agent = $currentAffiliation->getAgent();
			$normalizer= new Date_Normalizer_1(new Date_BankHolidays_1());
			$date_expiration = $normalizer->advanceBusinessDays(time(), 2);
			$agent->getQueue()->insertApplication($application, 'broken_arrangements', $date_expiration, time());
			$affiliations->add($agent, 'collections', 'owner', $date_expiration);
		}
		
		if(empty($comment))
			$comment = "Arrangements have been broken on this account.";
			
		if(!empty($agent))
			$agent_id = $agent->getAgentId();
		else
			$agent_id = Fetch_Default_Agent_ID();
			
		$comments = $application->getComments();
		$comments->add($comment, $agent_id);
	//		Follow_Up::createCollectionsFollowUp($parameters->application_id, date("Y-m-d H:i:s", time() + 60), $agent_id, $parameters->company_id, "Arrangements have been broken on this account.", $date_expiration, 'broken_arrangements');
	}

}
?>
