<?php
/**
 * Nirvana Interface
 * 
 * This is the interface to be called from Nirvana
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Sep 19, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */
require_once 'config.php';
require_once '/virtualhosts/nirvana_client/client.php';
require_once 'mysql.4.php';
require_once 'mysqli.1.php';
require_once BFW_CODE_DIR . 'server.php';
require_once BFW_CODE_DIR . 'setup_db.php';
require_once BFW_CODE_DIR . 'SessionHandler.php';
require_once 'maintenance_mode.php';

define('DEBUG', FALSE); // This is required, otherwise it evaluates to TRUE.

class Nirvana_OLP extends Nirvana_Client
{
	/**
	 * Customer Data Skeleton
	
	   //Transaction
	   "transaction_date"            => null, //Unix Timestamp
	   "track_key"                   => null, //string
	   "transaction_id"              => null, //int
	   "session_id"                  => null,
	   //Campaign Info
	   "promo_id"                    => null,  //int
	   "promo_sub_code"              => null,  //string
	   "ip_address"                  => null,  //string
	   "originating_address"         => null,  //string
	   "react_url"                   => null,  //string
	   "company_phone"               => null,  //string
	   "company_name"                => null,  //string
	   "company_name_short"          => null,  //string
	   //Bank Info
	   "bank_name"                   => null, //string
	   "bank_account"                => null, //string
	   "bank_routing"                => null, //string
	   "bank_account_type"           => null, //checking, savings
	   //Personal
	   "name_first"                  => null, 
	   "name_middle"                 => null,
	   "name_last"                   => null,
	   "dob"                         => null, //string (YYYY-mm-dd)
	   "ssn"					     => null, //string
	   "legal_id_number"		     => null, //string 
	   "legal_id_state"              => null, //string
	   //Contact
	   "phone_home"                  => null,  //string
	   "phone_cell"                  => null,  //string
	   "phone_fax"                   => null,  //string
	   "phone_work"                  => null,  //string
	   "phone_work_ext"              => null,  //string
	   "address_street"              => null,  //string
	   "address_unit"                => null,  //string
	   "address_city"                => null,  //string
	   "address_state"               => null,  //string
	   "address_zipcode"             => null,  //string
	   "email"                       => null,
	   "best_call_time"              => null,  //MORNING, AFTERNOON, EVENING
	   "personal_ref_1_name"         => null,
	   "personal_ref_1_phone"        => null,
	   "personal_ref_1_relationship" => null,
	   "personal_ref_2_name"         => null,
	   "personal_ref_2_phone"        => null,
	   "personal_ref_2_relationship" => null,
	   //Employment
	   "income_amount"               => null,  //int
	   "income_frequency"            => null,  //WEEKLY,BI_WEEKLY,TWICE_MONTHLY,MONTHLY
	   "income_type"                 => null,  //BENEFITS, EMPLOYMENT
	   "income_direct_deposit"       => null,  //bool
	   "work_name"                   => null,  //string
	   "work_title"                  => null,  //string
	   "work_shift"                  => null,  //string
	   "work_date_of_hire"           => null,  //string
	   //Paydate
	   "paydate_model_id"            => null,
	   "pdm_day_of_week"             => null,
	   "pdm_next_paydate"            => null,
	   "pdm_day_of_month_1"          => null,
	   "pdm_day_of_month_2"          => null,
	   "pdm_week_1"                  => null,
	   "pdm_week_2"                  => null
	*/
	
	private $days_of_week = array("mon" => 1,
								  "tue" => 2,
								  "wed" => 3,
								  "thu" => 4,
								  "fri" => 5);

	private $mode = null;
	private $olp_db = null;
	private $ldb_db = "ldb";
	private $react_db = "react_db";
	
	private $olp_sql = null;
	private $ldb_sql = null;
	private $react_sql = null;
	
	private $maintenace = null;
	
	private $hash_key = 'encode_string';

