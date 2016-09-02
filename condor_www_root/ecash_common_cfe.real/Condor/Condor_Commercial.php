<?php

/**
 * @package condor_Commercial
 * 
 * <b>Revision History</b>
 * <ul>
 *   <li><b>2008-6-10 - Richardb</b><br>
 *     Condor Token API created for use with eCash Commerical.
 *   </li>
 * 
 * </ul>
 */
 
require_once("Condor_Tokens.php");

class Condor_Commercial extends Condor_Tokens 
{
	
	/**
	 * Creation of the Condor Token object. 
	 * 
	 * eCash LDB connection need to be passed for collecting
	 * application information along with company id and application id. The Config 6 object with 
	 * space key will also need to be passed for collecting company specific information. Once all
	 * the parameters are set the function Get_Tokens will processing the information and retrun 
	 * the Condor Token object to be used with Condor.
	 * 
	 * @param object $ldb_db
	 * @param object $config_6
	 * @param int $company_id
	 * @param int $space_key
	 * @param int $application_id
	 */
	public function __construct($ldb_db,$config_6,$company_id,$space_key,$app)
	{
		$this->Set_LDB($ldb_db);
		$this->Set_Config_6($config_6);
		$this->Set_Company_ID($company_id);
		$this->Set_Space_Key($space_key);
		$this->Set_Application($app);
		$this->Set_Generic_Email(null,null,null);
		$this->Set_Login_ID(null);
	}
	/**
	 * Calculate monthly net
	 * 
	 *
	 * @param string $income_frequency
	 * @param float $income_monthly
	 */	
	public function Calculate_Monthly_Net($income_frequency, $income_monthly)
	{
		return 	$income_monthly;
	}
	/**
	 * Determine if any events exist in applications schedule
	 * 
	 *
	 * @param array $events
	 */
	public function Application_Has_Events_By_Event_Names(array $events)
	{
//		foreach($events as $event)
//		{
//			$transactions = $this->app->getSchedule()->Analyzer->getTransactionTypeCount($event);
//			if($transactions > 0)
//				return true;
//		}
//		return false;
		return Application_Has_Events_By_Event_Names($this->app->getId(), $events);
	}
	/**
	 * Retrieve total amount from a set of event types
	 * 
	 *
	 * @param array $events
	 */
	public function Fetch_Balance_Total_By_Event_Names($events)
	{
//		$transactions = array();
//		$total = 0;
//		foreach($events as $event)
//		{
//			$transactions = array_merge($transactions, $this->app->getSchedule()->Analyzer->getTransactionByType($event));
//	
//		}
//		foreach($transactions as $transaction)
//		{
//			$total = $transaction->getTotalAmount();
//		}
//		return $total;
		return Fetch_Balance_Total_By_Event_Names($this->app-getId(), $events);
	}
	/**
	 * Get Tokens
	 * 
	 * The token processor. This function will initalize the based components to create condor 
	 * (Site Config, Holdays, Qualify Info, Business Rules, Campagin Info, and Pay Day Calc) then 
	 * run the data processor (Get Condor Data).
	 *
	 * @return object $tokens
	 */
	public function Get_Tokens()
	{
		$this->Set_Campaign_Info($this->getCampaignInfo());
		$this->Set_Site_Config();
		$this->Set_Business_Rules();
		$this->Set_Holiday_List();
		$this->Set_Pay_Date_Calc();
		$tokens = $this->getCondorData();
		return (array)$tokens;
	}
	
