<?php

/**
	@publicsection
	@public
	@brief
		OLP Applications
	
	Replaces olp and olp_bb_partial database querys for applications. Added
	functions to validate against account_validate/
	
	@version
			
		0.0.1 2005-08-28 - Raymond Lopez
			- Application framework creation.
			
	@todo
			
		
*/


require_once ("mysql.4.php");
require_once("account_validate.1.php");

class OLP_Valid_Accounts

{
	private $DatFrom;
	private $DatTo;
	private $mode;
	private $db;
	private $olp_accounts;
	private $email_array;
	private $ssn_array;
	
	/**
		@publicsection
		@public
		@brief
			Construct class
			
		Gathers application information from database
		
		@param $DatFrom string SQL From Date
		@param $DatTo string SQL To Date
		@param $Database string OLP or OLP_BB_PARTIALS.
		@param $SiteList array URL Sites to filiter query.
		@param $mode string Connection Mode (LOCAL,RC,LIVE).
			
	*/	
	public function  __construct(	$DateFrom 	= NULL,  
									$DateTo		= NULL,
									$Database 	= "OLP",
									$SiteList	= NULL,
									$mode 		= "LOCAL")
	{
		$this->DateFrom 		= is_null($DateFrom) ? date("Ymd000000",strtotime("2 days ago")) : $DateFrom;
		$this->DateTo 			= is_null($DateTo) ? date("Ymd235959",strtotime("2 days ago")) : $DateTo;		
		$this->mode 			= $mode;
		$this->olp_accounts 	= array();
		$this->ssn_array 		= array();		
		$this->email_array		= array();		
		
		switch ($mode) 
		{
			case "LIVE":	
		    	$this->db = array (
		    				"host" => "reader.olp.ept.tss",
		    				"user" => "sellingsource",
		    				"password" => "password");
		    	break;
		    case "LOCAL":
		    	$this->db = array (
		    				"host" => "beast.tss",
		    				"user" => "root",
		    				"password" => "");
		    	break;		    

		}
		if($Database == "OLP_BB_PARTIALS")
		{	
			$this->Query_Partials_Database();
		} 
		else if($Database == "OLP_VPFCR")
		{
			$this->Query_OLP_VPFCR_Database($SiteList);	
		}
		else if($Database == "OLP_VP1")
		{
			$this->Query_OLP_VP1_Database();
		}	
		else
		{
			die();	
		}
	} 
	
		
	/**
		@publicsection
		@public
		@brief
			Get_All_Accounts
			
		Returns all distinct applications
		
		@return olp_accounts array Array object of accounts
			
	*/		
	public function Get_All_Accounts() 
	{
		return 	$this->olp_accounts;	
	}
	
	/**
		@publicsection
		@public
		@brief
			Get_Good_Standing_Accounts
			
		Returns all distinct applications in Good Standing
		
		@return olp_accounts array Array object of accounts
			
	*/		
	public function Get_Good_Standing_Accounts() 
	{
		return  $this->Exec_Account_Accounts(true);
	}		
	
	/**
		@publicsection
		@public
		@brief
			Get_Bad_Standing_Accounts
			
		Returns all distinct applications in Bad Standing
		
		@return olp_accounts array Array object of accounts
			
	*/		
	public function Get_Bad_Standing_Accounts() 
	{
		return  $this->Exec_Account_Validate(false);
	}	
	
