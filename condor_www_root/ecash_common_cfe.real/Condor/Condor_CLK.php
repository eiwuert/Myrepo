<?php

/**
 * @package condor_tokens
 * 
 * <b>Revision History</b>
 * <ul>
 *   <li><b>2007-12-06 - rayl</b><br>
 *     Condor Token API created for use with OLP and eCash.
 *   </li>
 *   <li><b>2007-12-10 - rayl</b><br>
 *     Code cleanup for Condor Tokens and addition of Child Tokens.
 *   </li>
 *   <li><b>2007-12-12 - mlively</b><br>
 *     Added the CustomerInitialPayDown and CustomerInitialInFull Tokens.
 *   </li>
 * </ul>
 */
 
require_once("Condor_Tokens.php");

class Condor_CLK extends Condor_Tokens 
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
	public function __construct($ldb_db,$config_6,$company_id,$space_key,$application_id)
	{
		$this->Set_LDB($ldb_db);
		$this->Set_Config_6($config_6);
		$this->Set_Company_ID($company_id);
		$this->Set_Space_Key($space_key);
		$this->Set_Application_ID($application_id);
		$this->Set_Generic_Email(null,null,null);
		$this->Set_Login_ID(null);
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
		$this->Set_Qualify_2();
		$tokens = $this->getCondorData();
		return (array)$tokens;
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
		$data = $this->Process_Application_ID($this->Get_Application_ID());
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
		return (($data->level1 == "prospect") || ($data->level2 == "applicant") ||
				($data->level1 == "applicant") || ($data->application_status == "funding_failed"));
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
      $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
         SELECT
            application.*,
	    	DATE(application.date_created) date_app_created,
	    	TIME(application.date_created) time_app_created,
	    	application.ip_address client_ip_address,
            date_format(application.date_hire, '%d') as date_hire_day,
            date_format(application.date_hire, '%m') as date_hire_month,
            date_format(application.date_hire, '%Y') as date_hire_year,
            application.zip_code as zip,
			( ifnull( (
						SELECT
							value
						FROM
							application_contact 
						JOIN
							application_contact_category ON application_contact.application_contact_category_id = application_contact_category.application_contact_category_id
						WHERE
							application_id = application.application_id AND
							company_id = application.company_id AND
							type = 'phone' AND
							column_name = 'phone_work'
						ORDER BY
							application_contact.date_created DESC
						LIMIT 1						
					 )
					, application.phone_work ) ) AS phone_work,
			application.phone_work_ext,
			( ifnull( (
						SELECT
							value
						FROM
							application_contact
						JOIN
							application_contact_category ON application_contact.application_contact_category_id = application_contact_category.application_contact_category_id
						WHERE
							application_id = application.application_id AND
							company_id = application.company_id AND
							type = 'phone' AND
							column_name = 'phone_home'
						ORDER BY
							application_contact.date_created DESC
						LIMIT 1						
					 )
					, application.phone_home ) ) AS phone_home, 
			( ifnull( (
						SELECT
							value
						FROM
							application_contact
						JOIN 
							application_contact_category ON application_contact.application_contact_category_id = application_contact_category.application_contact_category_id
						WHERE
							application_id = application.application_id AND
							company_id = application.company_id AND
							type = 'phone' AND
							column_name = 'phone_cell'
						ORDER BY
							application_contact.date_created DESC
						LIMIT 1						
					 )
					, phone_cell ) ) AS phone_cell,
			( ifnull( (
						SELECT
							value
						FROM
							application_contact
						JOIN
							application_contact_category ON application_contact.application_contact_category_id = application_contact_category.application_contact_category_id
						WHERE
							application_id = application.application_id AND
							company_id = application.company_id AND
							type = 'phone' AND
							column_name = 'phone_fax'
						ORDER BY
							application_contact.date_created DESC
						LIMIT 1						
					 )
					, application.phone_fax ) ) AS phone_fax,
	       	(
	            SELECT event_schedule.date_effective 
	            FROM event_schedule
	            JOIN event_type AS et USING (event_type_id)
	            JOIN transaction_register AS tr USING (event_schedule_id)
	            WHERE event_schedule.application_id = application.application_id
	            AND et.name_short = 'payment_service_chg'
	            AND event_status = 'registered'
	            AND origin_group_id > 0 
	            AND transaction_status <> 'failed'
	            ORDER BY event_schedule.date_effective DESC
	            LIMIT 1
	       	) as last_payment_date,
	        (
	            SELECT event_schedule.date_effective
	            FROM event_schedule
	            JOIN event_type AS et USING (event_type_id)
	            JOIN transaction_register AS tr USING (event_schedule_id)
	            WHERE event_schedule.application_id = application.application_id
	            AND et.name_short = 'assess_service_chg'
	            AND event_status = 'registered'
	            AND origin_group_id > 0
	            AND transaction_status <> 'failed'
	            ORDER BY date_effective DESC
	            LIMIT 1
	        ) as last_assessment_date,						
            application.email as customer_email,
            date_format(application.dob, '%m-%d-%Y') as dob,
            date_format(application.dob, '%d') as dob_day,
            date_format(application.dob, '%m') as dob_month,
            date_format(application.dob, '%Y') as dob_year,
            application.date_fund_actual as date_fund_stored,
            application.date_first_payment as date_first_payment_raw,
            date_format(application.date_first_payment,  '%d') AS date_first_payment_day,
            date_format(application.date_first_payment,  '%m') AS date_first_payment_month,
            date_format(application.date_first_payment,  '%Y') AS date_first_payment_year,
            date_format(application.date_first_payment,  '%m/%d/%Y') AS date_first_payment,
            ( CASE WHEN application.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%d') ELSE DATE_FORMAT(application.date_fund_actual,'%d') END ) as date_fund_actual_day,
            ( CASE WHEN application.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%m') ELSE DATE_FORMAT(application.date_fund_actual,'%m') END ) as date_fund_actual_month,
            ( CASE WHEN application.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%Y') ELSE DATE_FORMAT(application.date_fund_actual,'%Y') END ) as date_fund_actual_year,
            ( IF(application.fund_actual > 0, application.fund_actual, application.fund_qualified) ) as fund_amount,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%d') as date_fund_estimated_day,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%m') as date_fund_estimated_month,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%Y') as date_fund_estimated_year,
            date_format(if(application.date_fund_estimated < date_add(curdate(), interval 1 day), date_add(curdate(), interval 1 day), application.date_fund_estimated), '%m-%d-%Y') as date_fund_estimated,
            DATE_FORMAT(application.last_paydate, '%Y-%m-%d') as last_paydate,
            login.login as login_id,
            login.password as crypt_password,
			stat.level0_name as application_status,
			stat.level1,
			stat.level2,
			card.card_number,
			lt.name_short as 'loan_type',
			(
				SELECT provider_card_id
				FROM card
				WHERE card.customer_id = application.customer_id
				ORDER BY date_created DESC
				LIMIT 1
			) as 'provider_card_id',
			(
				SELECT date_format(es.date_effective, '%m/%d/%Y') as due_date_inactive
			    FROM event_schedule es
			    WHERE es.application_id = application.application_id 
			    AND es.event_status = 'registered' 
			    ORDER BY es.date_effective DESC LIMIT 1
			) as schedule_model,
            com.name_short as company
         FROM application
         LEFT JOIN customer AS login ON ( application.customer_id = login.customer_id)
         LEFT JOIN application_status_flat AS stat ON ( application.application_status_id = stat.application_status_id )
         LEFT JOIN loan_type lt USING (loan_type_id)
         LEFT JOIN schedule_model sm USING (schedule_model_id)
         LEFT JOIN company as com ON (application.company_id = com.company_id)
		LEFT JOIN card as card ON (
			SELECT card_id
			FROM card
			WHERE card.customer_id = login.customer_id
			ORDER BY date_created DESC
			LIMIT 1
		) = card.card_id
		WHERE application.application_id = $application_id
         " ;

      	$result = $this->Get_LDB()->Query($query);
		if($result->Row_Count() > 0)
		{
      		return $result->Fetch_Object_Row();
		}
		else
		{
			throw new Exception("No Application data {$application_id}");
		}
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
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT property, value 
			FROM company_property
			WHERE company_id = {$data->company_id} AND
			property in ('COMPANY_NAME',
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
						 'COMPANY_CARD_NAME',
						 'COMPANY_CARD_PROV_BANK',
						 'COMPANY_CARD_PROV_SERV',
						 'COMPANY_CARD_PROV_SERV_PHONE',
						 'MONEYGRAM_RECEIVE_CODE'
						)
			";
		$result = $this->Get_LDB()->Query($sql);
		while ($row_obj = $result->Fetch_Object_Row()) 
		{
			$n = strtolower($row_obj->property);
			$data->{$n} = $row_obj->value;
		}		
   		
	}
	
	/**
	 * Fetch Application Fund Date
	 * 
	 * Collect Fund Date for Application in eCash database.
	 *
	 * @param object $data
	 */
	function Fetch_Fund_Date(&$data)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		   		SELECT 
                    date_format(es.date_effective, '%m/%d/%Y') as due_date
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = '{$data->application_id}'  
                    AND et.event_type_id = es.event_type_id  
                    AND et.company_id = {$data->company_id}
				    AND es.date_effective < curdate()
				    AND es.event_status = 'registered'
                    AND et.name_short IN ( 	'payment_service_chg',
											'repayment_principal'
										  )  
                ORDER BY
                    es.date_effective desc
                LIMIT 1	
			";		
		$tfund_date = array_pop($this->Get_LDB()->Get_Column($query));
		$data->fund_date = ($tfund_date) ? $tfund_date : $data->fund_date;
	}
	
	/**
	 * Fetch Application Balance
	 *
	 * Collect Balance information for application in eCash Database.
	 * 
	 * @param object $data
	 */
	function Fetch_Application_Balance(&$data)
	{
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
				$fi = $this->qualify->Finance_Info(strtotime($data->next_due_date), strtotime($data->current_due_date), $data->next_principal_payoff_amount, $data->next_service_charge);
				$data->next_apr = $fi['apr'];
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
        // Get Data for the most recent past arrangment and the upcoming arrangement
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
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
                    AND et.company_id = {$this->Get_Company_ID()}					
                    AND et.name_short IN ( 	'quickcheck',
											'western_union',
											'credit_card',
											'personal_check',
											'payment_debt',
											'money_order',
											'moneygram',
											'payment_manual',
											'payment_arranged'
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
                    AND et.company_id = {$this->Get_Company_ID()}					
                    AND et.name_short IN ( 	'quickcheck',
											'western_union',
											'credit_card',
											'personal_check',
											'payment_debt',
											'money_order',
											'moneygram',
											'payment_manual',
											'payment_arranged'
										)  
				    AND datediff(es.date_effective, curdate()) >= 0
				    AND es.event_status = 'scheduled'
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective ASC
				LIMIT 1	
				)       
		";
        
		$result = $this->Get_LDB()->Query($sql);

		while($row = $result->Fetch_Object_Row()) {
			switch ($row->days_til_due < 0) {
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
	                $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
	                                                SELECT  holiday
	                                                FROM    holiday
	                                                WHERE   active_status = 'active'";
	
	                $result = $this->Get_LDB()->Query($query);
	                $holiday_list = array();
	                while( $row = $result->Fetch_Object_Row() )
	                {
	                        $holiday_list[] = $row->holiday;
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
        $query = "
                -- Condor Token API ".__FILE__.":".__LINE__.":".__METHOD__."()
                SELECT
                        camp.campaign_info_id,
                        camp.promo_id,
                        camp.promo_sub_code,
                        s.name as url,
                        s.license_key
                FROM
                        application a,
                        site s,
                        campaign_info camp
                WHERE
                        a.application_id = {$this->Get_Application_ID()}
                AND a.company_id = {$this->Get_Company_ID()}
                AND camp.application_id = a.application_id
                AND camp.campaign_info_id =
                        (
                                SELECT
                                        MAX(campaign_info_id)
                                FROM
                                        campaign_info cref
                                WHERE
                                        cref.application_id = camp.application_id
                        )
                        AND a.enterprise_site_id = s.site_id
                ";
        $result = $this->Get_LDB()->Query($query);
        return $result->Fetch_Object_Row();        		
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
				and tr.application_id = {$data->application_id}
			order by tr.date_modified desc limit 1
     	";
     	
       $data->reason_for_ach_return = array_pop($this->Get_LDB()->Get_Column($sql));
	
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
		// Yes, there are a few things grabbed twice because there were two
		// different functions that this is replacing.
		$references = array();
		$query = "
	        -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT 	personal_reference_id,
					name_full,
					phone_home,
					relationship,
					name_full as full_name,
					phone_home as phone
			FROM personal_reference
			WHERE application_id = {$application_id}";
	
		$results = $this->Get_LDB()->Query($query);
		while ($row = $results->Fetch_Object_Row())
		{
			$references[] = $row;
		}
		return $references;
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
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */	
						SELECT
						    app.application_id,
						    app.olp_process,
						    ra.agent_id,
						    app.application_status_id
						FROM
						   react_affiliation as ra
						   join application as app on (app.application_id = ra.react_application_id)
						where
						  ra.application_id = {$application} AND
						  ra.company_id = {$this->Get_Company_ID()}";
		$values = array();
		$result = $this->Get_LDB()->Query($query);
		while ($row = $result->Fetch_Object_Row())
		{
			$values[] = $row;
		}
		return $values;	
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
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT COUNT(*) as 'count'
			FROM event_schedule
			WHERE application_id = {$data->application_id}
			AND event_status = 'scheduled'"; //"
	
		
		$result = $this->Get_LDB()->Query($sql);
		$count = $result->Fetch_Object_Row()->count;
		return ($count != 0);
	}		
}
?>