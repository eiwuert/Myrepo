<?php


require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SERVER_CODE_DIR . 'edit.class.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(CUSTOMER_LIB. "complete_schedule_dfa.php");


class API_Schedule implements Module_Interface
{
	private $permissions;

	public function __construct(Server $server, $request, $module_name)
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
		$this->permissions = array(
			array('loan_servicing', 'customer_service',  'transactions'),
			array('loan_servicing', 'account_mgmt', 'transactions'),
			array('collections', 'internal', 'transactions'),
			array('fraud', 'watch', 'transactions'),
		);
	}

	public function get_permissions()
	{
		return $this->permissions;
	}

	public function Main()
	{
		$input = $this->request->params[0];

		switch ($input->action)
		{
			case 'schedule':
				return $this->get_scheduled($input->application_id);

			case 'arrangements':
				return $this->get_arrangements($input->application_id);

			case 'preview':
				$arrangement_events = $this->get_events_for_payment($input->payments[0],$input->application_id);
				$schedule = Complete_Schedule($input->application_id, FALSE, $arrangement_events,true);
				return $this->render_schedule($input->application_id, $schedule);

			case 'save_adjustment':
				$arrangement_events = $this->get_events_for_payment($input->next_payment_adjustment->rows[0],$input->application_id);
				$new_schedule = Complete_Schedule($input->application_id, TRUE, $arrangement_events,true);
				break;

			default:
				throw new Exception("Unknown action {$input->action}");

		}
	}

	private function get_arrangements($app_id)
	{
		$result = array();
		foreach ($this->get_scheduled($app_id) as $e)
		{
			if ($e->context === 'arrangement' || $e->context === 'partial')
			{
				$result[]=$e;
			}
		}
		return $result;
	}

	private function get_scheduled($app_id)
	{
		$schedule = Fetch_Schedule($app_id);
		$result = array();
		foreach ($schedule as $e)
		{
			if (($e->status === 'scheduled' || $e->status === 'registered') && strtotime($e->date_event) >= strtotime(Date('Y/m/d',time())))
			{
				$obj = new StdClass;
				$obj->type = $e->type;
				$obj->event_date = date('m/d/Y',strtotime($e->date_event));
				$obj->date = date('m/d/Y',strtotime($e->date_effective));
				$obj->amount = $e->principal + $e->service_charge + $e->fee;
				$obj->status = $e->status;
				$obj->context = $e->context;
				$result[]=$obj;
			}
		}
		return $result;
	}

	private function get_events_for_payment($payment,$app_id)
	{
		// paydate calculator is handy
	    $pd_calc = new Pay_Date_Calc_3(Fetch_Holiday_List());
		// Mangle payment date?
		$payment->date = preg_replace('^(\d{1,2})/(\d{1,2})/(\d{4})^', '${3}-${1}-${2}', $payment->date);
		$remaining = -$payment->amount;
		$balance_info = Fetch_Balance_Information($app_id);
		if(in_array($payment->type, array('moneygram', 'money_order', 'credit_card', 'western_union')))
		{
			$this->last_date = $payment->date;
		}
		else 
		{
			$this->last_date = $pd_calc->Get_Next_Business_Day($payment->date);
		}
		
		switch($payment->type)
		{
			case 'payment_arranged':
				$interest_type = 'payment_service_chg';
				$fee_type = 'payment_arranged';
				$principal_type = 'payment_arranged';
			break;
			case 'moneygram':
				$interest_type = 'moneygram';
				$fee_type = 'moneygram';
				$principal_type = 'moneygram';
			break;
			case 'money_order':
				$interest_type = 'money_order';
				$fee_type = 'money_order';
				$principal_type = 'money_order';
			break;
			case 'credit_card':
				$interest_type = 'credit_card';
				$fee_type = 'credit_card';
				$principal_type = 'credit_card';
			break;
		}
		
		if ($balance_info->fee_pending > 0)
		{
			$amount_for_this = max($remaining, -$balance_info->fee_pending);
			$remaining -= $amount_for_this;
		}


		if ($payment->interest + $balance_info->service_charge_pending > 0)
		{
			/* GF #21380
			 * This method was grabbing the current company rule set for
			 * whichever loan-type it was being passed. I changed it to
			 * grab the rule set specific to this app. This way when the company
			 * changes their global rules, it's not affecting this application.
			 */
			$rules = ECash::getApplicationById($app_id)->getBusinessRules();

			if($rules['service_charge']['svc_charge_type'] === 'Daily')
			{
				$temp_schedule =  Fetch_Schedule($app_id);
				$amount = Interest_Calculator::scheduleCalculateInterest($rules, $temp_schedule, $this->last_date);
				$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($temp_schedule,false);
				$first_date = $paid_to['date'];

				$days = Interest_Calculator::dateDiff($first_date, $this->last_date);
				$first_date_display = date('m/d/Y', strtotime($first_date));
				$last_date_display = date('m/d/Y', strtotime($this->last_date));
				$comment = "Payment For Interest accrued from {$first_date_display} to {$last_date_display} ($days days)";
				if (($amount + $balance_info->service_charge_pending) > 0) 
				{
	
				$amount_for_this = max($remaining, -($amount + $balance_info->service_charge_pending));
				$new_events[] = Schedule_Event::MakeEvent(
					$payment->date,
					$this->last_date,
					Array(Event_Amount::MakeEventAmount('service_charge', $amount_for_this)),
					$interest_type,
					$comment,
					'scheduled',
					'manual',
					null,
					null,
					true);
				$remaining -= $amount_for_this;
				}

		}
		else 
		{
			if (empty($rules['service_charge']['svc_charge_percentage'])) throw new Exception ("Required rule 'service_charge'->'svc_charge_percentage' for non-daily ({$rules['service_charge']['svc_charge_type']}) not supplied.<pre>" . print_r($rules, true) . "</pre>");
			$amount = ($rules['service_charge']['svc_charge_percentage'] * $balance_info->principal_balance);
			$comment = "Service charge on {$balance_info->principal_balance}"; 

			if (($balance_info->service_charge_pending) > 0) 
			{
				$amount_for_this = max($remaining, -($balance_info->service_charge_pending));
			$new_events[] = Schedule_Event::MakeEvent(
				$payment->date,
					$this->last_date,
					Array(Event_Amount::MakeEventAmount('service_charge', $amount_for_this)),
					$interest_type, 
					$comment,
					'scheduled', 
					'manual', 
					null, 
					null, 
					true);
				$remaining -= $amount_for_this;
			}
	
		}
		
			//Create event even with zero amount to trigger interest being added
			$new_events[] = Schedule_Event::MakeEvent(
				$payment->date,
				$this->last_date,
					Array(Event_Amount::MakeEventAmount('principal', $remaining)),
				$principal_type, 
				isset($payment->desc) ? $payment->desc : 'Arranged Payment',
					'scheduled',
					'manual',
					null,
					null,
					true);
	
			return $new_events;
		}
	}
	private function render_schedule ($application_id, $schedule)
	{
		// Fill in some missing data first
		foreach ($schedule as $row)
		{
			Schedule_Event::Set_Amounts_From_Event_Amount_Array($row, $row->amounts);
			$row->event_name = Get_Event_Type_Name($row->type, $this->server->company_id);
		}
		// We'll need the balance info too.
		$balance_info = Fetch_Balance_Information($application_id);
		// Use the appropriate object to get the html
		require_once(CLIENT_CODE_DIR . 'render_transactions_table.class.php');
		$trans_render = new Render_Transactions_Table();
		$formated_schedule = $trans_render->format_schedule(true,$schedule,null,null,$balance_info->total_pending);
		return $trans_render->Build_Schedule($formated_schedule);
	}
}
?>
