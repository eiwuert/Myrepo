<?php
/* If you're editing this file, please check aalm ticket #13633
 * That's what this is built off with some undocumented exceptions
 * for funding failed and whatnot that were not, and will never
 * be properly addressed in a spec.
 */

require_once('dfa.php');
require_once('dfa_ach_returns.php');
require_once(SQL_LIB_DIR . "agent_affiliation.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR."comment.func.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "app_flags.class.php");
require_once (LIB_DIR . "/Document/Document.class.php");

class ReschedulingDFA extends ReschedulingDFABase
{
	const NUM_STATES = 51;

	function __construct($server)
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(3,4,7,23,25,26,27,31,32,36,37,39,44,45,46, 47, 53, 54, 65, 66, 67, 68, 69);
		$this->tr_functions = array(
			  0 => 'optionally_queue_application',
			 35 => 'is_in_holding_status',
			  1 => 'has_balance',
			  2 => 'most_recent_failure_is_arrangement', 
			  3 => 'funding_failed',
			  4 => 'do_nothing',
			  5 => 'has_credits',
			  6 => 'is_disbursement', 
			  8 => 'fail_arrangement_discount',
			 15 => 'has_fatal_ach',
			 17 => 'has_fullpull',
			 18 => 'is_first_return', 
			 20 => 'is_at_return_limit', 
			 37 => 'do_nothing',
			 38 => 'check_failures',
			 39 => 'no_harm_no_foul',
			 40 => 'has_fatal_ach_current_bank_account',
			 41 => 'set_flag_had_fatal_ach_failure',
			 42 => 'set_flag_has_fatal_ach_failure',
			 43 => 'get_collections_process',
			 44 => 'send_to_collections_new',
			 45 => 'send_to_collections_general',
			 46 => 'send_to_collections_rework',
			 47 => 'do_nothing',
			 51 => 'check_to_assess_fees',
			 52 => 'is_reattempted_reattempt',
			 53 => 'reattempt_failures',
			 54 => 'past_due',
			 55 => 'check_payout',
			 60 => 'has_fatal_ach',
			 64 => 'decide_collections_process',
			 65 => 'previous_status',
			 67 => 'collections_new',
			 68 => 'collections_contact',
			 69 => 'collections_rework'
			 );

		$this->transitions = array ( 
			  0 => array( 0 => 35 ),
			 35 => array( 0 =>  6, 1 => 32, 2 => 7, 3 => 23),// 1 - Hold, 2 - 2nd Tier, 3 - Watch
			  1 => array( 0 =>  37, 1 =>  5),
			  2 => array( 0 =>  15, 1 => 8),
			  8 => array( 0 => 60),
			  60 => array(0 => 64, 1 => 41),
			  64 => array('my_queue' => 65, 'other' => 67, 'general' => 68, 'rework' => 69),
			  5 => array( 0 => 51, 1 =>  4),
			 38 => array(0 => 55, 1 => 39),
			  6 => array( 0 =>  1, 1 =>  3),
			 15 => array( 0 => 17, 1 => 41), // decide: Was there a fatal ach in our history?
			 17 => array( 0 => 38, 1 => 46),
			 18 => array( 0 => 20, 1 => 31),
			 20 => array( 0 => 26, 1 => 27),					  
			 40 => array( 0 => 46, 1 => 42), // decide: was there a fatal on our current account?
			 41 => array( 0 => 40), // operation: set had fatal ach failure flag
			 42 => array( 0 => 46), // operation: set has fatal ach failure flag
			 43 => array( 'new' => 44, 'general' => 45, 'rework' => 46, 'none' => 47, 'none_reatt' => 53, 'past_due' => 54),
			 51 => array( 0 => 2 ),
			 52 => array( 0 => 43 ),
			 55 => array( 0 => 52, 1 => 39),
			 ); 
			
		$this->server = $server;			

