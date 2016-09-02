<?php

require_once('dfa.php');
require_once('dfa_ach_returns.php');
require_once(SQL_LIB_DIR . "agent_affiliation.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR."comment.func.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "app_flags.class.php");
require_once (LIB_DIR . "/Document/Document.class.php");
require_once (LIB_DIR . "/Document/AutoEmail.class.php");

class ReschedulingDFA extends ReschedulingDFABase
{
	const NUM_STATES = 41;

	function __construct($server)
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(3,4,7,8,9,10,11,23,24,25,26,27,29,31,32,36,37,39);
		$this->tr_functions = array(
			  0 => 'optionally_queue_application',
			 35 => 'is_in_holding_status',
			  1 => 'has_balance',
			  2 => 'status_class', 
			  5 => 'has_credits',
			  6 => 'is_disbursement', 
			 12 => 'has_quickcheck',
			 13 => 'qc_status', 
			 14 => 'acct_in_second_tier', 
			 15 => 'has_fatal_ach',
			 16 => 'has_failed_personal_check',
			 17 => 'has_fullpull',
			 18 => 'is_first_return', 
			 20 => 'is_at_return_limit', 
			 22 => 'is_first_qc',
			 38 => 'check_failures',
			 40 => 'has_fatal_ach_current_bank_account',
			 41 => 'set_flag_had_fatal_ach_failure',
			 42 => 'set_flag_has_fatal_ach_failure',
			 );