	private $test_data = array("transaction_id" => 9999999,
							 "track_key" => 'AAAAAAAAAAAAAAAAAAAAAAAAAAA',
							   "transaction_date" => 1123184810,
					           "promo_id" => 99999,
					           "promo_sub_code" => "1167-cash1007",
					           "ip_address" => "127.0.0.1",
					           "company_name_short" => "ca",
					           "bank_name" => "selling source credit union",
					           "bank_account" => "00004128713",
					           "bank_routing" => 322275746,
					           "bank_account_type" => "checking",
					           "name_first" => "test",
					           "name_last" => "person",
					           "name_middle" => "",
					           "dob" => "1971-08-16",
					           "ssn" => "123456789",
					           "legal_id_number" => "A123456",
					           "legal_id_state" => "NV",
					           "phone_home" => "8003977706",
					           "phone_cell" => "7028855985", 
					           "phone_fax" => "7024929871",
					           "phone_work" => "8003977706",
					           "phone_work_ext" => "",
					           "address_street" => "325 Warm Springs",
					           "address_unit" => "",
					           "address_city" => "Las Vegas",
					           "address_state" => "NV",
					           "address_zipcode" => "89119",
					           "email" => "nirvanatss@gmail.com",
					           "best_call_time" => "MORNING",
					           "income_amount" => "2799.00",
					           "income_frequency" => "BI_WEEKLY",
					           "income_type" => "EMPLOYMENT",
					           "income_direct_deposit" => 1,
					           "work_name" => "Selling Source",
					           "work_title" => "",
					           "work_shift" => "",
					           "work_date_of_hire" => "2005-05-04 00:00:00",
					           "paydate_model_id" => "DWPD",
					           "pdm_day_of_week" => "5",
					           "pdm_next_paydate" => "2005-07-29",
					           "pdm_day_of_month_1" => "",
					           "pdm_day_of_month_2" => "",
					           "pdm_week_1" => "",
					           "pdm_week_2" => "",
					           "company_name" => "AmeriLoan",
					           "company_phone" => "1-800-362-9090",
					           "company_fax" => "1-800-256-9166",
					           "react_url" => "",
					           "originating_address" => "ameriloan.com",
					           "ent_site" => "ameriloan.com",
					           "react_url" => "http://ameriloan.com/?react_url",
					           "ent_url" => "http://ameriloan.com/?ent_url",
					           'bb_option_url' => 'http://paydayangels.com/',
					           'react_key' => 'abcdef1234567890',
					           'ent_short_url' => 'www.koutr.com',
							   'map_link' => 'http://maps.google.com'
							   );

	public function __construct()
	{
		$this->companies = array(
			'generic' => 	array('company_name' => 'someloancompany.com',
							  'phone'	=> '1-800-000-0000',
							  'fax'	=> '1-877-000-0000',
							  'email' => 'customerservice@someloancompany.com',
							  'url' => 'loanservicecompany.com',
							  'ecash_version' => 3.0,
							  'street' => '',
							  'city' => '',
							  'state' => '',
							  'zip' => '',
							  'ent_short_url' => '',
							  'teleweb_phone' => ''),
		);	

		
		//Set Mode
		$this->mode = BFW_MODE;
		//Set DB names
		switch($this->mode)
		{
		    case "RC":
				$this->olp_db = 'rc_olp';
		        break;
		    case "LOCAL":
		    case "LIVE":
				$this->olp_db = 'olp';
		}
		
		//Create DB Connections
		$this->maintenance = new Maintenance_Mode();
        if($this->maintenance->Is_Online()) 
        {
            try
            {
				$this->olp_sql = Setup_DB :: Get_Instance("blackbox", $this->mode);
            }
            catch(Exception $e) {}
            try
            {
				$this->react_sql = Setup_DB :: Get_Instance("react", $this->mode); //React CLK
            }
            catch(Exception $e) {}
        }

		// Run parent's constructor
		parent::__construct();
	}
	