	protected function format_time($mil)
	{
		if(empty($mil))
		{
			return 'Closed';
		}
		if($mil > 1200)
		{
			return intval((substr(($mil),0,2) - 12)) . ':' . substr(($mil),2)  . 'pm';
		}
		elseif($mil < 1200)
		{
			return intval((substr(($mil),0,2))) . ':' . substr(($mil),2)  . 'am';
		}
		else
		{
			return intval((substr(($mil),0,2))) . ':' . substr(($mil),2)  . 'pm';
		}
	}
	/**
	 * Build Application links for react and esig
	 * 
	 *
	 * @param object $data
	 */
	private function getlinks(&$data)
	{
		switch(strtolower($data->company_name_short))
		{
			case 'generic':
				$site_id = 1; 
				break;
			default: $site_id = null;
		}
		if($site_id)
		{
			$data->company_site_id = $site_id;	
		}
		$react_url = eCash_Config::getInstance()->REACT_URL;
		$esig_url = eCash_Config::getInstance()->ESIG_URL;
		
		$login_hash = md5($this->app->getId() . $site_id . 'L08N54M3');
		$encoded_app_id = urlencode(base64_encode($this->app->getId()));
		$resign = urlencode(base64_encode('resign'));
		$data->react_url = "{$react_url}?applicationid={$encoded_app_id}&login={$login_hash}";
		
		switch (strtolower($data->company_name_short))
		{
			
			case 'generic':
			default:
				$data->esig_url = "{$esig_url}/?page=ecash_sign_docs&application_id=". urlencode(base64_encode($this->app->getId()))."&login=" . md5($this->app->getId() . "encode_string") . "&ecvt&force_new_session" ;
				$data->cs_login_link = "{$esig_url}/?page=ent_cs_login&application_id=". urlencode(base64_encode($this->app->getId()))."&login=" . md5($this->app->getId() . "encode_string") . "&ecvt&force_new_session" ;
			break;	
				

		}
		
	}	
	
	/**
	 * Get Condor Data
	 * 
	 * Private function to run the functions needs to collect and process application data and creation of Condor
	 * Tokens
	 *
	 * @return object $tokens
	 */
	private function getCondorData()
	{
		$data = $this->Process_Application_ID($this->app->getId());
		$object = $this->Map_Condor_Data($data);
		
		// Fetch Child Application Information
		$this->Get_Condor_Child_Tokens($object);

		return $object;		
	}	

	/**
	 * Calculate Application Pay Dates
	 * 
	 * Generates application pay date information.
	 * @param object $data
	 */
	private function Calculate_Application_Pay_Dates(&$data)
	{   
		try
		{		   		
			$data->model_name		= $data->paydate_model;
			$data->frequency_name	= $data->income_frequency;
			$data->day_string_one	= $data->day_of_week;
			$data->day_int_one		= $data->day_of_month_1;
			$data->day_int_two		= $data->day_of_month_2;
			$data->week_one			= $data->week_1;
			$data->date_fund_stored	= $data->date_fund_actual;
			$data->direct_deposit 	= ($data->income_direct_deposit == 'yes') ? true : false;
			
			$dates = $this->pdc->Calculate_Pay_Dates($data->paydate_model, $data, $data->direct_deposit,10, date("Y-m-d"));
	
			$data->paydate_0 = $dates[0];
			$data->paydate_1 = $dates[1];
			$data->paydate_2 = $dates[2];
			$data->paydate_3 = $dates[3]; 
		
	     
		 // If a schedule exists, use it to derive doc values.
		 // Otherwise run off stored values.
		 // Pull out the transactional info needed
			if ($this->Has_Active_Schedule($data) ||
				!($this->Is_In_Prefund_Status($data))) 
			{
				$this->Fetch_Fund_Date($data);
				$this->Fetch_Application_Balance($data);
				$this->Fetch_Due_Date(&$data);
				$this->Fetch_Arrangement(&$data);
			}
			
			if(empty($data->current_service_charge))
	  	    {
	  	      $data->fund_action_date = $data->date_fund_actual_ymd;
	  	      $data->fund_due_date = $this->pdc->Get_Business_Days_Forward($data->fund_action_date, 1);
	          $data->estimated_service_charge = Interest_Calculator::calculateDailyInterest($data->business_rules, $data->fund_amount, date("Ymd", strtotime($data->fund_due_date)), $data->date_first_payment);
	    	}
	    	$data->interest_accrued = Interest_Calculator::scheduleCalculateInterest($data->business_rules, $this->app->getSchedule(), date('m/d/Y'));
				
			// income_date assumed to be next paydates
			$data->income_date_one_month 	= date('m',strtotime($dates[0]));
			$data->income_date_one_day   	= date('d',strtotime($dates[0]));
			$data->income_date_one_year  	= date('Y',strtotime($dates[0]));
			$data->income_date_two_month 	= date('m',strtotime($dates[1]));
			$data->income_date_two_day   	= date('d',strtotime($dates[1]));
			$data->income_date_two_year  	= date('Y',strtotime($dates[1]));
			
			if(!$data->date_first_payment) {
				$data->date_first_payment = date("m/d/Y", strtotime($dates[0]));
			}               
			
			$data->next_business_day = date('m/d/Y', strtotime($this->pdc->Get_Business_Days_Forward(date("Y-m-d"), 1)));
		}
		catch (Exception $e) // try/catch added for mantis:8015
		{
			$data->paydate_0 				= 'n/a';
			$data->paydate_1				= 'n/a';
			$data->paydate_2				= 'n/a';
			$data->paydate_3				= 'n/a';
			$data->date_first_payment		= 'n/a';
			$data->next_business_day		= 'n/a';
			$data->income_date_one_month	= 'n/a';
			$data->income_date_one_day		= 'n/a';
			$data->income_date_one_year		= 'n/a';
			$data->income_date_two_month	= 'n/a';
			$data->income_date_two_day 		= 'n/a';
			$data->income_date_two_year 	= 'n/a';
		}	
	}