	/**
		@privatesection
		@private
		@brief
			Exec_Account_Validate
			
		Runs Account Validator and returns Applications
		
		@return olp_accounts array Array object of accounts
			
	*/		
	private function Exec_Account_Validate($standing)
	{
		$act_validate = new Account_Validate($this->mode); 
		$counter = count($this->olp_accounts);
		$x = 0;	$i = 0;	
		$ssn_cache = array();
		$email_cache = array();
		for($i=0; $i<$counter; $i++)
		{
			if(($i == $x) || ($i == $counter))
			{
				$cache_size = 500;
				unset($ssn_cache);unset($email_cache);
				unset($valid_ssn_arr);unset($valid_email_arr);
				$in_count = (($i == $counter) || ($counter - $i <= $cache_size)) 
					? $counter : ($i + $cache_size);
				for($x=$i; $x<$in_count; $x++)
				{
					$item = $this->olp_accounts[$x];
					$ssn_cache[] 	= $item['ssn'];
					$email_cache[] 	= $item['email'];
				}
				$act_validate->Validate($ssn_cache, $email_cache);
				$valid_ssn_arr = $act_validate->GetSSNArray();
				$valid_email_arr = $act_validate->GetEmailArray();
			}
			$item = $this->olp_accounts[$i];
			if( (in_array($item['email'],$valid_email_arr) != $standing) && 
				(in_array($item['ssn'],$valid_ssn_arr) != $standing) )
			{			
				$this->olp_accounts[$i] = null;
			}
		}
		$cache_part = $this->olp_accounts;
		unset($this->olp_accounts);
		for($i=0; $i<count($cache_part); $i++) 
		{
			if($cache_part[$i] != null)	
				$this->olp_accounts[] = $cache_part[$i];
		}
		return $this->olp_accounts;
	}
	
	/**
		@privatesection
		@private
		@brief
			Process_Results
			
		Processes all SQL Queries and Eliminated Duplicates
		
		@return null
			
	*/			
	private function Process_Results($db,$query) {
		try 
		{			
			$mysql = new MySQL_4($this->db["host"], $this->db["user"], $this->db["password"]);
			$mysql->Connect();			
			$result = $mysql->Query($db, $query);
			$count = $mysql->Row_Count($result);
			$appids = array();
			$emails = array();
			$accounts = array();
			while($item =  $mysql->Fetch_Array_Row($result)) 		
			{
					if(!$appids[$item['application_id']] &&
					!$emails[$item['email']])
					{
						$accounts[] = $item;	
						$appids[$item['application_id']] = TRUE;	
						if(!is_null($item['email']))
							$emails[$item['email']] = TRUE;					
					}
			}
			unset($appids);
			$mysql->Close_Connection();			
		} 
		catch (MySQL_Exception $e)
		{
			print($e);
			die();
		}
		unset($result);unset($count);unset($mysql);		
		return $accounts;
		
	}
		
	/**
		@privatesection
		@private
		@brief
			Query_Partials_Database
			
		Query_Partials_Database
		
		@return null
			
	*/		
	private function Query_Partials_Database()
	{
		$query = "
			SELECT
				campaign_info.application_id as application_id,
				campaign_info.modified_date AS created,
				campaign_info.url AS signup_source,
				personal.first_name AS first_name,
				personal.middle_name AS middle_name,
				personal.last_name AS last_name,
				personal.home_phone AS home_phone,
				SUBSTRING(personal.home_phone,1,3) AS HOMEAREA,
				SUBSTRING(personal.home_phone,4,3) AS HOMEEXCH,
				SUBSTRING(personal.home_phone,7,4) AS HOMENUMB,
				employment.work_phone AS work_phone,
				SUBSTRING(employment.work_phone,1,3) AS WORKAREA,
				SUBSTRING(employment.work_phone,4,3) AS WORKEXCH,
				SUBSTRING(employment.work_phone,7,4) AS WORKNUMB,
				bank_info.direct_deposit,
				bank_info.routing_number,
				residence.address_1,
				residence.city,
				residence.state,
				residence.zip,
				personal.email AS email,
				personal.social_security_number AS ssn,
				personal.date_of_birth AS dob,
				campaign_info.ip_address AS ip_address,
				DATE_FORMAT(campaign_info.modified_date, '%m-%d-%Y %H:%i:%s') as dtime,
				income.pay_frequency,
				income.net_pay,
				employment.employer,
				personal.best_call_time AS best_call_time,
				'1000' AS NETINCOME
			FROM
				campaign_info
				JOIN personal ON (campaign_info.application_id=personal.application_id)
				JOIN residence USING (application_id)
				JOIN income ON (income.application_id = campaign_info.application_id)
				JOIN employment ON (employment.application_id = campaign_info.application_id)
				JOIN bank_info ON (bank_info.application_id = campaign_info.application_id)	
			WHERE
				campaign_info.modified_date BETWEEN '$this->DateFrom' AND '$this->DateTo'
				AND personal.first_name!=''
				AND personal.first_name NOT LIKE '%TEST'
				AND personal.first_name NOT LIKE 'TEST%'
				AND personal.first_name NOT LIKE '%SHIT%'
				AND personal.first_name NOT LIKE '%SPAM%'
				AND personal.first_name NOT LIKE '%FUCK%'
				AND personal.first_name NOT LIKE '%BITCH%'
				AND personal.last_name!=''
				AND personal.last_name NOT LIKE '%TEST'
				AND personal.last_name NOT LIKE 'TEST%'
				AND personal.last_name NOT LIKE '%SHIT%'
				AND personal.last_name NOT LIKE '%SPAM%'
				AND personal.last_name NOT LIKE '%FUCK%'
				AND personal.last_name NOT LIKE '%BITCH%'
				AND personal.home_phone!=''
				AND personal.email!=''
				AND personal.email NOT LIKE '%TEST'
				AND personal.email NOT LIKE 'TEST%'
				AND personal.email NOT LIKE '%SHIT%'
				AND personal.email NOT LIKE '%SPAM%'
				AND personal.email NOT LIKE '%FUCK%'
				AND personal.email NOT LIKE '%BITCH%'
				AND personal.email NOT LIKE '%ABUSE'
				AND personal.email NOT LIKE '%INTERNIC%'
				AND personal.email NOT LIKE '%NETWORKSOLUTIONS%'
				AND personal.email NOT LIKE '%TSSMASTERD%'
				AND campaign_info.application_id!=0
				AND campaign_info.url NOT IN ('ameriloan.com','usfastcash.com','unitedcashloans.com','500fastcash.com','ecashapp.com','credit.com','expeditepayday.com','christianfaithfinancial.com','wegivecash.com')
				AND LENGTH(residence.zip) = 5
				AND residence.address_1 != ''
				#AND bank_info.routing_number != ''
				#AND bank_info.account_number != ''			
				#AND personal.social_security_number != ''
				#AND residence.state NOT IN ('VT','NY','NJ','CT','PA','GA','WV','AR')
				AND bank_info.direct_deposit='TRUE'
				AND income.net_pay >= 1000";
				$this->olp_accounts = $this->Process_Results("olp_bb_partial",$query);	
	}
	
