<?php
	/**
		@publicsection
		@public
		@brief
			SMS OLP PRPC Server
		
		Communication layer for SMS message to integrate into OLP.

		@version 
			Check CVS for version - Ray Lopez
	*/

	// automode
	//error_reporting(E_ALL);
	
	require_once('automode.1.php');
	$auto_mode = new Auto_Mode();
	$config->mode = $auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);

	// Required files
	require_once ('prpc/server.php');
	require_once ('prpc/client.php');
	
	class SMSCom extends Prpc_Server
	{
		//const STATPRO_KEY = 'clk';
		//const STATPRO_PASS = 'dfbb7d578d6ca1c136304c845';
				
		private $mode;
		private $config;
		private $phone_number;
		private $message;
		private $message_id;
		private $prop_short;
		private $customer_data;
		private $sql;
		private $db;
		private $event;
		private $database;
		private $debug;
		private $debug_trace;
		private $statpro;
		private $message_items;
		private $apps;
		private $company;
		private $space_key;
		private $bb_partial_db;
		private $statpro_key = 'clk';
		private $statpro_pass = 'dfbb7d578d6ca1c136304c845';
		
		public function __construct()
		{

			parent:: __construct();

		}		

		public function init()
		{
			$auto_mode = new Auto_Mode();
			$this->mode = $auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);
	
			/* Defined Settings */
			require_once ('config.php');
			require_once ('config.5.php');			
			
			$this->config_obj = new Config_5($_SERVER['SERVER_NAME'], $this->mode);			
			require_once ('statpro_client.php');
			require_once (BFW_MODULE_DIR.'olp/config.php');
			require_once (BFW_MODULE_DIR.'blackbox/blackbox.php');
			require_once (BFW_CODE_DIR.'OLP_Applog_Singleton.php');
			require_once (BFW_CODE_DIR.'event_logging.php');
			include_once (BFW_CODE_DIR.'server.php');
			include_once (BFW_CODE_DIR.'setup_db.php');			
			
			require_once(BFW_CODE_DIR.'crypt_config.php');
			require_once(BFW_CODE_DIR.'crypt.singleton.class.php');
	
			
			$this->debug = FALSE;
			$this->message_items = array(	"loan" => "react",
											"loan1" => "market_olp",
											"loan2" => "market_partials",
											"optout" => "optout"
											);
			$this->ecash3		= array();

					

			// Set data
			$server = Server::Get_Server($this->mode, 'REACT');
			$this->database = $server['db'];
			$this->sql_react	= Setup_DB::Get_Instance('REACT', $this->mode);
			$this->sql_mysql	= Setup_DB::Get_Instance("mysql", $this->mode);
			$this->sql_black	= Setup_DB::Get_Instance("BLACKBOX", $this->mode);
			$this->evdb 		= Setup_DB::Get_Instance('event_log', $this->mode, null);				
			// StatPro
			$this->Get_StatPro($this->statpro_key, $this->statpro_pass, $this->mode);
			
			
			$this->company = array(	"pcl" => 	array(	"company_name" => "One Click Cash", 
														"phone"	=> "1-800-230-3266",
														"fax"	=> "1-888-553-6477",
														"teleweb_phone" => "1-800-298-7460",
														"email" => "customerservice@oneclickcash.com",
														"license" => array (
																	'LIVE' => '1f1baa5b8edac74eb4eaa329f14a03619f025e2000e0a7b26429af2395f847ce',
																	'RC' => '1f1baa5b8edac74eb4eaa329f14a03610a2177d7a01cd1a59258c95fdb31f87b',
																	'LOCAL' => '1f1baa5b8edac74eb4eaa329f14a0361604521a4b54937ed3385eb0b5e274b2a'
																	),														
														"react_site" => "oneclickcash.com",
														"promo_id" => 26182),
														
									"ucl" => 	array(	"company_name" => "United Cash Loans", 
														"phone"	=> "1-800-279-8511",
														"fax"	=> "1-800-803-8794",
														"teleweb_phone" => "1-800-303-4963",
														"email" => "customerservice@unitedcashloans.com",
														"license" => array (
																	'LIVE' => 'd386ac4380073ed7d193e350851fe34f',
																	'RC' => 'd63c6aaf39e22727c6438daf81f3a603',
																	'LOCAL' => '060431565db8215c0e44bd345a339cbe',
																	),														
														"react_site" => "unitedcashloans.com",
														"promo_id" => 26183),
														
									"ca" => 	array(	"company_name" => "AmeriLoan", 
														"phone"	=> "1-800-362-9090",
														"fax"	=> "1-800-256-9166",
														"teleweb_phone" => "1-800-303-9123",
														"email" => "customerservice@ameriloan.com",
														"license" => array (
																	'LIVE' => 'b8f225e1a2865c224d55c98cf85d399a',
																	'RC' => '2b76c04f9a36630314691f5b7d40825a',
																	'LOCAL' => 'b11647308d21180eb2e424ef6d4cae5a',
																	),														
														"react_site" => "ameriloan.com",
														"promo_id" => 26184),
														
									"d1" => 	array(	"company_name" => "500 Fast Cash", 
														"phone"	=> "1-888-919-6669",
														"fax"	=> "1-800-416-1619",
														"teleweb_phone" => "1-800-297-6309",
														"email" => "customerservice@500fastcash.com",
														"license" => array (
																		'LIVE' => '38652e89cffb810a98577dd04c8daf43',
																		'RC' => 'adfc593c968599f7f406aa84c0fa8a55',
																		'LOCAL' => 'bc599acd75dd875d5a33a597d68af14a',
																	),														
														"react_site" => "500fastcash.com",
														"promo_id" => 26181),
														
									"ufc" => 	array(	"company_name" => "US Fast Cash",
													 	"phone"	=> "1-800-640-1295",
													 	"fax"	=> "1-800-803-8796",
													 	"teleweb_phone" => "1-800-298-0487",
														"email" => "customerservice@usfastcash.com",
														"license" =>  array (
																		'LIVE' => '11041e0365baa557ec768915a501faab',
																		'RC' => 'f5b522467891c35bdf29db4365e8b253',
																		'LOCAL' => '2704c44311fc6383ed880c1c057a3bdf',
																		),														
														"react_site" => "usfastcash.com",
														"promo_id" => 26185),
									"ic" => 	array(	"company_name" => "Impact Cash",
													 	"phone"	=> "1-800-707-0102",
													 	"fax"	=> "1-888-430-5140",
													 	"teleweb_phone" => "",
														"email" => "support@impactcashusa.com",
														"license" =>  array (
																		'LIVE' => '6acd9423b6a2c32813e85d3705fd5300',
																		'RC' => '7d83d14e88f63a492e7375a6de460eb2',
																		'LOCAL' => '74cb58689fb09537cb37effafb06ba3b',
																		),														
														"react_site" => "impactcashusa.com",
														"promo_id" => 28155),
									"ifs" => 	array(	"company_name" => "Impact Solution Online, LLC",
													 	"phone"	=> "1-800-321-3886",
													 	"fax"	=> "1-800-321-3887",
													 	"teleweb_phone" => "",
														"email" => "support@impactsolutiononline.com",
														"license" =>  array (
																		'LIVE' => 'a55c2ae41cb0e9a7b5207e3c415c4d5d',
																		'RC' => 'd934c50af0f0ef2a0201557aa8aebe4f',
																		'LOCAL' => 'f096ee9c7357a23dabc9d5e312ef6b21',
																		),														
														"react_site" => "impactsolutiononline.com",
														"promo_id" => 28155),
									"icf" => 	array(	"company_name" => "Cash First LLC",
														"phone"	=> "1-800-321-8718",
														"fax"	=> "1-800-321-8719",
													 	"teleweb_phone" => "",
														"email" => "support@cashfirstonline.com",
														"license" =>  array (
																		'LIVE' => '4af52fea05cc512349c51a1ed64787da',
																		'RC' => '342437b42c0c96a724aabb19c6ed1f28',
																		'LOCAL' => '8a0ed89ba98f0f8a5f7363bad8cdf139',
																		),														
														"react_site" => "cashfirstonline.com",
														"promo_id" => 28155),
									"ipdl" => 	array(	"company_name" => "Impact Cash Capital LLC",
														"phone"	=> "1-800-321-6017",
														"fax"	=> "1-800-321-6018",
													 	"teleweb_phone" => "",
														"email" => "support@impactcashcap.com",
														"license" =>  array (
																		'LIVE' => '7ac786263857f87ce0c073956406f155',
																		'RC' => '018c82278487dcb4f7c22af62510ae0b',
																		'LOCAL' => 'b2b3dd245712c91c280e9275a59617ca',
																		),														
														"react_site" => "impactcashcap.com",
														"promo_id" => 28155),
									/** Added site enterprise company LCS for GForge #9878 [AE] **/
									"lcs" => 	array(	"company_name" => "Lending Cash Source",
														"phone"	=> "1-888-501-2698",
														"fax"	=> "1-888-501-2699",
													 	"teleweb_phone" => "",
														"email" => "customerservice@lendingcashsource.com",
														"license" =>  array (
																		'LIVE' => '181bc8e4fd42a9adcd5aec65dafb763b',
																		'RC' => 'ca4f3e4f001310994c3e80e9d4e2b147',
																		'LOCAL' => '6c25db443c90ee0eb6acaeb253eca27e',
																		),														
														"react_site" => "lendingcashsource.com",
														"promo_id" => 32402),
									);
														
			// Flip mode is the greatest
			switch ($this->mode)
			{
				CASE "LOCAL":
					$this->bb_partial_db = "olp_bb_partial";
					break;					
				CASE "RC":
					$this->bb_partial_db = "rc_olp_bb_partial";
					break;
				CASE "LIVE":
					$this->bb_partial_db = "olp_bb_partial";
					break;
			}
																		
				
		}
		
		protected function Get_StatPro($key, $password, $mode)
		{
			
			// not sure we need this, but just in case
			$mode = (strtoupper($mode) !== 'LIVE') ? $mode = 'test' : 'live';
			
			// create statpro object
			$bin = '/opt/statpro/bin/spc_'.$key.'_'.$mode;
			$this->statpro = new StatPro_Client($bin, NULL, $key, $password);
			
		}
				
		private function ValidateMessage($message)
		{
			$response = FALSE;
			/*
			if(in_array(strtolower(trim($message)),$this->message_items))
				$response = TRUE;		
			*/
			foreach($this->message_items as $key => $item)
			{
				if(stristr(trim($message),$key) && (strlen(trim($message)) == strlen($key)))
				{
					$response = $item;
					break;
				}
			}
				

			return $response;
			
		}
		
		/* Hit the Stat for SMS Marketing */
		public function SMSMarketStat($prop,$track_id,$stat)
		{
			$this->init();
			if(isset($this->company[$prop]))
			{
				$this->GetConfig($prop);
				$this->statpro->Track_Key($track_id);
				$this->statpro->Record_Event($stat);
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function SMSCronReact($track,$ps)
		{
			$this->Set_StatPro($ps);
			$this->init();
			//$app_data = $this->GetOLPApplication($track);
			$this->GetConfig(strtolower($ps));
			$this->statpro->Track_Key($track);
			$this->statpro->Record_Event('react_sms_send');
			return TRUE;
		}		

		private function GetConfig($prop,$space_key = null)
		{
			if(!isset($space_key))
			{
				$comp = $this->company[strtolower($prop)];
				$lic_key = $comp["license"][$this->mode];
				$promo_id = $comp["promo_id"];
				$this->config = $this->config_obj->Get_Site_Config($lic_key, $promo_id, null, null);
				$space_key = $this->statpro->Get_Space_Key($this->config->page_id, $promo_id, $this->config->promo_sub_code);
				$this->statpro->Space_Key($space_key);
			}
			
			$this->statpro->Space_Key($space_key);
		}
		
		/* Get Data from OLP if data is missing from OLP it will check the partials database*/
		public function GetOLPApplication($track_id)
		{
			$app = array();
			$this->init();	
			$this->query = "
				SELECT
					a.application_id,
					a.session_id,
					target.property_short AS PROPERTY_SHORT
				FROM
					application a
					LEFT JOIN target ON target.target_id = a.target_id
				WHERE
					a.track_id = '$track_id'";
			
			try {
				$result = $this->sql_black->Query($this->sql_black->db_info['db'],$this->query);
				while($rec = $this->sql_black->Fetch_Array_Row($result))
				{
					if(!isset($this->company[$rec['PROPERTY_SHORT']])) $rec['PROPERTY_SHORT'] = "ufc";					

					$app = $rec;
					$app['session_id'] = $rec['session_id'];
					$app['company_phone'] = $this->company[$rec['PROPERTY_SHORT']]['phone'];
					$app['teleweb_phone'] = $this->company[$rec['PROPERTY_SHORT']]['teleweb_phone'];
					$app['company_name'] = $this->company[$rec['PROPERTY_SHORT']]['company_name'];
					$application_id = $app['application_id'];
				}

			} catch (Exception $e) {  }
			if($application_id)
			{
				$table_array = array("personal_encrypted","residence","employment","bank_info_encrypted","income","paydate","personal_contact");
				foreach($table_array as $table_entry)
				{
					$query = "SELECT * from $table_entry where application_id = $application_id";
					try {
						$result = $this->sql_black->Query($this->sql_black->db_info['db'],$query);
						while($rec = $this->sql_black->Fetch_Array_Row($result))
						{
							if(mysql_numrows($result) > 1)
							{
								$app[$table_entry][] = $rec;
							}
							else 
							{
								$app[$table_entry] = $rec;
							}
						}
		
					} catch (Exception $e) {  }			
					if(!isset($app[$table_entry]))
					{
						try {
							$result = $this->sql_black->Query($this->bb_partial_db,$query);
							while($rec = $this->sql_black->Fetch_Array_Row($result))
							{
								if(mysql_numrows($result) > 1)
								{
									$app[$table_entry][] = $rec;
								}
								else 
								{
									$app[$table_entry] = $rec;
								}
							}
			
						} catch (Exception $e) {  }							
					}
				}
			}			
			return $app;
		}
		
				
		public function SMS_ReactURL($track)
		{
			$this->init();
		
			if(in_array($this->prop_short,$this->ecash3))
			{
				// Needs to be changed for Ecash 3
				$url =  $this->Get_React_Url($track);
			}
			else 
			{
				$url =  $this->Get_React_Url($track);				
			}
			
			return  $url;

			
		}
		
		public function SMS_Reply($phone_num, $message_id, $message, $prop_short,$track_id = null, $spack_key = null)
		{
			
			
			$this->init();
			$this->GetConfig($prop_short);
			$this->phone_number = $phone_num;
			$this->message 		= $message;
			$this->message_id 	= $message_id;
			$this->prop_short	= strtolower($prop_short);			
			$response = TRUE;
			
			try {
				$this->init();
				$track_key = $this->statpro->Create_Track();
				$this->statpro->Track_Key($track_key);
				

				$sms_act = $this->ValidateMessage($message);
				

				if(isset($phone_num) && isset($message_id) && isset($prop_short) && $sms_act)
				{
					
					switch( $sms_act )
					{
					    
						case "optout":
							// Trigger reactivation process 'optout' stat 
							$this->apps = $this->Find_By_Cell($this->phone_number,"ldb");
							if(count($this->apps))
							{
								$this->statpro->Record_Event('react_optout');
							}
							else
							{
								$this->Customer_Service();
							}
						break;
						case "react":
							// If we cant find any apps for this number we can't 
							// proceed with a react test.
							$this->apps = $this->Find_By_Cell($this->phone_number,"ldb");
							if(count($this->apps))
							{
								//stat react_sms_receieved_valid
								$this->statpro->Record_Event('react_sms_receieved_valid');
												
								$process_response = (in_array($this->prop_short,$this->ecash3))
												? $this->Process_Reply_ECash3()
												: $this->Process_Reply();
							}
							else
							{
								$this->Customer_Service();
							}
							break;
						case "market_olp":
							$this->apps = $this->Find_By_Cell($this->phone_number,"olp");
						
							if(count($this->apps))
							{							
									//stat react_sms_receieved_valid
									foreach($this->apps as $key => $data)
										$app = $data;

									if(!$_SESSION['statpro']['track_key']) $_SESSION['statpro']['track_key'] = $app["track_id"];
									
									$this->statpro->Track_Key($app["track_id"]);
									$this->statpro->Record_Event('sms_market_teleweb_loan1');

							}
							else
							{
								$this->Customer_Service();
							}
							$response = TRUE;
							break;							
						case "market_partials":							
							
							$this->apps = $this->Find_By_Cell($this->phone_number,"partials");
						
							if(count($this->apps))
							{							
									//stat react_sms_receieved_valid
									foreach($this->apps as $key => $data)
										$app = $data;

									if(!$_SESSION['statpro']['track_key']) $_SESSION['statpro']['track_key'] = $app["track_id"];
									$this->statpro->Track_Key($app["track_id"]);
									$this->statpro->Record_Event('sms_market_teleweb_loan2');

							}
							else
							{
								$this->Customer_Service();
							}
							$response = TRUE;
							break;
										
					}
				}
				else 
				{
						$this->Customer_Service();
				}
			
			} 
			catch (Exception $e)
			{
				$response = FALSE;
			}
			
			if($this->debug) 
			{	
				$response = $this;
				file_put_contents("/tmp/smscomm.log",var_export($this,true));
			}			
			return $response;
			
		}
		
		/* Send off to customer service */
		private function Customer_Service()
		{
				$apps = $this->apps;
				
				if (is_array($apps))
				{
					foreach ($apps as $id=>$info)
					{
						if(strtolower($this->prop_short) ==  strtolower($info['company']))
						{
							$link = "http://ecash.edataserver.com/?module=funding&action=show_applicant&application_id=".$id;
							$app_info .= 'Application #: <a href="'.$link.'">'.$id.' ('.$info['company'].')</a>, Status: '.$info['status'].', Name: '.$info['customer_name'].'<br>';
						}
					}
					
				}	

				if($this->mode == "LIVE")
				{
					
					$recipients = array(
						
						// customer service
						array(
							'email_primary' => $this->company[$this->prop_short]["email"],
							'email_primary_name' => $this->company[$this->prop_short]["company_name"],
						),
						
						// CC to crystal
						array(
							'email_primary' => 'crystal@fc500.com',
							'email_primary_name' => 'Crystal',
						),
						
					);
					
				} 
				else 
				{
					$recipients = array(
						
						// CC to Dev
						array(
							'email_primary' => 'raymond.lopez@sellingsource.com',
							'email_primary_name' => 'Ray',
						)				
						
					);					
				}
				
				//react_sms_receieved_invalid
				// Need to send customer serivec email
				// We need to email the response to CLK customer servic	
				// start building our mail data
				
				// rsk
				$app_info = !empty($app_info) ? $app_info : 'No applications found';
				
				$mail_data = array(
					'site_name' => 'sms.edataserver.com',
					'sender_name' => 'SMS Received <no-reply@sellingsource.com>',
					'subject' => "SMS Received from {$this->phone_number}",
					'message' => $this->message,
					'company_id' => $this->prop_short,
					'cell_phone' => $this->phone_number,
					'application_ids' => $app_info
				);
												
				require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
				$tx = new OlpTxMailClient(false);
				
				// have to start off failing
				// rsk $sent = FALSE;
				
                $mail_failed = false;
                $last_data = null;
                
				foreach ($recipients as $recipient)
				{
					
					// merge in our recipient data
					$mail_data = array_merge($mail_data, $recipient);
					
					// send the email
					try 
					{
						$tx->sendMessage('live','SMS_RECEIVED',$mail_data['email_primary'],'',$mail_data);
					}
					catch (Exception $e)
					{
						$tx_applog  = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
						$tx_applog->Write("Trendex Send Mail failed. Last message:\n".print_r($maiL_data,true)."\nCalled from ".__FILE__.":".__LINE__);
					}
				}
  

				// We sent one to customer service some there must have been something wrong.. Stat it
				$this->statpro->Record_Event('react_sms_receieved_invalid');			

		}
		
		private function Find_By_Cell($phone_number,$database)
		{
			
			$apps = FALSE;
			switch ($database)
			{
				CASE "ldb":
					if ($this->sql_react)
					{
						try
						{
							$query = "
								SELECT
									application.application_id AS id,
									company.name_short AS company,
									status.level0_name AS status,
									CONCAT(application.name_first, ' ', application.name_last) AS customer_name
								FROM
									application
									JOIN application_status_flat AS status USING (application_status_id)
									JOIN company ON company.company_id = application.company_id
								WHERE
									application.phone_cell = '{$phone_number}'
							";
							$result = $this->sql_mysql->Query($query, 'ldb');
							while($rec = $result->Fetch_Array_Row())
							{					
								$apps[$rec['id']] = $rec;
							}								
						} catch (Exception $e) {}
					}
					break;
				CASE "olp":			
			
					if ($this->sql_black)
					{
						try
						{
							$query = "
									SELECT
										a.application_id AS ID,
										target.property_short AS company,
										a.application_type as status,
										CONCAT(p.first_name, ' ', p.last_name) AS customer_name,
										a.track_id as track_id
									FROM
										application a
										JOIN personal_encrypted p
										ON 
											(a.application_id = p.application_id)
										LEFT JOIN target ON target.target_id = a.target_id
									WHERE
										p.cell_phone = '{$phone_number}' AND
									";					
							$result = $this->sql_black->Query($this->sql_black->db_info['db'],$query);
							while($rec = $this->sql_black->Fetch_Array_Row($result))
							{
								if(!isset($this->company[$rec['company']])) $rec['company'] = $this->prop_short;
								$apps[$rec['id']] = $rec;
							}
						} catch (Exception $e) {}
						
					}
					break;					
				CASE "partials":
					if ($this->sql_black)
					{
						try{
							$query = "select 
											application_id,
											first_name,
											last_name 
										FROM 
											personal_encrypted
										WHERE 
											cell_phone = '{$phone_number}'";
							
							$result = $this->sql_black->Query($this->bb_partial_db,$query);								
							while($rec = $this->sql_black->Fetch_Array_Row($result))
							{
								$app_id 		= $rec["application_id"];
								$app_first_name = $rec["first_name"];
								$app_last_name	= $rec["last_name"];
								
							}								
							if(isset($app_id))
							{
								$query = "
	
										SELECT
											a.application_id AS ID,
											target.property_short AS company,
											a.application_type as status,
											a.track_id as track_id
										FROM
											application a
											LEFT JOIN target ON target.target_id = a.target_id
										WHERE
											a.application_id = '{$app_id}'";
								$result = $this->sql_black->Query($this->sql_black->db_info['db'],$query);									
								while($rec = $this->sql_black->Fetch_Array_Row($result))
								{
									$rec["customer_name"] = $app_first_name." ".$app_last_name;
									if(!isset($this->company[$rec['company']])) $rec['company'] = $this->prop_short;
									$apps[$rec['id']] = $rec;
								}
							}
						} catch (Exception $e) {}
					}
					break;					

			}
			
			return $apps;
			
		}		
		
		private function Get_React_Url($track)
		{
			
			$apps = FALSE;
			$url = null;
			if ($this->sql_react)
			{
				
				try
				{
					
					$query = "SELECT
							application.application_id AS id,
							company.name_short AS company,
							status.level0_name AS status,
							ssn
						FROM
							application
							JOIN application_status_flat AS status USING (application_status_id)
							JOIN company ON company.company_id = application.company_id
						WHERE
							application.track_id = '{$track}'
					";
					
					$apps = array();
					
					$result = $this->sql_mysql->Query($query, 'ldb');
					while($rec = $result->Fetch_Array_Row())
					{					
						
						$ssn = $rec['ssn'];
						$this->prop_short = strtolower($rec['company']);
						$comp = $this->company[$this->prop_short];
						$query = "SELECT 
									reckey AS react_key, 
									ssn, 
									property_short, 
									namelast AS name_last, 
									namefirst AS name_first, 
									logins 
								FROM 
									react_verify 
								WHERE 
									ssn = {$ssn}
								and
									property_short = '{$this->prop_short}'
		                         order by datesent DESC LIMIT 1";
						$result_react = $this->sql_react->Query($this->database, $query);

						while($react  = $this->sql_react->Fetch_Array_Row($result_react))
						{
							$url = 	$comp["react_site"]."?promo_id=" . $comp["promo_id"] . "&promo_sub_code=LOAN&page=ent_cs_confirm_start&reckey=".$react["react_key"];
							break;
						}
					}
					
				}
				catch (Exception $e)
				{
					$query = $e;
				}
				
			}
			if($url == null) return "No react entry for this track key";
			
			return $url;
			
		}		
				
		private function Process_Reply()
		{
			$response = FALSE;
			// Check if the customer is a current react
			$query = "SELECT
							application.application_id,
							company.name_short as property_short, 
							application.name_last, 
							application.name_first,
							application.email,
							application.ssn,
							application.phone_cell,
							application.track_id
						FROM
							application
							JOIN company ON company.company_id = application.company_id
						WHERE
							application.phone_cell = '{$this->phone_number}'
						AND
							company.name_short = '{$this->prop_short}'
						order by application.application_id DESC LIMIT 1";
							
			$result = $this->sql_mysql->Query($query, 'ldb');
			
			while($record = $result->Fetch_Array_Row())
			{
				$_SESSION['statpro']['track_key'] = $record["track_id"];			
				$query = "SELECT 
							reckey AS react_key, 
							ssn, 
							property_short, 
							namelast AS name_last, 
							namefirst AS name_first, 
							logins 
						FROM 
							react_verify 
						WHERE 
							ssn = {$record["ssn"]}
						and
							property_short = '{$this->prop_short}'
						and 
                            (logins IS NULL
                                OR
                            logins between 0 and 6)
                         order by datesent DESC LIMIT 1";
				$result_react = $this->sql_react->Query($this->database, $query);

				while($react  = $this->sql_react->Fetch_Array_Row($result_react))
				{
					// We found the assoicated react
					$this->customer_data = $record;
					$_SESSION['statpro']['track_key'] = $this->customer_data["track_id"];
					break;
				}
				
				// We dont need to go through anymore applications we have found a good one
				if(isset($this->customer_data))
					break;
					
					
			}
					
			
			$response = ($this->customer_data) 
						? $this->Customer_Accept() 
						: $this->Customer_Reject();
			
			return $response;
			
		}		
		
		private function Process_Reply_ECash3()
		{
			$response = FALSE;
			// Check if the customer is a current react for ECash 3.0
			$query = "select 
					app.application_id,
					com.name_short as property_short, 
					app.name_last, 
					app.name_first,
					app.email,
					app.ssn,
					app.phone_cell,
					app.track_id
					from 
					    application as app,
					    company as com,
					    application_status as app_stat
					where  
					    app.company_id = com.company_id
					and
					    app.application_status_id = app_stat.application_status_id
					AND
					    app_stat.name_short IN ('paid','recovered')
					and 
						com.name_short = '{$this->prop_short}'
					AND
						app.phone_cell = '{$this->phone_number}'
					order by app.application_id DESC LIMIT 1";				
			
			$result = $this->sql_mysql->Query($query, 'ldb');
			while($record = $result->Fetch_Array_Row())
			{
					$app_id = $record["application_id"];
					if(!$_SESSION['statpro']['track_key']) $_SESSION['statpro']['track_key'] = $record["track_id"];
					
	   				// Count up any previous login attempts
	   				$this->event = new Event_Log($this->evdb, $this->evdb->db_info['db'], $app_id, NULL );
	   				$events = $this->event->Fetch_Events($app_id,"EVENT_REACT_LOGIN","PASS",null,"dynamic");
					if(count($events["EVENT_REACT_LOGIN"][null]))
					{
						$logins = -1;
					} else {
						$events = $this->event->Fetch_Events($app_id,"EVENT_REACT_LOGIN","START",null,"dynamic");
						$logins = count($events["EVENT_REACT_LOGIN"][null]) ? count($events["EVENT_REACT_LOGIN"][null]) : 0;
					}
					// Look like this app can be reacted
					if (($logins >= 0) && ($logins < 6))
					{
						$this->customer_data = $record;
						$_SESSION['statpro']['track_key'] = $this->customer_data["track_id"];
						break;		
					}
			}

			$response = ($this->customer_data) 
						? $this->Customer_Accept() 
						: $this->Customer_Reject();
			
			return $response;
		}		
		
		private function Customer_Accept()
		{
			//stat react_sms_eligible:
			$this->statpro->Track_Key($_SESSION['statpro']['track_key']);
			$this->statpro->Record_Event('react_sms_eligible');			
			return TRUE;
		}
		
		private function Customer_Reject()
		{
			// stat react_sms_non_eligible:
			// Notify Customer they are not eligible
			$this->statpro->Track_Key($_SESSION['statpro']['track_key']);
			$this->statpro->Record_Event('react_sms_non_eligible');			
			return FALSE;
		}
		
		private function Set_StatPro($property_short)
		{
			switch ($property_short)
			{
				case 'generic':
					$this->statpro_key = 'generic';
					$this->statpro_pass = 'password';
					break;
			}
		}
		
	}
	

		
	$sms_prpc = new SMSCom();
	$sms_prpc->_Prpc_Strict = TRUE;
	$sms_prpc->Prpc_Process();

?>