	/**
	 * Fetch Multiple
	 * 
	 * Fetch Multiple track keys
	 * @param array track_keys
	 * @return array
	 */
	public function Fetch_Multiple($track_keys)
	{
		$data = array();
		
		//Maintenance Mode
        if(!$this->maintenance->Is_Online()) 
        {
            return array();
        }
		
		//If empty return immediately
		if(empty($track_keys)) return $data;
		
		foreach ($track_keys as $track_key)
		{
			//$return_data = array();
			$i = array();
			
			//Return test data if all A's
			if($track_key == 'AAAAAAAAAAAAAAAAAAAAAAAAAAA')
			{
				$data[$track_key] = $this->test_data;
				continue;
			}
			$checked_servers = array();
			$i = false;
			foreach($this->companies as $prop_short => $company)
			{
				$this->ldb_sql = Setup_DB::Get_Instance("mysql", $this->mode.'_READONLY', $prop_short);
				$hash = md5(serialize($this->ldb_sql->db_info));
				if(in_array($hash,$checked_servers))
				{
					continue;
				}
				$checked_servers[] = $hash;
				// Attempt to get information from ldb database
				if(($i = $this->Get_LDB_Info($track_key, $prop_short)) !== FALSE)
				{
					//we found it
					//die("HOLY CRAP WE FOUND IT".print_r($i,true));
					break;
				}
			}
			if($i === FALSE)
			{
				// ldb didn't have the info, attempt to get information from olp database
				if(($i = $this->Get_OLP_Info($track_key)) === FALSE)  
				{
					// olp didn't have the info, attempt to get information from the session
					if(($i = $this->Get_Session_Info($track_key)) === FALSE)  
					{
						$i = array();
					}
				}
			}
			
			$return_data = $i;//array_merge($return_data, $i);
			
			// Get the information from the session
			if(isset($return_data['transaction_id']) && !isset($return_data['originating_address']))
			{
				$o = $this->Get_Session_Info($track_key);
				$return_data['originating_address'] = $o['originating_address'];
			}
			
			$return_data['start_url'] = (preg_match('!^http://!is', $return_data['originating_address']))
										? $return_data['originating_address']
										: 'http://' . $return_data['originating_address'];
			
			$return_data['application_date'] = date('Y-m-d', $return_data['transaction_date']);
			$return_data['today'] = date('Y-m-d');
						
			// This is where you query your database and get the user information for Nirvana
			$data[$track_key] = $return_data;
		}
		
		return $data;
	}
	
