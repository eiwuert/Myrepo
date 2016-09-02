<?php

/**
 * @package condor_Commercial
 * 
 * <b>Revision History</b>
 * <ul>
 *   <li><b>2008-6-10 - Richardb</b><br>
 *     Condor Token API created for use with eCash Commerical.
 *   </li>
 *   <li><b>2008-12-15 - Richardb</b><br>
 *     Reverted Scheduling portions to legacy code left refactored scheduling code commented.
 *   </li> 
 * 
 * </ul>
 */
 
require_once("Condor_Tokens.php");
require_once( dirname(__FILE__) . "/../ecash_api/interest_calculator.class.php");
require_once( dirname(__FILE__) . "/../../ecash_cfe/sql/lib/scheduling.func.php");

class Condor_Commercial extends Condor_Tokens 
{
	protected $number_of_payments;

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
		$this->Set_Login_ID(ECash::getAgent()->getModel()->login);
		$this->legacy_schedule = ECash::getFactory()->getData('LegacySchedule',$this->Get_LDB());
	}
	/**
	 * Calculate monthly net
	 * 
	 *
	 * @param string $income_frequency
	 * @param float $income_monthly
	 */	
	public function Calculate_Monthly_Net($pay_span, $pay)
	{
		$paycheck = FALSE;

		switch (strtoupper($pay_span))
		{

			case 'WEEKLY':
                $paycheck = round(($pay * 12) / 52);
                break;
            case 'BI_WEEKLY':
                $paycheck = round(($pay * 12) / 26);
                break;
            case 'TWICE_MONTHLY':
                $paycheck = round($pay / 2);
                break;
            case 'MONTHLY':
                $paycheck = round($pay);
                break;
			default:
				$this->errors[] = "Invalid pay span, or monthly net pay is zero.";
		}

		return $paycheck;
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
		return $this->legacy_schedule->Application_Has_Events_By_Event_Names($this->app->getId(), $events);
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
		return $this->legacy_schedule->Fetch_Balance_Total_By_Event_Names($this->app->getId(), $events);
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
	public function getlinks($data)
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
		$react_url = ECash::getConfig()->REACT_URL;
		$esig_url = ECash::getConfig()->ESIG_URL;
		
		if (method_exists($this->app,'getId')) $app_id = $this->app->getId();
		else $app_id = $this->app->application_id;
		$login_hash = md5($app_id . $site_id . 'L08N54M3');
		$encoded_app_id = urlencode(base64_encode($app_id));
		$resign = urlencode(base64_encode('resign'));
		$esign = urlencode(base64_encode('esign'));

		switch (strtolower($data->company_name_short))
		{
			case 'generic':
			case 'someloancompany.com':
			default:
				$data->cs_login_link = "{$esig_url}/auto_login?link=". urlencode(base64_encode($app_id))."&key=" . md5($app_id . "l04nsl0g1n")."&exp=". urlencode(base64_encode(time())) . "&ecvt&force_new_session" ;
				$data->esig_url = "{$esig_url}/esig_confirm_start?application_id=". urlencode(base64_encode($app_id))."&login=" . md5($app_id . "l04ns") . "&ecvt&force_new_session" ;
				$data->spam_link = "{$esig_url}/spam_login?link=". urlencode(base64_encode($app_id))."&key=" . md5($app_id . "l04nssp4m");
				$data->react_link = "{$esig_url}/react_loan?link=". urlencode(base64_encode($app_id))."&key=" . md5($app_id . "l04nsr34ct")."&exp=". urlencode(base64_encode(time())) . "&ecvt&force_new_session" ;
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
	public function Calculate_Application_Pay_Dates(&$data)
	{   

		
		try
		{		   		
			$data->model_name		= $data->paydate_model;
			$data->frequency_name	= $data->income_frequency;
			$data->day_string_one	= $data->day_of_week;
			$data->day_int_one		= $data->day_of_month_1;
			$data->day_int_two		= $data->day_of_month_2;
			$data->week_one			= $data->week_1;
			$data->week_two			= $data->week_2;
			$data->date_fund_stored	= $data->date_fund_actual;
			$data->direct_deposit 	= ($data->income_direct_deposit == 'yes') ? true : false;
			
			$dates = $this->pdc->Calculate_Pay_Dates($data->paydate_model, $data, $data->direct_deposit,10, date("Y-m-d"));
	
			$data->paydate_0 = date('m-d-Y', strtotime($dates[0]));
			$data->paydate_1 = date('m-d-Y', strtotime($dates[1]));
			$data->paydate_2 = date('m-d-Y', strtotime($dates[2]));;
			$data->paydate_3 = date('m-d-Y', strtotime($dates[3])); 

			

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
		//	echo '<pre>' . print_r($e,true) . '</pre>';
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
	 * Formats Customer Residence Length
	 *
	 * @param object $data
	 */
        /*
	protected function setResidenceDate($data)
	{
		if ($data->residence_start_date)
		{
			$secs = time() - strtotime($data->residence_start_date); // Get the difference in seconds
			$yrs = date("Y", $secs) - 1970; // Subtract the epoch date
			$mos = date("m", $secs);

			$data->CustomerResidenceLength = "{$yrs}yrs {$mos}mos";
		}
		else
		{
			$data->residence_start_date = "";
		}		
		
	}
        */

        /**
	 * Formats Customer Residence Length
	 *
	 * @param object $data
	 */
	protected function setResidenceDate($data)
        {
		if ($data->residence_start_date)
		{
                        $years_months = Date_Util_1::getYearsMonthsElapsed($data->residence_start_date);
			$yrs = $years_months["yrs"];
			$mos = $years_months["mos"];

			$data->CustomerResidenceLength = "{$yrs} years {$mos} months";
		}
		else
		{
			$data->CustomerResidenceLength = "0 years 0 months";
		}
	}

        /**
	 * Formats Customer Employment Length
	 *
	 * @param object $data
	 */
	protected function setEmploymentDate($data)
	{
		if ($data->date_hire)
		{
                        $years_months = Date_Util_1::getYearsMonthsElapsed($data->date_hire);
			$yrs = $years_months["yrs"];
			$mos = $years_months["mos"];

			$data->EmployerLength = "{$yrs} years {$mos} months";
		}
		else
		{
			$data->EmployerLength = "0 years 0 months";
		}
	}

	/**
	 * Determine if app has a schedule and build tokens accordingly
	 *
	 * @param object $data
	 */	
	protected function checkActive($data)
	{
		$application_id = $this->app->application_id;
		$data->interest_accrued = Interest_Calculator::scheduleCalculateInterest($data->business_rules, $this->legacy_schedule->fetch_schedule($application_id), date('m/d/Y'));
		
		//asm 107
		$result = $this->legacy_schedule->fetchLastPayment($data->application_id, $this->app->company_id);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$data->last_payment_date = $row->payment_date;
			$data->last_payment_amount = $row->payment_total;
			$data->card_auth_code = $row->authorization_code;
		}

	 // If a schedule exists, use it to derive doc values.
	 // Otherwise run off stored values.
	 // Pull out the transactional info needed
		if ($this->Has_Active_Schedule($application_id) ||
			!($this->Is_In_Prefund_Status($this->app)))
		{
			$this->Fetch_Fund_Date($data);
			$this->Fetch_Application_Balance($data);
			$this->Fetch_Due_Date($data);
			$this->Fetch_Arrangement($data);
			//$this->Fetch_Card_Payments($data); //asm 107 // Do not overwrite last_payment_date,last_payment_amount, card_auth_code
		}
	}
	/**
	 * Set estimates for apps that are not active yet
	 *
	 * @param object $data
	 */	
	protected function setEstimates(&$data)
	{
		if(empty($data->current_service_charge))
		{
			$rate_calc = $this->app->getRateCalculator();
			
			$data->fund_action_date = $data->date_fund_actual_ymd;
			$data->fund_due_date = $this->pdc->Get_Business_Days_Forward($data->fund_action_date, 1);

			if($this->Is_In_Prefund_Status($this->app))
			{
				try
				{
					$data->estimated_service_charge = $rate_calc->calculateCharge($data->fund_amount, strtotime($data->fund_due_date), strtotime($data->date_first_payment));
				
				}
				catch(Exception $e)
				{
					$data->estimated_service_charge = 0;
				}
			}
			if(empty($data->date_first_payment))
			{
				$result->date_first_payment = date("m/d/Y", strtotime($data->paydate_0));
			}
			try
			{
				$data->next_apr = $data->current_apr = $rate_calc->getAPR(strtotime($data->original_fund_estimate_date), strtotime($data->date_first_payment));
			}
			catch(Exception $e)
			{
				$data->next_apr = 0;
			}
		}		
			
	}

	protected function getLoanFinChargeMax($fund_amount, $rate_percent, $loan_amount_increment, $max_svc_charge_only_pmts)
	{
		$sc = ($fund_amount * $rate_percent / 100) * ($max_svc_charge_only_pmts);
		$princ = $fund_amount;
		$this->number_of_payments = $max_svc_charge_only_pmts;

		while ($princ > 0)
		{
			$sc = $sc + ($princ * $rate_percent / 100);
			$princ = $princ - $loan_amount_increment;
			$this->number_of_payments++;
		}

		return $sc;
	}

	/**
	 * Build the fee tokens
	 *
	 * @param object $data
	 */	
	protected function feeTokens($data)
	{
			//These are fees which affect principal (primarily fees that Agean adds)
			$principal_fees = 0;
			$WireTransferFee = 0;
			$DeliveryFee = 0;
			$TitleLienFee = 0;
			
			//GF 5431 Will only show fees that have been added
			if ($this->Application_Has_Events_By_Event_Names( array('assess_fee_transfer', 'payment_fee_transfer', 'writeoff_fee_transfer')) == TRUE)
			{
				$WireTransferFee = $this->Fetch_Balance_Total_By_Event_Names(array('assess_fee_transfer','payment_fee_transfer', 'writeoff_fee_transfer'));
			}
			$principal_fees = bcadd($WireTransferFee, $principal_fees, 2);
			$data->WireTransferFee = self::Format_Money($WireTransferFee);
	
			// GF 8293 Check if Title loan has event types relating to delivery fees, if so, total them up and return that, else use the old method.
			if ($this->Application_Has_Events_By_Event_Names( array('assess_fee_delivery', 'payment_fee_delivery', 'writeoff_fee_delivery')) == TRUE)
			{
				$DeliveryFee = $this->Fetch_Balance_Total_By_Event_Names(array('assess_fee_delivery','payment_fee_delivery', 'writeoff_fee_delivery'));
			}
			$principal_fees = bcadd($DeliveryFee, $principal_fees, 2);
			$data->DeliveryFee = self::Format_Money($DeliveryFee);
			
			//GF 5429 Only show Lien fee for Title Loans
			//GF 6334 Check if Title loan has event types relating to lien fees, if so, total them up and return that, else use the old
			//        method.
			if ($this->Application_Has_Events_By_Event_Names( array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien')) == true)
			{
				$TitleLienFee = $this->Fetch_Balance_Total_By_Event_Names(array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien'));
			}
			$data->principal_fees = bcadd($TitleLienFee, $principal_fees, 2);
			$data->TitleLienFee = self::Format_Money($TitleLienFee);

			//LoanFinChargeMax
			$application = ECash::getApplicationById($data->application_id);
			$rate_calc = $application->getRateCalculator();
			$data->rate_percent = $rate_calc->getPercent();
			/*
			//$loan_amount_increment = $data->business_rules["loan_amount_increment"];
			if(
			   !empty($data->fund_amount)
			   && ($data->business_rules['principal_payment']['principal_payment_type'] === 'Percentage')
			)
			{
				$loan_amount_increment = (($data->fund_amount / 100) * $data->business_rules['principal_payment']['principal_payment_percentage']);
			}
			else
			{
				$loan_amount_increment = $data->business_rules['principal_payment']['principal_payment_amount'];
			}
			*/
			$loan_amount_increment = $this->Get_Payment_Amount($data->business_rules, $data->fund_amount);
			$max_svc_charge_only_pmts = $data->business_rules["service_charge"]["max_svc_charge_only_pmts"];

			$data->loan_fin_charge_max = $this->getLoanFinChargeMax($data->fund_amount, $data->rate_percent, $loan_amount_increment, $max_svc_charge_only_pmts);
			//max apr
			if ($data->paydate_model == "dw")
			{
				$number_of_payments = 2 * $this->number_of_payments;
				$index = $number_of_payments - 2;
			}
			else
			{
				$number_of_payments = $this->number_of_payments;
				$index = $this->number_of_payments - 1;
			}

			$dates = $this->pdc->Calculate_Pay_Dates($data->paydate_model, $data, $data->direct_deposit,$number_of_payments, date("Y-m-d", strtotime($data->date_first_payment)-1));
			$last_payment_date = $dates[$index];

			$num_days = Date_Util_1::dateDiff(strtotime($data->fund_action_date), strtotime($last_payment_date));
			$num_days = ($num_days < 1) ? 1 : $num_days;

			$data->max_apr = $data->loan_fin_charge_max / $data->fund_amount * 365 / $num_days * 100;
			/////////////////////////

			$data->total_of_payments_max = $data->fund_amount + $data->loan_fin_charge_max;

			if(strtolower($data->business_rules['loan_type_model']) == 'cso')
			{
				$renewal_class =  ECash::getFactory()->getRenewalClassByApplicationID($data->application_id);
				//CSO Tokens [#17240]
				$data->cso_assess_fee_app = $renewal_class->getCSOFeeAmount('cso_assess_fee_app', $data->application_id); 	
				$data->cso_assess_fee_broker = $renewal_class->getCSOFeeAmount('cso_assess_fee_broker', $data->application_id,null,null,null,$data->principal_fees + $data->fund_amount); 
				$data->lend_assess_fee_ach = $renewal_class->getCSOFeeAmount('lend_assess_fee_ach', $data->application_id); 
				
				//I don't care much for this calculation - caused by shoehorning the percentage into CSO.
				//This should probably be entirely encapsulated in the rate calculator [JustinF][#38368]
				$data->svc_charge_percentage = round(($rate_calc->getPercent() * 52), 2); 
				
				//Value of eCash Business Rule w/ same name.  Ex. $7.50 or 5% of the payment amount, whichever is greater
				$data->cso_assess_fee_late = $renewal_class->getCSOFeeDescription('cso_assess_fee_late', $data->application_id);
				
				//Calendar date that a cancellation notice must be received by.  Derived from the values of estimated funding date and the eCash business rule "Cancellation delay" Ex. 8/18/2008
				$data->loan_cancellation_date = $this->pdc->Get_Business_Days_Forward($data->date_fund_actual_ymd, 1);
			}				

			//asm 83
			if (!empty($data->application_id))
			{
				$application_id = $data->application_id;
				//$application = ECash::getApplicationById($application_id);
				$company_id = $application->company_id;
				$ssn = $application->ssn;
	
				$customer = ECash::getFactory()->getCustomerBySSN($ssn, $company_id);
				$applications = $customer->getApplications();
				
				foreach($applications as $app)
				{
					$status = $app->getStatus();
					if(
						($status->level0 == 'refi') && ($app->application_id != $application_id)
					)
					{
						$balance_info = Fetch_Balance_Information($app->application_id);
						$data->converted_principal_bal_amount = abs($balance_info->principal_balance);
						break;
					}
				}
			}
	}

    /**
     * Returns the Lien Fee amounts for a given state
     *
     * @param string $state - Example: nv
     * @return string - Example: 10, 10.55
     */
    public function getLienFeeAmount($state)
    {
        $query = "
            SELECT
                    lf.fee_amount
            FROM    lien_fees AS lf
            WHERE   lf.state = '{$state}'";

        if(($result = $this->db['LDB']->query($query)))
        {
            if (($row = $result->fetch(PDO::FETCH_OBJ)))
			{
				return $row->fee_amount;
			}
        }

        return 0;
    }

	public function feeTokensWithNoApplication($data)
	{
			//These are fees which affect principal (primarily fees that Agean adds)
			$principal_fees = 0;
			$WireTransferFee = 0;
			$DeliveryFee = 0;
			$TitleLienFee = 0;
			
			if (strcasecmp($data->business_rules['loan_type_model'], 'title') == 0)
			{
				if (!empty($data->business_rules['moneygram_fee']))
				{
					$WireTransferFee = $data->business_rules['moneygram_fee'];
				}
				if (!empty($data->business_rules['ups_label_fee']))
				{
					$DeliveryFee = $data->business_rules['ups_label_fee'];
				}
				$TitleLienFee = $this->getLienFeeAmount($data->state);
				$data->WireTransferFee = $WireTransferFee;
				$data->DeliveryFee = $DeliveryFee;
				$data->TitleLienFee = $TitleLienFee;
				$data->principal_fees = bcadd($WireTransferFee, bcadd($TitleLienFee, $DeliveryFee, 2), 2);
				$data->TitleLienFee = self::Format_Money($TitleLienFee);

			}
			if(strtolower($data->business_rules['loan_type_model']) == 'cso')
			{
				$application = ECash::getApplicationById($data->application_id);
				$rate_calc = $application->getRateCalculator();				
				$renewal_class =  ECash::getFactory()->getRenewalClassFromBusinessRules($data->business_rules);
				$data->cso_assess_fee_app    = $renewal_class->getCSOFeeAmount('cso_assess_fee_app', $data->application_id, NULL, NULL, NULL, NULL, NULL, $data);
				$data->cso_assess_fee_broker = $renewal_class->getCSOFeeAmount('cso_assess_fee_broker', $data->application_id, NULL, NULL, NULL, NULL, NULL, $data);
				$data->lend_assess_fee_ach   = $renewal_class->getCSOFeeAmount('lend_assess_fee_ach', $data->application_id, NULL, NULL, NULL, NULL, NULL, $data);
				$data->svc_charge_percentage = round(($rate_calc->getPercent() * 52), 2);
				$data->cso_assess_fee_late = $renewal_class->getCSOFeeDescription('cso_assess_fee_late', $data->application_id, NULL, $data->business_rules);
				$data->loan_cancellation_date = date('Y-m-d', strtotime("{$data->date_fund_actual_ymd} + {$data->business_rules['cancelation_delay']} days"));

			}
			
			//LoanFinChargeMax
			if (!empty($data->fund_amount))
			{
				$data->rate_percent = $data->finance_charge / $data->fund_amount * 100;
				//$loan_amount_increment = $data->business_rules["loan_amount_increment"];
				$loan_amount_increment = $this->Get_Payment_Amount($data->business_rules, $data->fund_amount);
				$max_svc_charge_only_pmts = $data->business_rules["service_charge"]["max_svc_charge_only_pmts"];

				$data->loan_fin_charge_max = $this->getLoanFinChargeMax($data->fund_amount, $data->rate_percent, $loan_amount_increment, $max_svc_charge_only_pmts);
				$data->total_of_payments_max = $data->fund_amount + $data->loan_fin_charge_max;
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
	private function Is_In_Prefund_Status(ECash_Application $app)
	{
		$status = $app->getStatus();
		return (($status->level1 == "prospect") || 	($status->level2 == "applicant") ||
				($status->level1 == "applicant") || ($status->level0 == "funding_failed"));
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
		$data = $this->Get_Application_Data($application_id);

		$data->business_rules 	= $this->app->getBusinessRules();
		$this->Calculate_Application_Pay_Dates($data);
		$this->checkActive($data);
		$this->setEstimates($data);
		$this->feeTokens($data);
		$this->setResidenceDate($data);
                $this->setEmploymentDate($data);
		$this->Get_Company_Data($data);	
		$this->getlinks($data);
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
		return $data_query->Get_Application_Data($application_id);
	}
	
	/**
	 * Get Company Data
	 * 
	 * Retrieve data from the Comopany table in eCash Database.
	 *
	 * @param object $data
	 */
	public function Get_Company_Data(&$data)
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
					'LOAN_NOTICE_TIME',
					'COLLECTIONS_EMAIL',
					'COLLECTIONS_PHONE',
					'COLLECTIONS_FAX',
					'PRE_SUPPORT_EMAIL',
					'PRE_SUPPORT_PHONE',
					'PRE_SUPPORT_FAX',
					'SUGGESTED_PAYMENT_INCREMENT',
					'CSO_LENDER_NAME_LEGAL',
					'PAYMENT_STREET',
					'PAYMENT_CITY',
					'PAYMENT_ZIP',
					'PAYMENT_BANK',
					'PAYMENT_ABA',
					'PAYMENT_ACCOUNT',
			);

		foreach($company_properties as $property)
		{
			$val = ECash::getConfig()->$property;
			if(! empty($val))
			{
				$n = strtolower($property);
				$data->{$n} = $val;
			}
		}
		$data->paydown_percent = $data->business_rules['principal_payment']['principal_payment_percentage'].'%';	
		//$data->LoanNoticeTime = $this->format_time($data->loan_notice_time);		
		
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
		$data->fund_action_date = !empty($data->date_fund_actual_ymd) ? $data->date_fund_actual_ymd : $data->date_fund_estimated_ymd;
		$data->fund_due_date = $this->pdc->Get_Business_Days_Forward($data->fund_action_date, 1);
	}
	
	/**
	 * Fetch Application Balance
	 *
	 * Collect Balance information for application in eCash Database.
	 * 
	 * @param object $data
	 */
	protected function Fetch_Application_Balance(&$data)
	{

		$row = $this->legacy_schedule->Fetch_Balance($data->application_id);
		$data->current_principal_payoff_amount = $row->principal_pending;
		if(strtolower($data->business_rules['service_charge']['svc_charge_type']) == 'fixed')
		{
        	$data->current_payoff_amount = $row->total_pending;
		}
		else
		{
			$data->current_payoff_amount = $row->total_pending + $data->interest_accrued + $row->fee_balance;
		} 
		$data->fee_balance = $row->fee_balance;
		//$data->posted_total = $row->posted_total;
		$data->posted_total = $row->total_balance + $row->credit_principal_pending;
		return $row;  
	}
	
	/**
	 * Fetch Application Due Date
	 *
	 * Collect Due Date information for Application in eCash Database.
	 * 
	 * @param object $data
	 */
	private function Fetch_Due_Date($data)
	{

		$result = $this->legacy_schedule->fetch_due_dates($data->application_id, $this->app->company_id);
		
		if($row = $result->fetch(PDO::FETCH_OBJ)) {
			$data->current_fund_date = $data->fund_action_date;
			$data->current_fund_avail = date('m-d-Y', strtotime($this->pdc->Get_Business_Days_Forward($data->current_fund_date, 1)));
			$data->current_due_date = $row->due_date;
			$data->current_total_due = $row->total_due;
			$data->current_principal = $row->principal;
			$data->current_service_charge = $row->service_charge;
			$data->next_fund_date = $data->current_due_date;
			$data->next_fund_avail = date('m-d-Y', strtotime($this->pdc->Get_Business_Days_Forward($data->next_fund_date, 1)));
			$data->next_due_date = $row->due_date;
			$data->next_total_due = $row->total_due;
			$data->next_principal = $row->principal;
			$data->next_service_charge = $row->service_charge;
		}	
		
		if(isset($data->next_principal)) {
			$data->next_principal_payoff_amount = $data->current_principal_payoff_amount - $data->next_principal;
	
		}		

		$rate_calc = $this->app->getRateCalculator();
		$data->next_apr = $data->current_apr = $rate_calc->getAPR(strtotime($data->fund_action_date), strtotime($data->date_first_payment));
	}
	
	/**
	 * Fetch Application Arrangement data
	 *
	 * Collection Arrangement data for Application in eCash Database.
	 * 
	 * @param object $data
	 */
	private function Fetch_Arrangement($data)
	{
		/*
		// asm 107, do not allow to overwrite last_payment_date and last_payment_amount
		// Get Data for the most recent past arrangment and the upcoming arrangement
		$result = $this->legacy_schedule->fetch_last_payment($data->application_id, $this->app->company_id);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$data->last_payment_date = $row->payment_date;
			$data->last_payment_amount = $row->payment_total;
		}
		*/

		$result = $this->legacy_schedule->fetch_arrangements($data->application_id, $this->app->company_id);

		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			//[#47989] if days_til_due is zero, consider it in the past b/c it's today and the
			//documents using this are sent via NightlyEvents
			switch ($row->days_til_due <= 0)
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
	 * Fetch Card Payment data
	 *
	 * Collection Arrangement data for Application in eCash Database.
	 * 
	 * @param object $data
	 */
	private function Fetch_Card_Payments($data)
	{		
	// Get Data for the most recent payment card transaction
		$result = $this->legacy_schedule->fetch_card_payment($data->application_id, $this->app->company_id);
		if($row = $result->fetch(PDO::FETCH_OBJ)) {
			$data->last_payment_date = $row->payment_date;
			$data->last_payment_amount = $row->payment_total;
			$data->card_auth_code = $row->authorization_code;
		} else {}

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
		$data = ECash::getFactory()->getData('Application', $this->Get_LDB());
		return $data->getCampaignInfo($this->app->getId());
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
	public function Fetch_References($application_id)
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
	public function Get_Condor_Application_Child($application)
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
	private function Has_Active_Schedule($application_id)
	{
		return $this->legacy_schedule->has_schedule($application_id);
	}	
	/**
	 * Method used to determine the Principal Payment Amount for the application
	 *
	 * - This may not be the most appropriate place, but I needed a quick fix. [BR]
	 *
	 * @param array $rules - Business Rules
	 * @param integer $fund_amount - the fund amount
	 * @return integer
	 */
	protected function Get_Payment_Amount($rules, $fund_amount = null)
	{
		if(! is_array($rules) || ! is_numeric($fund_amount))
			return 0;

		// Try new rules, else fall back.
		if(isset($rules['principal_payment']))
		{
			if($rules['principal_payment']['principal_payment_type'] === 'Percentage')
			{
				$p_amount = (($fund_amount / 100) * $rules['principal_payment']['principal_payment_percentage']);
			}
			else
			{
				$p_amount = $rules['principal_payment']['principal_payment_amount'];
			}

			return $p_amount;

		}
		else
		{
			return $rules['principal_payment_amount'];
		}

	}	
}
?>