	/**
	 * Is In Prefund Status
	 * 
	 * Check if application is in Prefund
	 *
	 * @param object $data
	 * @return boolean
	 */		
	private function Is_In_Prefund_Status($data)
	{
		$status = $this->app->getStatus();
		return (($status->level1 == "prospect") || 	($status->level2 == "applicant") ||
				($status->level1 == "applicant") || ($status->status == "funding_failed"));
	}	
		
	/**
	 * Process Appplication ID
	 * 
	 * Gathers application rule_set, data, paydates and associated company data.
	 *
	 * @param int $application_id
	 * @return object $data
	 */
	public function Process_Application_ID($application_id)
	{
		$data 					= $this->Get_Application_Data($application_id);
		$data->business_rules 	= $this->Get_Business_Rules()->Get_Rule_Set_Tree($data->rule_set_id);
		$this->Calculate_Application_Pay_Dates($data);
		$this->Get_Company_Data(&$data);	
		$this->getlinks(&$data);
		return $data;		        
	}	

	
	/**
	 * Get Application Data
	 *
	 * Selects applicaton information for the LDB (eCash Database)
	 * 
	 * @param int $application_id
	 * @return object $application
	 */
	private function Get_Application_Data($application_id)
	{
		$data_query = ECash::getFactory()->getData('Application',$this->Get_LDB());
		return $data_query->Get_Application_Data($this->app->getId());
	}
	
	/**
	 * Get Company Data
	 * 
	 * Retrieve data from the Comopany table in eCash Database.
	 *
	 * @param object $data
	 */
	private function Get_Company_Data(&$data)
	{
		$company_properties = array(
			'COMPANY_NAME',
			'COMPANY_PHONE_NUMBER',
			'COMPANY_DEPT_NAME',
			'COMPANY_EMAIL',
			'COMPANY_FAX',
			'COMPANY_LOGO_LARGE',
			'COMPANY_LOGO_SMALL',
			'COMPANY_NAME_LEGAL',
			'COMPANY_NAME_SHORT',
			'COMPANY_ADDR_CITY',
			'COMPANY_ADDR_STATE',
			'COMPANY_ADDR_STREET',
			'COMPANY_ADDR_ZIP',
			'COMPANY_ADDR_UNIT',
			'COMPANY_ADDR',
			'COMPANY_SITE',
			'COMPANY_SUPPORT_FAX',
			'COMPANY_SUPPORT_PHONE',
			'COMPANY_SUPPORT_EMAIL',
			'COMPANY_COLLECTIONS_FAX',
			'COMPANY_COLLECTIONS_EMAIL',
			'COMPANY_COLLECTIONS_PHONE',
			'COMPANY_COLLECTIONS_CODE',
			'COMPANY_NAME_FORMAL',
			'COMPANY_PRE_SUPPORT_FAX',
			'COMPANY_PRE_SUPPORT_PHONE',
			'COMPANY_PRE_SUPPORT_EMAIL',
			'LOAN_NOTICE_DAYS',
			'LOAN_NOTICE_TIME'
			);

		foreach($company_properties as $property)
		{
			$val = eCash_Config::getInstance()->$property;
			if(! empty($val))
			{
				$n = strtolower($property);
				$data->{$n} = $val;
			}
		}
			
		$data->LoanNoticeTime = $this->format_time($data->loan_notice_time);		
		
		$data->TimeCSMFOpen = $this->format_time(Company_Rules::Get_Config("company_start_time"));
		$data->TimeCSMFClose = $this->format_time(Company_Rules::Get_Config("company_close_time"));
		$data->TimeCSSatOpen = $this->format_time(Company_Rules::Get_Config("sat_company_start_time"));
		$data->TimeCSSatClose = $this->format_time(Company_Rules::Get_Config("sat_company_close_time"));
		$data->TimeZoneCS =   Company_Rules::Get_Config("time_zone");
			
	}
	