	/**
	 * Attempts to retrieve the information from the ldb database.
	 *
	 * @param string The track_id of the application
	 * @param string The property short
	 * @return boolean True if ldb had the info, false otherwise
	 */
	private function Get_LDB_Info($track_id, $prop_short = NULL)
	{
		$data = array();
		
		$query = "
			SELECT
				app.application_id as transaction_id,
				UNIX_TIMESTAMP(app.date_created) as transaction_date,
				app.track_id as track_key,

				promo_id as promo_id,
				promo_sub_code as promo_sub_code,
				app.ip_address as ip_address,
				LOWER(company.name_short) as company_name_short,

				app.bank_name as bank_name,
				app.bank_account as bank_account,
				app.bank_aba as bank_routing,
				app.bank_account_type as bank_account_type,

				app.name_first AS name_first,
				app.name_last AS name_last,
				app.name_middle AS name_middle,
				app.dob as dob,
				app.ssn as ssn,
				app.legal_id_number as legal_id_number,
				app.legal_id_state as legal_id_state,
				
				app.phone_home as phone_home,
				app.phone_cell as phone_cell,
				app.phone_fax as phone_fax,
				app.phone_work as phone_work,
				app.phone_work_ext as phone_work_ext,
				app.street as address_street,
				app.unit as address_unit,
				app.city as address_city,
				app.state as address_state,
				app.zip_code as address_zipcode,
				app.email AS email,
				app.call_time_pref as best_call_time,

				app.income_monthly as income_amount,
				app.income_frequency as income_frequency,
				app.income_source as income_type,
				app.income_direct_deposit as income_direct_deposit,

				app.employer_name as work_name,
				app.job_title as work_title,
				app.shift as work_shift,
				app.date_hire as work_date_of_hire,

				app.paydate_model as paydate_model_id,
				app.day_of_week as pdm_day_of_week,
				app.last_paydate as pdm_next_paydate,
				app.day_of_month_1 as pdm_day_of_month_1,
				app.day_of_month_2 as pdm_day_of_month_2,
				app.week_1 as pdm_week_1,
				app.week_2 as pdm_week_2,
				app.is_react,
				customer.login
			FROM
				application as app
				JOIN company ON company.company_id = app.company_id
				JOIN campaign_info ON campaign_info.application_id = app.application_id
				JOIN customer ON customer.customer_id = app.customer_id
			WHERE
				app.track_id = '" . mysql_escape_string($track_id) . "'";
		
		try 
		{
			$result = $this->ldb_sql->Query($query);
		
		}
		catch(Exception $e)
		{
			return false;
		}
		
		if($result->Row_Count() == 0) return false;
		
		if($data = $result->Fetch_Array_Row(MYSQLI_ASSOC))
		{
			$data['income_direct_deposit'] = ($data['income_direct_deposit'] == "yes") ? true : false;
			$data['best_call_time'] = strtoupper($data['best_call_time']);
			$data['income_frequency'] = strtoupper($data['income_frequency']);
			$data['income_type'] = strtoupper($data['income_type']);
			$data['paydate_model_id'] = strtoupper($data['paydate_model_id']);
			$data['pdm_day_of_week'] = $this->days_of_week[$data['pdm_day_of_week']];

			// Pass back Enterprise Data
			if(isset($this->companies[$data['company_name_short']]))
			{
				$this->Get_Company_Data($data);

				$comp = $this->companies[$data['company_name_short']];
				$data['ent_url']				= $this->Get_Ent_URL($data['company_name_short'], $comp['url'], $data['transaction_id']);

				$react_info = $this->Get_React_URL(
					$data['transaction_id'],
					$data['ssn'],
					$data['company_name_short'],
					$comp['ecash_version']
				);
				$data['react_url']              = $react_info->url;
				$data['react_key']				= $react_info->react_key;
				$data['originating_address']    = $comp['url'];
			}
			
			$data['bb_option_url']			= 'http://' . $comp['url'] . '/?page=bb_option_email&bb_option=' . urlencode(base64_encode($data['transaction_id']));
			//Grab the most recent returned ach for the ReturnReason token.
			$query = 'SELECT 
				acr.name
				FROM 
					ach_return_code acr
				JOIN ach ON ach.ach_return_code_id = acr.ach_return_code_id
				WHERE
					ach.application_id = '.$data['transaction_id'].'
				AND
					ach.ach_return_code_id is not null 
				order by ach.date_created DESC LIMIT 1; ';
			try
			{
				$res = $this->ldb_sql->Query($query);
				if($row = $res->Fetch_Object_Row())
				{
					$data['ReturnReason'] = $row->name;
				}
				else
				{
					$data['ReturnReason'] = NULL;
				}
			}
			catch (Exception $e)
			{
				$data['ReturnReason'] = NULL;
			}
			
			 
			//Get rid of is_react
			unset($data['is_react']);
			
			return $data;
		}
		
		return false;
	}
	
