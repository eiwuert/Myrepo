<?php
/**
 * @package Documents
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 13, 2006
 *
 * @version $Revision$
 */

require_once eCash_Document_DIR . "/Document.class.php";
require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');

class eCash_Document_ApplicationData {

	static public function Get_Email(Server $server, $application_id)
	{
		$db = ECash_Config::getMasterDbConnection();
		$email = '';

		$app_query = "
			SELECT
				email
			FROM
				application
			WHERE
				application_id = {$application_id}"; //"

		//eCash_Document::Log()->write($query, LOG_DEBUG);

		$query_obj = $db->query($app_query);

		if ($row_obj = $query_obj->fetch(PDO::FETCH_OBJ))
		{
			$email .= $row_obj->email;
		}

		return $email;
	}

	/**
	 * A rewrite of the Get_Email function intended
	 * to get information about an application in order to
	 * send a document to them.  It will retrieve the loan
	 * type, loan_type_id, and rule_set_id for the application
	 * besides it's email address.
	 *
	 * @param Server $server
	 * @param integer $application_id
	 * @return stdClass Object
	 */
	static public function Get_Recipient_Data(Server $server, $application_id)
	{
		$db = ECash_Config::getMasterDbConnection();
		$email = '';

		$app_query = "
			SELECT
					app.email,
					app.loan_type_id,
					lt.name_short AS loan_type,
					app.rule_set_id,
					app.company_id,
					c.name_short AS company
			FROM	application AS app
			JOIN	company AS c ON (c.company_id = app.company_id)
			JOIN 	loan_type AS lt ON (lt.loan_type_id = app.loan_type_id)
			WHERE
				application_id = {$application_id}";

		$query_obj = $db->query($app_query);
		if ($row_obj = $query_obj->fetch(PDO::FETCH_OBJ))
		{
			return $row_obj;
		}

		return new stdClass();
	}


	static public function Get_History(Server $server, $application_id, $event = NULL)
	{
		$app_docs = array();

		$event_sql = ($event) ? " and document.document_event_type = '{$event}'" : "" ; //"
		$company_id = ECash::getCompany()->company_id;
		$query = "
					SELECT
						document.document_id,
						document_list.document_list_id,
						document_list.name_short as name,
						document_list.name as description,
						document_list.required,
						document_list.send_method,
						agent.agent_id,
						if(agent.login is null, 'unknown', agent.login) as login,
						document.document_event_type as event_type,
						if(document.document_method is null, document.document_method_legacy,document.document_method) as document_method,
						document.document_method_legacy,
						document.document_method,
						document.transport_method,
						document.signature_status,
						document.name_other as name_other,
						date_format(document.date_created,'%m-%d-%Y %H:%i') as xfer_date,
						DATE (document.date_modified) as alt_xfer_date,
						document.document_id_ext,
						document_list.document_api,
						document.archive_id
					FROM
						document left join agent on document.agent_id = agent.agent_id
					JOIN
						document_list ON (document_list.document_list_id = document.document_list_id)
					WHERE
						document.application_id = {$application_id}
					AND
						document.company_id = {$company_id}
					AND
						document_list.system_id = {$server->system_id}
					{$event_sql}
					order by document.date_created desc"; //"


		//eCash_Document::Log()->write($query, LOG_DEBUG);

		$db = ECash_Config::getMasterDbConnection();
		$q_obj = $db->query($query);

		for ( $app_docs = array(); $row = $q_obj->fetch(PDO::FETCH_OBJ); true )
		{
			$app_docs[] = $row;
		}

		return $app_docs;

	}

