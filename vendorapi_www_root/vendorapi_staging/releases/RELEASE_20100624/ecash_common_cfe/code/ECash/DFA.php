<?php

  /**
   * Deterministic Finite Automaton
   *
   * Replaces lib/dfa.php
   */
class ECash_DFA
{	
	protected $states;
	protected $tr_functions;
	protected $transitions;
	protected $initial_state;	
	protected $final_states;	
	protected $descriptions;
	protected $log_prefix;
	protected $details;
	protected $application;
	// For a run
	protected $current_state;
	
	// For logging
	protected $log;

	public function __construct()
	{
		// Hash-index the lookup arrays, 
		// b/c it's faster for lookup speed
		$this->details = array();
		$this->states = array_flip($this->states);  
		$this->final_states = array_flip($this->final_states);
	}

	public function run($parameters)
	{
		if (!isset($this->states[$this->initial_state])) throw new Exception("Initial state not in state list");

		// Add Application ID to Logging!!
		$this->log_prefix = '';
		if(isset($parameters->application_id))
		{
			$this->application = ECash::getApplicationById($parameters->application_id);
			$this->log_prefix = "[AppID:{$parameters->application_id}]";
		}
		
		if (is_array($parameters)) $parameters = (object) $parameters;

		if (isset($parameters->log)) $this->log = $parameters->log;
		
		$this->current_state = $this->initial_state;
		
		$parameters->current_state = $this->initial_state;
		
		$this->Log("Starting state: {$this->current_state}");
		while (!isset($this->final_states[$this->current_state]))
		{
			$this->Log("Current state: {$this->current_state} ({$this->tr_functions[$this->current_state]})");
			$function = $this->tr_functions[$this->current_state];
			$result = $this->$function($parameters);
			$this->Log("For function ".$this->tr_functions[$this->current_state]. ": {$result}");
			$new_state = $this->transitions[$this->current_state][$result];
			if ($new_state === null) 
			{
				throw new Exception("$log_prefix Did not return a valid value for this state");
			} 
			else 
			{
				$this->Log("Transition: {$this->current_state} -> {$new_state}");
				$this->current_state = $new_state;
				$parameters->current_state = $new_state;
			}
		}
		$this->Log("Returning data from end state {$this->current_state} {$this->tr_functions[$this->current_state]}");
		return ($this->take_action($this->current_state, $parameters));		
	}

	protected function getDFADetails()
	{
		return $this->details;
	}
	
	// I would like this to be able to recreate the DFA instance from the constituent
	// pieces, perchance our documentation gets lost or whatever.
	public function generate_dfa_map() {
		if (!isset($this->states) || !isset($this->initial_state) ||
		    !isset($this->final_states) || !isset($this->tr_functions) ||
		    !isset($this->transitions)) return null;

		$str = "Initial state: {$this->initial_state}\n\n";
		foreach($this->states as $s) 
		{
			$str .= "State {$s}: {\n";
			if (isset($this->descriptions))
				$str .= "Description: {$this->descriptions[$s]}\n";
			if (isset($this->final_states[$s])) 
			{
				$str .= "Final state: Yes\n";
			} 
			else 
			{
				$str .= "Final state: No\n";
				$str .= "test function: {$this->tr_functions[$s]}\n";
				foreach ($this->transitions[$s] as $result => $tr) 
				{
					$str .= "\t{$result} -> {$tr}\n";
				}			       
			}
			$str .= "}\n\n";
		}
		return $str;
	}

	// For now, we completely separate out the different actions to take in the different states.
	// It may be slower, but it's logically easier to debug.
	protected function take_action($state, $parameters) 
	{
		$response_function = empty($this->tr_functions[$state])? "State_{$state}" : $this->tr_functions[$state];
		return( $this->$response_function($parameters) );
	}

	/**
	 * Was SetLog but that shouldn't cause any problems
	 */
	public function setLog($log) {
		$this->log = $log;
	}
	
	protected function Log($message, $level = LOG_DEBUG) {
		
		if(! isset($this->log))
			return;
		
		// This will put the appropriate prefix in.
		if(isset($this->log_prefix)) 
		{

			$this->log->Write($this->log_prefix . ' ' . $message, $level );
			$this->details[] = ($this->log_prefix . ' ' . $message);
		} 
		else 
		{
			$this->log->Write($message, $level );
			$this->details[] = ($message);
		}
	}

	/*
	 * These (below) are here b/c eventually they will be used by both
	 * ECash_DFA_CompleteSchedule & ECash_DFA_Reschedule
	 */
	
	/**
	 * This method will use the business rules to determine the appropriate
	 * re-attempt date for the customer's first set of returns.
	 *
	 * @param Object $parameters
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	protected function getFirstReturnDate($parameters)
	{
		$rules = $parameters->rules;
		$reattempt_date = $rules['failed_pmnt_next_attempt_date']['1'];
		return $this->getReattemptDate($reattempt_date, $parameters);
	}
	
	/**
	 * This method will use the business rules to determine the appropriate
	 * re-attempt date for all returns after the customer's first.
	 *
	 * @param Object $parameters
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	protected function getAdditionalReturnDate($parameters)
	{
		$rules = $parameters->rules;
		$reattempt_date = $rules['failed_pmnt_next_attempt_date']['2'];
		return $this->getReattemptDate($reattempt_date, $parameters);		
	}

	/**
	 * Returns the appropriate reattempt date based on the business rule
	 * value in 'failed_pmt_next_attempt_date', and the delay passed in.
	 * 
	 * If the rule value doesn't match an appropriate case in the switch
	 * statement, the fallback is the customer's next pay day.
	 *
	 * 
	 * @param string $reattempt_date
	 * @param obj $parameters
	 * @param int $delay
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	protected function getReattemptDate($reattempt_date, $parameters, $delay = 0)
	{
		$application = ECash::getApplicationByID($parameters->application_id);
		//Using the centralized function in scheduling.func, because this logic is used in a couple places.
		$date_pair = getReattemptDate($reattempt_date,$application,$delay);
				
		$this->Log("Scheduling reattempt for {$date_pair['event']} - {$date_pair['effective']} based on the rule '{$reattempt_date}' with a delay of {$delay}");
		return $date_pair;
		
	}	
}
?>