	/**
	 * Attempts to retrieve the consumer info from the OLP database.
	 *
	 * @param string The track_id for the application
	 * @param string The date of the application
	 * @return boolean True if information was retrieved, false otherwise.
	 */
	private function Get_OLP_Info($track_id)
	{
		$data = array();

		$query = "
			SELECT
				application.application_id               AS transaction_id,
				UNIX_TIMESTAMP(application.created_date) AS transaction_date,
				session_id                               AS session_id,
				track_id                                 AS track_key,
				olp_process                              AS application_process,
				application_type						 AS application_type,
				target_id								 AS target_id,
				denied_target_id						 AS denied_target_id,

				promo_id                            AS promo_id,
				promo_sub_code                      AS promo_sub_code,
				campaign_info.url                   AS originating_address,
				ip_address                          AS ip_address,

				bank_name                           AS bank_name,
				routing_number                      AS bank_routing,
				account_number                      AS bank_account,
				bank_account_type                   AS bank_account_type,

				first_name                          AS name_first,
				middle_name                         AS name_middle,
				last_name                           AS name_last,
				date_of_birth					AS dob,
				social_security_number				AS ssn,
				drivers_license_number				AS state_id_number,
				drivers_license_state				AS legal_id_state,

				home_phone                          AS phone_home,
				cell_phone                          AS phone_cell,
				work_phone                          AS phone_work,
				work_ext                            AS phone_work_ext,
				fax_phone                           AS phone_fax,
				residence.address_1				AS address_street,
				apartment				AS address_unit,
				residence.city					AS address_city,
				residence.state					AS address_state,
				residence.zip      				AS address_zipcode,
				best_call_time				AS best_call_time,
				email                               AS email,
				
				net_pay                             AS income_amount,
				pay_frequency                       AS income_frequency,
				direct_deposit                      AS income_direct_deposit,
				income_type                         AS income_type,
				employer                            AS work_name,
				title                               AS work_title,
				shift                               AS work_shift,
				date_of_hire                        AS work_date_of_hire,
				
				paydate_model_id                    AS paydate_model_id,
				day_of_week                         AS pdm_day_of_week,
				next_paydate                        AS pdm_next_paydate,
				day_of_month_1                      AS pdm_day_of_month_1,
				day_of_month_2                      AS pdm_day_of_month_2,                                          
				week_1                              AS pdm_week_1,
				week_2                              AS pdm_week_2


			FROM application
			JOIN campaign_info ON campaign_info.application_id = application.application_id
			JOIN bank_info_encrypted ON bank_info_encrypted.application_id = application.application_id
			JOIN personal_encrypted ON personal_encrypted.application_id = application.application_id
			JOIN employment ON employment.application_id = application.application_id
			JOIN income ON income.application_id = application.application_id
			JOIN paydate ON paydate.application_id = application.application_id
			JOIN residence ON residence.application_id = application.application_id
			WHERE			
				application.track_id = '" . mysql_escape_string($track_id) . "'";
		
		try
		{
			$result = $this->olp_sql->Query($this->olp_db, $query);
		}
		catch(Exception $e)
		{
			return false;
		}
		
		if($this->olp_sql->Row_Count($result) == 0) return false;
		
		if($data = $this->olp_sql->Fetch_Array_Row($result))
		{				
			$crypt_config 				= Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object				= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			$data['dob'] 				= $crypt_object->decrypt($data['dob']);
			$data['ssn'] 				= $crypt_object->decrypt($data['ssn']);
			$data['bank_routing'] 		= $crypt_object->decrypt($data['bank_routing']);
			$data['bank_account'] 		= $crypt_object->decrypt($data['bank_account']);
				
			
			//Get target or failed target
			$query = "SELECT LOWER(property_short) AS company_name_short
					  FROM target
					  WHERE target_id=";
			
			$query .= ($data['target_id'] != '') ? (int)$data['target_id'] : (int)$data['denied_target_id'];
			
			try
			{
				$result = $this->olp_sql->Query($this->olp_db, $query);
			}
			catch (Exception $e)
			{
				return FALSE;
			}
			
			if($target_data = $this->olp_sql->Fetch_Array_Row($result))
			{
				$data['company_name_short'] = strtolower(Enterprise_Data::resolveAlias($target_data['company_name_short']));
			}
			unset($data['target_id']);
			unset($data['denied_target_id']);
			
			//If application is in visitor status return false
			if($data['application_type'] == 'VISITOR')
			{
				return false;
			}
			else
			{
				unset($data['application_type']);
			}
			
			$data['income_direct_deposit'] = ($data['income_direct_deposit'] == 'TRUE') ? true : false;
			$data['best_call_time'] = strtoupper($data['best_call_time']);
			$data['bank_account_type'] = strtolower($data['bank_account_type']);
			$data['income_frequency'] = strtoupper($data['income_frequency']);
			$data['income_type'] = strtoupper($data['income_type']);
			$data['paydate_model_id'] = strtoupper($data['paydate_model_id']);
			$data['react_url'] = '';
			
			// Pass back Enterprise Data
			if(isset($this->companies[$data['company_name_short']]))
			{
				$this->Get_Company_Data($data);
				
				$comp = $this->companies[$data['company_name_short']];
			    $data['ent_url']				= $this->Get_Ent_URL($data['company_name_short'], $comp['url'], $data['transaction_id']);

			    // We need to pass back just the key as well - Mantis #10770 [BF].
			    $react_info = $this->Get_React_URL(
					$data['transaction_id'],
					$data['ssn'],
					$data['company_name_short'],
					$comp['ecash_version']
				);
				$data['react_url']              = $react_info->url;
				$data['react_key']				= $react_info->react_key;
			}
			$data['bb_option_url']			= 'http://' . $comp['url'] . '/?page=bb_option_email&bb_option=' . urlencode(base64_encode($data['transaction_id']));
			  
			//********************************************* 
			// Let's query the database for a ace store
			//********************************************* 
			if($data['company_name_short'] == 'ace')
			{
				$query = "
					select 
						zip_code store_zip,
						store_id store_id,
						address1 store_address1,
						city store_city,
						state store_state,
						phone1 store_phone,
						fax store_fax
					from ace_stores
					where zip_code = '{$data['address_zipcode']}'
				";
				try
				{
					$result = $this->olp_sql->Query($this->olp_db, $query);
				}
				catch(Exception $e)
				{
				//	return 
				}
				if($row = $this->olp_sql->Fetch_Array_Row($result))
				{
					foreach($row as $key => $value)
					{
						$data[$key] = $value;
					}
				}

				// GForge #9999 Ace Store 
				if($data['store_zip'])
				{
					$location_url = "http://maps.google.com/maps?f=q&hl=en&geocode=&q="
						. urlencode($data['store_address1']) . '+'
						. urlencode($data['store_address2']) . '+'
						. urlencode($data['store_city']) . '+'
						. urlencode($data['store_state']) . '+'
						. urlencode($data['store_zip']) . '+'
						. '&ie=UTF8&iwloc=addr';

					$data['map_link'] = $location_url;
				}
			}
			return $data;
		}
		return false;
	}
	