	/**
	 * Fetch Application Fund Date
	 * 
	 * Collect Fund Date for Application in eCash database.
	 *
	 * @param object $data
	 */
	private function Fetch_Fund_Date(&$data)
	{
		//$data->fund_date = $this->app->getScheduleBuilder()->getFundDate();
		$data->fund_date = !empty($data->date_fund_actual_ymd) ? $data->date_fund_actual_ymd : $data->date_fund_estimated_ymd;
	}
	
	/**
	 * Fetch Application Balance
	 *
	 * Collect Balance information for application in eCash Database.
	 * 
	 * @param object $data
	 */
	private function Fetch_Application_Balance(&$data)
	{
//		$amounts = $this->app->getSchedule()->Analyzer->getBalanceAmounts();
//		$data->current_principal_payoff_amount = $row->principal_pending;
//		$data->current_payoff_amount = $row->total_pending;	

	        $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) principal_balance,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) service_charge_balance,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) fee_balance,
			    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) irrecoverable_balance,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance,
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) principal_pending,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) service_charge_pending,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) fee_pending,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) total_pending
			  FROM
			        event_amount ea
			        JOIN event_amount_type eat USING (event_amount_type_id)
			        JOIN transaction_register tr USING(transaction_register_id)
			  WHERE
			        ea.application_id = $data->application_id
			  GROUP BY ea.application_id
			
			";

		$result = $this->Get_LDB()->Query($query);
		$row = $result->Fetch_Object_Row();
		$data->current_principal_payoff_amount = $row->principal_pending;
		$data->current_payoff_amount = $row->total_pending;	    
	}
	
	/**
	 * Fetch Application Due Date
	 *
	 * Collect Due Date information for Application in eCash Database.
	 * 
	 * @param object $data
	 */
	private function Fetch_Due_Date(&$data)
	{

//		$schedule_builder = $this->app->getScheduleBuilder();
//		$transactions = $schedule_builder->getNextEvents();
//
//		foreach($transactions as $transaction)
//		{
//			$due_date = $transaction->getDateEffective();
//			if(in_array($transaction->getType(), array('payment_service_chg', 'repayment_principal','payout','paydown') ))
//			{
//				
//				$amounts = $transaction->getAmounts();
//				$total_principal += $amounts['principal'];
//				$total_service_charge += $amounts['service_charge'];
//				$total_fees += $amounts['fees'];
//				$total += 	$amounts['principal'] + $amounts['service_charge'] + $amounts['fees'] + $amounts['irrcoverable'];
//			}
//		}
//		
//		if(!empty($transactions)) {
//			if ($data->current_principal_payoff_amount)
//			{
//				$ecash_api = eCash_API_2::Get_eCash_API(ECash::getCompany()->getModel()->name_short, $this->Get_LDB(), $this->app->getId());
//				$data->current_apr = $ecash_api->getAPR($this->app->getLoanType()->name_short, ECash::getCompany()->getModel()->name_short, strtotime($data->fund_date), strtotime($due_date));
//			}
//			else
//			{
//				$data->current_apr = 0;
//			}
//			$data->current_fund_date = $data->fund_date;
//			$data->current_fund_avail = date('m-d-Y', strtotime($this->pdc->Get_Business_Days_Forward(date('Y-m-d', $data->current_fund_date), 1)));
//			$data->current_due_date = $due_date;
//			$data->current_total_due = $data->current_payoff_amount;
//			$data->current_principal = $total_principal;
//			$data->current_service_charge =  $total_service_charge;
//		}
//		$transactions = $schedule_builder->getNextEvents();	
//		$total_principal = 0;
//		$total_service_charge = 0;
//		$total_fees = 0;
//		$total = 	0;
//		foreach($transactions as $transaction)
//		{
//
//			if(in_array($transaction->getType(), array('payment_service_chg', 'repayment_principal','payout','paydown') ))
//			{
//				$due_date = $transaction->getDateEffective();
//				$amounts = $transaction->getAmounts();
//				$total_principal += $amounts['principal'];
//				$total_service_charge += $amounts['service_charge'];
//				$total_fees += $amounts['fees'];
//				$total += 	$amounts['principal'] + $amounts['service_charge'] + $amounts['fees'] + $amounts['irrcoverable'];
//			}
//		}
//		if(!empty($transactions)) {
//			$data->next_fund_date = $data->current_due_date;
//			$data->next_fund_avail = date('m-d-Y', strtotime($this->pdc->Get_Business_Days_Forward(date('Y-m-d', $data->next_fund_date), 1)));
//			$data->next_due_date =  $due_date;
//			$data->next_total_due = $data->current_payoff_amount;
//			$data->next_principal = $total_principal;
//			$data->next_service_charge = $total_service_charge;
//		}	
//
//	if($data->next_principal) {
//		$data->next_principal_payoff_amount = $data->current_principal_payoff_amount - $data->next_principal;
//			if ($data->next_principal_payoff_amount)
//			{
//				$ecash_api = eCash_API_2::Get_eCash_API(ECash::getCompany()->getModel()->name_short, $this->Get_LDB(), $this->app->getId());
//				$data->next_apr = $ecash_api->getAPR($this->app->getLoanType()->name_short, ECash::getCompany()->getModel()->name_short, strtotime($data->current_due_date), strtotime($data->next_due_date));
//			}
//			else
//			{
//				$data->next_apr = 0;
//			}
//		}	
  // Now pull up data for the Current and NEXT set of due dates
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
    			SELECT 
                    es.application_id,
                    date_format(es.date_effective, '%m/%d/%Y') as due_date,
                    abs(sum(es.amount_principal)) + abs(sum(es.amount_non_principal)) as total_due,
                    abs(sum(es.amount_non_principal)) as service_charge,
					abs(sum(es.amount_principal)) as principal
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = '{$data->application_id}'  
                    AND et.event_type_id = es.event_type_id  
                    AND et.company_id = {$data->company_id}
					AND es.event_status = 'scheduled'
                    AND et.name_short IN ('payment_service_chg',
					                      'repayment_principal',
					                      'payout',
					                      'paydown'
										  )  
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective
                LIMIT 2
		";
		$result = $this->Get_LDB()->Query($query);
		
		if($row = $result->Fetch_Object_Row()) {
			if ($data->current_principal_payoff_amount)
			{
				$fi = $this->qualify->Finance_Info(strtotime($row->due_date), strtotime($data->fund_date), $data->current_principal_payoff_amount, $row->service_charge);
				$data->current_apr = $fi['apr'];
			}
			else
			{
				$data->current_apr = 0;
			}
			$data->current_fund_date = $data->fund_date;
			$data->current_fund_avail = date('m-d-Y', strtotime($this->pdc->Get_Business_Days_Forward($data->current_fund_date, 1)));
			$data->current_due_date = $row->due_date;
			$data->current_total_due = $row->total_due;
			$data->current_principal = $row->principal;
			$data->current_service_charge = $row->service_charge;
		}
			
		if($row = $result->Fetch_Object_Row()) {
			$data->next_fund_date = $data->current_due_date;
			$data->next_fund_avail = date('m-d-Y', strtotime($this->pdc->Get_Business_Days_Forward($data->next_fund_date, 1)));
			$data->next_due_date = $row->due_date;
			$data->next_total_due = $row->total_due;
			$data->next_principal = $row->principal;
			$data->next_service_charge = $row->service_charge;
		}	
		
        if($data->next_principal) {
        	$data->next_principal_payoff_amount = $data->current_principal_payoff_amount - $data->next_principal;
			if ($data->next_principal_payoff_amount)
			{
				$ecash_api = eCash_API_2::Get_eCash_API(ECash::getCompany()->getModel()->name_short, $this->Get_LDB(), $this->app->getId());
				$data->next_apr = $ecash_api->getAPR($this->app->getLoanType()->name_short, ECash::getCompany()->getModel()->name_short, strtotime($data->current_due_date), strtotime($data->next_due_date));

			}
			else
			{
				$data->next_apr = 0;
			}
        }		

	}
	
	/**
	 * Fetch Application Arrangement data
	 *
	 * Collection Arrangement data for Application in eCash Database.
	 * 
	 * @param object $data
	 */
	private function Fetch_Arrangement(&$data)
	{		
//		$events = array('quickcheck', 
//						'western_union',
//						'credit_card',
//						'personal_check',
//						'payment_debt',
//						'money_order',
//						'moneygram',
//						'payment_manual',
//						'payment_arranged',
//						'paydown',
//						'payout');
//		$past_transaction = null; 
//		$future_transaction = null;
//		
//		foreach($this->app->getSchedule() as $transaction)
//		{
//			if(in_array($transaction->getType(), $events))
//			{
//				if($transaction->getStatus() == ECash_Transactions_Transaction::STATUS_COMPLETE || $transaction->getStatus() == ECash_Transactions_Transaction::STATUS_PENDING)
//				{
//					$past_transaction = $transaction;
//				}
//				if($transaction->getStatus() == ECash_Transactions_Transaction::STATUS_SCHEDULED)
//				{
//					$future_transaction = $transaction;
//					break;					
//				}
//						
//			}
//			
//		}
//
//		if(!empty($past_transaction))
//		{
//			$data->past_arrangement_type = $past_transaction->getType();
//			$data->past_arrangement_due_date = $past_transaction->getDateEffective();
//			$data->past_arrangement_payment = $past_transaction->getTotalAmount();
//		}
//		
//		if(!empty($future_transaction))
//		{
//			$data->next_arrangement_type = $past_transaction->getType();
//			$data->next_arrangement_due_date = $past_transaction->getDateEffective();
//			$data->next_arrangement_payment = $past_transaction->getTotalAmount();
//		}	
//		
//		$last_payment = $this->app->getSchedule()->Analyzer->getLastPayment();
//		
//		if(!empty($last_payment))
//		{
//			$data->last_payment_date = $last_payment->getDateEffective();
//			$data->last_payment_amount = $last_payment->getTotalAmount();	
//		}

    // Get Data for the most recent past arrangment and the upcoming arrangement
		$sql = <<<ESQL
				(
				SELECT 
                    es.application_id,
				    es.event_status,
				    et.name,
                    date_format(es.date_effective, '%m/%d/%Y') as due_date,
                    abs(sum(es.amount_principal)) + abs(sum(es.amount_non_principal)) as total_due,
				    datediff(es.date_effective, curdate()) as days_til_due
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = '{$application_id}'  
                    AND et.event_type_id = es.event_type_id  
                    AND et.company_id = {$server->company_id}					
                    AND et.name_short IN ( 	'quickcheck',
											'western_union',
											'credit_card',
											'personal_check',
											'payment_debt',
											'money_order',
											'moneygram',
											'payment_manual',
											'payment_arranged',
											'paydown'
										)  
		    		AND datediff(es.date_effective, curdate()) < 0
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective DESC
				LIMIT 1	
				)
			UNION	
				(	
				SELECT 
                    es.application_id,
				    es.event_status,
				    et.name,
                    date_format(es.date_effective, '%m/%d/%Y') as due_date,
                    abs(sum(es.amount_principal)) + abs(sum(es.amount_non_principal)) as total_due,
				    datediff(es.date_effective, curdate()) as days_til_due
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = '{$application_id}'  
                    AND et.event_type_id = es.event_type_id  
                    AND et.company_id = {$server->company_id}					
                    AND et.name_short IN ( 	'quickcheck',
											'western_union',
											'credit_card',
											'personal_check',
											'payment_debt',
											'money_order',
											'moneygram',
											'payment_manual',
											'payment_arranged',
											'paydown',
											'payout'
										)  
				    AND datediff(es.date_effective, curdate()) >= 0
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective ASC
				LIMIT 1	
				)       
ESQL;
        
		//eCash_Document::Log()->write($sql, LOG_DEBUG);	
		$result = $db->query($sql);

		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			switch ($row->days_til_due < 0)
			{
				case TRUE:
					$data->past_arrangement_type = $row->name;
					$data->past_arrangement_due_date = $row->due_date;
					$data->past_arrangement_payment = $row->total_due;
					break;
					
				case FALSE:
					$data->next_arrangement_type = $row->name;
					$data->next_arrangement_due_date = $row->due_date;
					$data->next_arrangement_payment = $row->total_due;
					break 2; // the loop _should_ halt after this anyway, but we're just being explicit
			}
		}
	}	
		
	/**
	 * Fetch Holiday List
	 * 
	 * Collection Holday List from eCash Database.
	 *
	 * @return array $holiday_list
	 */
	public function Fetch_Holiday_List()
	{
		static $holiday_list;
		if(empty($holiday_list))
		{
			$holiday_model = ECash::getFactory()->getModel('HolidayList', $this->Get_LDB());
			$holiday_model->getActive();
			$holiday_list = array();
			foreach($holiday_model as $holiday)
			{
				$holiday_list[] = $holiday->holiday;
			}
		}
		return $holiday_list;
	}	

	/**
	 * Get Campaign Info
	 * 
	 * Collect Campaign infromation for application in eCash Database.
	 *
	 * @return object $ci
	 */
	private function getCampaignInfo()
	{
		$data_query =	ECash::getFactory()->getData('Campaign', $this->Get_LDB());
		$temp = $data_query->getConfigInfoRow($this->app->getId());
		return $temp;
	}
	
	/**
	 * Get ACH Reason 
	 * 
	 * Get Reason of Application ACH from eCash database.
	 *
	 * @param object $data
	 * @return array $reason
	 */
	private function Get_ACH_Reason(&$data)
	{
      $data_query = ECash::getFactory()->getData('ACH', $this->Get_LDB());
      $data->reason_for_ach_return = $data_query->Get_ACH_Reason($this->app->getId());
	}
	/**
	 * Fetch Aplication References
	 *
	 * Collection References contacts for Application in eCash Database
	 * @param unknown_type $application_id
	 * @return unknown
	 */
	function Fetch_References($application_id)
	{
		return $this->app->getPersonalReferences();
	}	


	/**
	 * Get Condor Application Child
	 * 
	 * This will return an array of applications that were created from this applications
	 * it is possibe that there can be multiple reacts from one app but thats not a smart thing
	 * to do or have.
	 *
	 * @param int $application
	 * @return array
	 */
	function Get_Condor_Application_Child($application)
	{
		return $this->app->getReactChildren();
	}
	
	/**
	 * Has Active Schedule
	 * 
	 * Check to see if Application has an Active Schedule
	 *
	 * @param unknown_type $data
	 * @return boolean
	 */
	private function Has_Active_Schedule($data)
	{
	//	return $this->app->getSchedule()->Analyzer->getHasScheduledTransactions();
		$sql = "
			SELECT COUNT(*) as 'count'
			FROM event_schedule
			WHERE application_id = {$application_id}
			AND event_status = 'scheduled'"; //"

						
		//eCash_Document::Log()->write($sql, LOG_DEBUG);		
		$db = ECash_Config::getMasterDbConnection();
		
		$result = $db->query($sql);
		$count = $result->fetch(PDO::FETCH_OBJ)->count;
		return ($count != 0);
	}		
}
?>
