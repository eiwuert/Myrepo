<?php
require_once('dfa.php');
require_once(SQL_LIB_DIR . '/fetch_ach_return_code_map.func.php');

/**
 * This is the entry-point for any times a transaction failure is recorded.
 * It will gather together the transactional information for the account in
 * question, and learning that decide which branch-DFA to use for actually
 * taking action.
 */

class FailureDFA extends DFA {
	
	public $application_id;
	const NUM_STATES = 6;
	
	public function __construct()
	{

		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(3,4,5,6);
		$this->tr_functions = array(
			 1 => 'has_failures', 
			 2 => 'transaction_type',
			 0 => 'is_disallowed_status');

		$this->transitions = array ( 
			 0 => array(0 => 1, 1 => 6),
			 1 => array (0 => 6, 1 => 2),
			 2 => array('ach' => 3, 'quickcheck' => 4,	'other' => 5)); 
			
		parent::__construct();
	}
	// Here I'm setting up the 
	function run ($parameters) {

		require_once(SQL_LIB_DIR ."application.func.php");
		require_once(SQL_LIB_DIR ."fetch_status_map.func.php");
		require_once(SQL_LIB_DIR ."scheduling.func.php");
		require_once(SQL_LIB_DIR ."util.func.php");

		// Yes, I threw in everything but the kitchen sink in here.
		$application_id = $parameters->application_id;
		
		$data     = Get_Transactional_Data($application_id);
		$holidays = Fetch_Holiday_List();

		$parameters->log = get_log("scheduling");
		$parameters->info = $data->info;
		$parameters->rules = Prepare_Rules($data->rules, $data->info);
		$parameters->schedule = Fetch_Schedule($application_id);
		$parameters->verified = Analyze_Schedule($parameters->schedule, true);
		$parameters->grace_periods = Get_Grace_Periods();
		$parameters->pd_calc = new Pay_Date_Calc_3($holidays);
		$parameters->arc_map = Fetch_ACH_Return_Code_Map();
		$parameters->event_transaction_map = Load_Transaction_Map(ECash::getCompany()->company_id);	
		$parameters->app_status_map = Fetch_Status_Map();
		$parameters->application_status_id = $data->info->application_status_id;
		$parameters->application_status_chain = $parameters->app_status_map[$parameters->application_status_id]['chain'];
		$parameters->is_watched = $data->info->is_watched;
		$parameters->most_recent_failure = Grab_Most_Recent_Failure($application_id, $parameters->schedule);

		if (!$parameters->company_id) $parameters->company_id = ECash::getCompany()->company_id;

		// Get the app status
		$app_status = Fetch_Application_Status($application_id);
		foreach ($app_status as $key => $value) $parameters->$key = $value;

		$parameters->status   = Analyze_Schedule($parameters->schedule);

		return parent::run($parameters);
	}

	/**
	 * Clears out any discounts they received on arrangements
	 *
	 * @param object $parameters
	 */
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
	
	/**
	 * is_disallowed_status
	 * If the application is in a disallowed status, we do not want to do ANY returns processing on it.
	 * This prevents us from assessing more fees or scheduling reattempts on charge_offs and other accounts that should not experience
	 * any more transactions. [IMPACT LIVE #14788]
	 */
	function is_disallowed_status($parameters)
	{
		require_once(SQL_LIB_DIR . "fetch_status_map.func.php");
		
		$application_id = $parameters->application_id;
		
		$status_map = Fetch_Status_Map();
		$disallowed_statuses = array();
	
		$disallowed_statuses[] = Search_Status_Map('sent::external_collections::*root',$status_map);
		$disallowed_statuses[] = Search_Status_Map('pending::external_collections::*root',$status_map);
		$disallowed_statuses[] = Search_Status_Map('chargeoff::collections::customer::*root',$status_map);
	
		$db = ECash_Config::getMasterDbConnection();
	
		$query = "
			SELECT application_status_id, is_watched
			FROM   application
			WHERE  application_id = {$db->quote($application_id)} ";
	
		$st = $db->query($query);
		$row = $st->fetch(PDO::FETCH_OBJ);
	
		if ((in_array($row->application_status_id, $disallowed_statuses)) || $row->is_watched == 'yes') 
		{
			$this->Log("This application is in a disallowed status, no returns processing will happen");
			return true;
		} 
		else 
		{
			return false;
		}
	}
	
	
	function has_failures ($parameters) {
		if(count($parameters->status->fail_set) > 0)
			return 1;

		$this->Log("No failures found in the fail_set for this application!");
		return 0;
	}

	function transaction_type ($parameters) {
		$fail_set = $parameters->status->fail_set;
		$map = $parameters->event_transaction_map;
		$f = $parameters->most_recent_failure;
		if($f->context == 'arrangement' || $f->context == 'partial')
		{
			$this->fail_arrangement_discount($parameters);
		}
		
		switch($map[$f->type]->clearing_type)
		{
			case 'ach':
				return 'ach';
				break;
			case 'quickcheck':
				return 'quickcheck';
				break;
			default:
				return 'other';
				break;
		}
	}
	
	// The app has an ACH failure
	function State_3($parameters) {
		require_once(CUSTOMER_LIB."/ach_returns_dfa.php");
		if(isset($parameters->server) && is_a($parameters->server, "Server"))
		{
			if (!isset($dfas['ach_returns'])) {
				$dfa = new ReschedulingDFA($parameters->server);
				$dfa->SetLog($parameters->log);
				$dfas['ach_returns'] = $dfa;
			} else {
				$dfa = $dfas['ach_returns'];
			}

			$dfa->run($parameters);
		} else {
			throw new Exception("Server object not passed to FailureDFA!  This is required for the ACH Reschuling DFA!");
		}

	}

	// The app has a QuickCheck Failure
	function State_4($parameters) {
		require_once(CUSTOMER_LIB."/quickcheck_returns_dfa.php");
		
		if (!isset($dfas['quickchecks'])) {
			$dfa = new QuickCheckDFA();
			$dfa->SetLog($parameters->log);
			$dfas['quickchecks'] = $dfa;
		} else {
			$dfa = $dfas['quickchecks'];
		}

		$dfa->run($parameters);
		
	}
	
	// The app has a failure that is Non-ACH and Non-QuickCheck
	function State_5($parameters) {
		require_once(CUSTOMER_LIB."/other_returns_dfa.php");
		if (!isset($dfas['other'])) {
			$dfa = new Other_Transaction_DFA();
			$dfa->SetLog($parameters->log);
			$dfas['other'] = $dfa;
		} else {
			$dfa = $dfas['other'];
		}

		$dfa->run($parameters);
		
	}
	
	// The app has no failures in it's fail_set
	function State_6($parameters) {
		return false;
	}
	
}


?>