	static public function Get_Data(Server $server, $application_id, $transaction_id = NULL)
	{
	  $db = ECash_Config::getMasterDbConnection();
      $result = array();

      $app_query = "
         SELECT
            application.application_id,
	    	application.application_status_id,
            application.bank_name,
            application.bank_account,
            application.bank_account_type,
            application.bank_aba,
            application.income_direct_deposit,
            application.employer_name,
            application.phone_work,
            application.phone_work_ext,
            application.job_title,
            application.shift,
            date_format(application.date_hire, '%d') as date_hire_day,
            date_format(application.date_hire, '%m') as date_hire_month,
            date_format(application.date_hire, '%Y') as date_hire_year,
            application.income_source,
            application.income_monthly,
            application.income_frequency,
            application.tenancy_type,
            application.street,
            application.unit,
            application.city,
            application.county,
            application.state,
            application.zip_code as zip,
            application.name_first,
            application.name_middle,
            application.name_last,
            application.phone_home,
            application.phone_cell,
            application.phone_fax,
            application.email as customer_email,
            date_format(application.dob, '%m-%d-%Y') as dob,
            date_format(application.dob, '%d') as dob_day,
            date_format(application.dob, '%m') as dob_month,
            date_format(application.dob, '%Y') as dob_year,
            application.ssn,
            application.track_id,
            application.legal_id_number,
            application.call_time_pref,
            application.date_fund_actual as date_fund_stored,
            date_format(application.date_first_payment,  '%d') AS date_first_payment_day,
            date_format(application.date_first_payment,  '%m') AS date_first_payment_month,
            date_format(application.date_first_payment,  '%Y') AS date_first_payment_year,
            date_format(application.date_first_payment,  '%m/%d/%Y') AS date_first_payment,
            ( CASE WHEN application.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%d') ELSE DATE_FORMAT(application.date_fund_actual,'%d') END ) as date_fund_actual_day,
            ( CASE WHEN application.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%m') ELSE DATE_FORMAT(application.date_fund_actual,'%m') END ) as date_fund_actual_month,
            ( CASE WHEN application.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%Y') ELSE DATE_FORMAT(application.date_fund_actual,'%Y') END ) as date_fund_actual_year,
            ( CASE WHEN application.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%Y-%m-%d') ELSE DATE_FORMAT(application.date_fund_actual,'%Y-%m-%d') END ) as date_fund_actual_ymd,
            ( IF(application.fund_actual > 0, application.fund_actual, application.fund_qualified) ) as fund_amount,
            application.finance_charge,
            application.payment_total,
            application.apr,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%d') as date_fund_estimated_day,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%m') as date_fund_estimated_month,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%Y') as date_fund_estimated_year,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%m-%d-%Y') as date_fund_estimated,
 			date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%Y-%m-%d') as date_fund_estimated_ymd,
            application.date_fund_estimated as original_fund_estimate_date,
            application.paydate_model,
            application.day_of_week,
            DATE_FORMAT(application.last_paydate, '%Y-%m-%d') as last_paydate,
            application.day_of_month_1,
            application.day_of_month_2,
            application.week_1,
            application.week_2,
            login.login as login_id,
            login.password as crypt_password,
			stat.name as application_status,
			application.residence_start_date,
			vehicle.vin as vehicle_vin,
			vehicle.year as vehicle_year,
			vehicle.model as vehicle_model,
			vehicle.series as vehicle_series,
			vehicle.make as vehicle_make,
			vehicle.mileage as vehicle_mileage,
			loan_type.name_short as loan_type,
			lf.fee_amount as lien_fee,
			application.county as customer_county
         FROM application
         LEFT JOIN customer AS login ON ( application.customer_id = login.customer_id)
         LEFT JOIN application_status AS stat ON ( application.application_status_id = stat.application_status_id )
         LEFT JOIN vehicle ON (application.application_id = vehicle.application_id)
         LEFT JOIN loan_type ON (application.loan_type_id = loan_type.loan_type_id)
         LEFT JOIN lien_fees AS lf ON (lf.state = application.state)
         WHERE application.application_id = $application_id
         " ;

      //eCash_Document::Log()->write($app_query, LOG_DEBUG);
	//	echo "<pre>$app_query</pre>";
      $query_obj = $db->Query($app_query);

      if ((count($query_obj) > 0) && ($row_obj = $query_obj->fetch(PDO::FETCH_OBJ)))
      {

        $result = $row_obj;
		if ($result->residence_start_date)
		{
			$secs = time() - strtotime($result->residence_start_date); // Get the difference in seconds
			$yrs = date("Y", $secs) - 1970; // Subtract the epoch date
			$mos = date("m", $secs);

			$result->CustomerResidenceLength = "{$yrs}yrs {$mos}mos";
		}
		else
		{
			$result->residence_start_date = "";
		}
         // Calculate paydates
		try
		{
			$pdc =  new Pay_Date_Calc_3(Fetch_Holiday_List());
	        $tr_data = Get_Transactional_Data($application_id, $db);
	        $tr_data->info->direct_deposit = ($tr_data->info->direct_deposit == 1) ? true : false;
	        $dates = $pdc->Calculate_Pay_Dates($tr_data->info->paydate_model,
	                       $tr_data->info->model, $tr_data->info->direct_deposit,
	                       10, date("Y-m-d"));
	        $result->paydate_0 = date("m-d-Y", strtotime($dates[0]));
        	$result->paydate_1 = date("m-d-Y", strtotime($dates[1]));
        	$result->paydate_2 = date("m-d-Y", strtotime($dates[2]));
        	$result->paydate_3 = date("m-d-Y", strtotime($dates[3]));

	         if(!$result->date_first_payment)
			 {
	         	$result->date_first_payment = date("m/d/Y", strtotime($dates[0]));
	         }
	     	$log = get_log();
			$data = Fetch_Application_Info($application_id);
			$loan_type = $data->loan_type;
			$result->net_paycheck = self::Calculate_Monthly_Net($result->income_frequency, $result->income_monthly);
			$date_fund = ($result->date_fund_stored) ? $result->date_fund_stored : $result->date_fund_estimated;

			$result->date_fund_2 = str_replace("-","/", $result->original_fund_estimate_date); //$pdc->Get_Business_Days_Forward(str_replace("-","/", $date_fund), 1);

			require_once(ECASH_COMMON_DIR . "ecash_api/ecash_api.2.php");
			$ecash_api = eCash_API_2::Get_eCash_API($server->company, $db, $application_id);
			$result->current_apr = $result->next_apr = $ecash_api->getAPR($loan_type, $server->company, strtotime(str_replace("-","/", $result->date_fund_2)), strtotime($result->date_first_payment));
			$renewal_class =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
			// GF:3113 - Need estimated date the funds will be available.
			$result->fund_action_date = $result->date_fund_actual_ymd;
			$result->fund_due_date = $pdc->Get_Business_Days_Forward($result->fund_action_date, 1);
			$business_rules = self::Get_Business_Rules($server, $application_id);
			// If a schedule exists, use it to derive doc values.
	        // Otherwise run off stored values.
			if (self::Has_Active_Schedule($server, $application_id) ||
	           ! (self::Is_In_Prefund_Status($application_id)))
			{
				// Pull out the transactional info needed
				$trinfo = self::getDocumentInfo($server, $application_id, $date_fund );

				foreach ($trinfo as $key => $value)
				{
				   $result->$key = $value;
				}
			}
			else
			{
				//Fees need to be figured in to the principal when calculating the estimated service charges. [#10603]
				$principal_fees = 0;
				$WireTransferFee = 0;
				$DeliveryFee = 0;
				$TitleLienFee = 0;

				if (Application_Has_Events_By_Event_Names($application_id, array('assess_fee_transfer', 'payment_fee_transfer', 'writeoff_fee_transfer')) == TRUE)
				{
					$WireTransferFee = Fetch_Balance_Total_By_Event_Names($application_id, array('assess_fee_transfer','payment_fee_transfer', 'writeoff_fee_transfer'));
				}

				if (Application_Has_Events_By_Event_Names($application_id, array('assess_fee_delivery', 'payment_fee_delivery', 'writeoff_fee_delivery')) == TRUE)
				{
					$DeliveryFee = Fetch_Balance_Total_By_Event_Names($application_id, array('assess_fee_delivery','payment_fee_delivery', 'writeoff_fee_delivery'));
				}

				if (Application_Has_Events_By_Event_Names($application_id, array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien')) == TRUE)
				{
					$TitleLienFee = Fetch_Balance_Total_By_Event_Names($application_id, array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien'));
				}

				$principal_fees = bcadd($WireTransferFee, $principal_fees, 2);
				$principal_fees = bcadd($DeliveryFee, $principal_fees, 2);
				$principal_fees = bcadd($TitleLienFee, $principal_fees, 2);

				$principal = bcadd($principal_fees, $result->fund_amount, 2);

				$result->estimated_service_charge = Interest_Calculator::calculateDailyInterest($business_rules, $principal, date("Ymd", strtotime($result->fund_due_date)), $result->date_first_payment);
			}

			$today = date("Y-m-d");
			$result->next_business_day = date('m/d/Y', strtotime($pdc->Get_Business_Days_Forward($today, 1)));

			// income_date assumed to be next paydates
			$result->income_date_one_month = date('m',strtotime($result->paydate_0));
			$result->income_date_one_day   = date('d',strtotime($result->paydate_0));
			$result->income_date_one_year  = date('Y',strtotime($result->paydate_0));
			$result->income_date_two_month = date('m',strtotime($result->paydate_1));
			$result->income_date_two_day   = date('d',strtotime($result->paydate_1));
			$result->income_date_two_year  = date('Y',strtotime($result->paydate_1));
			
			if(strtolower($business_rules['loan_type_model']) == 'cso')
			{
				
				//CSO Tokens [#17240]
				$result->cso_assess_fee_app = $renewal_class->getCSOFeeAmount('cso_assess_fee_app', $application_id); 	
				$result->cso_assess_fee_broker = $renewal_class->getCSOFeeAmount('cso_assess_fee_broker', $application_id,null,null,null,$principal); 
				$result->lend_assess_fee_ach = $renewal_class->getCSOFeeAmount('lend_assess_fee_ach', $application_id); 
				
				//I don't care much for this calculation - caused by shoehorning the percentage into CSO
				$result->svc_charge_percentage = round(($business_rules['service_charge']['svc_charge_percentage'] * 52), 2); 
				
				//Value of eCash Business Rule w/ same name.  Ex. $7.50 or 5% of the payment amount, whichever is greater
				$result->cso_assess_fee_late = $renewal_class->getCSOFeeDescription('cso_assess_fee_late', $application_id);
				
				//Calendar date that a cancellation notice must be received by.  Derived from the values of estimated funding date and the eCash business rule "Cancellation delay" Ex. 8/18/2008
				$result->loan_cancellation_date = $pdc->Get_Business_Days_Forward($result->date_fund_actual_ymd, 1);
		
			}

		}
		catch (Exception $e) // try/catch added for mantis:8015
		{
			eCash_Document::Log()->write("Failed calculating paydates for application_id {$application_id}, most likely due to incomplete application data. " . $e->getMessage(), LOG_DEBUG);
	        $result->paydate_0 				= 'n/a';
	        $result->paydate_1				= 'n/a';
	        $result->paydate_2				= 'n/a';
	        $result->paydate_3				= 'n/a';
			$result->date_first_payment		= 'n/a';
			$result->next_business_day		= 'n/a';
	        $result->income_date_one_month	= 'n/a';
	        $result->income_date_one_day	= 'n/a';
	        $result->income_date_one_year	= 'n/a';
	        $result->income_date_two_month	= 'n/a';
	        $result->income_date_two_day 	= 'n/a';
	        $result->income_date_two_year 	= 'n/a';
		}

         $result->reason_for_ach_return = NULL;
         if(NULL !== $transaction_id)
		 {
            $sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
               SELECT ach_return_code.name as reason_string
               FROM transaction_register
               JOIN ach ON (transaction_register.ach_id = ach.ach_id)
               JOIN ach_return_code ON (ach.ach_return_code_id = ach_return_code.ach_return_code_id)
               WHERE transaction_register.transaction_register_id = $transaction_id
               ";

           // eCash_Document::Log()->write($sql, LOG_DEBUG);

            $result->reason_for_ach_return = $db->querySingleValue($sql);

         }
		 else
		 {
            $sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				select
					rc.name as reason
				from
					transaction_register tr
					JOIN ach ON (tr.ach_id = ach.ach_id)
					JOIN ach_return_code rc ON (rc.ach_return_code_id = ach.ach_return_code_id)
				where
					tr.ach_id is not null
					and transaction_status = 'failed'
					and tr.application_id = {$application_id}
				order by tr.date_modified desc limit 1
         	";

           $result->reason_for_ach_return = $db->querySingleValue($sql);
         }

      	//The percentage for the paydown, used to populate PDPercent [AALM RC 4388]
		$result->paydown_percent = $business_rules['principal_payment']['principal_payment_percentage'].'%';
         /**
          * If the company_id is set, fetch the company properties
          * and add their values to $result.
          */

         if(isset(ECash::getTransport()->company_id) || isset($server->company_id))
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
				$val = eCash_Config::getInstance()->$property;
				if(! empty($val))
				{
					$n = strtolower($property);
					$result->{$n} = $val;
				}
			}
		}

		// Agean name_short -> 'site_id' mapping
		switch(strtolower($result->company_name_short))
		{
			case 'generic':
				$site_id = 1;
				break;
			default: $site_id = null;
		}
		if($site_id)
		{
			$result->company_site_id = $site_id;
		}
		$react_url = eCash_Config::getInstance()->REACT_URL;
		$esig_url = eCash_Config::getInstance()->ESIG_URL;

		$login_hash = md5($application_id . $site_id . 'L08N54M3');
		$encoded_app_id = urlencode(base64_encode($application_id));
		$resign = urlencode(base64_encode('resign'));
		$result->react_url = "{$react_url}?applicationid={$encoded_app_id}&login={$login_hash}";

		switch (strtolower($result->company_name_short))
		{

			case 'generic':
			default:
				$result->esig_url = "{$esig_url}/?page=ecash_sign_docs&application_id=". urlencode(base64_encode($application_id))."&login=" . md5($application_id . "encode_string") . "&ecvt&force_new_session" ;
				$result->cs_login_link = "{$esig_url}/?page=ent_cs_login&application_id=". urlencode(base64_encode($application_id))."&login=" . md5($application_id . "encode_string") . "&ecvt&force_new_session" ;
			break;
		}

		if(!isset($result->company_support_fax))
		{
			$result->company_support_fax = $result->company_fax;
		}

		// Get the agent login if it's there - otherwise use a stub
        if (isset($server->login))
		{
            $result->agent_login = strtoupper($server->login);
            $result->agent_name = $server->agent_name;
        }
		else
		{
            $result->agent_login = strtoupper($server->company);
            $result->agent_name = $result->company_name;
        }

        foreach(get_object_vars($result) as $key => $value)
        {
            $result->$key = strval($value);
        }

        $result->business_rules = self::Get_Business_Rules($server, $application_id);

        $result->decrypt_pass = 'UNKNOWN';
        if (isset($result->crypt_password) && strlen($result->crypt_password))
        {
            $result->decrypt_pass = crypt_3::Decrypt($result->crypt_password);
        }

		//mantis:5924
		require_once(SQL_LIB_DIR.'fetch_status_map.func.php');
		$status_map = Fetch_Status_Map();
		$status_id['inactive'] 	= Search_Status_Map("paid::customer::*root",$status_map);
		$status_id['recovered'] = Search_Status_Map("recovered::external_collections::*root",$status_map);

		if (in_array($result->application_status_id, array($status_id['inactive'], $status_id['recovered'])))
		{
			$sql = "
    			SELECT
                    		date_format(es.date_effective, '%m/%d/%Y') as due_date_inactive
                	FROM
                    		event_schedule es
                	WHERE
                    		es.application_id = '{$application_id}'

			    AND
				es.event_status = 'registered'

                	ORDER BY
                    		es.date_effective DESC
                	LIMIT 1";

				$sql_result = $db->query($sql);
				$row = $sql_result->fetch(PDO::FETCH_OBJ);

				$result->due_date_inactive = $row->due_date_inactive;
		}
			//end mantis:5924

        	return $result;
	}

		return false;
	}

	static private function Is_In_Prefund_Status($application_id)
	{
		$status = Fetch_Application_Status($application_id);
		return (($status['level1'] == "prospect") || 	($status['level2'] == "applicant") ||
				($status['level1'] == "applicant") || ($status['status'] == "funding_failed"));
	}

	static private function Has_Active_Schedule(Server $server, $application_id)
	{
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

	static private function getDocumentInfo(Server $server, $application_id, $fund_date)
	{
		$log = get_log();
		$db = ECash_Config::getMasterDbConnection();
		$data = Fetch_Application_Info($application_id);
		$loan_type = $data->loan_type;

	//	$qualify = new Qualify_2_Ecash($server->company, $loan_type, $db, NULL, $application_id, $log);

		$data = new stdClass();

		// Get the most recent due date
		$sql = <<<ESQL
		   		SELECT
                    date_format(es.date_effective, '%m/%d/%Y') as due_date
                FROM
                    event_schedule es,
                    event_type et
                WHERE
                    es.application_id = '{$application_id}'
                    AND et.event_type_id = es.event_type_id
                    AND et.company_id = {$server->company_id}
				    AND es.date_effective < curdate()
				    AND es.event_status = 'registered'
                    AND et.name_short IN ( 	'payment_service_chg',
											'repayment_principal'
										  )
                ORDER BY
                    es.date_effective desc
                LIMIT 1
ESQL;

		$tfund_date = $db->querySingleValue($sql);
		$fund_date = ($tfund_date) ? $tfund_date : $fund_date;

		require_once(SQL_LIB_DIR . "/scheduling.func.php");

		//Calculate Accrued Interest

		$business_rules = self::Get_Business_Rules($server, $application_id);
		$schedule = Fetch_Schedule($application_id);
		$interest = Interest_Calculator::scheduleCalculateInterest($business_rules, $schedule, date('m/d/Y'));
		$data->interest_accrued = $interest;

		$bi = Fetch_Balance_Information($application_id);
        $data->current_principal_payoff_amount = $bi->principal_pending;
        $data->fee_balance = $bi->fee_balance;
        $data->ups_label_fee = $bi->delivery_fee;

        if(strtolower($business_rules['service_charge']['svc_charge_type']) == 'fixed')
		{
        	$data->current_payoff_amount = $bi->total_pending;
		}
		else
		{
			$data->current_payoff_amount = $bi->total_pending + $data->interest_accrued + $bi->fee_balance;
		}

        // Now pull up data for the Current and NEXT set of due dates
		$sql = <<<ESQL
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
                    es.application_id = '{$application_id}'
                    AND et.event_type_id = es.event_type_id
                    AND et.company_id = {$server->company_id}
					AND es.event_status = 'scheduled'
                  	AND et.name_short IN ( 	'payment_service_chg',
											'repayment_principal',
											'paydown'
										  )
                GROUP BY
                    es.date_effective
                ORDER BY
                    es.date_effective
                LIMIT 2
ESQL;

		//eCash_Document::Log()->write($sql, LOG_DEBUG);

		$result = $db->query($sql);

		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
//			if ($data->current_principal_payoff_amount)
//			{
//				$fi = $qualify->Finance_Info(strtotime($row->due_date), strtotime($fund_date), $data->current_principal_payoff_amount, $row->service_charge);
//				$data->current_apr = $fi['apr'];
//			}
//			else
//			{
//				$data->current_apr = 0;
//			}
			$data->current_fund_date = $fund_date;
			$data->current_due_date = $row->due_date;
			$data->current_total_due = $row->total_due;
			$data->current_principal = $row->principal;
			$data->current_service_charge = $row->service_charge;
			$data->next_fund_date = $data->current_due_date;
			$data->next_due_date = $row->due_date;
			$data->next_total_due = $row->total_due;
			$data->next_principal = $row->principal;
			$data->next_service_charge = $row->service_charge;
		}

        if(!empty($data->next_principal))
		{
        	$data->next_principal_payoff_amount = $data->current_principal_payoff_amount - $data->next_principal;
//			if ($data->next_principal_payoff_amount)
//			{
//				$fi = $qualify->Finance_Info(strtotime($data->next_due_date), strtotime($data->current_due_date), $data->next_principal_payoff_amount, $data->next_service_charge);
//				$data->next_apr = $fi['apr'];
//			}
//			else
//			{
//				$data->next_apr = 0;
//			}
        }

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



		 // get disbursement reference #s if they exist
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . "\n" . <<<ESQL
    			SELECT
    				configuration_trace_data as check_no
    			FROM
    				event_schedule as es
				JOIN event_type as et USING (event_type_id)
				WHERE
					et.name_short IN ('moneygram_disbursement','check_disbursement')
				AND
					es.application_id = '{$application_id}'
ESQL;

		$result = $db->query($sql);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$data->check_number = $row->check_no;
		}

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . "\n" . <<<ESQL

				SELECT
                    es.date_effective                                  AS payment_date,
                   ABS( SUM(es.amount_principal + es.amount_non_principal)) AS payment_total
                FROM
                    event_schedule es
                WHERE
                    es.application_id = '{$application_id}'
                    AND es.company_id = {$server->company_id}
                    AND (es.amount_principal < 0 OR es.amount_non_principal < 0)
                    AND es.date_effective <= CURDATE()
                    AND es.event_status = 'registered'
                GROUP BY
                    date_effective
                ORDER BY
                    date_effective DESC
                LIMIT 1
ESQL;
		$result = $db->query($query);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$data->last_payment_date = $row->payment_date;
			$data->last_payment_amount = $row->payment_total;
		}

		return $data;

	}

	static private function Get_Business_Rules(Server $server, $application_id)
	{
		$business_rules = new ECash_BusinessRulesCache(ECash_Config::getMasterDbConnection());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		return $business_rules->Get_Rule_Set_Tree($rule_set_id);
	}

	static public function Format_Money($value, $default = NULL)
	{
		if ($value && (ctype_digit( (string) $value) || is_numeric($value)))
		{
			return money_format('%.2n', (float) $value);
		}
		elseif ($value && preg_match('/\$\d+\.\d{2}/',$value))
		{
			return $value;

		}
		elseif (!$value && $default != NULL)
		{
			return self::Format_Money($default);
		}
		else
		{
			return money_format('%.2n', (float) 0);
		}
	}

	/**
		Calculate From Monthly Net
		Stole this function from Qualify 2.  There's no reason to be calling Qualify 2 to perform a simple calculation
		like this.
    	@param $pay_span array Payment Span
    	@param $pay string Payment
    	@return $monthly_net array Monthly Span, FALSE on failure
    */
	static public function Calculate_Monthly_Net($pay_span, $pay)
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


	// taken from qualify.2, which was flawed due to a calculated finance charge vs. an explicit one used here
	static public function Calc_APR($payoff_date, $fund_date, $loan_amount, $finance_charge)
	{
		$days = round((strtotime($payoff_date) - strtotime($fund_date)) / 86400 );
		$days = ($days >= 1) ? $days : 1;

		if ($loan_amount > 0)
		{
			return round( (($finance_charge / (float) $loan_amount / $days) * 365 * 100), 2);
		}
		else
		{
			return 0;
		}

	}
}

?>
