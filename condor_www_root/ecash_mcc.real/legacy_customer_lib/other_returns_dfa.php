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

	function __construct()
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(2,3,4,6,11,12,15,17);
		$this->initial_state = 0;
		$this->tr_functions = array( 
				0 => 'is_in_holding_status',
				1 => 'has_2nd_tier_status',
				5 => 'in_arrangements',
				7 => 'most_recent_failure_is_arrangement',
				8 => 'has_quickcheck',
				9 => 'is_debt_consolidation',
				10 => 'is_at_max_debt_consolidation_failure_limit',
				13 => 'using_cso_model',
				14 => 'is_early_payment',
				16 => 'is_past_due_date',
			);
				
		$this->transitions = array(
				 0 => array( 	0 => 1, 1 => 3),
				 1 => array( 	0 => 9, 1 => 4),
				 5 => array(	0 => 8, 1 => 6),
				 7 => array( 	0 => 13, 1 => 6),
				 13 => array(	0 => 2, 1 => 14),
				 14 => array(	0 => 2, 1 => 16),
				 16 => array(	0 => 15, 1 => 17),
				 8 => array(	0 => 7, 1 => 4),
				 9 => array(	0 => 5, 1 => 10),
				 10 => array(	0 => 11, 1 => 12)
				);
		
		parent::__construct();
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

	/**
	 * Function that checks the business rule loan_type_model
	 * to determine whether the loan is a CSO loan or not.
	 */
	function using_cso_model($parameters)
	{
		return ($parameters->rules['loan_type_model'] === 'CSO') ? 1 : 0;
	}
	function has_2nd_tier_status($parameters) 
	{
		if ($parameters->application_status_chain == 'sent::external_collections::*root') 
			return 1;
			
		return 0;
	}
	function is_early_payment($parameters)
	{
		$renewal_class = ECash::getFactory()->getRenewalClassByApplicationID($parameters->application_id);
		//It can't very well be an early payment if they've already defaulted, now can it?!!
		if (!$renewal_class->hasDefaulted($parameters->application_id))
		{
			//determine whether or not the failure is a paydown/payout/manual payment
			foreach ($parameters->status->fail_set as $e)
			{
				if($e->context == 'manual' || $e->context == 'paydown' || $e->context == 'payout' || $e->context == 'cancel')
				{
					return 1;
				}
			}
		}
		return 0;
	}
	
		function is_past_due_date($parameters)
	{
		//determine last loan due date.
		$loan_due_date = null;
		//Determine whether or not there are scheduled events, if there aren't, that means that we had previously thought the loan
		//closed
		$has_scheduled = false;
		foreach ($parameters->schedule as $e) 
		{
			if ($e->type == 'cso_assess_fee_broker' && $e->status == 'complete')
			{
				$loan_due_date = $e->date_effective;
			}
			if ($e->status == 'scheduled')
			{
				$has_scheduled = true;
			}
		}
		foreach ($parameters->status->fail_set as $e)
		{
			if(($e->context == 'manual' || $e->context == 'paydown' || $e->context == 'payout' || $e->context == 'cancel') 
			&& (strtotime($e->date_registered) < strtotime($loan_due_date) || !$has_scheduled))
			{
				return 1;
			}
		}
		return 0;
	}
	
	//That's right, we need this function in non-ach returns processing now! AWESOME!
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
	
	function most_recent_failure_is_arrangement($parameters) 
	{
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);
		
		return (bool)($e->context == 'arrangement');
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
	
	function in_arrangements($parameters) 
	{
		
		if (($parameters->level1 == 'arrangements' && $parameters->level0 == 'current') ||
			($parameters->level1 == 'quickcheck' && $parameters->level0 == 'arrangements')) return 1;
			
		else return 0;
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
	function is_credit_card($parameters)
	{
		if(($this->has_type($parameters,'credit_card_fees')) || $this->has_type($parameters,'credit_card_princ'))
		{
			return 1;
		}
		return 0;
	}
	function has_quickcheck($parameters) 
	{ 
		// If we're not using QuickChecks, disable QC Related activities
		if(eCash_Config::getInstance()->USE_QUICKCHECKS === FALSE) return 0;
		
		return $this->has_type($parameters, 'quickcheck','schedule'); 
	}

	/* These are the end states for the state machine */

	// Situation: There has been a failure on an 'early payment'.  
	//
	//Action: Because MCC is from the moon, we are assessing a return fee, sending a document, and rebuilding the schedule.  
	function State_15 ($parameters)
	{
		$this->Log("Early Non-ACH Payment returned");
		//////////////////////////////////////
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		$rules = $parameters->rules;

		$date_event = date("Y-m-d");
		$application_id = $parameters->application_id;
		$application =  ECash::getApplicationByID($application_id);
		$comments = $application->getComments();
		
		//We're no longer assessing any fees for non-fatal early payment returns!!
		
		//Send Condor doc to notify customer
		eCash_Document_AutoEmail::Queue_For_Send($parameters->server, $application_id, 'RETURN_LETTER_EARLY_PAYMENT', $parameters->status->fail_set[0]->transaction_register_id);
		
		//Rebuild schedule, which will take care of scheduling payments for the failed transactions as well as the fees.
		Complete_Schedule($parameters->application_id);
		
		
		//Add note?
		$comments->add('Early Non-ACH Payment failure',Fetch_Default_Agent_ID());
	}
	
	/**
	 * Its a return on an early payment after we've considered the loan closed out, its defaulting time!
	 *
	 * @param unknown_type $parameters
	 */
	function State_17 ($parameters)
	{
		$date_event = date("Y-m-d");

		$application_id = $parameters->application_id;
		$renewal_class = ECash::getFactory()->getRenewalClassByApplicationID($application_id);
		$db = ECash_Config::getMasterDbConnection();
		$ecash_api = eCash_API_2::Get_eCash_API(NULL, $db, $application_id);
		list($date_funded, $loan_status, $application_status_id, $loan_type) = $ecash_api->_Get_Application_Info($application_id);
		
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
		
		
		//If its a CSO loan (and it totally is), and it hasn't defaulted: 
		//Let's ding the living Hell out of them!
		if($parameters->rules['loan_type_model'] === 'CSO' && !$renewal_class->hasDefaulted($application_id))
		{
			$failure_amount = null;
			//That's right!  We're assessing an ACH fee for a transaction that isn't an ACH Transaction, YOU GUYS ARE GENIUOSES'!!!!!!1!!!!!!!!!!!11111!
			$bank_fee = $renewal_class->getCSOFeeAmount('lend_assess_fee_ach',$application_id,null,null,$failure_amount);
			//Haha, guess what!  We don't assess CSO Late fees until 10 days later!!!!!!!!!!!!!!!!!!!!!111111111!!!!!1
			
			//Assess the NSF fee. That's right a bank fee for a transaction that never touched the bank! BEST IDEA EVAR!
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('fee', $bank_fee);
			$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, 'lend_assess_fee_ach','Bank Fee Assessed');
			Post_Event($parameters->application_id, $e);
			
			//No more CSO Late fee!!! Sure, why not!
			//Update to Default status
			Update_Status(null, $application_id, array('default','collections','customer','*root'), NULL, NULL, FALSE);
			
			//If this is in the middle of a rollover assess interest
			if($parameters->status->num_scheduled_events > 1)
			{
				$balance = Fetch_Balance_Information($application_id);
				require_once(ECASH_COMMON_DIR . "/ecash_api/interest_calculator.class.php");

				$rules = $parameters->rules;
		         
				$last_sc = $parameters->status->last_service_charge_date;
				$holidays = Fetch_Holiday_List();
				$pdc = new Pay_Date_Calc_3($holidays);
				//$first_date = $pdc->Get_Business_Days_Forward($date_event, 1)
				
				//$last_date  = $this->dates['effective'][0];
				$return_date = date('Y-m-d');
				$amount = Interest_Calculator::calculateDailyInterest($rules, $balance->principal_balance, $last_sc, $return_date);
		
				$first_date_display = date('m/d/Y', strtotime($last_sc));
				$last_date_display = date('m/d/Y', strtotime($return_date));
				$comment = "$amount Interest accrued from {$first_date_display} to {$last_date_display} - Defaulted Loan";
				$this->Log($comment);
				
				// Create the SC assessment
				$amounts = array();
				$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
				$e = Schedule_Event::MakeEvent($return_date,$return_date,
							  $amounts, 'assess_service_chg', $comment);
				Post_Event($application_id,$e);
				$this->log("Defaulted loan for returned 'early payment'");
			}
			
		}
		
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		
		//If its fatal, insert it into the collections general queue with super high priority, if not meh.  Its already been inserted by the 
		//CFE rule for updating the status to collections contact
		if($this->has_fatal_ach($parameters))
		{
			$qm = ECash::getFactory()->getQueueManager();
			$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($parameters->application_id);
			$queue_item->Priority = 200;
			$qm->moveToQueue($queue_item, 'collections_general');
		}
		
		
		// Send Return Letter 1 - Specific Reason - to apps which have fatal ACH returns
		eCash_Document_AutoEmail::Queue_For_Send($parameters->server, $parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
		
	}
	
	// The applicant is in neither a Hold Status or 2nd Tier.  The failure was most likely
	// an arrangement.  Add the Application to the Arrangements Failed Queue for now.
	function State_2 ($parameters) 
	{
		// Write code to put the customer in the arrangements failed queue.

		$cs = Fetch_Application_Status($parameters->application_id);
		//If its an accrued charge, we don't care if its failed or not!
		$e = Grab_Most_Recent_Failure($parameters->application_id, $parameters->schedule);

		if ($e->clearing_type == "accrued charge")
		{
			return;
		}
		
		if("new" == $cs["level0"] && "collections" == $cs["level1"] && "customer" == $cs["level2"] && "*root" == $cs["level3"])
		{
			$qm = ECash::getFactory()->getQueueManager();
			Update_Status(NULL,$parameters->application_id,"queued::contact::collections::customer::*root",NULL,NULL,FALSE);
			
			$qi = $qm->getQueue('internal_collections_general')->getNewQueueItem($parameters->application_id);
			if (Has_Fatal_Failures($parameters->application_id))
			{
				$qi->Priority = 200;
			}
			$qm->moveToQueue($qi, 'internal_collections_general');


		}
		else
		{
			$qm = ECash::getFactory()->getQueueManager();
			Update_Status(NULL,$parameters->application_id,"new::collections::customer::*root",NULL,NULL,FALSE);
	
			$qi = $qm->getQueue('collections_new')->getNewQueueItem($parameters->application_id);
			if (Has_Fatal_Failures($parameters->application_id))
			{
				$qi->Priority = 200;
			}
			$qm->moveToQueue($qi, 'collections_new');

		//	$this->Log("Exception State_2: Failure on manual payment for {$parameters->application_id}, can't determine where to put this app!");
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
			$dfa = new Arrangement_DFA();
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
		$db = ECash_Config::getMasterDbConnection();
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
				Schedule_Full_Pull($parameters->application_id);
			} 
			else 
			{
				if ($parameters->status->num_qc < 2 && eCash_Config::getInstance()->USE_QUICKCHECKS === TRUE) 
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
