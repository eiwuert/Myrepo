<?php

require_once(SQL_LIB_DIR . "application.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "fetch_card_return_code_map.func.php");

  /**
   * Returns DFA
   *
   * Replaces lib/dfa_returns.php
   */
class ECash_DFA_Returns extends ECash_DFA
{
	protected $server; //used for state_7 collections
	protected $num_states = 42;
	
	function __construct($server)
	{
		for($i = 0; $i < $this->num_states; $i++) $this->states[$i] = $i;
		$this->final_states = array(3,4,7,23,24,25,26,27,31,32,36,39);

		$this->initial_state = 0;

		$this->tr_functions = array(
			  0 => 'is_in_holding_status',
			  1 => 'has_balance',
			  2 => 'status_class', 
			  5 => 'has_credits',
			  6 => 'is_disbursement', 
			 15 => 'has_fatal_ach',
			 16 => 'has_failed_personal_check',
			 17 => 'has_fullpull',
			 18 => 'is_first_return',
			 19 => 'has_fatal_ach_flag', //[#49094]
			 20 => 'is_at_return_limit', 
			 38 => 'check_failures',
			 40 => 'has_fatal_ach_current_bank_account',
			 35 => 'set_flag_has_fatal_ach_failure',
			 41 => 'set_flag_had_fatal_ach_failure',			 
			 );

		//"STANDARD" transition list
		$this->transitions = array ( 
			  0 => array( 0 =>  6, 1 => 32, 2 => 7, 3 => 23),// 1 - Hold, 2 - 2nd Tier, 3 - Watch
			  1 => array( 0 =>  4, 1 =>  5),
			  2 => array('servicing' =>  16, 'arrangements' => 36),
			  5 => array( 0 => 38, 1 =>  4),
			 38 => array(0 => 15, 1 => 39),
			  6 => array( 0 =>  1, 1 =>  3),
			 15 => array( 0 => 19, 1 => 41), // decide: Was there a fatal ach in our history?
			 16 => array( 0 => 17, 1 => 27), // Personal Check
			 17 => array( 0 => 18, 1 => 25 ),
			 18 => array( 0 => 20, 1 => 31),
			 19 => array( 0 => 2, 1 => 24), //[#49094]
			 20 => array( 0 => 26, 1 => 27),					  
			 35 => array( 0 => 24), // operation: set has fatal ach failure flag
			 40 => array( 0 => 16, 1 => 35), // decide: was there a fatal on our current account?
			 41 => array( 0 => 40), // operation: set had fatal ach failure flag			 
			 ); 
			
		$this->server = $server;			

		parent::__construct();
	}

	/**
	 * If the application is in a Hold Status (Watch Flag, Hold,
	 * Bankruptcy, etc... Then we want to postpone rescheduling
	 * the account.  Overridden from the default.
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
	
	/**
	 * Checks to see if there are credits in the fail set.  We usually
	 * don't take such a harsh approach when there's a problem with
	 * the customer taking money.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function has_credits($parameters) 
	{
		foreach ($parameters->status->fail_set as $e) 
		{
			$total = 0;
			foreach($e->amounts as $amount)
			{
				$total = bcadd($total,$amount->amount,2);
			}
			if ($total > 0.0) return 1;
		}
		return 0;
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
	
	/**
	 * Checks to see if there's a disbursement in the failset
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_disbursement($parameters) 
	{ 
		return ($this->has_type($parameters, 'loan_disbursement') || $this->has_type($parameters, 'card_disbursement')); 
	}

	/**
	 * Not used in the base returns DFA transitions, but called
	 * directly by others
	 */
	function acct_in_second_tier($parameters) 
	{
		if (
			in_array($parameters->level0, array('pending','sent'))
			&& ($parameters->level1 == 'external_collections')
			&& ($parameters->level2 == '*root')
		)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Not used in the base returns DFA transitions, but called
	 * directly by others
	 */
	function is_watched($parameters)
	{
		return (($parameters->is_watched == 'yes')?1:0);
	}
	
	/**
	 * Checks to see if there's a fatal ACH return in the current failset
	 *
	 * Yes, it duplicates functionality, but I haven't had time to
	 * refactor all the DFAs and strip it all out.
	 * 
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function has_fatal_ach($parameters) 
	{
		$ach_code_map = Fetch_ACH_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($ach_code_map as $options)
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

	
	function has_failed_personal_check($parameters)
	{
		return $this->has_type($parameters, 'personal_check','failures');
	}

	/**
	 * Checks to see if there's a fullpull in the failset
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function has_fullpull($parameters) 
	{ 
		return ($this->has_type($parameters, 'full_balance') || $this->has_type($parameters, 'card_full_balance')); 
	}
	
	// @todo The depends on the way the customer handles
	//        ACH Fees
	function is_first_return($parameters) 
	{
		foreach ($parameters->schedule as $e) 
		{
			if (in_array($e->type, array('assess_fee_ach_fail','assess_fee_card_fail')))
				return 0;
		}
		return 1;
	}
	
	/**
	 * If an application has a non-fatal return on a different day after a fatal return,
	 * make sure it still goes 'the fatal route' to collections contact, rather than having
	 * their schedule reactivated [#49094]
	 */
	function has_fatal_ach_flag($parameters)
	{
		$app = 	ECash::getApplicationByID($parameters->application_id);
		$flags = $app->getFlags();
		if($flags->get('has_fatal_ach_failure'))
		{
			return 1;
		}
		if($flags->get('has_fatal_card_failure'))
		{
			return 1;
		}
		
		return 0;
	}
	
	/**
	 * Checks to see if an application has reached the return limit
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_at_return_limit($parameters) 
	{
		$r = $parameters->rules;
		$s = $parameters->status;

		return (($s->max_reattempt_count >= $r['max_svc_charge_failures'])?1:0);
	}
	
	/**
	*  This function routes principal cancellation failures and payout failures to the proper end state.
	*/
	function check_failures($parameters) 
	{ 
		if($this->has_type($parameters, 'cancel_principal')
		   || $this->has_type($parameters, 'card_cancel_principal')
		)
		{
			return 1;
		}
		return 0;
	}

	/**
	 * Checks to see if there was a fatal return on the current bank account.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
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

	/**
	 * Sets the specified flag on an application.
	 *
	 * @param unknown_type $application_id
	 * @param unknown_type $flag
	 * @return unknown
	 */
	protected function _set_flag($application_id, $flag)
	{
		$app = 	ECash::getApplicationByID($application_id);

		$flags = $app->getFlags();
		
		// only set it if its not set already
		$flags->set($flag);
		
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

	//a.k.a "do nothing"
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
		
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		
		$affiliations = ECash::getApplicationById($application_id)->getAffiliations();
		$affiliations->expire('Watch', 'Owner');
	}

	// Situation: One of the returns came back with a fatal return code,
	//            or there was a return while the account still has a
	//            fatal ACH flag [#49094]
	// Action:    Assess a fee if it's the first fatal failure. Immediately
	// 			  move the customer to "Collections/Contact" for their
	//            'one shot' contact try.
	function State_24($parameters) 
	{
		$application_id = $parameters->application_id;
		$db = ECash::getMasterDb();
		try 
		{
			$db->beginTransaction();

			Remove_Unregistered_Events_From_Schedule($application_id);
			
			//add fee only for the first fatal failure [#28252]/[#30517]
			//need to double check incase > 1 failure happened on same day
			//(same ACH ID)
			$fatal_fail_count_by_date = array();
			foreach($parameters->status->fail_set as $event)
			{
				if($event->is_fatal == 'yes')
				{
					if(!isset($fatal_fail_count_by_date[$event->date_event]))
						$fatal_fail_count_by_date[$event->date_event] = 0;				
					$fatal_fail_count_by_date[$event->date_event]++;
				}
			}
			$fatal_fail_count = count($fatal_fail_count_by_date);
			
			$this->Log(__METHOD__.": Fatal failures (by date): {$fatal_fail_count}");
			if($fatal_fail_count <= 1 &&
			   (empty($parameters->rules['return_fee_max']) ||
				($parameters->status->ach_fee_count + 1) * $parameters->rules['return_transaction_fee'] <= $parameters->rules['return_fee_max']))
			{
				if (isCardSchedule($parameters->application_id))
				{
					$payment = 'assess_fee_card_fail';
					$description = 'Card Fee Assessed';
				}
				else
				{
					$payment = 'assess_fee_ach_fail';
					$description = 'ACH Fee Assessed';
				}

				$date_event = date("Y-m-d");
				
				// Add a fee (if not over fee maximum [#49039])
				$amounts = array();
				$amounts[] = Event_Amount::MakeEventAmount('fee', intval($parameters->rules['return_transaction_fee']));
				$oid = $parameters->status->fail_set[0]->transaction_register_id;
				$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, $payment,$description);
				Post_Event($parameters->application_id, $e);
			}
			else
			{
				$this->Log(__METHOD__.": Not adding fee - {$fatal_fail_count} (fatal fail count) > 1 OR {$parameters->status->ach_fee_count} (fee count) +1 * {$parameters->rules['return_transaction_fee']} (fee amt) > {$parameters->rules['return_fee_max']} (max)");
			}

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
		if(ECash::getConfig()->USE_QUICKCHECKS === TRUE)
		{
			Set_Standby($application_id, $parameters->company_id, 'qc_ready');
		}
		
		// Send Return Letter 1 - Specific Reason - to apps which have fatal ACH returns
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

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
	// Action:    Add fees [#28252]. Add all returns to the events on
	// 			  the next scheduled pay date for that customer.
	// 			  Customer receives the first Collections email.
	// 			  *NOTE: Should be placed in first Collections status
	// 			  (Collections New), but NOT contact.
	function State_26($parameters) 
	{
		$application_id = $parameters->application_id;
		//ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);
		
		$this->Log("Next Action Date: {$parameters->next_action_date}");
		$this->Log("Next Due Date: {$parameters->next_due_date}");
		
		$db = ECash::getMasterDb();

		// Now add fees & reattempts
		if ($date_pair = $this->getAdditionalReturnDate($parameters))
		{		
			try 
			{			
				$db->beginTransaction();

				/**
				 * Took out fatal failure check here, as it shouldn't
				 * be neccessary and it seems I may have added it
				 * erroneously as truely fatal accounts should only
				 * wind up in State_24 [JustinF] [#32201]/[#30517]/[#28252]
				 */
				$date_event = date("Y-m-d");
				
				// Add a fee (if not over fee maximum [#49039])
				if(empty($parameters->rules['return_fee_max']) ||
				   ($parameters->status->ach_fee_count + 1)* $parameters->rules['return_transaction_fee'] <= $parameters->rules['return_fee_max'])
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

					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', intval($parameters->rules['return_transaction_fee']));
					$oid = $parameters->status->fail_set[0]->transaction_register_id;
					$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, $payment1,$description1);
					Post_Event($parameters->application_id, $e);
					
					// And then pay it.
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', -intval($parameters->rules['return_transaction_fee']));
					$e = Schedule_Event::MakeEvent($date_pair['event'], $date_pair['effective'], $amounts, $payment2, $description2);
					Record_Event($parameters->application_id, $e);
				}
				else
				{
					$this->Log(__METHOD__.": Not adding fee - {$parameters->status->ach_fee_count} (fee count) +1 * {$parameters->rules['return_transaction_fee']} (fee amt) > {$parameters->rules['return_fee_max']} (max)");
				}
				
				foreach($parameters->status->fail_set as $f)
				{
					$this->Log("Reattemping {$f->tranaction_register_id} on {$date_pair['event']}");
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
		//ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'RETURN_LETTER_4_FINAL_NOTICE', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);

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
		//Reattempts immediately
		if ($date_pair = $this->getFirstReturnDate($parameters))
		{		
			try 
			{
				$db->beginTransaction();
				// Agean Live 5179, add ach fee but not schedule to pay it [richardb]
				if ($parameters->status->ach_fee_count == 0) 
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
					// Add our fee (if not over fee maximum [#49039])
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', intval($rules['return_transaction_fee']));
					$oid = $parameters->status->fail_set[0]->transaction_register_id;
					$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, $payment1,$description1);
					Post_Event($parameters->application_id, $e);
		
					// And then pay it.
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', -intval($rules['return_transaction_fee']));
					$e = Schedule_Event::MakeEvent($date_pair['event'], $date_pair['effective'], $amounts, $payment2, $description2);
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
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT', $parameters->status->fail_set[0]->transaction_register_id);
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);

		// Change the status to Past Due, Send to Collections New queue
		$agent_id = Fetch_Default_Agent_ID();
		Update_Status(null, $parameters->application_id, array('past_due', 'servicing', 'customer', '*root'), NULL, NULL, FALSE );
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
		
		//Now we're actually going to determine whether or not the applicatiion's inactive paid by what the application's current
		//status is, rather than what it was when the DFA started.  This will prevent inaccuracies due to the status changing
		//within the DFA, like in #22508 [W!-01-08-2009][#22508]
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$application =  ECash::getApplicationByID($parameters->application_id);

		//If status is Inactive(Paid) or Cancel, rollback to the previous status
		if($application->application_status_id == $asf->toId('paid::customer::*root')
                        ||
                        //$application->application_status_id == $asf->toId('canceled::servicing::customer::*root')
                        $application->application_status_id == $asf->toId('canceled::applicant::*root')
                )
		{
			if($prev_status = Get_Previous_Status($parameters->application_id))
			{
				Update_Status(NULL, $parameters->application_id, $prev_status);
			}
		}
		
		Complete_Schedule($parameters->application_id);
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
}
