<?php

require_once('dfa.php');
require_once(COMMON_LIB_DIR."pay_date_calc.3.php");

class QuickCheckDFA extends DFA
{
	const NUM_STATES = 9;

	function __construct()
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(5,6,7,8);
		$this->initial_state = 0;
		$this->tr_functions = array( 
				0 => 'has_watch_flag',
				1 => 'has_bankruptcy_status',
				2 => 'has_2nd_tier_status',
				3 => 'has_fatal_code', 
				4 => 'is_first_qc');
				
		$this->transitions = array(
				0 => array( 	0 => 1, 1 => 7),
				1 => array( 	0 => 2, 1 => 7),
				2 => array( 	0 => 3, 1 => 8),
				3 => array(		0 => 4, 1 => 5),
				4 => array(		0 => 5, 1 => 6)
				);
		parent::__construct();
	}

	function has_watch_flag($parameters) {
		if ($parameters->is_watched == 'yes')
			return 1;

		return 0;
	}
	
	function has_bankruptcy_status($parameters) {
		if (($parameters->application_status_chain == 'verified::bankruptcy::collections::customer::*root') 
			|| ($parameters->application_status_chain == 'unverified::bankruptcy::collections::customer::*root'))
			return 1;
			
		return 0;
	}

	function has_2nd_tier_status($parameters) {
		if ($parameters->application_status_chain == 'sent::external_collections::*root') 
			return 1;
			
		return 0;
	}
	
	// true if there are fatal returns in any QC items
	function has_fatal_code($parameters) {
		$f = $parameters->most_recent_failure;

		$parameters->Log("Most Recent Failure:" .var_export($f,true));

		if ($parameters->arc_map[$f->ach_return_code_id]) {
			if ($parameters->arc_map[$f->ach_return_code_id]['is_fatal'] == 'yes')
			return 1;
		}
		else
		{
			throw new Exception ("Can't find ACH Return Code in map!");
		}
		return 0;
	}
		
	// Counts the number of QC's.  If it's less than the maximum allowable,
	// then true.
	function is_first_qc($parameters) {
		return(($parameters->status->num_qc == 1)?1:0);
	}
	
	// The customer has either a fatal return or more than one QC, go directly to collections
	function State_5($parameters) {
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		
		Set_Standby($parameters->application_id, 'qc_return');
		Update_Status(null, $parameters->application_id, "return::quickcheck::collections::customer::*root", NULL, NULL, FALSE);
	
//		move_to_automated_queue
//			(   "Collections Returned QC"
//			,   $parameters->application_id
//			,   date("Y-m-d H:i:s") // Sort String
//			,   NULL // Time available : Now
//			,   strtotime($pdc->Get_Business_Days_Forward(date("Y-m-d H:i:s"),3))+((60*60*24)-1) // Time unavailable, returns as Y-m-d string, add one-second shy of new day
//			)   ;
	}
	
	// The customer is ready for another QC
	function State_6($parameters) {
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		
		Set_Standby($parameters->application_id, 'qc_ready');
		Update_Status(null, $parameters->application_id, "return::quickcheck::collections::customer::*root", NULL, NULL, FALSE);
			
//		move_to_automated_queue
//			(   "Collections Returned QC"
//			,   $parameters->application_id
//			,   date("Y-m-d H:i:s") // Sort String
//			,   NULL // Time available : Now
//			,   strtotime($pdc->Get_Business_Days_Forward(date("Y-m-d H:i:s"),5))+((60*60*24)-1) // Time unavailable, returns as Y-m-d string, add one-second shy of new day
//			)   ;
	}
	
	// This is an empty function because the customer must be in the watch or bankruptcy status,
	// therefore they should not change status when there is a return
	function State_7($parameters) {
		// Do nothing... 
	}
	
	// The customer is in external collections
	function State_8($parameters) {
		$amount = $parameters->return_amount;
		if(! is_numeric($amount))
			throw new Exception ("Can't find failed QC in schedule!");
		
		$ec = new External_Collections($parameters->server);
		$ec->Create_EC_Delta_From( $parameters->application_id, $amount );
	}
	
	
}
?>
