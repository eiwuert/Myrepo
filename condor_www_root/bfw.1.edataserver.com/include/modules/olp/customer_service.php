<?php

	class Customer_Service
	{
		protected $config;
		protected $applog;
		protected $session;
		protected $event;
		protected $property_short;
		protected $prop_data;

		protected $data;
		protected $app_id;
		
		protected $soap_client;
		protected $site_info;
		protected $errors = array();
		protected $page = null;
		
		public function __construct(&$config, &$session, $prop_data, $data, $app_id = null)
		{
			$this->config = $config;
			$this->session = $session;
			$this->applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $this->config->site_name, APPLOG_ROTATE, APPLOG_UMASK);
			$this->property_short = $prop_data['property_short'];
			$this->prop_data = $prop_data;
			$this->data = $data;
			$this->app_id = $app_id;
			$this->Event_Log();
			
		
			switch(strtolower($this->config->mode))
			{
				default:
				case 'local':	$soap_url = 'http://cs.1.edataserver.com.ds59.tss:8080/cs.wsdl'; break;
				case 'rc':	$soap_url = 'http://rc.cs.1.edataserver.com/cs.wsdl'; break;
				case 'live':	$soap_url = 'http://cs.1.edataserver.com/cs.wsdl'; break;
			}

			$this->soap_client = new SoapClient($soap_url);
		
			$this->site_info = array(
				'LicenseKey' => $prop_data['license'][$this->config->mode],
				'PromoID' => $this->config->promo_id,
				'PromoSubCode' => $this->config->promo_sub_code,
				'ClientIPAddress' => $_SESSION['data']['client_ip_address'],
			);
		}
		
		protected function Event_Log($force = false)
		{
			if((empty($this->event) || $force) && !empty($this->app_id))
			{
				require_once(BFW_CODE_DIR . 'event_log.singleton.class.php');
	
				$this->event = Event_Log_Singleton::Get_Instance(BFW_MODE, $this->app_id);
				
				$_SESSION['event_log_table'] = $this->event->table;
			}
		}
		
		protected function Get_Track_Key()
		{
			$track_key = (!empty($_SESSION['statpro']['track_key'])) ? $_SESSION['statpro']['track_key'] : null;
			
			if(!empty($this->app_id))
			{
				$query = "SELECT track_id FROM application WHERE application_id = {$this->app_id}";

				try
				{
					$sql = Setup_DB::Get_Instance('blackbox', BFW_MODE, $this->property_short);
					$result = $sql->Query($sql->db_info['db'], $query);
					if($result && ($obj = $sql->Fetch_Object_Row($result)) !== false)
					{
						$track_key = $obj->track_id;
					}
				}
				catch(Exception $e)
				{
					$this->applog->Write('[CS] Failed to get track key for app_id: ' . $this->app_id);
				}
			}
			
			return $track_key;
		}

		public function Get_ID()
		{
			return $this->app_id;
		}
		
		public function Get_Errors()
		{
			return $this->errors;
		}

		public function Add_Errors($errors)
		{
			foreach($errors as $error)
			{
				$this->errors[] = $error->Description;
			}
		}
		
		public function Page($page = null)
		{
			if(!empty($page))
			{
				$this->page = $page;
			}
			
			return $this->page;
		}
		
		public function Mail_Password()
		{
			$request = array(
				'SiteInfo' => $this->site_info,
				'Email' => $this->data['cust_email']
			);
			
			$response = $this->soap_client->MailPassword($request);

			if($response->Result)
			{
				$_SESSION['data']['message'] = 'Your login/password has been mailed to the email address entered.';
				$this->page = 'ent_cs_login';
			}
			else
			{
				$this->Add_Errors($response->Errors);
				$this->page = 'cs_password';
			}
			
			return $response->Result;
		}
		
		public function Login()
		{
			$sqli = Setup_DB::Get_Instance('mysql', BFW_MODE . '_READONLY', $this->property_short);
			$login = new Login_Handler($sqli, $this->property_short, 'olp', $this->applog);
			$status = '';
			
			// unset users session legal agree field in case they came right from the completed app
			unset( $_SESSION['data']['legal_agree'] );
			if(!isset($_SESSION['data']['reckey'])) unset( $_SESSION['condor'] );
	
			if(isset($_SESSION['data']['reckey']) && isset($_SESSION['application_id']))
			//Reacts
			{
				$this->app_id = $_SESSION['application_id'];
				
				$_SESSION['cs']['md5_hash_match'] = 1;
					
				// Log session_id in olp.cs_session
				// User hits a cs page w/ a decoded application_id
				$this->Log_CS_Session($this->app_id);
				
				//Load User Data
				//$cs = $this->Get_User_Data($this->application_id);
				//$cs = $cs['cs'];
	
				//Update cs array
				//$_SESSION['cs'] = $cs;
				
				// Grab a new configuration pointing to the enterprise set
				//$this->Set_Enterprise_Config($cs);
				
				//Marked as log in
				$_SESSION['cs']['logged_in'] = TRUE;
				$result = true;
			}
			//  login with application id
			elseif($this->app_id)
			{
				// set md5_hash_match in session if it matches
				if($this->data['login'] == md5($this->app_id . 'l04ns'))
				{
					$_SESSION['cs']['md5_hash_match'] = 1;
				}
				// TEMP HACK FOR TELEWEB - REMOVE ME! 
				elseif(isset($this->normalized_data['promo_override']) && $this->data['page'] == 'ent_cs_login')
				{
					$_SESSION['cs']['md5_hash_match'] = 1;
				}
				else
				{
					//Try old process before erroring out
					$this->login = $login->Login_User_App_ID($this->app_id, $sqli);

					if($this->data['login'] == md5($this->login . 'l04ns'))
					{
						$_SESSION['cs']['md5_hash_match'] = 1;
					}
				}
				
				if($_SESSION['cs']['md5_hash_match'] === 1)
				{
					// Log session_id in olp.cs_session
					// User hits a cs page w/ a decoded application_id
					$this->Log_CS_Session($this->app_id);
				
					$sql = Setup_DB::Get_Instance('blackbox', BFW_MODE, $this->property_short);
					$acm = new App_Campaign_Manager($sql, $sql->db_info['db'], $this->applog);
					$status = $acm->Get_Application_Type($this->app_id);
				
					// Hit the cs_login_link stat
					//Stats::Hit_Stats('cs_login_link', $this->session, $this->event, $this->applog, $this->application_id);
					
					//Marked as log in
					$_SESSION['cs']['logged_in'] = TRUE;
					$result = true;
				}
				else
				{
					$this->errors[] =  'No login for this application ID, please log in with your username and password';
					$this->page = 'ent_cs_login';
					$result = false;
				}
			}
			else
			{
				// login returns next page and errors
				//$login = $ent_cs->Login();
				$request = array(
					'SiteInfo' => $this->site_info,
					'Username' => $this->data['cust_username'],
					'Password' => $this->data['cust_password']
				);
				
				$response = $this->soap_client->Login($request);
				$result = $response->Result;				
	
				// check for errors
				if(!empty($response->Errors))
				{
					$this->page = 'ent_cs_login';
					$this->Add_Errors($response->Errors);
				}
				else  // no errors hand off to page handler
				{
					$status = $response->ApplicationStatus;
					$this->app_id = $response->ApplicationID;
					$_SESSION['cs']['logged_in'] = true;
				}
			}

			if(!empty($status) && !empty($this->app_id))
			{
				$_SESSION['statpro']['space_key'] = null;
				$_SESSION['statpro']['track_key'] = $this->Get_Track_Key();
			}
			
			$this->Event_Log(true);
			
			switch(strtolower($status))
			{
				case 'pending':
				{
					$unique = !$this->event->Check_Event($this->app_id, 'STAT_POPCONFIRM');
				
					// hit popconfirm stat and redirect_page (if we pulled up the confirm page,
					// the redirect was successful)
					Stats::Hit_Stats('redirect', $this->session, $this->event, $this->applog, $this->app_id);
					Stats::Hit_Stats('popconfirm', $this->session, $this->event, $this->applog, $this->app_id);
					
					$request = array(
						'SiteInfo' => $this->site_info,
						'ApplicationID' => $this->app_id
					);
					
					$response = $this->soap_client->GetApplicationStatus($request);
					
					$olp_process = '';
					if($response->Result)
					{
						$olp_process = $response->ProcessType;
					}
					
					//Make sure we only update this once.
					if($unique && !preg_match('/^ecashapp/is', $olp_process))
					{
						$sql = Setup_DB::Get_Instance('blackbox', BFW_MODE, $this->property_short);
	
						//New stat limit for Overflow apps which are based on popconfirms.
						$limits = new Stat_Limits($sql, $sql->db_info['db']);
						$result = $limits->Increment('bb_' . $this->property_short . '_popconfirm', null, null, null);
		
						if($result === false)
						{
							$this->applog->Write("[CS] Failed to update popconfirm limit for {$this->property_short} on app_id {$this->app_id}");
						}
					}
					
					$this->page = 'ent_online_confirm';
					break;
				}
				
				case 'confirmed':
				{
					Stats::Hit_Stats('popagree', $this->session, $this->event, $this->applog, $this->app_id);
					
					$this->page = 'ent_online_confirm_legal';
					break;
				}
				
				case 'agreed':
				{
					$this->page = 'ent_status';
					break;
				}
				
				default:
				{
					$this->page = 'ent_cs_login';
					break;
				}
			}

			return $result;
		}
		
		
		private function Log_CS_Session($application_id)
		{
			if(strlen($application_id) && strlen(session_id()) && !isset($_SESSION['cs_session_id']))
			{
				$sql = Setup_DB::Get_Instance('blackbox', BFW_MODE, $this->property_short);
				
				$query = "REPLACE INTO cs_session SET
						application_id={$application_id},
						session_id='".session_id()."',
						date_created=NOW()";
				try
				{
	            	$sql_result = $sql->Query($sql->db_info['db'], $query);
					$_SESSION['cs_session_id'] = session_id();
				}
				catch(MySQL_Exception $e)
				{
					// Could not insert into cs_session table
					$this->applog->Write("Replace into cs_session failed.  application_id='{$application_id}', unique_id='". session_id(). "'");
					//throw $e;
				}
			}
		}

		public function GetFundAmount()
		{
                        $request = array(
                                'SiteInfo' => $this->site_info,
			        'ApplicationID' => $this->app_id
			);

			$response = $this->soap_client->GetApplicationStatus($request);

			if($response->Result)
			{
				return array_pop($response->FundAmounts);
			}

			return 0;
		}

		public function Confirm()
		{
			$request = array(
				'SiteInfo' => $this->site_info,
				'Action' => 'confirm',
				'ApplicationID' => $this->app_id,
				'ConfirmRequest' => array('FundAmount' => $_SESSION['data']['fund_amount'])
			);

			$response = $this->soap_client->CustomerService($request);

			if($response->Result) $this->Page('ent_online_confirm_legal');

			return $response->Result;
		}

		public function Agree()
		{
			$request = array(
				'SiteInfo' => $this->site_info,
				'Action' => 'agree',
				'ApplicationID' => $this->app_id
			);

			$response = $this->soap_client->CustomerService($request);

			if($response->Result) $this->Page('ent_thankyou');

			return $response->Result;
		}

	}

?>