	/**
	 * Attempts to retrieve consumer information from the session.
	 *
	 * @param string The track_id for the application
	 * @param string The date of the application
	 */
	private function Get_Session_Info($track_id)
	{		
		$query = "SELECT application_id               AS transaction_id,
						 UNIX_TIMESTAMP(created_date) AS transaction_date,
						 session_id                   AS session_id,
						 application_type
                  FROM application
                  WHERE track_id = '" . mysql_escape_string($track_id) . "'";
		
		try
		{
			$result = $this->olp_sql->Query($this->olp_db, $query);
		}
		catch(Exception $e)
		{
			return false;
		}
		
		if($this->olp_sql->Row_Count($result) == 0) return false;
		
		$data = $this->olp_sql->Fetch_Array_Row($result);
		
		//Load Session
		$table = 'session_'.substr($data['session_id'], 0, 1);
		$session = new SessionHandler($this->olp_sql, $this->olp_db, $table, $data['session_id']);
	    
	    //Campaign Data
	    if(isset($_SESSION['config']->promo_id)) $data["promo_id"] = $_SESSION['config']->promo_id;
	    if(isset($_SESSION['config']->promo_sub_code)) $data["promo_sub_code"] = $_SESSION['config']->promo_sub_code;
	    if(isset($_SESSION['data']['client_ip_address'])) $data["ip_address"] = $_SESSION['data']['client_ip_address'];
	    if(isset($_SESSION['data']['client_url_root'])) $data["originating_address"] = $_SESSION['data']['client_url_root'];
	    
	    //Bank Data
	    if(isset($_SESSION['data']['bank_name'])) $data["bank_name"] = $_SESSION['data']['bank_name'];
	    if(isset($_SESSION['data']['bank_account'])) $data["bank_account"] = $_SESSION['data']['bank_account'];
	    if(isset($_SESSION['data']['bank_aba'])) $data["bank_routing"] = $_SESSION['data']['bank_aba'];
		if(isset($_SESSION['data']['bank_account_type'])) $data["bank_account_type"] = strtolower($_SESSION['data']['bank_account_type']);
				    
	    //Personal
	    if(isset($_SESSION['data']['name_first'])) $data["name_first"] = $_SESSION['data']['name_first'];
	    if(isset($_SESSION['data']['name_middle'])) $data["name_middle"] = $_SESSION['data']['name_middle'];
    	if(isset($_SESSION['data']['name_last'])) $data["name_last"] = $_SESSION['data']['name_last'];
    	if(isset($_SESSION['data']['date_dob_d'])) 
    	{
    		$data["dob"] = $_SESSION['data']['date_dob_y'] . "-" . $_SESSION['data']['date_dob_m'] . "-" .
    					   $_SESSION['data']['date_dob_d'];
    	}
		if(isset($_SESSION['data']['social_security_number'])) $data["ssn"] = $_SESSION['data']['social_security_number'];
		if(isset($_SESSION['data']['state_id_number'])) $data["legal_id_number"] = $_SESSION['data']['state_id_number'];

		//Contact Data
		if(isset($_SESSION['data']['phone_home'])) $data["phone_home"] = $_SESSION['data']['phone_home'];
		if(isset($_SESSION['data']['phone_cell'])) $data["phone_cell"] = $_SESSION['data']['phone_cell'];
		if(isset($_SESSION['data']['phone_fax'])) $data["phone_fax"] = $_SESSION['data']['phone_fax'];
		if(isset($_SESSION['data']['phone_work'])) $data["phone_work"] = $_SESSION['data']['phone_work'];
		if(isset($_SESSION['data']['phone_work_ext'])) $data["phone_work_ext"] = $_SESSION['data']['phone_work_ext'];
		if(isset($_SESSION['data']['home_street'])) $data["address_street"] = $_SESSION['data']['home_street'];
		if(isset($_SESSION['data']['home_unit'])) $data["address_unit"] = $_SESSION['data']['home_unit'];
		if(isset($_SESSION['data']['home_city'])) $data["address_city"] = $_SESSION['data']['home_city'];
		if(isset($_SESSION['data']['home_state'])) $data["address_state"] = $_SESSION['data']['home_state'];
		if(isset($_SESSION['data']['home_zip'])) $data["address_zipcode"] = $_SESSION['data']['home_zip'];
		if(isset($_SESSION['data']['email_primary'])) $data["email"] = $_SESSION['data']['email_primary'];
		if(isset($_SESSION['data']['best_call_time'])) $data["best_call_time"] = strtoupper($_SESSION['data']['best_call_time']);
		if(isset($_SESSION['data']['ref_01_name_full'])) $data["personal_ref_1_name"] = $_SESSION['data']['ref_01_name_full'];
		if(isset($_SESSION['data']['ref_01_phone_home'])) $data["personal_ref_1_phone"] = $_SESSION['data']['ref_01_phone_home'];
		if(isset($_SESSION['data']['ref_01_relationship'])) $data["personal_ref_2_relationship"] = $_SESSION['data']['ref_01_relationship'];
		if(isset($_SESSION['data']['ref_02_name_full'])) $data["personal_ref_2_name"] = $_SESSION['data']['ref_02_name_full'];
		if(isset($_SESSION['data']['ref_02_phone_home'])) $data["personal_ref_2_phone"] = $_SESSION['data']['ref_02_phone_home'];
		if(isset($_SESSION['data']['ref_02_relationship'])) $data["personal_ref_2_relationship"] = $_SESSION['data']['ref_02_relationship'];

		//Employment
		if(isset($_SESSION['data']['income_monthly_net'])) $data["income_amount"] = $_SESSION['data']['income_monthly_net'];
		if(isset($_SESSION['data']['income_frequency'])) $data["income_frequency"] = strtoupper($_SESSION['data']['income_frequency']);
		if(isset($_SESSION['data']['income_type'])) $data["income_type"] = strtoupper($_SESSION['data']['income_type']);
		if(isset($_SESSION['data']['income_direct_deposit']))
		{ 
			$data["income_direct_deposit"] = ($_SESSION['data']['income_direct_deposit'] == "TRUE") ? true : false;
		}
		if(isset($_SESSION['data']['employer_name'])) $data["work_name"] = $_SESSION['data']['employer_name'];
    	if(isset($_SESSION['data']['title'])) $data["work_title"] = $_SESSION['data']['title'];
    	if(isset($_SESSION['data']['shift'])) $data["work_shift"] = $_SESSION['data']['shift'];
    	
    	//Paydate
    	if(isset($_SESSION['data']['paydate']))
    	{
    		if(isset($_SESSION['data']['paydate']['weekly_day'])) $data["pdm_day_of_week"] = $_SESSION['data']['paydate']['weekly_day'];
    	}
    	
    	//React is blank
    	$data['react_url'] = "";
    	
    	if($data['application_type'] != "FAILED" && $data['application_type'] != "VISITOR")
	    {
	    	$data['company_name_short'] = strtolower(Enterprise_Data::resolveAlias($_SESSION["blackbox"]["winner"]));
	    	$this->Get_Company_Data($data);
			
	    }
			$data['bb_option_url']			= 'http://' . $data['ent_site'] . '/?page=bb_option_email&bb_option=' . urlencode(base64_encode($data['transaction_id']));
			 
        //Remove application_type
        unset($data['application_type']);
        
        //Set Company Name from URL
        if(!isset($data['company_name']))
        {
		$data['company_name'] = str_replace('.com', '', $_SESSION['config']->name_view);
		// #gforge #4915 [TP] multiloan source wants the .com
		if (strcasecmp($data['company_name'],'someloancompany') == 0)
		{
			$data['company_name'] = 'someloancompany.com';
		}
        }
        
        session_write_close();
        unset($_SESSION);
        
        return $data;
	}
	
