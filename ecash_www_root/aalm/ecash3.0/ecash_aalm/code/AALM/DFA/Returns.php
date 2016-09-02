<?php

class AALM_DFA_Returns extends ECash_DFA_Returns
{
	protected $num_states = 67;

	public function __construct($server)
	{
		//do this first so we can override
		parent::__construct($server);
		
		//ugly
		$this->final_states = array_flip(array_merge(array_keys($this->final_states), array(55, 56, 57, 58, 59, 63, 64, 65, 66)));
		
		$this->tr_functions[51] = 'check_to_assess_fees';
		$this->tr_functions[52] = 'most_recent_failure_is_arrangement';
		$this->tr_functions[53] = 'fail_arrangement_discount';
		$this->tr_functions[54] = 'decide_collections_process';
		$this->tr_functions[60] = 'check_payout';
		$this->tr_functions[61] = 'is_reattempted_reattempt';
		$this->tr_functions[62] = 'get_collections_process';
		$this->tr_functions[67] = 'has_fatal_ach';
		$this->tr_functions[68] = 'has_fatal_ach_flag';

		$this->transitions[5] = array( 0 => 51, 1 =>  4);
		$this->transitions[15] = array( 0 => 19, 1 => 41);
		$this->transitions[17] = array( 0 => 38, 1 => 59);
		$this->transitions[19] = array( 0 => 17, 1 => 59);
		$this->transitions[38] = array(0 => 60, 1 => 39);
		$this->transitions[35] = array(0 => 59);
		$this->transitions[60] = array(0 => 61, 1 => 39);
		$this->transitions[61] = array(0 => 62);
		$this->transitions[40] = array( 0 => 59, 1 => 35);
		$this->transitions[51] = array( 0 => 52 );
		$this->transitions[52] = array( 0 =>  15, 1 => 53);
		$this->transitions[53] = array( 0 => 67);		
		$this->transitions[54] = array('my_queue' => 55, 'other' => 56, 'general' => 57, 'rework' => 58);
		$this->transitions[62] = array('rework' => 59, 'general' => 64, 'none' => 4, 'none_reatt' => 65, 'past_due' => 66, 'new' => 63);
		$this->transitions[67] = array( 0 => 68, 1 => 41);
		$this->transitions[68] = array( 0 => 54, 1 => 59);
	}
	
