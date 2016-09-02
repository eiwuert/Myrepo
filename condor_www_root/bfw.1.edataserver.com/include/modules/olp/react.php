<?php
	
	define('REACT_DB', 'react_db');
	
	class React
	{
		
		private $db;
		private $sql;
		private $mode;
		private $database;
		private $applog;
		private $event;
		

		public function React(&$db, &$sql, $mode, $database, &$applog, &$event)
		{
			
			$this->db = isset($db) ? $db : Setup_DB::Get_Instance("mysql", $mode, "ufc");
			$this->sql = &$sql;
			$this->database = $database;
			$this->applog = &$applog;
			$this->event = &$event;			
			
			
		}
		
		public function Get_React_Loan_App_ID ($app_id)
		{
			$app_id = preg_replace("/[^0-9a-zA-Z=]/","",urldecode($app_id));
	
			// base64 decode application_id if it's not all digits
			if ( !is_numeric($app_id) )
			{
				$app_id = base64_decode($app_id);
				if ( !is_numeric($app_id) )
				{
					// This is a valid encoded number
					return false;
				}

			}
	
			// back to a number so strip any non numeric at this point
			$app_id = preg_replace("/[^0-9]/","",$app_id);
			
			return $app_id;
		}		
		
		/**
		 * Existing React
		 * 
		 * Does person already have a loan pending that they haven't paid
		 * @param Object mysqli object
		 * @param String SSN
		 * @param String property
		 * @return boolean True if app pending
		 */
		public function Existing_React($sqli, $ssn, $property_short)
		{
			$ecash3 = array_map('strtolower',$_SESSION['config']->ecash3_prop_list);
			if(in_array(strtolower($property_short), $ecash3))
			{
				$query="SELECT count(*) as app 
					FROM application as a
					JOIN 
						company as c
					ON (c.company_id = a.company_id)
					JOIN 
						application_status as app_stat
					ON (a.application_status_id = app_stat.application_status_id)
						 
					WHERE a.ssn = '" . mysql_escape_string($ssn) . "'
					  AND c.name_short = '" . mysql_escape_string(strtolower($property_short)) . "'
					  AND a.olp_process IN ('cs_react','email_react','ecashapp_react', 'ecashapp_preact')
					  AND app_stat.name_short NOT IN ('paid','recovered')";

				$result = $sqli->Query( $query );
				
				if($row = $result->Fetch_Object_Row())
				{
					$exisitng_reacts = ($row->app > 0) ? TRUE : FALSE;
				}
				else
				{
					$existing_reacts = FALSE;
				}
			
			}
			else
			{
			
			// Check the status in the sync_cashline table for this property based on ssn
				$query = "SELECT 
							status
						FROM
							cashline_customer_list
						WHERE
							social_security_number = '{$ssn}'";
				
				$result = $this->sql->Query('sync_cashline_' . strtolower($property_short), $query);
					
				if($row = $this->sql->Fetch_Array_Row($result))
				{
					// If the status is INACTIVE, pass the react_info along, else reset it to NULL
					$existing_reacts = (strtolower($row['status']) != 'inactive') ? TRUE : FALSE;
				}
				else
				{
					$existing_reacts = FALSE;
				}
			}

			return $existing_reacts;
		}
		
		public function Get_React_Info($key)
		{
			
			// Check to see if we can decode the Reckey if we can
			// then we are using a ECashe 3.0 compatible React
			// else, it must've been a react email
			$app_id = $this->Get_React_Loan_App_ID($key);

			if(is_numeric($app_id))
			{

   				// Count up any previous login attempts
   				$events = $this->event->Fetch_Events($app_id,"EVENT_REACT_LOGIN","PASS",null,"dynamic");
				if(count($events["EVENT_REACT_LOGIN"][null]))
				{
					$logins = -1;
				} else {
					$events = $this->event->Fetch_Events($app_id,"EVENT_REACT_LOGIN","START",null,"dynamic");
					$logins = count($events["EVENT_REACT_LOGIN"][null]) ? count($events["EVENT_REACT_LOGIN"][null]) : 0;
				}
				
				
				// Determine if it's an ecash 3.0 or 2.7 react attempt and use proper queries.
				$ecash3 = array_map('strtolower', $_SESSION['config']->ecash3_prop_list);
				if(in_array(strtolower($_SESSION['config']->property_short), $ecash3))
				{
				// New 3.0 React Query
				$query = "SELECT
							'$key' as react_key,
							ssn,
							com.name_short as property_short, 
							name_last, 
							name_first,
							$logins as logins,
							3 as version,
							application_id as app_id
							from 
							    application as app
							JOIN company as com
							ON 
								(app.company_id = com.company_id)
							JOIN application_status as app_stat
							ON 
								(app.application_status_id = app_stat.application_status_id)
							WHERE  
							    app.company_id = com.company_id
							and
							    app.application_status_id = app_stat.application_status_id
							AND
							    app_stat.name_short IN ('paid','recovered')
							AND
							    application_id = $app_id";
				

				$result = $this->db->Query($query);

				if($row = $result->Fetch_Array_Row())
				{
					// because I'm too lazy too
					$react_info = $row;
				}
				
				}
				else
				{
					//Gather the react_info data from the application table
					
					$query = "SELECT
								ssn,
								name_first,
								name_last,
								com.name_short as property_short,
								'$key' as react_key,
								$logins as logins,
								2 as version,
								application_id as app_id
							FROM
								application as app
							JOIN
								company as com
							ON
								app.company_id = com.company_id
							WHERE
								app.application_id = '{$app_id}'
							";
					$result = $this->db->Query($query);

					if($row = $result->Fetch_Array_Row())
					{
						$react_info = $row;
					}
					
					
					// Check the status in the sync_cashline table for this property based on ssn
					$query = "SELECT
								status
							FROM
								cashline_customer_list
							WHERE
								social_security_number = '{$react_info['ssn']}'";
					
					$result = $this->sql->Query('sync_cashline_' . $react_info['property_short'], $query);
					
					if($row = $this->sql->Fetch_Array_Row($result))
					{
						// If the status is INACTIVE, pass the react_info along, else reset it to NULL
						$react_info = (strtolower($row['status']) == 'inactive') ? $react_info: NULL;
					}
					else
					{
						$react_info = NULL;
					}				
				}
				
			}
			else 
			{

				$react_info = NULL;
				
				// GET STUFF FROM MYSQL
				
				// get info from the database
				$query = "SELECT 
							reckey AS react_key, 
							ssn, 
							property_short, 
							namelast AS name_last, 
							namefirst AS name_first, 
							logins, 
							2 as version
						FROM 
							react_verify 
						WHERE 
							reckey = '{$key}'";
				
				$result = $this->sql->Query($this->database, $query);
				if ($row = $this->sql->Fetch_Array_Row($result))
				{
					// because I'm too lazy
					$react_info = $row;
	
				}				
			}

				
			if (!$react_info) $react_info = FALSE;
			return($react_info);
			
			
		}
		
		public function Get_Cust_Info(&$db, $ssn, $appid = null)
		{
			
			$cust_info = NULL;
			$this->db = &$db;
			
			if ($this->db->db_type == 'mysql')
			{
				if(is_null($appid))
				{
					$query = "
					SELECT 
						a.application_id,
						a.dob
					FROM 
						application a 
					WHERE 
						a.ssn='".$ssn."' 
					ORDER BY a.date_created desc
					LIMIT 1
					";	
				} else {
					$query = "
					SELECT 
						a.application_id,
						a.dob
					FROM 
						application a 
					WHERE 
						a.application_id = $appid";						
				}
				
				$result = $this->db->Query($query);
				
				$row = $result->Fetch_Array_Row();
				
				$cust_info['transaction_id'] = $row['application_id'];
				$cust_info['date_birth'] = $row['dob'];
				
			}
			else 
			{
				$query = "SELECT transaction.transaction_id, customer.date_birth FROM transaction JOIN customer ON
					transaction.customer_id=customer.customer_id WHERE customer.social_security_number='{$ssn}'
					ORDER BY customer.date_modified desc FETCH FIRST 1 ROWS ONLY";
				
				$result = $db->Execute($query);
				
				if ($row = $result->Fetch_Array())
				{
					
					// import the new stuff
					$row = array_change_key_case($row);
					$cust_info['transaction_id'] = $row['transaction_id'];
					$cust_info['date_birth'] = $row['date_birth'];
					
				}
			
			}
			
			
			if (!$cust_info) $cust_info = FALSE;
			return($cust_info);
			
		}
		
		private function Increment_Logins($key)
		{
			$app_id = $this->Get_React_Loan_App_ID($key);
			if(is_numeric($app_id))
			{			
				$this->event->Log_Event("EVENT_REACT_LOGIN","START",null,$app_id);
				$result = TRUE;
			}
			else 
			{
				try
				{
					
					$query = "UPDATE react_verify SET login=(login + 1) WHERE reckey='{$key}' AND (login >= 0)";
					$this->sql->Query($this->database, $query);
					
					$result = TRUE;
					
				}
				catch (MySQL_Exception $e)
				{
					$result = FALSE;
				}
			}
			
			return($result);
			
		}
		
		public function Key_Used($key)
		{
			
			$app_id = $this->Get_React_Loan_App_ID($key);
			if(is_numeric($app_id))
			{			
				$this->event->Log_Event("EVENT_REACT_LOGIN","PASS",null,$app_id);
				$result = TRUE;
			}
			else 
			{
						
				try
				{
					
					$query = "UPDATE react_verify SET logins='-1' WHERE reckey='{$key}'";
					$this->sql->Query($this->database, $query);
					
					$result = TRUE;
					
				}
				catch (MySQL_Exception $e)
				{
					$result = FALSE;
				}
			}
			return($result);
			
		}
		
		public function Verify_Info($react_info, $ssn, $dob, $increment_logins = TRUE)
		{
			

			$result = FALSE;
			
			// get our information
			//$react_info = $this->Get_React_Info($react_key);
			$react_key = $react_info['react_key'];
			
			if ($react_key)
			{
				
				$dob = date('Y-m-d', strtotime($dob));
				$react_dob = date('Y-m-d', strtotime($react_info['date_birth']));
				
				$valid = ($ssn == $react_info['ssn']);
				$valid = ($dob == $react_dob);
				
				if ($valid)
				{
					$valid = $react_info['app_id'];
				}
				
				// record this attempt
				if ($increment_logins)
				{

					$this->Increment_Logins($react_key);
				}
				
			}

			if (!$valid) $valid = FALSE;
			return($valid);
			
		}
		
		public function Get_The_Kitchen_Sink($app_id)
		{
			
			// GET A BUNCH'O'STUFF
			
			if ($this->db->db_type =='mysql')
			{
				$query = "
				SELECT
					a.name_first,
					a.name_middle,
					a.name_last,
					a.dob as date_of_birth,
					a.ssn as social_security_number,
					a.street as home_street,
					a.unit as home_unit,
					a.zip_code as home_zip,
					a.city as home_city,
					a.state as home_state,
					a.call_time_pref as best_call,
					a.email as email_primary,
					a.phone_work,
					a.phone_home,
					a.phone_cell,
					a.phone_fax,
					a.bank_name,
					a.bank_aba,
					a.bank_account,
					a.bank_account_type,
					a.date_hire as employer_length,
					a.legal_id_number as state_id_number,
					a.legal_id_state as legal_state,
					a.paydate_model,
					a.income_monthly as income_monthly_net,
					a.income_date_soap_1 as pay_date1,
					a.income_date_soap_2 as pay_date2,
					a.income_direct_deposit,
					a.income_source as income_type,
					a.income_frequency,
				FROM
					application a 
				WHERE 
					a.application_id=".$app_id." 
				LIMIT 1
				";
				$result = $this->db->Query($query);
				$application_row = $result->Fetch_Array_Row();
				
				$query = "
				SELECT 
					* 
				FROM 
					campaign_info 
				WHERE 
					application_id = ".$app_id." 
				ORDER BY date_created DESC 
				LIMIT 1
				";
				$result = $this->db->Query($query);
				$campaign_row = $resull->Fetch_Array_Row();
			
				
				// combine application and campaign info if campaign info has a result
				$data = ($campaign_row) ? array_merge($application_row, $campaign_row) : $application_row;
				
					
				// change a few fields into the required format
				$data['dob'] = $data['date_of_birth'];
				$data['esignature'] = $data['name_first']. ' ' .$data['name_last'];
				
				$dob = strtotime($data['date_of_birth']);
				$data['date_dob_y'] = date('Y', $dob);
				$data['date_dob_m'] = date('m', $dob);
				$data['date_dob_d'] = date('d', $dob);
				
				$ssn = $data['social_security_number'];
				$data['ssn_part_1'] = substr($ssn, 0, 3);
				$data['ssn_part_2'] = substr($ssn, 3, 2);
				$data['ssn_part_3'] = substr($ssn, 5, 4);
				
				$paydate = strtotime($data['pay_date1']);
				$data['income_date1_y'] = date('Y', $paydate);
				$data['income_date1_m'] = date('m', $paydate);
				$data['income_date1_d'] = date('d', $paydate);
				
				$paydate = strtotime($data['pay_date2']);
				$data['income_date2_y'] = date('Y', $paydate);
				$data['income_date2_m'] = date('m', $paydate);
				$data['income_date2_d'] = date('d', $paydate);
				
				// format employer_length
				$data['employer_length'] = date('Y-m-d', strtotime($data['employer_length']));
				
				// GET REFERENCES
				
				$query = "
				SELECT 
					name_full, 
					phone_home, 
					relationship 
				FROM 
					personal_reference 
				WHERE 
					application_id={$data['application_id']}";
				$result = $this->db->Query($query);
				
				$x = 1;
				
				while($ref = $result->Fetch_Array())
				{
					
					$ref = array_change_key_case($ref);
					
					$data["ref_0{$x}_name_full"] = trim($ref['name_full']);
					$data["ref_0{$x}_phone_home"] = trim($ref['phone_home']);
					$data["ref_0{$x}_relationship"] = trim($ref['relationship']);
					$x++;
					
				}
				
			}
			else 
			{
				$query = "
					SELECT
						
						name_first, name_middle, name_last, date_birth AS date_of_birth, social_security_number,
						address.street AS home_street, address.unit AS home_unit, address.city AS home_city,
						address_state.name AS home_state, address.zip AS home_zip,	best_call_period.name AS best_call,
						email_address AS email_primary, employment.active_phone_id AS no_work_phone_id,
						
						campaign_info.promo_id, campaign_info.promo_sub_code,
						
						bank_name, bank_aba, bank_account, bank_account_type.name AS bank_account_type,
						employment.name AS employer_name, employment.date_hire AS employer_length,
						legal_id.legal_id_number AS state_id_number, legal_state.name AS legal_state,
						
						paydate.day_of_week AS no_day_of_week, paydate.next_paydate AS no_next_paydate,
						paydate.day_of_month_1 AS no_day_of_month_1, paydate.day_of_month_2 AS no_day_of_month_2,
						paydate.week_1 AS no_week_1, paydate.week_2 AS no_week_2, paydate_model.name AS paydate_model,
						decimal (transaction.income_monthly, 8, 2) as income_monthly_net, income_date_one AS pay_date1,
						income_date_two AS pay_date2,	income_direct_deposit, income_source.name AS income_type,
						income_frequency.name AS income_frequency
						
					FROM transaction
						
						INNER JOIN customer ON customer.customer_id=transaction.customer_id
						INNER JOIN email ON email.email_id=transaction.active_email_id
						INNER JOIN income_source ON income_source.income_source_id=transaction.income_source_id
						INNER JOIN bank_account_type ON bank_account_type.bank_account_type_id=transaction.bank_account_type_id
						INNER JOIN legal_id ON legal_id.legal_id_id=transaction.legal_id_id
						INNER JOIN state AS legal_state ON legal_state.state_id=legal_id.state_id
						INNER JOIN address ON address.address_id=transaction.active_address_id
						INNER JOIN state AS address_state ON address_state.state_id=address.state_id
						INNER JOIN best_call_period ON best_call_period.best_call_period_id=transaction.best_call_period_id
						INNER JOIN employment ON employment.employment_id=transaction.active_employment_id
						LEFT JOIN paydate ON paydate.transaction_id=transaction.transaction_id
						LEFT JOIN paydate_model ON paydate_model.paydate_model_id = paydate.paydate_model_id
						INNER JOIN income_frequency ON income_frequency.income_frequency_id=transaction.income_frequency_id
						INNER JOIN campaign_info ON campaign_info.transaction_id=transaction.transaction_id
						
					WHERE transaction.transaction_id={$app_id}
					
					FETCH FIRST 1 ROWS ONLY";

				$result = $this->db->Execute($query);
	
				
				if ($row = $result->Fetch_Array())
				{
					
					$row = array_change_key_case($row);
					
					// import fields without a no_:
					// these do not require special manipulation
					foreach ($row as $field=>$value)
					{
						
						if (substr($field, 0, 3)!='no_')
						{
							$data[$field] = trim($value);
						}
						
					}
					
					// change a few fields into the required format
					$data['dob'] = $data['date_of_birth'];
					$data['esignature'] = $data['name_first']. ' ' .$data['name_last'];
					
					$dob = strtotime($data['date_of_birth']);
					$data['date_dob_y'] = date('Y', $dob);
					$data['date_dob_m'] = date('m', $dob);
					$data['date_dob_d'] = date('d', $dob);
					
					$ssn = $data['social_security_number'];
					$data['ssn_part_1'] = substr($ssn, 0, 3);
					$data['ssn_part_2'] = substr($ssn, 3, 2);
					$data['ssn_part_3'] = substr($ssn, 5, 4);
					
					$paydate = strtotime($data['pay_date1']);
					$data['income_date1_y'] = date('Y', $paydate);
					$data['income_date1_m'] = date('m', $paydate);
					$data['income_date1_d'] = date('d', $paydate);
					
					$paydate = strtotime($data['pay_date2']);
					$data['income_date2_y'] = date('Y', $paydate);
					$data['income_date2_m'] = date('m', $paydate);
					$data['income_date2_d'] = date('d', $paydate);
					
					// GET REFERENCES
					
					$query = "SELECT name_full, phone_home, relationship FROM reference
						WHERE transaction_id={$app_id}";
					$result = $this->db->Execute($query);
					
					$x = 1;
					
					while($ref = $result->Fetch_Array())
					{
						
						$ref = array_change_key_case($ref);
						
						$data["ref_0{$x}_name_full"] = trim($ref['name_full']);
						$data["ref_0{$x}_phone_home"] = trim($ref['phone_home']);
						$data["ref_0{$x}_relationship"] = trim($ref['relationship']);
						$x++;
						
					}
					
					// GET PHONE NUMBERS
					
					if ($row['no_work_phone_id'])
					{
						$work_phone = "OR phone.phone_id={$row['no_work_phone_id']}";
					}
					else
					{
						$work_phone = '';
					}
					
					$query = "
						SELECT
							phone_number, phone_extension, phone_type.name AS phone_type
							
						FROM transaction
						
						INNER JOIN phone ON
							(phone.phone_id=transaction.active_home_phone_id
							OR phone.phone_id=transaction.active_fax_phone_id
							OR phone.phone_id=transaction.active_cell_phone_id {$work_phone})
						INNER JOIN phone_type ON phone_type.phone_type_id=phone.phone_type_id
						
						WHERE transaction.transaction_id={$app_id}";
					
					$result = $this->db->Execute($query);
					
					while ($phone = $result->Fetch_Array())
					{
						
						$phone = array_change_key_case($phone);
						
						$phone_type = strtolower($phone['phone_type']);
						$data['phone_'.$phone_type] = trim(str_replace('-', '', $phone['phone_number']));
						
					}
				}	
			}
			
			return($data);
			
		}

		public function getEventLog()
		{
			return $this->event;
		}

		public function setEventLog($event)
		{
			$this->event = $event;
		}
		
	}
	
?>