	/**
	 * Returns the react URL.
	 *
	 * @param int $application_id
	 * @param string $ssn
	 * @param string $property_short
	 * @param string $ecash_version
	 * @return object
	 */
	private function Get_React_URL($application_id, $ssn, $property_short, $ecash_version)
	{
		$ret_val = false;
		
		$property_short = strtolower($property_short);
		if(isset($this->companies[$property_short]))
		{
			$comp = $this->companies[$property_short];
			
			//ECash 3+
			if($this->companies[$property_short] && 
			   $this->companies[$property_short]['ecash_version'] >= 3.0)
			{
				$react_key = urlencode(base64_encode($application_id));
			}
			//ECash 2.7+
			else
			{
				$react_key = null;
				$query = "SELECT 
							reckey AS react_key, 
							ssn, 
							property_short, 
							namelast AS name_last, 
							namefirst AS name_first, 
							logins 
						  FROM 
							react_verify 
						  WHERE ssn = '" . mysql_escape_string($ssn) . "'
							AND property_short = '"  . mysql_escape_string($property_short) . "'
		                  ORDER BY datesent DESC 
						  LIMIT 1";
		       
		        try
		        {      
					$result = $this->react_sql->Query($this->react_db, $query);
					
					if($this->olp_sql->Row_Count($result) == 0)
					{
						$ret_val = null;
					}
					else
					{
						$r = $this->react_sql->Fetch_Array_Row($result);
						$react_key = $r['react_key'];
					}
		        }
		        catch(Exception $e)
		        {
		        	$ret_val = false;
		        }
			}
			
			if(!empty($react_key))
			{
				$ret_val = new stdClass();
				$ret_val->url = "http://" . $comp['url'] . "?page=ent_cs_confirm_start&reckey=" . $react_key;
				$ret_val->react_key = $react_key;
			}
		}
		
		return $ret_val;
	}
	