	function check_to_assess_fees($parameters)
	{
		foreach($parameters->schedule as $e)
		{
			if (in_array($e->type, array('assess_fee_ach_fail','assess_fee_card_fail')))
			{
				return 0;
			}
		}
		
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
		
		$today = date('Y-m-d');

		// 6.1.1.2 - Assess late fee
		// Clarified with Jared that the late fee is the return transaction fee.
		$late_fee = $parameters->rules['return_transaction_fee'];
		$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => $parameters->rules['return_transaction_fee']));
		$event    = Schedule_Event::MakeEvent($today, $today, $amounts, $payment1,$description1);

		Post_Event($parameters->application_id, $event);

		// Generate a late fee payment
		$next_payday = Get_Next_Payday(date("Y-m-d"), $parameters->info, $parameters->rules);

		$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => -$parameters->rules['return_transaction_fee']));
		$event    = Schedule_Event::MakeEvent($next_payday['event'], $next_payday['effective'], $amounts, $payment2,$description2);

		Record_Event($parameters->application_id, $event);

		return 0;
	}

	function most_recent_failure_is_arrangement($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		
		if(in_array($e->type, array("payout","payout_principal","payout_fees","card_payout","card_payout_principal","card_payout_fees")))
		{
			foreach ($parameters->schedule as $e1) 
			{
				if (
					in_array($e1->type, array('adjustment_internal','adjustment_internal_fees','adjustment_internal_princ'))
					&& $e1->context == 'payout'
					&& $e1->status != 'failed'
				)
				{
					Record_Event_Failure($parameters->application_id, $e1->event_schedule_id);
				}
			}
		}
		
		return (bool)($e->context == 'arrangement' || $e->context == 'partial');
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
		return 0;
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
		}
		else if (($status[0] == 'queued' || $status[0] == 'dequeued') && $status[1] == 'contact' && $status[2] == 'collections')
		{
			if ($arranged_failures >= 2)
				return 'rework';
			else if ($arranged_failures == 1)
				return 'my_queue';	
		}
		else if ($status[0] == 'collections_rework' && $status[1] == 'collections')
		{
            if ($arranged_failures >= 2)
                return 'rework';
            else if ($arranged_failures == 1)
                return 'my_queue';
		}
		else
		{
			return 'other';
		}
	}

	/**
	 * Route payout failures after fatal ACH has been checked
	 */
	function check_payout($parameters) 
	{ 
		if($this->has_type($parameters, 'payout_principal')
		   || $this->has_type($parameters, 'card_payout_principal')
		)
		{
			return 1;
		}
		return 0;
	}

    function is_reattempted_reattempt($parameters)
    {
        //Does this have an origin_id and a context of reattempt?  Does the transaction it's reattempting also have one?
        //Uh-oh!
        foreach ($parameters->status->fail_set as $f)
        {
            //Reattempts have an origin_id (because they originated from another transaction
            //Reattempts also have a context of reattempt!
            if ($f->origin_id != null && $f->context == 'reattempt')
            {
                foreach ($parameters->status->posted_schedule as $e)
                {
                    if ($e->transaction_register_id == $f->origin_id && $e->origin_id != null && $e->context == 'reattempt')
                    {
						$parameters->reattempted_reattempt_failure = true;
                        return 0;
                    }
                }
            }
        }

		$parameters->reattempted_reattempt_failure = false;
        return 0;
    }

	// This determines the collections process route to follow
	// Acceptable return values are 'new', 'general', and 'rework'
	// The behavior of this function is described in the AALM collections
	// spec for ticket #13633
	function get_collections_process($parameters)
	{
		$application_id = $parameters->application_id;

		$trans_ids = array();

		// If they had a fatal return, send them to the rework process
		foreach ($parameters->status->fail_set as $f)
		{
			if ($f->is_fatal == 'yes')
			{
				$this->Log("There is a fatal failure in the failset, moving the application to Rework");
				return 'rework';
			}
		}
		$this->Log("No fatal failures in the return set");
		// We're only interested in failures past the date they went into this status, so 
		// get that date, and then count failures which occurred after that date which are not
		// reattempts, and that are a debit
		foreach ($parameters->schedule as $e)
		{
			// We don't want to count things already part of the fail set
			// We don't want to count reattempts
			if ($e->status == 'failed' && !in_array($e->transaction_register_id, $trans_ids) && $e->context != 'reattempt')
			{
				// If it was a debit
				if ($e->principal + $e->service_charge + $e->fee < 0)
				{
					$fail_dates[] = $e->return_date;
                    $trans_ids[]  = $e->transaction_register_id;
				}
			}

		}

		$num_failures = count(array_unique($fail_dates));

		// Only 3 different branches
		if ($num_failures > 3)
			$num_failures = 3;

		if ($parameters->level0 == 'past_due')
		{
			if ($parameters->reattempted_reattempt_failure == TRUE)
			{
				$this->Log("Application is in Past Due status with a reattempted reattempt failure");
				return 'new';
			}
			else
			{
				$this->Log("Application is in Past Due status without a reattempted reattempt failure");
				return 'none_reatt';
			}
		}

		if ($parameters->level0 == 'new')
		{
			if ($parameters->reattempted_reattempt_failure == TRUE)
			{
				$this->Log("Application is in Collections New status with a reattempted reattempt failure");
				return 'general';
			}
			else
			{
				$this->Log("Application is in Collections New status with a reattempted reattempt failure");
				return 'none_reatt';
			}
		}

		if ($parameters->level0 == 'collections_rework')
		{
			$this->Log("Is already in application Rework status.");
			return 'rework';
		}
		// full pull failure only
		foreach ($parameters->status->fail_set as $f)
		{
			if ($f->type == 'full_pull')
			{
				$this->Log("Had a failed Full Pull");
				return 'rework';
			}
		}

		if ($parameters->level0 == 'active')
		{
			$this->Log("Application is in Active status, has no fatals, no full pulls, and no reattempted reattempts");
			return 'past_due';
		}

		$this->Log("Not in active,collections new, collections general, past due, active status. Currently in {$parameters->level0} status.  Has {$num_failures} failures. And no fatal returns.  THIS SHOULD NOT BE HAPPENING!!!!!!");
		return 'none';
	}

	// previous_status
	// Send to my queue
	// Situation: Non-fatal arrangement failure in any collections process will put it in the agent's my queue for 3 days
	// Action:    Immediately move the customer to the current agent's My Queue
	function State_55($parameters) 
	{

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

	// collections_new
	// Situation: This account had an arrangements failure outside of a collections process
	// Action:    Immediately move the customer to Collections New process
	function State_56($parameters) 
	{
		
		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm = ECash::getFactory()->getQueueManager();
		$qm->removeFromAllQueues($qi);

		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		$today = date("Y-m-d");
		$application_id = $parameters->application_id;

		Remove_Standby($application_id);

		// Send Return Letter 1 - 'Specific Reason Letter' 6.1.1.3
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		// 6.1.1.2 - Assess late fee
		$assess_fee = FALSE;
		
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

		// 6.1.1.1 - Change status to Collections New
		Update_Status(null, $application_id, array('new','collections','customer','*root'), NULL, NULL, FALSE);

		// part of 6.1.2 and 6.1.3 - Add to Collections New Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
		//		$queue_item->Priority = 200; Not in spec
		$qm->moveToQueue($queue_item, 'collections_new');

		Complete_Schedule($parameters->application_id);

		$this->Log(__METHOD__.": Processed application {$application_id} as Collections New.");

		return 0;
	}

	// collections_contact
	// Situation: This account was in collections new status and had 2 non-fatal failures
	// Action:    Immediately move the customer to Collections General Process
	function State_57($parameters) 
	{

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

		// 6.2.1.3 - Schedule Full Pull on next Payment Due Date
		// Get their next due date
		$data = Get_Transactional_Data($application_id);
		$info  = $data->info;
		$rules = $data->rules;

		$paydates      = Get_Date_List($data->info, date('m/d/Y'), $data->rules, 10, NULL, NULL);
	
	    while(strtotime($paydates['event'][0]) < strtotime(date('Y-m-d')))
	    {
	        array_shift($paydates['event']);
	        array_shift($paydates['effective']);
	    }

		$next_action   = date('m/d/Y', strtotime($paydates['event'][0]));
		$next_due_date = date('m/d/Y', strtotime($paydates['effective'][0]));

	// Removed for escalation #26062
		//Schedule_Full_Pull($application_id, NULL, NULL, $next_action, $next_due_date);
		
		return 0;
	}

	// collections_rework
	// Send to collections rework
	// Situation: Collections general had 2 non-fatal returns, 1 fatal, or collections new had 1 fatal and arrangements failed
	// Action:    Immediately move the customer to Collections Rework process.
	function State_58($parameters) 
	{

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


	// send_to_collections_rework
	// Situation: One of the returns came back with a fatal return code.
	// Action:    Immediately move the customer to Collections Rework process.
	// 6.3.1
	function State_59($parameters) 
	{
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
		
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		// 6.3.1.1 - Change status to Collections Rework
		Update_Status(null, $application_id, array('collections_rework','collections','customer','*root'), NULL, NULL, FALSE);
		
		// 6.3.1.4 - Add to Collections Rework Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 200; Not specified by spec
		$qm->moveToQueue($queue_item, 'collections_rework');

		return 0;		
	}

	// send_to_collections_new
	// Situation: This account has had its 1nd ACH Debit Return
	// Action:    Immediately move the customer to Collections New Process
	// 6.1.1
	// Moved ACH fee to different place
	function State_63($parameters) 
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		$today = date("Y-m-d");
		$application_id = $parameters->application_id;

		Remove_Standby($application_id);
		

		// 6.1.1.1 - Change status to Collections New
		Update_Status(null, $application_id, array('new','collections','customer','*root'), NULL, NULL, FALSE);
		

		// Get their next due date
        $data = Get_Transactional_Data($application_id);
        $info  = $data->info;
        $rules = $data->rules;

		// Schedule Reattempts
		foreach ($parameters->status->fail_set as $f)
		{
			$ogid = -$f->transaction_register_id;
			$reattempt = TRUE;
			foreach($parameters->schedule as $s)
			{
				if(($s->origin_id && $f->transaction_register_id == $s->origin_id) || $f->is_fatal == 'yes')
				{
					$reattempt = FALSE;
				}
			}
			if($reattempt)
			{
				if ($f->context == 'reattempt')
				{
					$date_pair = $this->getAdditionalReturnDate($parameters);

					if ($date_pair != NULL && !empty($date_pair))
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
				}
				else
				{
					$date_pair = $this->getFirstReturnDate($parameters);

					if ($date_pair != NULL && !empty($date_pair))
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
				}
			}
			else
			{
				$this->Log("Skipping reattempt ({$f->transaction_register_id}). One already exists, or return was fatal.");
			}
		}
			
		// part of 6.1.2 and 6.1.3 - Add to Collections New Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($queue_item, 'collections_new');

		$this->Log(__METHOD__.": Processed application {$application_id} as Collections New.");
		
		alignActionDateForCard($parameters->application_id);
		
		return 0;
	}

	// send_to_collections_general
	// Situation: This account has had its 2nd ACH Return
	// Action:    Immediately move the customer to Collections General Process
	// 6.2.1
	function State_64($parameters) 
	{
		$application_id = $parameters->application_id;

		Remove_Standby($application_id);
		
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		// Send Return Letter 3 - 'Overdue Account Letter' 6.2.1.4

		// 6.2.1.1 - Change status to Collections Contact
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		
		// 6.2.1.5 - Add to Collections General Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 200; Not specified by spec
		$qm->moveToQueue($queue_item, 'collections_general');

		// 6.2.1.3 - Schedule Full Pull on next Payment Due Date
		// Get their next due date
		$data = Get_Transactional_Data($application_id);
		$info  = $data->info;
		$rules = $data->rules;

		$paydates      = Get_Date_List($data->info, date('m/d/Y'), $data->rules, 10, NULL, NULL);
	
	    while(strtotime($paydates['event'][0]) < strtotime(date('Y-m-d')))
	    {
	        array_shift($paydates['event']);
	        array_shift($paydates['effective']);
	    }
	
		// If $paydates['event'] == date('Y-m-d'), and the batch has closed, shift one more time
		if (strtotime($paydates['event'][0]) == strtotime(date('Y-m-d')) && Has_Batch_Closed($this->server->company_id))
		{
	        array_shift($paydates['event']);
	        array_shift($paydates['effective']);
	    }

		$next_action   = date('m/d/Y', strtotime($paydates['event'][0]));
		$next_due_date = date('m/d/Y', strtotime($paydates['effective'][0]));

		// Removed for escalation #26062
		//Schedule_Full_Pull($application_id, NULL, NULL, $next_action, $next_due_date);
		
		return 0;
	}

	//reattempt_failures
	function State_65($parameters) 
	{
		foreach ($parameters->status->fail_set as $f)
		{
			$ogid = -$f->transaction_register_id;
			$reattempt = TRUE;
			foreach($parameters->schedule as $s)
			{
				if(($s->origin_id && $f->transaction_register_id == $s->origin_id) || $f->is_fatal == 'yes')
				{
					$reattempt = FALSE;
				}
			}
			if($reattempt)
			{
				if ($f->context == 'reattempt')
				{
					$date_pair = $this->getAdditionalReturnDate($parameters);

					if ($date_pair != NULL && !empty($date_pair))
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
				}
				else
				{
					$date_pair = $this->getFirstReturnDate($parameters);

					if ($date_pair != NULL && !empty($date_pair))
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
				}
			}
			else
			{
				$this->Log("Skipping reattempt ({$f->transaction_register_id}). One already exists, or return was fatal.");
			}
		}
		
		alignActionDateForCard($parameters->application_id);
	}

	//past_due
	function State_66($parameters) 
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		$today = date("Y-m-d");
		$application_id = $parameters->application_id;

		// Send Return Letter 1 - 'Specific Reason Letter' 6.1.1.3
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		Remove_Standby($application_id);
		
		// 6.1.1.1 - Change status to Collections New
		Update_Status(null, $application_id, array('past_due','servicing','customer','*root'), NULL, NULL, FALSE);
		

		// Get their next due date
        $data = Get_Transactional_Data($application_id);
        $info  = $data->info;
        $rules = $data->rules;

		foreach ($parameters->status->fail_set as $f)
		{
			$ogid = -$f->transaction_register_id;
			$reattempt = TRUE;
			foreach($parameters->schedule as $s)
			{
				if(($s->origin_id && $f->transaction_register_id == $s->origin_id) || $f->is_fatal == 'yes')
				{
					$reattempt = FALSE;
				}
			}
			if($reattempt)
			{
				if ($f->context == 'reattempt')
				{
					$date_pair = $this->getAdditionalReturnDate($parameters);

					if ($date_pair != NULL && !empty($date_pair))
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
				}
				else
				{
					$date_pair = $this->getFirstReturnDate($parameters);

					if ($date_pair != NULL && !empty($date_pair))
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
				}
			}
			else
			{
				$this->Log("Skipping reattempt ({$f->transaction_register_id}). One already exists, or return was fatal.");
			}
		}

			
		// part of 6.1.2 and 6.1.3 - Add to Collections New Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($queue_item, 'collections_new');

		$this->Log(__METHOD__.": Processed application {$application_id} as Past Due.");
		
		alignActionDateForCard($parameters->application_id);
	
		return 0;
	}
	
}
