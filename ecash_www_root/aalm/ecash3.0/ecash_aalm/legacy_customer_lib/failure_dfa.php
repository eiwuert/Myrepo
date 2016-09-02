<?php
require_once('dfa.php');
require_once(SQL_LIB_DIR . '/fetch_ach_return_code_map.func.php');
require_once(SQL_LIB_DIR . '/fetch_card_return_code_map.func.php');
require_once(SQL_LIB_DIR . '/comment.func.php');
require_once(SQL_LIB_DIR . '/react.func.php');

/**
 * This is the entry-point for any times a transaction failure is recorded.
 * It will gather together the transactional information for the account in
 * question, and learning that decide which branch-DFA to use for actually
 * taking action.
 */

class FailureDFA extends DFA {
	
	public $application_id;
	const NUM_STATES = 7;
	
	public function __construct()
	{

		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(2,3,4,5,7);
		$this->tr_functions = array(
			 0 => 'has_failures',             // Good
			 1 => 'transaction_type',         
			 6 => 'has_balance',
			 8 => 'is_inactive_paid',
			 10 => 'manage_preacts',
			 11 => 'has_scheduled_events',

		);

		$this->transitions = array ( 
			 0 => array(0 => 5, 1 => 10),
			 1 => array('ach' => 2, 'quickcheck' => 3,	'other' => 4),
			 6 => array(0 => 11, 1 => 8),
			 8 => array(0 => 1, 1 => 1),
			 10 => array(0 => 6, 1 => 6),
			 11 => array(0 => 7, 1 => 8),
		); 
			
		parent::__construct();
	}
	// Here I'm setting up the 
	function run ($parameters) 
	{

		require_once(SQL_LIB_DIR ."application.func.php");
		require_once(SQL_LIB_DIR ."fetch_status_map.func.php");
		require_once(SQL_LIB_DIR ."scheduling.func.php");
		require_once(SQL_LIB_DIR ."util.func.php");
		require_once(LIB_DIR."common_functions.php");
		require_once(LIB_DIR."status_utility.class.php");

		// Yes, I threw in everything but the kitchen sink in here.
		$application_id = $parameters->application_id;
		
		$data     = Get_Transactional_Data($application_id);
		$holidays = Fetch_Holiday_List();

		$current_schedule = Fetch_Schedule($application_id);
		$status = Analyze_Schedule($current_schedule, true);
		$log = get_log("scheduling");
		
		$parameters->log = get_log("scheduling");
		$parameters->info = $data->info;
		$parameters->rules = Prepare_Rules($data->rules, $data->info);
		$parameters->schedule = $current_schedule;
		$parameters->verified = $status;
		$parameters->grace_periods = Get_Grace_Periods();
		$parameters->pd_calc = new Pay_Date_Calc_3($holidays);
		$parameters->arc_map = Fetch_ACH_Return_Code_Map();
		$parameters->crc_map = Fetch_Card_Return_Code_Map();
		$parameters->event_transaction_map = Load_Transaction_Map(ECash::getCompany()->company_id);
		$parameters->app_status_map = Fetch_Status_Map();
		$parameters->application_status_id = $data->info->application_status_id;
		$parameters->application_status_chain = $parameters->app_status_map[$parameters->application_status_id]['chain'];
		$parameters->is_watched = $data->info->is_watched;
		$parameters->most_recent_failure = Grab_Most_Recent_Failure($application_id, $parameters->schedule);

		if (!$parameters->company_id) $parameters->company_id = ECash::getCompany()->company_id;
		
		$parameters->react_applications = Get_Reacts_From_App($application_id, $parameters->company_id);

		/**
		 * AFAIK, the stopping location is supposed to be the array index of the NEXT scheduled event, 
		 * or the end of the schedule.  From what I can see, it's ALWAYS the end of the schedule, so 
		 * the following will never return an event at that index. [BR]
		 */
		if (isset($current_schedule[$status->stopping_location]->date_event) || $current_schedule[$status->stopping_location]->is_shifted)
		{
  			$parameters->next_action_date = $current_schedule[$status->stopping_location]->date_event;
  			$parameters->next_due_date = $parameters->pd_calc->Get_Next_Business_Day($parameters->next_action_date);
  		}
  		else
  		{
  			$parameters->next_action_date = false;
  			$parameters->next_due_date = false;
  		}

		if (!$parameters->company_id) $parameters->company_id = ECash::getCompany()->company_id;

		// Get the app status
		$app_status = Fetch_Application_Status($application_id);
		foreach ($app_status as $key => $value) $parameters->$key = $value;

		$parameters->status   = Analyze_Schedule($parameters->schedule);

		return parent::run($parameters);
	}