	/**
		@privatesection
		@private
		@brief
			Query_OLP_Database
			
		Query_OLP_Database
		
		@return null
			
	*/		
	private function Query_OLP_VPFCR_Database($SiteList)
	{
		$query = "
			SELECT
				application.application_id AS application_id,
				application.created_date AS created_date,
				UNIX_TIMESTAMP(application.created_date) CD0,
				personal.first_name AS first_name,
				personal.middle_name AS middle_name,
				personal.last_name AS last_name,
				personal.home_phone AS home_phone,
				personal.email AS email,
				personal.date_of_birth AS dob,
				personal.social_security_number AS ssn,
				residence.address_1 AS address_1,
				residence.city AS city,
				residence.state AS state,
				residence.zip AS zip,
				bank_info.account_number AS bank_account_number,
				bank_info.routing_number AS bank_routing_number,
				SUBSTRING(bank_info.bank_account_type,1,1) AS bank_account_type,
				campaign_info.ip_address AS ip_address,
				campaign_info.url AS url,
				campaign_info.promo_id AS promo_id,
				DATE_FORMAT(application.created_date, '%Y-%m-%dT%h:%i:%s') as cdf,
				DATE_FORMAT(personal.date_of_birth, '%Y-%m-%d') as dobf,
				income.pay_frequency as income_frequency,
				paydate.paydate_model_id as paydate_model_id,
				paydate.day_of_week as day_of_week,
				paydate.next_paydate as next_paydate,
				paydate.day_of_month_1 as day_of_month_1,
				paydate.day_of_month_2 as day_of_month_2,
				paydate.week_1 as week_1,
				paydate.week_2 as week_2
			FROM
				application
				JOIN personal ON (application.application_id=personal.application_id)
				JOIN residence ON (application.application_id=residence.application_id)
				JOIN bank_info ON (application.application_id=bank_info.application_id)
				JOIN campaign_info ON (application.application_id=campaign_info.application_id)
				JOIN income ON (application.application_id=income.application_id)
				JOIN paydate ON (application.application_id=paydate.application_id)
				JOIN target ON (application.target_id=target.target_id)
			WHERE
				application.created_date BETWEEN '$this->DateFrom' and '$this->DateTo'
				AND personal.first_name!=''
				AND personal.first_name NOT LIKE '%TEST'
				AND personal.first_name NOT LIKE 'TEST%'
				AND personal.first_name NOT LIKE '%SHIT%'
				AND personal.first_name NOT LIKE '%SPAM%'
				AND personal.first_name NOT LIKE '%FUCK%'
				AND personal.first_name NOT LIKE '%BITCH%'
				AND personal.last_name!=''
				AND personal.last_name NOT LIKE '%TEST'
				AND personal.last_name NOT LIKE 'TEST%'
				AND personal.last_name NOT LIKE '%SHIT%'
				AND personal.last_name NOT LIKE '%SPAM%'
				AND personal.last_name NOT LIKE '%FUCK%'
				AND personal.last_name NOT LIKE '%BITCH%'
				AND personal.home_phone!=''
				AND personal.email!=''
				AND personal.email NOT LIKE '%TEST'
				AND personal.email NOT LIKE 'TEST%'
				AND personal.email NOT LIKE '%SHIT%'
				AND personal.email NOT LIKE '%SPAM%'
				AND personal.email NOT LIKE '%FUCK%'
				AND personal.email NOT LIKE '%BITCH%'
				AND personal.email NOT LIKE '%ABUSE'
				AND personal.email NOT LIKE '%INTERNIC%'
				AND personal.email NOT LIKE '%NETWORKSOLUTIONS%'
				AND personal.email NOT LIKE '%TSSMASTERD%'
				AND personal.date_of_birth!=''
				AND personal.social_security_number!=''
				AND residence.address_1!=''
				AND residence.city!=''
				AND residence.state!=''
				AND residence.zip!=''
				AND bank_info.account_number!=''
				AND bank_info.routing_number!=''
				AND campaign_info.ip_address!=''
				AND campaign_info.url!=''
				AND campaign_info.offers='TRUE'
				AND campaign_info.url NOT IN ('ameriloan.com','usfastcash.com','unitedcashloans.com','500fastcash.com','ecashapp.com','credit.com','expeditepayday.com','christianfaithfinancial.com','wegivecash.com')
				AND target.property_short != 'cg'
				AND target.property_short != 'cgdd'			
				AND target.property_short != 'cg2'
				AND target.property_short != 'cg4'";
			if($SiteList)
			$query .= " AND campaign_info.url IN ('".implode("','",$SiteList)."')";		
			$this->olp_accounts = $this->Process_Results("olp",$query);
	}