		parent::__construct($server);
	}

	function optionally_queue_application($parameters)
	{
		return 0;
	}
	
	// First define the functions needed to make state transitions

	/**
	 * If the application is in a Hold Status (Watch Flag, Hold,
	 * Bankruptcy, etc... Then we want to postpone rescheduling
	 * the account.
	 */
	function is_in_holding_status($parameters) 
	{
		$application_id = $parameters->application_id;

		// If the account is in 2nd Tier
		if($this->acct_in_second_tier($parameters))
			return 2;

		// Watch Checks -- Go to 24 if true
		if($this->is_watched($parameters))
			return 3;
			
		// If the account is in Bankruptcy, Watch, Arrangements Hold, Ammortization
		if(In_Holding_Status($application_id)) 
		{
			return 1;
		}

		return 0;
	}

	function check_to_assess_fees($parameters)
	{
		foreach($parameters->schedule as $e)
		{
			if ($e->type == 'assess_fee_ach_fail')
			{
				return 0;
			}
		}
		
		$today = date('Y-m-d');

		// 6.1.1.2 - Assess late fee
		// Clarified with Jared that the late fee is the return transaction fee.
		$late_fee = $parameters->rules['return_transaction_fee'];
		$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => $parameters->rules['return_transaction_fee']));
		$event    = Schedule_Event::MakeEvent($today, $today, $amounts, 'assess_fee_ach_fail','ACH Fee Assessed');

		Post_Event($parameters->application_id, $event);

		// Generate a late fee payment
		$next_payday = Get_Next_Payday(date("Y-m-d"), $parameters->info, $parameters->rules);

		$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => -$parameters->rules['return_transaction_fee']));
		$event    = Schedule_Event::MakeEvent($next_payday['event'], $next_payday['effective'], $amounts, 'payment_fee_ach_fail','ACH Fee Payment');

		Record_Event($parameters->application_id, $event);

		return 0;
	}

	function has_balance($parameters)
	{
		$status = $parameters->status;
		if($status->posted_and_pending_total <= 0) 
		{
			return 0;
		}
		
		return 1;
	}
	

	function has_credits($parameters) 
	{
		foreach ($parameters->status->fail_set as $e) 
		{
			if (($e->principal_amount + $e->fee_amount) > 0.0) return 1;
		}
		return 0;
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
	
	function is_disbursement($parameters)
	{
		if ($this->has_type($parameters, 'loan_disbursement'))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	
	function has_fullpull($parameters) 
	{
		if ($this->has_type($parameters, 'full_balance'))
		{
			return 1;
		}
		return 0;
	}

	function is_watched($parameters) { return (($parameters->is_watched == 'yes')?1:0); }
	
	function has_failed_personal_check($parameters) 
	{ 
		return $this->has_type($parameters, 'personal_check','failures'); 
	}
	
	/**
	*  This function routes principal cancellation failures to the proper end state.
	*/
	function check_failures($parameters) 
	{ 
		if($this->has_type($parameters, 'cancel_principal'))
		{
			return 1;
		}
		return 0;
	}

	/**
	 * Route payout failures after fatal ACH has been checked
	 */
	function check_payout($parameters) 
	{ 
		if($this->has_type($parameters, 'payout_principal'))
		{
			return 1;
		}
		return 0;
	}

	/**
	 */
	function has_fatal_ach($parameters) 
	{
		 
		$code_map = Fetch_ACH_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					if ($options['is_fatal'] == 'yes')
					{
						return 1;
					}
				}
			}
		}

		
		return 0;
	}

	function has_fatal_ach_current_bank_account ($parameters) 
	{
		
		if (empty($parameters->status->fail_set)) return 0;

		$code_map = Fetch_ACH_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
            foreach ($code_map as $options)
            {
                if ($options['return_code'] == $f->return_code)
                {
                    if ($options['is_fatal'] == 'yes') 
                    {
$this->log->Write($this->log_prefix . ' ' . print_r($f, true));

						if ($f->bank_account === $f->current_bank_account && $f->bank_aba === $f->current_bank_aba )
	                        return 1;
                    }
                }
            }
		}
		
		return 0;
	}

	function acct_in_second_tier($parameters) 
	{

		if (($parameters->level1 == 'external_collections') &&
		    ($parameters->level2 == '*root')) return 1;
		else return 0;
	}

	// @todo The depends on the way the customer handles
	//        ACH Fees
	function is_first_return($parameters) 
	{
		foreach ($parameters->schedule as $e) 
		{
			if ($e->type == 'assess_fee_ach_fail')
				return 0;
		}
		return 1;
	}

	function is_at_return_limit($parameters) 
	{
		$r = $parameters->rules;
		$s = $parameters->status;

		return (($s->max_reattempt_count >= $r['max_svc_charge_failures']) ? 1:0);
	}


	function most_recent_failure_is_arrangement($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		return (bool)($e->context == 'arrangement' || $e->context == 'partial');
	}
	
	function most_recent_failure_last_status($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		$status = Grab_Transactions_Previous_Status($e->transaction_register_id);
		
		switch ($status) 
		{
			case 'complete':
				return 'complete';
			default:
				return 'pending';
		}
	}
	
	function set_flag_had_fatal_ach_failure($parameters)
	{
		$this->_set_flag($parameters->application_id, 'had_fatal_ach_failure');
		return 0;
	}

	function set_flag_has_fatal_ach_failure($parameters)
	{
		$this->_set_flag($parameters->application_id, 'has_fatal_ach_failure');
		return 0;
	}

	protected function _set_flag($application_id, $flag)
	{
		$application_flags = new Application_Flags($this->server, $application_id);
		// only set it if its not set already
		if (!$application_flags->Get_Flag_State($flag)) 
		{
			$application_flags->Set_Flag($flag, Array('collections'));
		}
		return 0;
	}
	function send_ach_fatal_email($parameters) 
	{
		// Send Return Letter 1 - Specific Reason - to apps which have fatal ACH returns
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
	}

	function is_quick_check_enabled($parameters) 
	{
		if (ECash::getConfig()->USE_QUICKCHECKS === TRUE) 
			return 1;
		return 0;
	}

	function add_quickcheck_standby($parameters) 
	{
        $application_id = $parameters->application_id;
		Set_Standby($application_id, $parameters->company_id, 'qc_ready');
		return 0;
	}

	function remove_standby($parameters) 
	{
        $application_id = $parameters->application_id;
		Remove_Standby($application_id);
		return 0;
	}
		
	function remove_schedule($parameters) 
	{
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

		return 0;
	}

	function move_to_collections_new($parameters)
	{		
		Update_Status(
			NULL,
			$parameters->application_id,
			array(
				'new',
				'collections',
				'customer',
				'*root'), 
			NULL,
			NULL,
			FALSE
		);
		
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
		$queue_item->Priority = 200;
		
		$qm->moveToQueue($queue_item, 'collections_new');
		return 0;
	}

	function move_to_collections_general($parameters) 
	{			
		Update_Status(null, $parameters->application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
		$queue_item->Priority = 200;
		$qm->moveToQueue($queue_item, 'collections_general');
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
	
	
	
	
	// Here is where we define all the different responses -- the description
	// states what actions need to be taken.

	// Situation: The returns have at least one credit, and there's a 'loan disbursement'
	//            in the failure set.
	// Action:    Move the customer to 'Funding Failed' status and notify an agent.
	//            Email an agent about the funding failed.
	function funding_failed($parameters) 
	{
		$status = $parameters->verified;

		// Gather the total of all unpaired fees/scs, and adjust for it.
		$total = 0.0;
		
		$fund_date = strtotime($parameters->info->date_fund_stored);
		$balance = Fetch_Balance_Information($parameters->application_id);
		$total = $balance->total_balance;
		
		$db = ECash::getMasterDb();
		
		try 
		{
			$db->beginTransaction();
			// Remove the schedule immediately
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
	
			if ($total > 0.0) 
			{
				$today = date("Y-m-d");
				$amounts = array();

				if($balance->fee_pending > 0)
				{
					$amounts[] = Event_Amount::MakeEventAmount('fee', -$balance->fee_pending);
				}

				if($balance->service_charge_pending > 0)
				{
					$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$balance->service_charge_pending);
				}

				$e = Schedule_Event::MakeEvent($today, $today, $amounts, 'adjustment_internal',
									'Adjusting out all accrued fees due to failure.');

				Post_Event($parameters->application_id, $e);
			}
			
			$db->commit();
			
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to update transactions.");

			$db->rollBack();
			throw $e;
		}
		
		// update status
		
		try 
		{
			Update_Status(null, $parameters->application_id,  array('funding_failed','servicing','customer','*root'));
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to update status.");
			throw $e;
		}
		// Finally send the email - are we supposed to do this?
	}

	// Situation: The returns have at least one credit, and none of the credits
	//            in the returns are 'loan disbursements'.
	// Action:    Email customer service.
	function State_4($parameters) 
	{
	}

	// Situation: The customer is in 2nd Tier
	//            
	// Action:    Add the EC Delta, exit gracefully.
	function State_7($parameters) 
	{ 
		$application_id = $parameters->application_id;
		$amount = 0;

		$status = $parameters->status;
		
		// Tally up the amount of the last return set
		foreach($status->fail_set as $f) 
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
		$this->Log($parameters->application_id . ": Account is in 2nd Tier.  Adding EC Delta for \$$amount");

		// Add the failed total to the EC Delta File.
		try 
		{
			$ec = new External_Collections($this->server);
			$ec->Create_EC_Delta_From( $application_id , 0 - $amount );
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to update EC Delta.");
			throw $e;
		}
	}
	
	// Response functions below assume this as part of the situation:
	// - There are no credits in the return set.
	// - The customer has not had a Quickcheck produced for them yet.

	// Situation: The customer has the watch flag set. Preempts our other unchecked aspects.
	// Action   : Delete all of the scheduled events, remove the watcher affiliation, and place the
	//            account in the Collections/Contact queue.
	function State_23($parameters) 
	{
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

			$this->Log(__METHOD__.": Unable to queue app.");
			$db->rollBack();
			throw $e;
		}
		
//		$qm = ECash::getFactory()->getQueueManager();
//		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($application_id);
//		$queue_item->Priority = 100;
//		$qm->moveToQueue($queue_item, 'collections_general');
		
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		
		$affiliations = ECash::getApplicationById($application_id)->getAffiliations();
		$affiliations->expire('Watch', 'Owner');

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

		if ($parameters->level0 == 'active' || $parameters->level0 == 'paid')
		{
			$this->Log("Application is in Active status, has no fatals, no full pulls, and no reattempted reattempts");
			return 'past_due';
		}

		$this->Log("Not in active,collections new, collections general, past due, active status. Currently in {$parameters->level0} status.  Has {$num_failures} failures. And no fatal returns.  THIS SHOULD NOT BE HAPPENING!!!!!!");
		return 'none';
	}

	function past_due($parameters) 
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		$today = date("Y-m-d");
		$application_id = $parameters->application_id;

		// Send Return Letter 1 - 'Specific Reason Letter' 6.1.1.3
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);

		Remove_Standby($application_id);
		
		// 6.1.1.1 - Change status to Collections New
		Update_Status(null, $application_id, array('past_due','servicing','customer','*root'), NULL, NULL, FALSE);
		

		// Get their next due date
    		$data = Get_Transactional_Data($application_id);
    		$info  = $data->info;
     		$rules = $data->rules;

		$this->reattempt_failures($parameters); 	 

			
		// part of 6.1.2 and 6.1.3 - Add to Collections New Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($queue_item, 'collections_new');

		$this->Log(__METHOD__.": Processed application {$application_id} as Past Due.");
	
		return 0;
	}

	

	// Situation: This account has had its 1nd ACH Debit Return
	// Action:    Immediately move the customer to Collections New Process
	// 6.1.1
	// Moved ACH fee to different place
	function send_to_collections_new($parameters) 
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
		$this->reattempt_failures($parameters); 	 
			
		// part of 6.1.2 and 6.1.3 - Add to Collections New Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
		$qm->moveToQueue($queue_item, 'collections_new');

		$this->Log(__METHOD__.": Processed application {$application_id} as Collections New.");
	
		return 0;
	}


	// Situation: This account has had its 2nd ACH Return
	// Action:    Immediately move the customer to Collections General Process
	// 6.2.1
	function send_to_collections_general($parameters) 
	{
		$application_id = $parameters->application_id;

		Remove_Standby($application_id);
		
		
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT', $parameters->status->fail_set[0]->transaction_register_id);

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
		Schedule_Full_Pull($application_id, NULL, NULL, $next_action, $next_due_date);
		
		return 0;
	}

	
	// Situation: One of the returns came back with a fatal return code.
	// Action:    Immediately move the customer to Collections Rework process.
	// 6.3.1
	function send_to_collections_rework($parameters) 
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
		
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);

		// 6.3.1.1 - Change status to Collections Rework
		Update_Status(null, $application_id, array('collections_rework','collections','customer','*root'), NULL, NULL, FALSE);
		
		// 6.3.1.4 - Add to Collections Rework Queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 200; Not specified by spec
		$qm->moveToQueue($queue_item, 'collections_rework');

		return 0;		
	}

	
	
	// Situation: This account had an arrangements failure outside of a collections process
	// Action:    Immediately move the customer to Collections New process
	function collections_new($parameters) 
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
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);

		// 6.1.1.2 - Assess late fee
		$assess_fee = FALSE;
		
		foreach ($parameters->status->fail_set as $f)
		{
			if ($f->clearing_type == 'ach')
			{
				$assess_fee = TRUE;
				break;
			}
		}

		foreach ($parameters->schedule as $e)
		{
			if ($e->type == 'assess_fee_ach_fail')
			{
				$assess_fee = FALSE;
				break;
			}
		}
		
		if ($assess_fee)
		{
			$late_fee = $parameters->rules['return_transaction_fee'];
			$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => $parameters->rules['return_transaction_fee']));
			$event    = Schedule_Event::MakeEvent($today, $today, $amounts, 'assess_fee_ach_fail','ACH Fee Assessed');

			Post_Event($parameters->application_id, $event);

			// Generate a late fee payment
			$next_payday = Get_Next_Payday(date("Y-m-d"), $parameters->info, $parameters->rules);

			$amounts  = AmountAllocationCalculator::generateGivenAmounts(array('fee' => -$parameters->rules['return_transaction_fee']));
			$event    = Schedule_Event::MakeEvent($next_payday['event'], $next_payday['effective'], $amounts, 'payment_fee_ach_fail','ACH Fee Payment');

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


	// State 18
	// Situation: This account was in collections new status and had 2 non-fatal failures
	// Action:    Immediately move the customer to Collections General Process
	function collections_contact($parameters) 
	{

		$qi = new ECash_Queues_BasicQueueItem($parameters->application_id);
		$qm = ECash::getFactory()->getQueueManager();
		$qm->removeFromAllQueues($qi);

		$application_id = $parameters->application_id;

		Remove_Standby($application_id);
		
		// Send Return Letter 3 - 'Overdue Account Letter' 6.2.1.4
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);

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
		Schedule_Full_Pull($application_id, NULL, NULL, $next_action, $next_due_date);
		
		return 0;
	}

	
	// State 19
	// Send to collections rework
	// Situation: Collections general had 2 non-fatal returns, 1 fatal, or collections new had 1 fatal and arrangements failed
	// Action:    Immediately move the customer to Collections Rework process.
	function collections_rework($parameters) 
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
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_4_FINAL_NOTICE', $parameters->status->fail_set[0]->transaction_register_id);

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
	function previous_status($parameters) 
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
	
	
	
	// Situation: No fatal return codes were found, however the returns
	//            contained a 'full pull' transaction.
	// Action:    Immediately move the customer to 'QC Ready' status.
	function State_25($parameters) 
	{
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

			$this->Log(__METHOD__.": Unable to QC Ready account.");
			$db->rollBack();
			throw $e;
		}

		// If we're not using QuickChecks, disable QC Related activities
		if(ECash::getConfig()->USE_QUICKCHECKS === TRUE)
		{
			Update_Status(null, $application_id, array('ready','quickcheck','collections','customer','*root'));
		}
		else
		{
			Update_Status(null, $application_id, array('pending','external_collections','*root'));
		}
	}

	// Situation: No fatal return codes found, no full pulls found,
	//            and this is not the first set of returns for this
	//            customer, but it less than the maximum number of
	//            allowable returns.
	// Action:    Add all returns to the events on the next scheduled pay
	//            date for that customer. Customer receives the first
	//            Collections email. *NOTE: Should be placed in first
	//            Collections status (Collections New), but NOT contact.
	function State_26($parameters) 
	{
		$application_id = $parameters->application_id;
		ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);
		
		$this->Log("Next Action Date: {$parameters->next_action_date}");
		$this->Log("Next Due Date: {$parameters->next_due_date}");
		