		$this->transitions = array ( 
			  0 => array( 0 => 35 ),
			 35 => array( 0 =>  6, 1 => 32, 2 => 7, 3 => 23),// 1 - Hold, 2 - 2nd Tier, 3 - Watch
			  1 => array( 0 =>  37, 1 =>  2),
			  2 => array('servicing' =>  5, 'arrangements' => 36,	'non_ach' => 30),
			  5 => array( 0 => 38, 1 =>  4),
			 38 => array(0 => 12, 1 => 39),
			  6 => array( 0 =>  1, 1 =>  3),
			 12 => array( 0 => 15, 1 => 13),
			 13 => array('complete' => 8, 'pending' => 9, 'failed' => 22),
			 14 => array( 0 => 10, 1 => 11),
			 15 => array( 0 => 16, 1 => 41), // decide: Was there a fatal ach in our history?
			 16 => array( 0 => 17, 1 => 27), // Personal Check
			 17 => array( 0 => 18, 1 => 25),
			 18 => array( 0 => 20, 1 => 31),
			 20 => array( 0 => 26, 1 => 27),					  
			 22 => array( 0 => 14, 1 => 29),
			 40 => array( 0 => 16, 1 => 42), // decide: was there a fatal on our current account?
			 41 => array( 0 => 40), // operation: set had fatal ach failure flag
			 42 => array( 0 => 24), // operation: set has fatal ach failure flag
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

	function has_balance($parameters)
	{
		$status = $parameters->status;
		if($status->posted_and_pending_total <= 0) 
		{
			return 0;
		}
		
		return 1;
	}
	
	function status_class($parameters) 
	{
		if (($parameters->level1 == 'arrangements' && $parameters->level0 == 'current') ||
			($parameters->level1 == 'quickcheck' && $parameters->level0 == 'arrangements')) return 'arrangements';
		
		if($parameters->status->made_arrangement)
		{
			return 'arrangements';
		}
		
		return 'servicing';
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

	function has_quickcheck($parameters) 
	{ 
		// If we're not using QuickChecks, disable QC Related activities
		if(eCash_Config::getInstance()->USE_QUICKCHECKS === FALSE) return 0;
		
		return $this->has_type($parameters, 'quickcheck','schedule'); 
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
	
	function has_failed_personal_check($parameters) { return $this->has_type($parameters, 'personal_check','failures'); }
	
	/**
	*  This function routes principal cancellation failures and payout failures to the proper end state.
	*/
	function check_failures($parameters) 
	{ 
		if($this->has_type($parameters, 'cancel_principal'))
		{
			return 1;
		}
		elseif($this->has_type($parameters, 'payout_principal'))
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

	function qc_status($parameters) 
	{
		$backwards = array_reverse($parameters->schedule);
		foreach ($backwards as $e) 
		{
			if ($e->type == 'quickcheck') return (strtolower($e->status));
		}
	        $this->Log($parameters->application_id . ": Expected QuickCheck not found.");
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

	function is_first_qc($parameters) 
	{
		return(($parameters->status->num_qc == 1)?1:0);
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

	private function _set_flag($application_id, $flag)
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
		eCash_Document_AutoEmail::Queue_For_Send($this->server, $parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
	}

	function is_quick_check_enabled($parameters) 
	{
		if (eCash_Config::getInstance()->USE_QUICKCHECKS === TRUE) 
			return 1;
		return 0;
	}

	function add_quickcheck_standby($parameters) 
	{
        $application_id = $parameters->application_id;
		Set_Standby($application_id, 'qc_ready');
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
        $db = ECash_Config::getMasterDbConnection();
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

	
	// Here is where we define all the different responses -- the description
	// states what actions need to be taken.

	// Situation: The returns have at least one credit, and there's a 'loan disbursement'
	//            in the failure set.
	// Action:    Move the customer to 'Funding Failed' status and notify an agent.
	//            Email an agent about the funding failed.
	function State_3($parameters) 
	{
		$status = $parameters->verified;

		// Gather the total of all unpaired fees/scs, and adjust for it.
		$total = 0.0;
		
		$fund_date = strtotime($parameters->info->date_fund_stored);
		$balance = Fetch_Balance_Information($parameters->application_id);
		$total = $balance->total_balance;
		
		$db = ECash_Config::getMasterDbConnection();
		
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
	
	// Response functions 8,9,10, and 11 assume this as part of the situation:
	// - There are no credits in the return set.
	// - The customer has had a Quickcheck produced for them already.

	// Situation: The existing Quickcheck has a "complete" transactional status.
	// Action:    Send another quick check if the account has less than 2 quickchecks, or send the account to 2nd tier
	function State_8($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$db->commit();

		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to update Status.");
			$db->commit();
			throw $e;
		}

		if ($parameters->status->num_qc < 2) 
		{
			Update_Status(null, $parameters->application_id, array('ready','quickcheck','collections','customer','*root'));
		} 
		else 
		{
			Update_Status(null, $parameters->application_id, array('pending','external_collections','*root'));
		}
		
	}

	// Situation: The existing Quickcheck has a "pending" transactional status.
	// Action:    Take no action until the quick check fails or succeeds.
	function State_9($parameters) 
	{
	}

	// Situation: The existing Quickcheck has a "failed" transactional status,
	//            and the customer is NOT in 2nd Tier. It is not their first QC failure.
	// Action:    If there are made arrangements that zero it out, leave 'em alone.
	//            Otherwise put them in 2nd Tier Ready.
	function State_10($parameters) 
	{
		try 
		{
			Update_Status(null, $parameters->application_id,
							array('pending','external_collections','*root'));
		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__.": Unable to update Status.");
			throw $e;
		}
	}

	// Situation: The customer is in 2nd Tier, and the existing Quickcheck
	//            has a "failed" transactional status.
	// Action:    Add the returned amount to the outstanding balance for 2nd Tier.
	function State_11($parameters) 
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
		$ec = new External_Collections($this->server);
		$ec->Create_EC_Delta_From( $application_id , 0 - $amount );
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
		$db = ECash_Config::getMasterDbConnection();
		
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

	// Situation: One of the returns came back with a fatal return code.
	// Action:    Immediately move the customer to "Collections/Contact" for their
	//            'one shot' contact try.
	function State_24($parameters) 
	{
		$application_id = $parameters->application_id;
		$db = ECash_Config::getMasterDbConnection();
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
		
		// If we're not using QuickChecks, disable QC Related activities
		if(eCash_Config::getInstance()->USE_QUICKCHECKS === TRUE)
		{
			Set_Standby($application_id, 'qc_ready');
		}
		
		// Send Return Letter 1 - Specific Reason - to apps which have fatal ACH returns
		eCash_Document_AutoEmail::Queue_For_Send($this->server, $parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);

		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
		$queue_item->Priority = 200;
		$qm->moveToQueue($queue_item, 'collections_general');
		
	}


	// Situation: No fatal return codes were found, however the returns
	//            contained a 'full pull' transaction.
	// Action:    Immediately move the customer to 'QC Ready' status.
	function State_25($parameters) 
	{
		$application_id = $parameters->application_id;
		$db = ECash_Config::getMasterDbConnection();
		
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
		if(eCash_Config::getInstance()->USE_QUICKCHECKS === TRUE)
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
		eCash_Document_AutoEmail::Queue_For_Send($this->server, $application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);

		$this->Log("Next Action Date: {$parameters->next_action_date}");
		$this->Log("Next Due Date: {$parameters->next_due_date}");

		/** - Per 16663, the customer does not want to reattempt customers.
		 *  - Per 22286, The customer realized it was a bad idea to not reattempt customers, and is now reenabling reattempts. 
		 */
 		$db = ECash_Config::getMasterDbConnection();

 		// reattempt on the next pay day
 		// Now add all the remaining reattempts
		if ($date_pair = $this->getAdditionalReturnDate($parameters))
		{		
	 		try 
	 		{			
	 			$db->beginTransaction();
	 			foreach($parameters->status->fail_set as $f)
	 			{
	 				$this->Log("Reattemping {$f->transaction_register_id} on {$date_pair['event']}");
	 				$ogid = -$f->transaction_register_id;
	 				Reattempt_Event($application_id, $f, $date_pair['event'], $ogid);
	 			}			
	 			$db->commit();					     
	 		} 
	 		catch (Exception $e) 
	 		{
	 			$this->Log(__METHOD__.": Unable to reattempt events.");
	 			$db->rollBack();
	 			throw $e;
	 		}
		}

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
		eCash_Document_AutoEmail::Queue_For_Send($this->server, $application_id, 'RETURN_LETTER_4_FINAL_NOTICE', $parameters->status->fail_set[0]->transaction_register_id);
		
//		$qm = ECash::getFactory()->getQueueManager();
//		$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
//		$queue_item->Priority = 100;
//		$qm->moveToQueue($queue_item, 'collections_general');

		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);

	}

	// Situation: The customer has only one QC failure.
	//
	// Action:    If there are arrangements and they account for the balance, leave 'em be.
	//            Otherwise put 'em in Collections/Contact, standby for QC Ready.
	function State_29($parameters) 
	{
		$application_id = $parameters->application_id;

		try 
		{
//			$qm = ECash::getFactory()->getQueueManager();
//			$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
//			$queue_item->Priority = 100;
//			$qm->moveToQueue($queue_item, 'collections_general');

			Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);

			// Here, they don't have arrangements.
			Remove_Standby($application_id);
			Set_Standby($application_id, 'qc_ready');

		} 
		catch (Exception $e) 
		{
			$this->Log(__METHOD__ . ': ' . $e->getMessage() . ': Unable to queue the account.');
			throw $e;
		}
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

		$db = ECash_Config::getMasterDbConnection();
		
		/*  - Per 16663, the customer does not want to reattempt customers, no assess ACH fees.
		 *  - Per 22286, the customer decided they do want to reattempt customers, and assess ACH fees.
		 */
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
				foreach($parameters->status->fail_set as $f) 
				{
					$ogid = -$f->transaction_register_id;
					Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
				}
	
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
		eCash_Document_AutoEmail::Queue_For_Send($this->server, $parameters->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT', $parameters->status->fail_set[0]->transaction_register_id);

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
		Set_Standby($application_id, 'hold_reschedule');
	}
	
	//This is an arrangement, pass of to the arrangements_dfa.php
	function State_36($parameters) 
	{
		require_once(CUSTOMER_LIB."/arrangements_dfa.php");
		
		if (!isset($dfas['arrangements'])) 
		{
			$dfa = new Arrangement_DFA();
			$dfa->SetLog($parameters->log);
			$dfas['arrangements'] = $dfa;
		} 
		else 
		{
			$dfa = $dfas['arrangements'];
		}

		return $dfa->run($parameters);
	}

	// Situation: We have a balance <= 0, so corrective measures are not necessary
	function State_37($parameters) 
	{
	}
	
	// Situation: Failed attempt to cancel_principal. May occur when debiting the customer fails after they've cancelled.
	// We want to delete the cancellation events and regenerate schedule
	// MikeL: Unifying cancel and payout failures. Makes processing cashline 
	// returns 'correctly' much easier.
	function State_39($parameters) 
	{
		//Cycle through schedule and fail principal cancellations & Internal adjustments
		foreach ($parameters->schedule as $e) 
		{
			if ($e->type == 'adjustment_internal_fees' && $e->context == 'cancel' && $e->status != 'failed')
			{
				Record_Event_Failure($parameters->application_id, $e->event_schedule_id);
			}
		}
		
		//If status is Inactive(Paid), rollback to the previous status
		if($parameters->application_status_chain == "paid::customer::*root")
		{
			if($prev_status = Get_Previous_Status($parameters->application_id))
			{
				Update_Status(NULL, $parameters->application_id, $prev_status);
			}
		}
		
		Complete_Schedule($parameters->application_id);
	}
}

?>