	/**
		@privatesection
		@private
		@brief
			Query_OLP_VP1_Database
			
		Query_OLP_VP1_Database
		
		@return null
			
	*/			
	private function Query_OLP_VP1_Database()
	{
			$query = "
				SELECT
					application.application_id as application_id,
					personal.first_name,
					personal.last_name,
					personal.home_phone,
					personal.email AS email,
					residence.address_1,
					residence.city,
					residence.state,
					residence.zip,
					application.created_date AS created_date,
					campaign_info.url AS url,
					campaign_info.ip_address
				FROM 
					application
					JOIN personal ON application.application_id = personal.application_id
					JOIN residence ON application.application_id = residence.application_id
					JOIN campaign_info ON application.application_id = campaign_info.application_id
				WHERE
					application.created_date BETWEEN '$this->DateFrom' AND '$this->DateTo'
					AND application.application_type != 'VISITOR'
					AND personal.first_name != ''
					AND personal.first_name IS NOT NULL
					AND personal.last_name != ''
					AND personal.last_name IS NOT NULL
					AND personal.home_phone != ''
					AND personal.home_phone IS NOT NULL	
					AND personal.email != ''
					AND personal.email IS NOT NULL
					AND campaign_info.url NOT IN ('ameriloan.com','usfastcash.com','unitedcashloans.com','500fastcash.com','ecashapp.com','credit.com','expeditepayday.com','christianfaithfinancial.com','wegivecash.com')
					AND campaign_info.offers = 'TRUE'
					AND residence.address_1 != ''
					AND residence.address_1 IS NOT NULL";		
			$this->olp_accounts = $this->Process_Results('olp', $query);
	}
	

}



?>