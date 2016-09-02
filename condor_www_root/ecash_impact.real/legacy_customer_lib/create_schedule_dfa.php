<?php

require_once('dfa.php');
require_once(SERVER_CODE_DIR . "schedule_event.class.php");

/**
 * Generates a fresh schedule from scratch.
 *
 * Current assumptions:
 *
 * 1) There is no initial principal balance.
 * 2) The fund_amount member is defined in the parameters.
 * 3) The parameters also contain a list of dates to be used as
 *    pay dates. Need to have "period skip" accounted for.
 * 4) The rules have been run through the "Prepare_Rules" function.
 */

class CreateScheduleDFA extends DFA {

	public $principal_balance;
	public $scs_assessed;
	public $new_events;
	public $dates;
	protected $fund_effective;
	const NUM_STATES = 13;

	public function __construct() {
		for ($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(1,12);
		$this->tr_functions = array(
					    0 => 'has_fund_event',
					    2 => 'create_fund_event',
					    3 => 'funding_method',
					    4 => 'add_converted_service_charge_events',
					    5 => 'ascap',
					    6 => 'payout',
					    7 => 'at_sc_only_threshold',
					    8 => 'ascap',
					    9 => 'has_principal_balance',
					   10 => 'add_principal_payment',
					   11 => 'ascap',
					   );

		$this->transitions = array(
					   0 => array( 0 => 2, 1 => 1),
					   2 => array( 1 => 3),
					   3 => array( 'Fund' => 7, 'Fund_Paydown' => 4, 'Fund_Payout' => 5),
					   4 => array( 1 => 7),
					   5 => array( 1 => 6),
					   6 => array( 1 => 12), // Commit changes
					   7 => array( 0 => 8, 1 => 9),
					   8 => array( 1 => 7),
					   9 => array( 0 => 12, 1 => 10),
					  10 => array( 1 => 11),
					  11 => array( 1 => 9));

		parent::__construct();
	}
	
	/**
	 * Do nothing. Don't create a newly funded loan if we see that someone was already funded.
	 */
	function State_1($parameters) { return false; }


	/**
	 * Take the new events list, and record all the events into the database.
	 */
	function State_12($parameters) {
		return $this->new_events; 

		
	}


	/**
	 * Checks the current schedule (if one exists) for a
	 * fund event that is either scheduled, pending, or complete.
	 */
	function has_fund_event($parameters) {
		if (!isset($parameters->schedule)) return 0;
		
		foreach ($parameters->schedule as $e)
		{
			
			if (in_array($e->type, array('loan_disbursement', 'moneygram_disbursement', 'check_disbursement'))
				&& in_array($e->status, array('scheduled','pending','complete')))
			{
				 return 1;
			}
		}
	
		return 0;
	}

	/**
	 * Creates the loan disbursement
	 *
	 * @param Object $parameters
	 * @return integer 1
	 */
	function create_fund_event(&$parameters) 
	{
		$today = strtotime(date('Ymd'));
		$pdc = $parameters->pdc;
		$fund_date = $parameters->fund_date;
		$fund_date_stamp = strtotime($fund_date);

		// This should always be true, but just in case we're going to handle
		// it below.
		if(($fund_date_stamp === $today) && (Has_Batch_Closed($parameters->company_id)))
		{
			$date_event     = $pdc->Get_Next_Business_Day($fund_date);
			$date_effective = $pdc->Get_Next_Business_Day($date_event);
			
			// Adjust the fund_date so that when we create the first
			// Service Charge Assessment it'll use the correct date.
			$parameters->fund_date = $date_event;
		}
		else if(($fund_date_stamp === $today) && (! $pdc->isBusinessDay($fund_date_stamp)))
		{
			// It's not a business day, but some companies will
			// send their batches on the weekends or holiday's anyways
			// so we want to keep the date_event for today, but adjust
			// the date_effective to reflect the day it will actually
			// hit the customer's account. [BR]
			if(($parameters->rules['ach_weekend_batch']['allow_weekend'] == 'Yes' && $pdc->Is_Weekend($fund_date_stamp)) ||
				($parameters->rules['ach_weekend_batch']['allow_holiday'] == 'Yes' && $pdc->Is_Holiday($fund_date_stamp)))
			{

				if($parameters->rules['ach_weekend_batch']['day_type'] == 'Business')
				{
					$date_event     = $pdc->Get_Business_Days_Forward($fund_date, $parameters->rules['ach_weekend_batch']['action_forward']);
					$date_effective = $pdc->Get_Business_Days_Forward($fund_date, $parameters->rules['ach_weekend_batch']['effective_forward']);
				}
				else
				{
					$date_event     = $pdc->Get_Calendar_Days_Forward($fund_date, $parameters->rules['ach_weekend_batch']['action_forward']);
					$date_effective = $pdc->Get_Calendar_Days_Forward($fund_date, $parameters->rules['ach_weekend_batch']['effective_forward']);					
				}
			}
			else
			{
				$date_event     = $fund_date;
				$date_effective = $pdc->Get_Business_Days_Forward($fund_date, 2);
			}
		}
		else
		{
			$date_event     = $fund_date;
			$date_effective = $pdc->Get_Next_Business_Day($date_event);
		}
		$this->fund_effective = $date_effective;
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', $parameters->fund_amount);
		$this->new_events[] = Schedule_Event::MakeEvent($date_event, $date_effective,
					  $amounts, 'loan_disbursement','Fund of Loan');
		return 1;
	}

	/**
	 * ascap = assess_service_charge_and_payment
	 * It does...just that.
	 *
	 * 1) Create an SC assessment.
	 * 2) Shift off dates
	 * 3) Create the SC payment.
	 */
	function ascap($parameters) {
		
		if($this->principal_balance == 0) return 1;
		
		$sc_amount = ($parameters->rules['interest'] * $this->principal_balance);
		
		// If this is the first SC Assessment, use the fund date.
		if(	$this->scs_assessed == 0
			|| ($parameters->fund_method == 'Fund_Paydown'
				&& $this->scs_assessed == $parameters->rules['max_svc_charge_only_pmts'])) 
		{
			if(($parameters->rules['ach_weekend_batch']['allow_weekend'] == 'Yes' && $parameters->pdc->Is_Weekend(strtotime($parameters->fund_date))) ||
			   ($parameters->rules['ach_weekend_batch']['allow_holiday'] == 'Yes' && $parameters->pdc->Is_Holiday(strtotime($parameters->fund_date))))
			{
				$sc_date = 	$parameters->pdc->Get_Business_Days_Forward($parameters->fund_date, $parameters->rules['ach_weekend_batch']['action_forward']);
			}
			else
			{
				$sc_date = $parameters->pdc->Get_Last_Business_Day($this->fund_effective);
			}
		} else {
			$sc_date = $this->dates['event'][0];
		}
			
		// Create the SC assessment
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', $sc_amount);
		$this->new_events[] = Schedule_Event::MakeEvent($sc_date,
														$sc_date,
					  $amounts, 'assess_service_chg', 'Scheduled Service Charge Assessment');

		// If there are no service charges assessed, do not shift the dates yet.
		if(($this->scs_assessed != 0 && $parameters->fund_method != 'Fund_Paydown')
			|| ($parameters->fund_method == 'Fund_Paydown'
				&& $this->scs_assessed > $parameters->rules['max_svc_charge_only_pmts'])) 
		{
			// Now shift our dates off - include any period skips we might need.
			array_shift($this->dates['event']);
			array_shift($this->dates['effective']);
		}

		// Count it!
		$this->scs_assessed++;
		
		// Now create the SC payment event
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$sc_amount);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], 
														$this->dates['effective'][0],
					  $amounts, 'payment_service_chg', 'Service Charge Payment');

		return 1;
	}

	function at_sc_only_threshold($parameters) {
		return (($this->scs_assessed > $parameters->rules['service_charge']['max_svc_charge_only_pmts'])?1:0);
	}
	
	function has_principal_balance($parameters) {
		return (($this->principal_balance > 0)?1:0);
	}

	function add_principal_payment($parameters) {
		$princ_decrement = min($parameters->rules['principal_payment_amount'], $this->principal_balance);
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$princ_decrement);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal','Principal Payment');
		$this->principal_balance -= $princ_decrement;
		$this->Log("Adding payment of $princ_decrement with {$this->principal_balance } remaining.");
		return 1;
	}	
	
	// Used as a simple way to determind what funding method to use	
	function funding_method($parameters) {
		// Default to Fund
		if (!empty($parameters->fund_method)) {
			return $parameters->fund_method;
		} else {
			return 'Fund';
		}
	}

	// This is used to insert empty service charge only events for applications that are funded with
	// the Paydown method, where the customer starts making principal payments right away.
	function add_converted_service_charge_events($parameters) {
		$date = $parameters->fund_date;
		$num_sco_events = $parameters->rules['service_charge']['max_svc_charge_only_pmts'];
		for ($x = 0; $x < $num_sco_events; $x++) {
			$this->new_events[] = Schedule_Event::MakeEvent($date, $date, array(), 'converted_sc_event', 'Placeholder for SC Only Event for Paydown Funding');
			$this->Log("Adding converted service charge.");
			$this->scs_assessed++;
		}
		return 1;
	}

	// Payout the entire principal balance
	function payout($parameters) {
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$this->principal_balance);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal');
		$this->Log("Adding payout of {$this->principal_balance }");
		return 1;
	}
	
	function run($parameters) {
		
		$amount      = $parameters->amount;
		$start_date  = $parameters->fund_date;
		$info        = $parameters->info;
		$rules       = $parameters->rules;
		$fund_amount = $parameters->fund_amount;
		$this->scs_assessed = 0;

		
		// Figure out the dates..
		$payments = $amount / $rules['principal_payment_amount'];
		$total_payments = $rules['service_charge']['max_svc_charge_only_pmts'] + $payments + 1;

		$dates = Get_Date_List($info, $start_date, $rules, ($total_payments * 4));

		if (!isset($rules['grace_period'])) $rules['grace_period'] = 10;

		$gp = $parameters->pdc->Get_Calendar_Days_Forward($start_date, $rules['grace_period']);

		// If the date_first_payment is greater than the GP, use it.
		if(strtotime($info->date_first_payment) > strtotime($gp)) {
			$min_date = $info->date_first_payment;
		} else {
			$min_date = $gp;
		}
		
		// Note: Get_Date_List should take care of this:
		// Make sure our first payment is at least past the grace period.
		while(strtotime($dates['effective'][0]) < strtotime($min_date)) {
			array_shift($dates['event']);
			array_shift($dates['effective']);
		}
		
		$this->dates = $dates;
		$this->principal_balance = $fund_amount;
		
		return parent::run($parameters);

	}

}

?>
