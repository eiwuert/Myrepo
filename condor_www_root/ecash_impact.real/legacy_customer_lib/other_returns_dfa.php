<?php
/*
 * This DFA is meant to handle failures for transactions that are
 * non-ACH and not QuickCheck related.
 *
 */

require_once('dfa.php');

class Other_Transaction_DFA extends DFA
{
	const NUM_STATES = 5;

	function __construct()
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(2,3,4);
		$this->initial_state = 0;
		$this->tr_functions = array( 
				0 => 'is_in_holding_status',
  			    1 => 'is_first_return',
				
				);
				
		$this->transitions = array(
				0 => array( 	0 => 1, 1 => 4),
				1 => array( 	0 => 2, 1 => 3)
				);
		parent::__construct();
	}
	
	/* Helper Functions go here */

	// If the application is in a Hold Status (Watch Flag, Hold,
	// Bankruptcy, etc... Then we want to postpone rescheduling
	// the account.
	function is_in_holding_status($parameters) {
		$application_id = $parameters->application_id;
		if(In_Holding_Status($application_id)) {
			return 1;
		}
		return 0;
	}

	function is_first_return($parameters) {
		foreach ($parameters->status->fail_set as $e) {
			if ($e->origin_id != null) return 0;
		}
		return 1;
	}
	
	/* These are the end states for the state machine */
	
	
	// The applicant is in Hold Status - Do nothing
	function State_4 ($parameters) {
	}
	
	// Situation: This is not the first return for this customer
	// Action:    Remove all unregistered events and send the customer
	//            to the "General" Collections Queue.
	function State_2($parameters) 
	{
		 $db = ECash_Config::getMasterDbConnection();
		try {
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$db->commit();
		}
		Catch (Exception $e)
		{
			$this->Log(__METHOD__.": Unable to process failure.");
			$db->rollBack();
			throw $e;
		}
		
		Update_Status(null, $parameters->application_id, array('queued','contact','collections','customer','*root'));

		// Do we need to send mail here?
		// eCash_Document_AutoEmail::Send($this->server, $parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT');
	}

	// Situation: This is the first set of returns for this customer.
	// Action:    Remove all unregistered events and send the customer 
    //            to the "New" Collections Queue.
	function State_3($parameters) 
	{
		$db = ECash_Config::getMasterDbConnection();
		try {
			$db->beginTransaction();
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
			$db->commit();
		}
		Catch (Exception $e)
		{
			$this->Log(__METHOD__.": Unable to process failure.");
			$db->rollBack();
			throw $e;
		}

		Update_Status(null, $parameters->application_id, array('new','collections','customer','*root'));

		// Do we need to send mail here?
		// eCash_Document_AutoEmail::Send($this->server, $parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT');
	}
}
?>
