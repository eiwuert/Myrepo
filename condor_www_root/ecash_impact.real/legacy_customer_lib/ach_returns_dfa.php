<?php
/** 
 * Returns & Corrections ImpactCash
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * 
 * History:
 * [#17731] - Added code to determine the appropriate reattempt date using
 *            the 'failed_pmt_next_attempt_date' rules.  [BR]
 */

require_once('dfa.php');
require_once('dfa_ach_rescheduling.php');
require_once(SQL_LIB_DIR . "agent_affiliation.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once (LIB_DIR . "/Document/Document.class.php");
require_once (LIB_DIR . "/Document/AutoEmail.class.php");

class ReschedulingDFA extends ReschedulingDFABase
{
	const NUM_STATES = 13;

	function __construct($server)
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(6,7,8,9,10,11,12);
		$this->tr_functions = array(
			  0 => 'is_in_holding_status',
			  1 => 'has_credits',
			  2 => 'is_disbursement',
			  3 => 'has_fatal_ach',
  			  4 => 'is_first_return',
  			  5 => 'is_at_return_limit'
			);

		$this->transitions = array ( 
			  0 => array( 0 =>  1,  1 =>  6 ), 
			  1 => array( 0 =>  3,  1 =>  2 ), 
			  2 => array( 0 =>  7,  1 =>  8 ), 
			  3 => array( 0 =>  4,  1 => 12 ),
			  4 => array( 0 =>  5,  1 => 10),
			  5 => array( 0 => 11,  1 =>  9)
			); 

		$this->has_ach_fail_fee = false;
		$this->server = $server;			

		parent::__construct();
	}

	// Situation: We are in a "Held" status, meaning the account is in a status that should not
	// transition until an expiration period or some sort of human intervention takes place.
	// We should not attempt to adjust the account at this time.  We will earmark the account
	// via the Standby table so that the nightly processes will pick it up and restart the
	// rescheduling process if the account moves out of it's hold status.
	function State_6($parameters) {
		$application_id = $parameters->application_id;
		Set_Standby($application_id, 'hold_reschedule');
	}

	// Situation: The returns have at least one credit, and none of the credits
	//            in the returns are 'loan disbursements'.
	// Action:    Email customer service.
	function State_7($parameters) {
		return true;
	}

	// Situation: The returns have at least one credit, and there's a 'loan disbursement'
	//            in the failure set.
	// Action:    Move the customer to 'Funding Failed' status and notify an agent.
	//            Email an agent about the funding failed.
	function State_8($parameters) 
	{
		$status = $parameters->verified;

		// Gather the total of all unpaired fees/scs, and adjust for it.
		$total = 0.0;
		foreach ($status->posted_schedule as $e) {
			if(($e->type == 'assess_service_chg') || ($e->type == 'converted_service_chg_bal')) {
				if($e->status == 'complete') {
					$total += $e->fee_amount;
				}
			}
		}

		foreach ($status->outstanding['ach'] as $e) {
			$total += $e->fee_amount;
		}
 		$db = ECash_Config::getMasterDbConnection();
		try {
			$db->beginTransaction();
	
			// Remove the schedule immediately
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
	
			if ($total > 0.0) 
			{
				$today = date("Y-m-d");
				$amounts = array();
				$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$total);
				$e = Schedule_Event::MakeEvent($today, $today, $amounts,
							       'adjustment_internal',
							       'Adjusting out all accrued fees due to failure.');
				Post_Event($parameters->application_id, $e);
			}
			$db->commit();
		} catch (Exception $e) {
			$this->Log(__METHOD__.": Unable to modify account.");
			$db->rollBack();
			throw $e;
		}

		Update_Status(null, $parameters->application_id,  array('funding_failed','servicing','customer','*root'));

	}

	// Situation: This is not the first return for this customer
	// Action:    Remove all unregistered events and send the customer
	//            to the "General" Collections Queue.
	function State_9($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		try {
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$db->commit();
		} catch (Exception $e) {
			$this->Log(__METHOD__.": Unable to modify account.");
			$db->rollBack();
			throw $e;
		}

		Update_Status(null, $parameters->application_id, array('queued','contact','collections','customer','*root'));
		//If a failed arrangment, Imapct wants to remove the agent affiliation [#20474]
		$f = $parameters->most_recent_failure;
		if($f->context == 'arrangement')
		{
			$this->remove_affiliations($parameters);
		}
		// Do we need to send mail here?
		//eCash_Document_AutoEmail::Send($this->server, $parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT');
	}

	// Situation: This is the first set of returns for this customer.
	// Action:    Remove all unregistered events and send the customer 
    //            to the "New" Collections Queue.
	function State_10($parameters) 
	{
		if($date_pair = $this->getFirstReturnDate($parameters))
		{
			$today = date('Y-m-d');
			
			$rules = $parameters->rules;
	
			$db = ECash_Config::getMasterDbConnection();
			try {
				$db->beginTransaction();
		
				$fee_events = array();
				
				// Add all the reattempts
				foreach($parameters->status->fail_set as $f) 
				{
	
					/**
					 * If this is the first failure for the event and the failure is 
					 * not on an ach fee payment, then add an ACH Fee for the event.
					 * 
					 * What about multiple transactions for the same event?  That probably
					 * shouldn't have multiple ach fees.  <-- This is handled with the
					 * $fee_events array so that only one part will get charged.
					 * 
					 * Ignoring the payment_service_chg event so for a given pay period
					 * we won't charge two ACH fees if they both fail. [Mantis:9852]
					 */
					if( (! in_array($f->event_schedule_id, $fee_events)) &&
	 					(($f->context !== 'reattempt' || ($f->origin_group_id !== $f->event_schedule_id)) 
					    && (! in_array($f->type, array('payment_fee_ach_fail')))) && !$this->has_ach_fail_fee)
					{
						$amounts = array();
						$amounts[] = Event_Amount::MakeEventAmount('fee', intval($rules['return_transaction_fee']));
						$oid = $parameters->status->fail_set[0]->transaction_register_id;
						
						$e = Schedule_Event::MakeEvent($today, $today, $amounts, 'assess_fee_ach_fail', 
							"ACH fee for return for event {$f->event_schedule_id}");
						
						Post_Event($parameters->application_id, $e);
			
						// And then pay it.
						$amounts = array();
						$amounts[] = Event_Amount::MakeEventAmount('fee', -intval($rules['return_transaction_fee']));
						
						$e = Schedule_Event::MakeEvent($date_pair['event'], $date_pair['effective'], $amounts, 'payment_fee_ach_fail', 
							"ACH fee payment for return for event {$f->event_schedule_id}");
						
						Record_Event($parameters->application_id, $e);
						
						$fee_events[] = $f->event_schedule_id;
						
						$this->has_ach_fail_fee = true;
					}				
					
					$ogid = -$f->transaction_register_id;
					$reattempt = TRUE;
					foreach($parameters->schedule as $s)
					{
						if($s->origin_id && $f->transaction_register_id == $s->origin_id)
						{
							$reattempt = FALSE;
						}
					}
					if($reattempt)
					{
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
					}
					else
					{
						$this->Log("Skipping reattempt ({$f->transaction_register_id}). One already exists.");
					}
				}
				$db->commit();
			} catch (Exception $e) {
				$this->Log(__METHOD__ . ': ' . $e->getMessage() . ' Unable to modify transactions.');
				$db->rollBack();
				throw $e;
			}
		}
		Update_Status(null, $parameters->application_id, array('new','collections','customer','*root'));
		//If a failed arrangment, Imapct wants to remove the agent affiliation [#20474]
		$f = $parameters->most_recent_failure;
		if($f->context == 'arrangement')
		{
			$this->remove_affiliations($parameters);
		}
		// Do we need to send mail here?
		//eCash_Document_AutoEmail::Send($this->server, $parameters->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT');
	}
	
	// Situation: This is if they are past the first return, but not at max failure limit
	// Action:   Reattempt on Next Due Date and move to the "New" Collections Queue.
	function State_11($parameters) 
	{
		if($date_pair = $this->getAdditionalReturnDate($parameters))
		{
		
			$db = ECash_Config::getMasterDbConnection();
			try {
				$db->beginTransaction();
				// Add all the reattempts
				foreach($parameters->status->fail_set as $f) {
					$ogid = -$f->transaction_register_id;
					$reattempt = TRUE;
					foreach($parameters->schedule as $s)
					{
						if($s->origin_id && $f->transaction_register_id == $s->origin_id)
						{
							$reattempt = FALSE;
						}
					}
					if($reattempt)
					{
						Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
					}
					else
					{
						$this->Log("Skipping reattempt ({$f->transaction_register_id}). One already exists.");
					}
				}
				$db->commit();
			} catch (Exception $e) {
				$this->Log(__METHOD__ . ': ' . $e->getMessage() . ' Unable to modify transactions.');
				$db->rollBack();
				throw $e;
			}
		}
		Update_Status(null, $parameters->application_id, array('new','collections','customer','*root'));
		//If a failed arrangment, Imapct wants to remove the agent affiliation [#20474]
		$f = $parameters->most_recent_failure;
		if($f->context == 'arrangement')
		{
			$this->remove_affiliations($parameters);
		}
	}

	// Situation: The customer has received a Fatal ACH return
	// Action: Remove all unregistered events and send them to the
	// Collections General queue.
	function State_12($parameters)
	{
		$db = ECash_Config::getMasterDbConnection();
		try {
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$db->commit();
		} catch (Exception $e) {
			$this->Log(__METHOD__.": Unable to modify account.");
			$db->rollBack();
			throw $e;
		}

		Update_Status(null, $parameters->application_id, array('queued','contact','collections','customer','*root'));
		//If a failed arrangment, Imapct wants to remove the agent affiliation [#20474]
		$f = $parameters->most_recent_failure;
		if($f->context == 'arrangement')
		{
			$this->remove_affiliations($parameters);
		}
	}

	// Common Functions
	function is_in_holding_status($parameters) {
		$application_id = $parameters->application_id;
		if(In_Holding_Status($application_id)) {
			return 1;
		}
		return 0;
	}

	function has_credits($parameters) {
		foreach ($parameters->status->fail_set as $e) {
			if (($e->principal_amount > 0.0) ||
			    ($e->fee_amount > 0.0)) return 1;
		}
		return 0;
	}

	function is_first_return($parameters) {
		foreach ($parameters->status->fail_set as $e) {
			if ($e->origin_id != null) return 0;
		}
		return 1;
	}

	// Currenly unused functions
	function status_class($parameters) {
		foreach($parameters->status->fail_set as $f) {
			if(isset($f->clearing_type)) {
				switch ($f->clearing_type) {
					case 'ach' :
					case 'quickcheck' :
						return 'servicing';
						break;
					default:
						return 'non_ach';
						break;
				}
			}
		}

		if (($parameters->level1 == 'arrangements') &&
		    ($parameters->level0 == 'current')) return 'arrangements';
		return 'servicing';
	}

	function has_type($parameters, $comparison_type, $checklist='failures') {
		if ($checklist == 'failures') $list = $parameters->status->fail_set;
		else $list = $parameters->schedule;
		foreach ($list as $e) {
			if ($e->type == $comparison_type) return 1;
		}
		return 0;
	}

	function is_disbursement($parameters) { return $this->has_type($parameters, 'loan_disbursement'); }
	function has_quickcheck($parameters) { return $this->has_type($parameters, 'quickcheck','schedule'); }
	function has_fullpull($parameters) { return $this->has_type($parameters, 'full_balance'); }
	function is_watched($parameters) { return (($parameters->is_watched == 'yes')?1:0); }

	function has_fatal_ach($parameters) {
		foreach ($parameters->status->fail_set as $f) {
			if (in_array($f->return_code, array('R02', 'R05', 'R07', 'R08', 'R10',
							    'R16', 'R29', 'R38', 'R51',
							    'R52')))
				return 1;
		}
		return 0;
	}

	function qc_status($parameters) {
		$backwards = array_reverse($parameters->schedule);
		foreach ($backwards as $e) {
			if ($e->type == 'quickcheck') return (strtolower($e->status));
		}
	        $this->Log($parameters->application_id . ": Expected QuickCheck not found.");
	    return false;
	}

	function acct_in_second_tier($parameters) {

		if (($parameters->level1 == 'external_collections') &&
		    ($parameters->level2 == '*root')) return 1;
		else return 0;
	}

	function is_repeat_first_return($parameters) {
		foreach ($parameters->schedule as $e) {
			if ($e->type == 'assess_fee_ach_fail')
				return true;
		}
		if(isset($parameters->cashline_schedule)) {
			foreach ($parameters->cashline_schedule as $ce) {
				if ($ce->transaction_type == 'return fee')
					return true;
			}
		}
		return false;
	}

	function is_at_return_limit($parameters) {
		$r = $parameters->rules;
		$s = $parameters->status;

		return (($s->max_reattempt_count >= $r['max_svc_charge_failures'])?1:0);
	}

	function is_first_qc($parameters) {
		return(($parameters->status->num_qc == 1)?1:0);
	}
	
	/**
	 * Remove all affiliations from the current app
	 * 
	 * @param object $parameters bath tub object for dfa's
	 */
	protected function remove_affiliations($parameters)
	{
		$app = ECash::getApplicationById($parameters->application_id);
		$affiliations = $app->getAffiliations();
		$affiliations->expireAll();	
	}
	
}
?>