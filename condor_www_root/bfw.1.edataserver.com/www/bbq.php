<?php
	/**
		@publicsection
		@public
		@brief
			Blackbox Qualify
		
		Blackbox wrapper to retreive previous application data by application ID
		and run the application data through blackbox in RC mode.  This will return the series 
		of events, pass/fail, and the tier/property it was brokered to.

		@version 
			Check CVS for version - Don Adriano
	*/

	// automode
	require_once('automode.1.php');
	$auto_mode = new Auto_Mode();
	$config->mode = $auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);
	
	// Required files
	require_once ('config.php');
	require_once ('prpc/server.php');
	require_once ('prpc/client.php');
	require_once (BFW_MODULE_DIR.'olp/config.php');
	require_once (BFW_MODULE_DIR.'blackbox/blackbox.php');
	require_once (BFW_CODE_DIR.'OLP_Applog_Singleton.php');
	include_once (BFW_CODE_DIR.'server.php');
	include_once (BFW_CODE_DIR.'setup_db.php');


	class BBQ extends Prpc_Server
	{
		private $collected_data;	// Data collected by the PRPC client and sent to us
		private $license_key;		// License key
		private $site_type;			// What site type to use
		private $bfw;				// Base Frame Work object
		private $mysql_db;
		private $db2_db;
		private $debug;
		
		/**
			@publicsection
			@public
			@fn CM_IVR __construct()
			@brief
				This constructor just calls the parent class's constructor
				
			@return CM_IVR
				Returns instance of CM_IVR
			
		*/
		public function __construct()
		{
			parent:: __construct();
		}
		
		/**
			@publicsection
			@public
			@fn object Process_Data($license_key, $site_type, $session_id, $collected_data, $debug, $extra=0)
			@brief
				This is the method that the PRPC client (this being the server) will call
				
			This is a gateway method that handels pre processing and then hands off to Page_Handler and then returns what ever the Page_Handler give it
				
			@param $license_key string
				Site license key
			@param $site_type string
				What site type to use
			@param $session_id string
				Tells me what set of session data, if any to use
			@param $collected_data array
				All the data collected by the PRPC client and then sent to us
			@param $debug boolen
				Weather to show debug information or not
			@param $extra mixed
				I don't know, maybe used to call extra data
			@return object
				Returns what ever Page_Handler gives it
			
		*/
		public function Process_Data($application_id, $bbq_mode)
		{
			// Set data
			$this->sql				= Setup_DB::Get_Instance("blackbox", 'RC');
			$this->db				= Setup_DB::Get_Instance("mysql", 'LIVE');
			$this->applog			= OLP_Applog_Singleton::Get_Instance('bbq', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'bbq.edataserver.com', APPLOG_ROTATE);
			$this->event_log		= new Simple_Event_Log();
			$this->session			= new Simple_Session();
			$this->application_id 	= $application_id;
			$this->bbq_mode 		= $bbq_mode;
			
			// retreive application data
			if ( isset($this->application_id) && $this->Application_Data() )
			{
			
				// Session was commited in "Page_Handler", so don't try and use any session data from here on
				$this->Configure_Blackbox();

				// Run task handler
				return $this->Handler();
				
			}
			else 
			{
				return array(
					'error' => 'There is no application data for application_id: '.$this->application_id.'.',
				);	
			}
			
		}
	
		private function Handler()
		{			
			switch(strtolower($this->bbq_mode))
			{
				case 'report':
				
					$this->blackbox->Debug_Option(DEBUG_RUN_DATAX_IDV, FALSE);
					$this->blackbox->Debug_Option(DEBUG_RUN_DATAX_PERF, FALSE);
			
					if ($this->winner = $this->blackbox->Pick_Winner())
					{
						return array(
							'status' => TRUE,
							'data' => $this->data,
							'winner' => $this->winner,
							'snapshot' => $this->blackbox->Snapshot(),
							'event_log' => $this->event_log->Log()
						);
					}
					else 
					{
						return array(
							'status' => FALSE,
							'data' => $this->data,
							'reason' => $this->Gather_Reasons(),
							'snapshot' => $this->blackbox->Snapshot(),
							'event_log' => $this->event_log->Log()
						);
					}
					//echo '<pre>'; print_r($this->winner); print_r($this->blackbox->Snapshot()); print_r($this->event_log->Log()); exit;
				break;
				
				case 'decision':
				
					$this->blackbox->Restrict(array('1'=>TRUE));
				
					$this->blackbox->Debug_Option(DEBUG_RUN_DATAX_IDV, FALSE);
					$this->blackbox->Debug_Option(DEBUG_RUN_DATAX_PERF, FALSE);
			
					if ($this->winner = $this->blackbox->Pick_Winner())
					{
						return array(
							'status' => TRUE,
							'data' => $this->data,
							'winner' => $this->winner,
							'snapshot' => $this->blackbox->Snapshot(),
							'event_log' => $this->event_log->Log()
						);
					}
					else 
					{
						return array(
							'status' => FALSE,
							'data' => $this->data,
							'reason' => $this->Gather_Reasons(),
							'snapshot' => $this->blackbox->Snapshot(),
							'event_log' => $this->event_log->Log()
						);
					}
					echo '<pre>'; print_r($this->winner); echo '<br><br><br>'; print_r($this->blackbox->Snapshot()); print_r($this->event_log->Log()); exit;
				break;	
			}
			
		}
		
		private function Configure_Blackbox()
		{
				
			$_SESSION = array();
			$_SESSION['application_id'] = $this->application_id;
			
			$config->db = &$this->db;
			$config->sql = &$this->sql;
			$config->session = &$this->session;
			$config->log = &$this->event_log;
			$config->applog = &$this->applog;
			$config->database = 'olp';
			$config->data = $this->data;
			$config->mode = 'LIVE';
			$config->application_id = $this->application_id;
			$config->fle_dupe_id = 10;
			$config->site_name = 'bbq.edataserver.com';
			
			$this->blackbox = new BlackBox($config);
		}
		
		private function Application_Data()
		{
			try 
			{
				$query = "select * from application where application_id=".$this->application_id;
				$result = $this->db->Query($query, 'ldb');
				$record = $result->Fetch_Array_Row();
			}
			catch (MySQL_Exception $e)	
			{
				$this->applog->Write('Could not retreive application data for '.$application_id);	
			}
			
			if( $record )
			{
				
				// normalize data to strtoupper
				foreach($record as $key => $data)
				{
					$record[$key] = strtoupper($data);
				}
			
				$this->data = array(
					'name_first' => $record['name_first'],
					'name_middle' => $record['name_middle'],
					'name_last' => $record['name_last'],
					'income_frequency' => $record['income_frequency'], 
					'home_state' => $record['state'], 
					'bank_account_type' => $record['bank_account_type'],
					'state_id_number' => $record['legal_id_number'], 
					'social_security_number' => $record['ssn'], 
					'email_primary' => $record['email'],
		 			'income_monthly_net' => $record['income_monthly'], 
		 			'income_direct_deposit' => ($record['income_direct_deposit'] == 'YES') ? 'TRUE' : 'FALSE', 
		 			'paydate_model'=>array('income_frequency' => $record['income_frequency'])
				);

			}
			else 
			{
				return false;	
			}
			
			return true;
		}
		
		private function Gather_Reasons()
		{
			$reasons = "";
			
			foreach($this->event_log->Log() as $log)
			{
				foreach($log as $event => $result)
				{
					
					if (in_array($result, array('FAIL','OVERACTIVE','BAD','OVER_LIMIT')) && $comment = $this->Event_Error_Mapping($event))
					{
						if (!count($dup[$event]))
						{
							$reasons .= $comment."<br>";
							$dup[$event] = TRUE;
						}
					}
				}
			}	
			
			return $reasons;
		}
		
		private function Event_Error_Mapping($event)
		{
			switch(strtoupper($event))
			{
				case 'WEEKEND':
					$comment = 'This target does not except leads within the weekend.';
				break;
				
				case 'ACCOUNT_TYPE':
					$comment = 'This target does not accept leads with this bank account type: '.$this->data['bank_account_type'].'.';
				break;
				
				case 'DIRECT_DEPOSIT':
					$comment = 'This target does not accept leads with direct deposit set to '.$this->data['income_direct_deposit'].'.';
				break;
				
				case 'EXCL_STATES':
					$comment = 'This target does not accept leads from the state of '.$this->data['home_state'].'.';
				break;
				
				case 'INCOME_FREQ':
					$comment = 'This target does not accept leads with the income frequency of '.$this->data['income_frequency'].'.';
				break;
				
				case 'STATE_ID':
					$comment = 'This target does not accept leads without a legal ID number';
				break;
				
				case 'EMAIL_RECUR':
					$comment = 'This target did not accept this lead due to the email being used in the last thirty days';
				break;
				
				case 'SSN_RECUR':
					$comment = 'This applicant has application record in the OLP database created in the last 14 days.';
				break;
				
				case 'DAILY_LEADS':
					$comment = 'This target has surpassed the daily limit for leads.';
				break;
				
				case 'STAT_CHECK':
					$comment = 'The target has supassed the daily/hourly limit.';
				break;
				
				case 'QUALIFY':
					$comment = 'There was an error retreiving the loan amount for this target.';
				break;
				
				case 'ABA_BAD':
					$comment = 'The ABA routing number was not found in the thompson database.';
				break;
			}
			
			return $comment;
		}

	} 

	
	class Simple_Session
	{
		
		private $stats = array();
		
		public function Hit_Stat($stat_name)
		{
			
			$this->stats[] = $stat_name;
			
		}
		
		public function Stats()
		{
			
			return($this->stats);
			
		}
		
		public function Flush()
		{
			
			$this->stats = array();
			
		}
		
	}
	
	class Simple_Event_Log 
	{
		
		private $log = array();
		
		public function Log_Event($event = '', $outcome = '', $target = '')
		{
			$this->log[$target][strtoupper($event)] = $outcome;
		}
		
		public function Log()
		{
			
			return($this->log);
			
		}
		
		public function Flush()
		{
			
			$this->log = array();
			
		}
		
	}
	
	
	$cm_ivr = new BBQ();
	$cm_ivr->_Prpc_Strict = TRUE;
	$cm_ivr->Prpc_Process();
?>