	function has_failures ($parameters) 
	{
		if(count($parameters->status->fail_set) > 0)
			return 1;

		return 0;
	}

	function transaction_type ($parameters) 
	{
		$fail_set = $parameters->status->fail_set;
		$map = $parameters->event_transaction_map;
		$f = $parameters->most_recent_failure;
	
		switch($map[$f->type]->clearing_type)
		{
			case 'ach':
			case 'card':
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

	function has_balance($parameters)
	{
		$status = $parameters->status;
		if($status->posted_and_pending_total <= 0) 
		{
			return 0;
		}
		
		return 1;
	}
	
	function has_scheduled_events($parameters) 
	{
		if ($parameters->status->num_scheduled_events > 0) 
		{
			return 1;
		}
		return 0;
	}

	function is_inactive_paid($parameters) 
	{
		//Now we're actually going to determine whether or not the applicatiion's inactive paid by what the application's current
		//status is, rather than what it was when the DFA started.  This will prevent inaccuracies due to the status changing
		//within the DFA, like in #22508 [W!-01-08-2009][#22508]
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$application =  ECash::getApplicationByID($parameters->application_id);

		return ($application->application_status_id == $asf->toId('paid::customer::*root'));
	}

	//if this account failed we need to take all preacts
	//and withdraw them [rlopez] [mantis:5060]
	function manage_preacts($parameters)
	{
		$react_app = null;

		for($i=0; $i<count($parameters->react_applications); $i++)
		{
			$react_app = $parameters->react_applications[$i];
			if($react_app->olp_process == "ecashapp_preact")
			{
				// If the last payment fails, automatically move the new react application to a withdraw status. 
				Update_Status(null, $react_app, 'withdrawn::applicant::*root');
				//Add a comment: ?Withdrawn because last payment did not clear.?
				$agent_id 	= Fetch_Current_Agent();
				$comment 	= "Withdrawn because last payment did not clear.";
				Add_Comment($parameters->company_id, $react_app->application_id, $agent_id, $comment, "preact_withdraw");
			}
		}			
		
		return 0;
	}
	
	// The app has an ACH failure
	function State_2($parameters) 
	{
		if(isset($parameters->server) && is_a($parameters->server, "Server"))
		{
			//if (!isset($dfas['ach_returns'])) 
			//{
				//$returns_dfa = ECash::getFactory()->getClassString('DFA_Returns');
				//$dfa = new $returns_dfa($parameters->server);
				require_once(ECASH_COMMON_DIR . '/code/ECash/DFA/Returns.php');
				$dfa = new ECash_DFA_Returns($parameters->server);
				$this->Log("Invoked ECash_DFA_Returns.");
				$dfa->setLog($parameters->log);
				//$dfas['ach_returns'] = $dfa;
			/*
			} 
			else 
			{
				$dfa = $dfas['ach_returns'];
			}
			*/
			$dfa->run($parameters);
		} 
		else 
		{
			throw new Exception("Server object not passed to FailureDFA!  This is required for the ACH Reschuling DFA!");
		}

	}

	// The app has a QuickCheck Failure
	function State_3($parameters) 
	{
		require_once(CUSTOMER_LIB."/quickcheck_returns_dfa.php");
		
		if (!isset($dfas['quickchecks'])) 
		{
			$dfa = new QuickCheckDFA();
			$dfa->SetLog($parameters->log);
			$dfas['quickchecks'] = $dfa;
		} 
		else 
		{
			$dfa = $dfas['quickchecks'];
		}

		$dfa->run($parameters);
		
	}
	
	// The app has a failure that is Non-ACH and Non-QuickCheck
	function State_4($parameters) 
	{
		require_once(CUSTOMER_LIB."/other_returns_dfa.php");
		if (!isset($dfas['other'])) 
		{
			$dfa = new Other_Transaction_DFA($parameters->server);
			$dfa->SetLog($parameters->log);
			$dfas['other'] = $dfa;
		} 
		else 
		{
			$dfa = $dfas['other'];
		}

		$dfa->run($parameters);
		
	}
	
	// The app has no failures in it's fail_set
	function State_5($parameters) 
	{
		throw new Exception ("No failures found in the fail_set for this application!");
	}
	
	// The app has no balance, skip processing
	function State_7($parameters) 
	{
	}
	
}


?>