/** - Per 16663, the customer does not want to reattempt customers.
 *  - Per 22286, The customer realized it was a bad idea to not reattempt customers, and is now reenabling reattempts. 
 * 
 */
 		$db = ECash::getMasterDb();
 
 		// reattempt on the next pay day
 		// Now add all the remaining reattempts
	//	if ($date_pair = $this->getAdditionalReturnDate($parameters))
	//	{		
	 		try 
	 		{			
	 			$db->beginTransaction();
	 //			foreach($parameters->status->fail_set as $f)
	 //			{
	 //				$this->Log("Reattemping {$f->transaction_register_id} on {$date_pair['event']}");
	 //				$ogid = -$f->transaction_register_id;
	 //				Reattempt_Event($application_id, $f, $date_pair['event'], $ogid);
	 //			}			
				$this->reattempt_failures($parameters); 	 
				$db->commit();					     
	 		} 
	 		catch (Exception $e) 
	 		{

	 			$this->Log(__METHOD__.": Unable to reattempt events.");
	 			$db->rollBack();
	 			throw $e;
	 		}
	//	}

//		$qm = ECash::getFactory()->getQueueManager();
//		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 100;
//		$qm->moveToQueue($queue_item, 'collections_new');

		Update_Status(null, $application_id, array('new','collections','customer','*root'), NULL, NULL, FALSE);
	}

	// Situation: No fatal return codes found, no full pulls found,
	//            and this is the last allowed set of returns for
	//            this customer.
	// Action:    Remove scheduled events, Email customer Final Notice letter, 
	//			  move to Collections General queue.
	function State_27($parameters) 
	{
		$application_id = $parameters->application_id;
		Remove_Unregistered_Events_From_Schedule($application_id);

		// Send Return Letter 4 - Final Notice
		ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'RETURN_LETTER_4_FINAL_NOTICE', $parameters->status->fail_set[0]->transaction_register_id);
		
