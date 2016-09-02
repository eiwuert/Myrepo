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
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "fetch_card_return_code_map.func.php");

class Arrangement_DFA extends DFA
{
	const NUM_STATES = 18;

	function __construct($server)
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(4,5,7,10,11,12,16, 8, 9, 10, 11, 12);
		$this->initial_state = 0;
		$this->tr_functions = array( 
				 0 => 'most_recent_failure_is_arrangement',
				 1 => 'most_recent_failure_last_status',
				 2 => 'arrangements_include_pending',
				 3 => 'has_fatal_ach',
				 6 => 'decide_collections_process',
				15 => 'has_pending_transactions',
			);

		$this->transitions = array(
				  0 => array(0 =>  1, 1 =>  3),
				  1 => array('complete' =>  4, 'pending' => 2, 'failed' => 5),
				  2 => array(0 =>  4, 1 =>  5),
				  3 => array(0 => 15, 1 =>  7),
				  6 => array('general' => 8, 'rework' => 9, 'my_queue' => 10, 'second_tier' => 11, 'other' => 12),
				 15 => array(0 =>  6, 1 => 16),
				);
		parent::__construct();

		$this->server = $server;
	}

	function most_recent_failure_is_arrangement($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		
		//Fee Assessment
		/*
                $assess_fee = FALSE;
		
		foreach ($parameters->status->fail_set as $f)
		{
			if (in_array($f->clearing_type, array('ach','card')))
			{
				$assess_fee = TRUE;
				break;
			}
		}
		*/
		if(
                        //$assess_fee
			in_array($e->clearing_type, array('ach','card'))
                        &&
                        (
			empty($parameters->rules['return_fee_max'])
			||
			(($parameters->status->ach_fee_count + 1) * $parameters->rules['return_transaction_fee'] <= $parameters->rules['return_fee_max'])
                        )
		)
		{
			if (isCardSchedule($parameters->application_id))
			{
				$payment1 = 'assess_fee_card_fail';
				$description1 = 'Card Fee Assessed';
			}
			else
			{
				$payment1 = 'assess_fee_ach_fail';
				$description1 = 'ACH Fee Assessed';
			}
			
			$date_event = date("Y-m-d");
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('fee', intval($parameters->rules['return_transaction_fee']));
			$oid = $parameters->status->fail_set[0]->transaction_register_id;
			$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, $payment1,$description1);
			Post_Event($parameters->application_id, $e);
		}
		else
		{
			$this->Log(__METHOD__.": Not adding fee - {$parameters->status->ach_fee_count} (fee count) +1 * {$parameters->rules['return_transaction_fee']} (fee amt) > {$parameters->rules['return_fee_max']} (max)");
		}
		//////
		
		return (bool)($e->context == 'arrangement' || $e->context == 'partial');
	}
	
	function in_arrangements($parameters) 
	{
		$cs = Fetch_Application_Status($parameters->application_id);
		
		return (bool)("current" == $cs["level0"] && "arrangements" == $cs["level1"] && "collections" == $cs["level2"] && "customer" == $cs["level3"] && "*root" == $cs["level4"]);
	}

	function has_fatal_ach($parameters) 
	{
		$ach_code_map = Fetch_ACH_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($ach_code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					//asm 100
					if (in_array($f->return_code, array('R05','R07','R08','R10','R29','R51')))
					{
						require_once(SQL_LIB_DIR . "do_not_loan.class.php");
						$app =  ECash::getApplicationByID($parameters->application_id);
						$ssn = $app->ssn;
						$dnl = ECash::getCustomerBySSN($ssn)->getDoNotLoan();
				
						if (!($dnl->getByCompany($dnl->getByCompany(ECash::getCompany()->company_id))))
						{
							$agent_id = ECash::getAgent()->AgentId;
							$do_not_loan_exp = "Hostile ACH return " . $options['return_code'] . " " . $options['return_description'];
							$do_not_loan_category = "other";
							$dnl->set($agent_id, $do_not_loan_exp, $do_not_loan_category);
						}
					}
					/////////
					if ($options['is_fatal'] == 'yes')
					{
						return 1;
					}
				}
			}
		}
		
		$card_code_map = Fetch_Card_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($card_code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					if ($options['is_fatal'])
					{
						return 1;
					}
				}
			}
		}
		
		return 0;
	}
	
	function num_ach_failures($parameters) 
	{
		$failed_events = array();
		foreach ($parameters->schedule as $e) 
		{
			if(($e->clearing_type === 'ach' || $e->clearing_type === 'card') && $e->status === 'failed')
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
	// Action: Count arrangements 
	// if in collections new, 2 arrangement failures will send to collections general
	//                        1 arrangement failures will send to stick in My Queue for 3 days
	// if in collections general, 2 arrangement failures will send to collections rework
	//                            1 arrangement failures will send to My Queue for 3 days
	// if in collections rework, 2 arrangement failures will send to 2nd tier
	//                           1 arrangement failure will send to My Queue for 3 days
	function decide_collections_process($parameters) 
	{
		// First we have to find the non-arrangement status change date
		$info = Get_Last_Collections_Status_Changed_Info($parameters->application_id);

		Remove_Unregistered_Events_From_Schedule($parameters->application_id);
	
		// We couldn't find a previous collections status
		if ($info == FALSE)
			return 'other';

		$date          = $info['date_created'];
		$status        = explode('::', Status_Utility::Get_Status_Chain_By_ID($info['application_status_id']));

		// We're counting failures since it first went into the last collections status
		$return_dates = array();
		foreach ($parameters->schedule as $e)
		{
			if (($e->context == 'arrangement' || $e->context == 'partial') && $e->status == 'failed')
			{
				// we're only interested in failures that occurred at or after $date
				if (strtotime($e->return_date) >= strtotime($date))
				{
					$return_dates[] = $e->return_date;
				}
			}
		}

		$arranged_failures = count(array_unique($return_dates));

		// If they're in collections new
		if ($status[0] == 'new' && $status[1] == 'collections')
		{
			if ($arranged_failures >= 2)
				return 'general';
			else if ($arranged_failures == 1)
				return 'my_queue';
			else
				return 'general';
		}
		else if (($status[0] == 'queued' || $status[0] == 'dequeued') && $status[1] == 'contact' && $status[2] == 'collections')
		{
			if ($arranged_failures >= 2)
				return 'rework';
			else if ($arranged_failures == 1)
				return 'my_queue';
			else
				return 'general';
		}
		else if ($status[0] == 'collections_rework' && $status[1] == 'collections')
		{
			if ($arranged_failures >= 2)
				return 'second_tier';
			else if ($arranged_failures == 1)
				return 'my_queue';
			else
				return 'rework';
		}
		else if ($status[0] == 'pending' && $status[1] == 'external_collections')
		{
			return 'second_tier';
		}
		else
		{
			return 'other';
		}
	}
	
	// Situation: One of the returns came back with a fatal return code.
	// Action:    Immediately move the customer to "My Queue" so agent's can
	//            make arrangements to get the balance paid.
	function State_7($parameters) 
	{
		/*
		Remove_Unregistered_Events_From_Schedule($application_id);
		$this->fail_arrangement_discount($parameters);

		Update_Status(null, $application_id, array('arrangements_failed','arrangements','collections','customer','*root'), NULL, NULL, FALSE);

		$application = ECash::getApplicationById($parameters->application_id);

		// Get the controlling agent
		$m_agent = ECash::getFactory()->getModel('AgentAffiliation');
		if ($m_agent->loadActiveAffiliation($parameters->application_id, 'collections', 'owner') == FALSE)
		{
			$agent = ECash::getAgent();
		}
		else
		{
			$agent_id = $m_agent->agent_id;
			$agent    = ECash::getAgentById($agent_id);
		}

		$reason         = 'collections';
		$date_available = time();
		$expiration     = NULL;

		// Insert it into the agent queue
		$agent->getQueue()->insertApplication($application, $reason, $expiration, $date_available);

		Remove_Standby($application_id);
		*/
		
		$application_id = $parameters->application_id;
		$db = ECash::getMasterDb();
		try 
		{
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($application_id);
			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to place account in collections.");
			$db->rollBack();
			throw $e;
		}
		
		Remove_Standby($application_id);
		
		// Send Return Letter 1 - Specific Reason - to apps which have fatal ACH returns
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		$qm = ECash::getFactory()->getQueueManager();
		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm->removeFromAllQueues($qi);
		Update_Status(null, $application_id, array('collections_rework','collections','customer','*root'), NULL, NULL, FALSE);
		$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($queue_item, 'collections_rework');
		
		$this->Log(__METHOD__.": Processed application {$application_id} as Collections Rework.");
	}
	
	// Situation: The customer is in the 'Made Arrangements' status, and none of the
	//            returns has a fatal code, but there are still pending transactions
	// Action: Notify the Agent by setting the date next contact and let the pending 
	//         transactions run their course before any actions are to occur.
	function State_16($parameters) 
	{
		$db = ECash::getMasterDb();
		
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

	// Situation: This account had an arrangements failure outside of a collections process
	// Action:    Immediately move the customer to Collections New process
	function State_12($parameters) 
	{
		$this->fail_arrangement_discount($parameters);
		
		$application_id = $parameters->application_id;
		
		$db = ECash::getMasterDb();
		
		try 
		{
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($application_id);
			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to place account in collections.");
			$db->rollBack();
			throw $e;
		}

		$qm = ECash::getFactory()->getQueueManager();
		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm->removeFromAllQueues($qi);

		Remove_Standby($application_id);

		// Send Return Letter 1 - 'Specific Reason Letter' 6.1.1.3
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		/*
		// 6.1.1.2 - Assess late fee
		$assess_fee = FALSE;
		$today = date("Y-m-d");
		foreach ($parameters->status->fail_set as $f)
		{
			if (in_array($f->clearing_type, array('ach','card')))
			{
				$assess_fee = TRUE;
				break;
			}
		}

		foreach ($parameters->schedule as $e)
		{
			if (in_array($e->type, array('assess_fee_ach_fail','assess_fee_card_fail')))
			{
				$assess_fee = FALSE;
				break;
			}
		}
		
		if ($assess_fee)
		{
			if (isCardSchedule($parameters->application_id))
			{
				$payment1 = 'assess_fee_card_fail';
				$description1 = 'Card Fee Assessed';
				$payment2 = 'payment_fee_card_fail';
				$description2 = 'Card Fee Payment';
			}
			else
			{
				$payment1 = 'assess_fee_ach_fail';
				$description1 = 'ACH Fee Assessed';
				$payment2 = 'payment_fee_ach_fail';
				$description2 = 'ACH Fee Payment';
			}
			$late_fee = $parameters->rules['return_transaction_fee'];
			$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => $parameters->rules['return_transaction_fee']));
			$event    = Schedule_Event::MakeEvent($today, $today, $amounts, $payment1,$description1);

			Post_Event($parameters->application_id, $event);

			// Generate a late fee payment
			$next_payday = Get_Next_Payday(date("Y-m-d"), $parameters->info, $parameters->rules);

			$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => -$parameters->rules['return_transaction_fee']));
			$event    = Schedule_Event::MakeEvent($next_payday['event'], $next_payday['effective'], $amounts, $payment2,$description2);

			Record_Event($parameters->application_id, $event);
		}
		*/

		// 6.1.1.1 - Change status to Collections New
		//Update_Status(null, $application_id, array('new','collections','customer','*root'), NULL, NULL, FALSE);
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);

		// part of 6.1.2 and 6.1.3 - Add to Collections New Queue. Per Jared, 10/8/2014, to Collections Contact
		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($queue_item, 'collections_general');

		//Complete_Schedule($parameters->application_id);

		$this->Log(__METHOD__.": Processed application {$application_id} as Collections Contact.");

		return 0;
	}


	// State 18
	// Situation: This account was in collections new status and had 2 non-fatal failures
	// Action:    Immediately move the customer to Collections General Process
	function State_8($parameters) 
	{
		$this->fail_arrangement_discount($parameters);

		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm = ECash::getFactory()->getQueueManager();
		$qm->removeFromAllQueues($qi);

		$application_id = $parameters->application_id;

		Remove_Standby($application_id);
		
		// Send Return Letter 3 - 'Overdue Account Letter' 6.2.1.4
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		// 6.2.1.1 - Change status to Collections Contact
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		
		// 6.2.1.5 - Add to Collections General Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 200; Not specified by spec
		$qm->moveToQueue($queue_item, 'collections_general');
		
		return 0;
	}

	
	// State 19
	// Send to collections rework
	// Situation: Collections general had 2 non-fatal returns, 1 fatal, or collections new had 1 fatal and arrangements failed
	// Action:    Immediately move the customer to Collections Rework process.
	function State_9($parameters) 
	{
		$this->fail_arrangement_discount($parameters);

		// Remove from all Queues
		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm = ECash::getFactory()->getQueueManager();
		$qm->removeFromAllQueues($qi);

		$application_id = $parameters->application_id;

		$db = ECash::getMasterDb();
		try 
		{
			$db->beginTransaction();

			// 6.3.1.3
			Remove_Unregistered_Events_From_Schedule($application_id);

			$db->commit();
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to place account in collections.");
			$db->rollBack();
			throw $e;
		}
		
		Remove_Standby($application_id);
		
		// Send Return Letter 4 - 'Final Notice Letter' 6.3.1.2
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_4_FINAL_NOTICE', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		// 6.3.1.1 - Change status to Collections Rework
		Update_Status(null, $parameters->application_id, array('collections_rework','collections','customer','*root'), NULL, NULL, FALSE);
		
		// 6.3.1.4 - Add to Collections Rework Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($queue_item, 'collections_rework');

		return 0;		
	}

	// Send to my queue
	// Situation: Non-fatal arrangement failure in any collections process will put it in the agent's my queue for 3 days
	// Action:    Immediately move the customer to the current agent's My Queue
	function State_10($parameters) 
	{
		$this->fail_arrangement_discount($parameters);

		$info = Get_Last_Collections_Status_Changed_Info($parameters->application_id);
	
		// We couldn't find a previous collections status
		if ($info == FALSE)
			return 0;

		$application     = ECash::getApplicationById($parameters->application_id);
		$previous_status = Status_Utility::Get_Status_Chain_By_ID($info['application_status_id']);

		Update_Status(null, $parameters->application_id, $previous_status, NULL, NULL, FALSE);	

		// Get the controlling agent
		$m_agent = ECash::getFactory()->getModel('AgentAffiliation');
		if ($m_agent->loadActiveAffiliation($parameters->application_id, 'collections', 'owner') == FALSE)
		{
			$agent = ECash::getAgent();
		}
		else
		{
			$agent_id = $m_agent->agent_id;
			$agent    = ECash::getAgentById($agent_id);
		}

		$reason         = 'collections';
		$date_available = time();
		$expiration     = NULL;

		// Remove it from all queues first
		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm = ECash::getFactory()->getQueueManager();
		$qm->removeFromAllQueues($qi);


		// Insert it into the agent queue
		$agent->getQueue()->insertApplication($application, $reason, $expiration, $date_available);


		return 0;		
	}

	// Send to 2nd tier collections
	// Situation: 2 non-fatal arrangement failures for collections rework process
	// or previous status is 2nd Tier Pending
	function State_11($parameters)
	{
		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm = ECash::getFactory()->getQueueManager();
		$qm->removeFromAllQueues($qi);

		$this->fail_arrangement_discount($parameters);

		// Set their status to 2nd tier pending
		Update_Status(null, $parameters->application_id, array('pending','external_collections','*root'), NULL, NULL, FALSE);

		Remove_Standby($parameters->application_id);

		return 0;
	}


}
?>