	private function Get_Ent_URL($prop_short, $url, $application_id)
	{
		$ent_url = '';
		
		$encoded_app_id = urlencode(base64_encode($application_id));
		
                $login_hash = md5($application_id . $this->hash_key); 
                $ent_url = "http://{$url}/?application_id={$encoded_app_id}&page=ent_cs_login&login={$login_hash}&ecvt&force_new_session";
		
		return $ent_url;
	}
	
	/**
	 * Adds company specific information to the data array.
	 *
	 * @param array $data an array of data being passed back to Nirvana
	 */
	private function Get_Company_Data(&$data)
	{
		if(isset($this->companies[$data['company_name_short']]))
		{
			$comp = $this->companies[$data['company_name_short']];
			$data['company_name']			= $comp['company_name'];
			$data['company_name_short']	    = $data['company_name_short'];
			$data['company_phone']          = $comp['phone'];
			$data['company_fax']            = $comp['fax'];
			$data['company_email']          = $comp['email'];
			$data['company_street']			= $comp['street'];
			$data['company_city']			= $comp['city'];
			$data['company_state']			= $comp['state'];
			$data['company_zip']			= $comp['zip'];
			$data['ent_short_url']			= $comp['ent_short_url'];
		    $data['ent_site']               = $comp['url'];
		    $data['teleweb_phone']			= $comp['teleweb_phone'];
		}
	}
		
}

// This line is required, and should be changed to whatever the name of your particular superclass is.
$nirvana = new Nirvana_OLP(TRUE,TRUE);
?>