//		$qm = ECash::getFactory()->getQueueManager();
//		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 100;
//		$qm->moveToQueue($queue_item, 'collections_general');

		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);

	}

	function reattempt_failures($parameters) 
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
	}

	function do_nothing($parameters) 
	{
		//literally!  Do nothing!
	}


	// Situation: No fatal return codes found, no full pulls found,
	//            and this is the first 'level' of returns for this customer.
	// Action:    First return, therefore immediately schedule all the
	//            returned items for that business day (reattempt) and add
	//            a service charge failure fee.
	//            Customer's status is changed to 'past due'.
	function State_31($parameters) 
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		$rules = $parameters->rules;

		$additions = array();

		$date_event = date("Y-m-d");
		$date_effective = $pdc->Get_Business_Days_Forward($date_event, 1);

		$db = ECash::getMasterDb();
		
/*  - Per 16663, the customer does not want to reattempt customers, no assess ACH fees.
	- Per 22286, the customer decided they do want to reattempt customers, and assess ACH fees.
*/
		//Reattempt immediately
		if ($date_pair = $this->getFirstReturnDate($parameters))
		{		
			try 
			{
				$db->beginTransaction();
				
				if ($parameters->status->ach_fee_count == 0) 
				{
					// Add our fee
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', intval($rules['return_transaction_fee']));
					$oid = $parameters->status->fail_set[0]->transaction_register_id;
					$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, 'assess_fee_ach_fail','ACH fee assessed');
					Post_Event($parameters->application_id, $e);
		
					// And then pay it.
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', -intval($rules['return_transaction_fee']));
					$e = Schedule_Event::MakeEvent($date_pair['event'], $date_pair['effective'], $amounts, 'payment_fee_ach_fail', 'ACH fee payment');
					Record_Event($parameters->application_id, $e);
				}
		
	
				// Add all the reattempts
				$this->reattempt_failures($parameters); 	 
			//	foreach($parameters->status->fail_set as $f) 
			//	{
			//		$ogid = -$f->transaction_register_id;
			//		Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
			//	}
	
				$db->commit();
			} 
			catch (Exception $e) 
			{

				$this->Log(__METHOD__ . ': ' . $e->getMessage() . ' Unable to modify transactions.');
				$db->rollBack();
				throw $e;
			}
		}
		// Send Return Letter 2 - Second Attempt - to apps which have non-fatal ACH returns
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT', $parameters->status->fail_set[0]->transaction_register_id);

		// Change the status to Past Due, Send to Collections New queue
		$agent_id = Fetch_Default_Agent_ID();
		Update_Status(null, $parameters->application_id, array('past_due', 'servicing', 'customer', '*root'), NULL, NULL, FALSE );
		
