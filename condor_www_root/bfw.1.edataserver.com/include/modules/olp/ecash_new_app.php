<?php

	require_once(BFW_CODE_DIR . 'condor_display.class.php');
	require_once('prpc/client.php');

	class eCash_New_App
	{
		protected $db;				//LDB connection
		protected $sql;				//OLP connection
		protected $database;		//OLP database name
		protected $applog;			//self-explanatory
		
		protected $application_id;
		protected $property_short;
		protected $prop_data;		//Array from ent_prop_short_list in olp.php
		
		protected $type;			//fax / email / both
		protected $errors;			//Array of failures, if they occurred
		
		public function __construct($application_id, $prop_data, &$db, &$sql, $database, &$applog)
		{
			$this->db = $db;
			$this->sql = $sql;
			$this->database = $database;
			$this->applog = $applog;

			$this->application_id = $application_id;
			$this->property_short = $prop_data['property_short'];
			$this->prop_data = $prop_data;
			
			$this->type = 'both';
			$this->errors = array();
		}
		
		/**
		 * Returns the content to display on the page to the agent,
		 * based on the success of whatever operation they were
		 * attempting to perform.
		 */
		public function Get_Content($content = '')
		{
			//If we don't have any errors, then everything's peachy
			if(empty($this->errors))
			{
				$type_words = ($this->type == 'both')
								? 'fax and email were'
								: (($this->type == 'fax') ? 'fax was' : 'email was');
	
				$content .= "<strong>The {$type_words} sent successfully.</strong><br />";
			}
			else
			{
				$content .= '<strong>Some errors occurred while trying to send the documents:</strong><br /><br />';
				
				if(isset($this->errors['EMAIL']))
				{
					$content .= 'The email failed to send.<br />';
				}
				
				if(isset($this->errors['FAX']))
				{
					$content .= 'The fax failed to send.<br />';
				}
			}
	
			$content .=<<<CONTENT
<br />
The application id for this application is <strong>{$this->application_id}</strong><br />
<a href="javascript:window.close();">Click here to close this window.</a>
CONTENT;
			
			return $content;
		}

		
		protected function Get_Holiday_Array()
		{
			$holidays = array();
			
			if(empty($_SESSION['holiday_array']))
			{
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
	
				// We grab the holiday list from the holiday table in ldb.
				$_SESSION['holiday_array'] = $app_campaign_manager->Get_Holidays($this->db);
			}
	
			// build the holiday array so that it's acceptable to both models
			if(isset($_SESSION['holiday_array']) && is_array($_SESSION['holiday_array']))
			{
				foreach($_SESSION['holiday_array'] as $holiday)
				{
					$holidays[$holiday] = $holiday;
				}
			}
			
			return $holidays;
		}
		

		/**
		 * Sends documents of the specified type
		 * 
		 * @param $type string	fax / email / both
		 */
		public function Send_Docs($type)
		{
			$this->type = $type;
			$data = FALSE;
			//This time we're trying to send a fax
			if($type == 'fax' || $type == 'both')
			{
				$data = $this->Fetch_Sink();
				$this->Send_Fax($data);
			}		
			//If we're sending an email
			if($type == 'email' || $type == 'both')
			{
				if($data == FALSE) $data = $this->Fetch_Sink();
				$this->Send_Email($data);
			}
		}
		
		/**
		 * Sends an email to the customer
		 * 
		 * @param data array	All the customer's data
		 */
		protected function Send_Email(&$data)
		{
			$site_name = $this->prop_data['site_name'];
			$link_app_id = base64_encode($this->application_id);
			$login_hash = md5($this->application_id . 'l04ns');
			$link = "http://{$site_name}/?page=ecash_sign_docs&application_id={$link_app_id}&login={$login_hash}&ecvt&force_new_session";

			//We need to get the next fund dates for the esign doc email.
			$paydate_obj = new Pay_Date_Validation($data, $this->Get_Holiday_Array());
			
			$fund_date = $data['date_fund_estimated'];
			$fund_date2= strtotime($fund_date) + (3600 * 24);
			
			while($paydate_obj->_Is_Weekend($fund_date2) || ($paydate_obj->_Is_Holiday($fund_date2)))
			{
				$fund_date2 += (3600 * 24);
			}
			
			//Now we need to grab the username and password...
			require_once(BFW_CODE_DIR . 'login_handler.php');
			$login_handler = new Login_Handler($this->db, $this->property_short, $this->database, $this->applog);
			$login = $login_handler->Login_User_App_ID($this->application_id, $this->sql);
			
			if($login !== FALSE)
			{
				$login_info = array('username' => $login, 'password' => $_SESSION['cs']['cust_password']);
			}
			
			
			$name = strtoupper($data['name_first'] . ' ' . $data['name_last']);
			
			$data = array(
				'name_view' => 'clientservices@' . $site_name,
				'site_name' => $site_name,
				'name' => $name,
				'application_id' => $this->application_id,
				'username' => $login_info['username'],
				'password' => $login_info['password'],
				'esig_link' => $link,
				'estimated_fund_date_1' => date('m/d/Y', strtotime($fund_date)),
				'estimated_fund_date_2' => date('m/d/Y', $fund_date2),
				'email_primary' => $data['email_primary'],
				'email_primary_name' => $name
			);

			$message = '';
			try
			{
				require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
				$tx = new OlpTxMailClient(false);
				$mail_id = $tx->sendMessage('live', 'ECASH_ESIG_LOAN_DOCS', $data['email_primary'], $_SESSION['statpro']['track_key'], $data);
			}
			catch(Exception $e)
			{
				$mail_id = FALSE;
				$message = $e->getMessage();
			}
	        
			if($mail_id === FALSE)
			{
				$this->applog->Write("Email send failed for new ecashapp, app_id={$this->application_id}\n{$message}");
				$this->errors['EMAIL'] = TRUE;
			}
			
			//No idea what this was here for
			/*if($this->type != 'both')
			{
				$this->Generate_Condor_Docs();
			}*/
		}
		
		
		protected function Send_Fax($data, $token_data = null)
		{
			if(empty($token_data))
			{
				//Generate the tokens
				$condor_display = new Condor_Display('preview');
				$token_data = $condor_display->Generate_Condor_Tokens();
			}

			$message = '';
			try
			{
				$prpc_server = Server::Get_Server(BFW_MODE, 'CONDOR', $this->property_short);
				$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");
				
				//Save the doc in Condor
				$condor_data = $condor_api->Create(
					'Loan Document',
					$token_data,
					true,
					$this->application_id,
					$_SESSION['statpro']['track_key'],
					$_SESSION['statpro']['space_key']
				);
				
				$archive_id = $condor_data['archive_id'];
				unset($condor_data['document']);
				$_SESSION['condor_data'] = $condor_data;
				
				//Try and fax it
				$fax_result = $condor_api->Send($archive_id, array('fax_number' => $data['phone_fax']), 'FAX');
				
				//Insert the doc into LDB
				$olp_db = OLP_LDB::Get_Object($this->property_short, $this->db);
				$olp_db->Document_Event($this->application_id, $this->property_short, 'fax');
			}
			catch(Exception $e)
			{
				$fax_result = FALSE;
				$message = $e->getMessage();
			}
			
			if($fax_result === FALSE)
			{
				$this->applog->Write("Condor failed to send fax documents for new ecashapp, app_id={$this->application_id}\n{$message}");
				$this->errors['FAX'] = TRUE;
			}
		}
		
		protected function Fetch_Sink()
		{
			include_once('ent_cs.mysqli.php');
			$db = ($_SESSION['config']->use_new_process) ? $this->sql : $this->db;
			return Ent_CS_MySQLi::Get_The_Kitchen_Sink($db, $this->database, $this->application_id);
		}
	}
	
	
	
	class Teleweb_New_App extends eCash_New_App
	{
		protected $ent_cs;
		
		public function __construct($application_id, $prop_data, &$db, &$sql, $database, &$applog, &$ent_cs)
		{
			parent::__construct($application_id, $prop_data, $db, $sql, $database, $applog);
			
			$this->ent_cs = $ent_cs;
		}
			
		protected function Send_Email(&$data)
		{
			$_SESSION['username'] = $_SESSION['data']['cust_username'];
			$_SESSION['password'] = $_SESSION['data']['cust_password'];

			//IC_CC won't work at all with the one in ent_cs, so we need to use OLP_LDB.
			if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $this->property_short))
			{
				$data = $_SESSION['data'];
				$data['username'] = $_SESSION['username'];
				$data['password'] = $_SESSION['password'];
				$data['application_id'] = $this->application_id;
				//Need to fake this so that it sends the proper email
				$data['ecashapp'] = $this->property_short;

				try
				{
					$olp_ldb = OLP_LDB::Get_Object($this->property_short, null, $data);
					$olp_ldb->Mail_Confirmation();
					$result = true;
				}
				catch(Exception $e)
				{
					$result = false;
				}
			}
			else
			{
				$result = $this->ent_cs->Mail_Confirmation($this->prop_data['site_name']);
			}
	        
			if(!$result)
			{
				$this->applog->Write("Email send failed for teleweb app, app_id={$this->application_id}");
				$this->errors['EMAIL'] = TRUE;
			}
			
			/*if($this->type != 'both')
			{
				$this->Generate_Condor_Docs();
			}*/
		}
		
		protected function Send_Fax(&$data, $token_data = null)
		{
			//Generate the tokens
			$condor_display = new Condor_Display('preview');
			$token_data = $condor_display->Generate_Condor_Tokens($this->prop_data);

			parent::Send_Fax($data, $token_data);
		}
		
		public function Get_Content($content = '')
		{
			$content = 'This application sold to ' . $this->prop_data['legal_entity'] . ' (' . $this->property_short . ')<br /><br />';
			
			return parent::Get_Content($content);
		}
	}


?>