//		$qm = ECash::getFactory()->getQueueManager();
//		$queue_item = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 100;
//		$qm->moveToQueue($queue_item, 'collections_new');				
	}
	
	// Situation: We are in a "Held" status, meaning the account is in a status that should not
	// transition until an expiration period or some sort of human intervention takes place.
	// We should not attempt to adjust the account at this time.  We will earmark the account
	// via the Standby table so that the nightly processes will pick it up and restart the
	// rescheduling process if the account moves out of it's hold status.
	function State_32($parameters) 
	{
		$application_id = $parameters->application_id;
		Remove_Standby($parameters->application_id);
		Set_Standby($application_id, $parameters->company_id, 'hold_reschedule');
	}
	
	//This is an arrangement, pass of to the arrangements_dfa.php
	function State_36($parameters) 
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

		return $dfa->run($parameters);
	}
	
	// Situation: Failed attempt to cancel_principal. May occur when debiting the customer fails after they've cancelled.
	// We want to delete the cancellation events and regenerate schedule
	// MikeL: Unifying cancel and payout failures. Makes processing cashline 
	// returns 'correctly' much easier.
	function no_harm_no_foul($parameters) 
	{
		//Cycle through schedule and fail principal cancellations & Internal adjustments
		foreach ($parameters->schedule as $e) 
		{
			if ($e->type == 'adjustment_internal_fees' && $e->context == 'cancel' && $e->status != 'failed')
			{
				Record_Event_Failure($parameters->application_id, $e->event_schedule_id);
			}
		}
		//Now we're actually going to determine whether or not the applicatiion's inactive paid by what the application's current
		//status is, rather than what it was when the DFA started.  This will prevent inaccuracies due to the status changing
		//within the DFA, like in #22508 [W!-01-08-2009][#22508]
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$application =  ECash::getApplicationByID($parameters->application_id);

		//If status is Inactive(Paid), rollback to the previous status
		if($application->application_status_id == $asf->toId('paid::customer::*root'))
		{
			if($prev_status = Get_Previous_Status($parameters->application_id))
			{
				Update_Status(NULL, $parameters->application_id, $prev_status);
			}
		}
		
		Complete_Schedule($parameters->application_id);
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

}

?>
