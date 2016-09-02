<?php
/**
 * OLPBlackbox_Rule_DataX class.
 * 
 * @package OLPBlackbox
 * @author  Rob Voss <rob.voss@sellingsource.com>
 * 
 * @desc Interface to DataX for BlackBox that does some of the more heavy lifting. 
 * It also handles persistance of the trackhash throughout the entire application process: 
 * i.e., carrying the same hash from the prequal call to the actual IDV call.
 * 
 */
abstract class OLPBlackbox_Rule_DataX extends OLPBlackbox_Rule
{
	// Debugging Options
	const DEBUG_RUN_DATAX_IDV 		= 'RUN_DATAX_IDV';
	const DEBUG_RUN_DATAX_PERF 		= 'RUN_DATAX_PERF';
	
	const LOAN_AMOUNT_INCREASE = 'INCREASE';
	
	/**
	 * Array of call_types
	 * 
	 * @var Array $call_type_list
	 */
	protected $call_type_list = array();
	
	/**
	 * @var string $datax_dupe_filter_days a flag to incur a check of
	 * duplicate dataX fails prior to running DataX.  If set to non-0 value,
	 * indicates the number of days to include in the check and sets the
	 * check to run.
	 * @see GForge [#24966]
	 */
	protected $datax_dupe_filter_days = '30';

	/**
	 * Various flags for this instance of the object
	 *
	 * @var Array
	 */
	protected $data_values = array(
		'account'			=> '',
		'datax_event_type'	=> '',
		'decision'			=> '',
		'elapsed'			=> '',
		'fail_type'			=> '',
		'last_call'			=> '',
		'last_response'		=> '',
		'lead_cost'			=> '',
		'loan_amount_decision' => '',
		'reason'			=> '',
		'response'			=> '',
		'result'			=> '',
		'score'				=> '',
		'source'			=> '',
		'track_hash'		=> '',
		'start_time'		=> '',
		'stop_time'			=> '',
	);
	
	/**
	 * History of decisions from datax
	 *
	 * @var array
	 */
	protected static $decisions = array();
	
	/**
	 * History of results based on call_type
	 *
	 * @var array
	 */
	protected static $history = array();
	
	/**
	 * store adverse actions until blackbox is finished running
	 *
	 * @var array
	 */
	protected static $aa_queue = array();
	
	/**
	 * Flag for when we've hit an adverse action
	 *
	 * @var bool
	 */
	protected $aa_hit = FALSE;
	
	/**
	 * call_type
	 * 
	 * @var call_type
	 */
	protected $call_type;
	
	/**
	 * Blackbox Config data.
	 * 
	 * @var OLPBlackbox_Config
	 */
	protected $config_data;
	
	/**
	 * Blackbox data.
	 * 
	 * @var Blackbox_Data
	 */
	protected $blackbox_data;
	
	/**
	 * State data for this campaign.
	 * 
	 * @var Blackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * DataX Object
	 * 
	 * @var DataX
	 */
	protected $datax;
	
	/**
	 * Authentication Class.
	 * 
	 * @var object
	 */
	protected $authentication;
	
	/**
	 * @var DataX_Parser
	 */
	protected $xml_sent;
	
	/**
	 * @var DataX_Parser
	 */
	protected $xml_received;
	
	/**
	 * Fail the rule on error
	 * TRUE will fail/FALSE will verify
	 *
	 * @var bool
	 */
	protected $fail_on_error;
	
	/**
	 * OLP database connection object.
	 *
	 * @var MySQL_4
	 */
	protected $olp_db;

	/**
	 * The name of the OLP database.
	 *
	 * @var string
	 */
	protected $olp_db_name;

	/**
	 * Can we build it?.... Yes we can!
	 *
	 * @param string $call_type 		The call type we are making
	 * @param string $account 			The account we are using
	 * @param string $fail_on_error		Fail on error
	 */
	public function __construct($call_type, $account, $fail_on_error)
	{
		parent::__construct();
		
		// Get our config object
		$this->initConfigData();
		
		// Set up the account
		$this->account = $account;
		
		// Set up the call type
		$this->setCallType($call_type);
		
		$this->fail_on_error = $fail_on_error;
	}

	/**
	 * This validates that the rule can be run, based on the passed-in data.
	 * Then it actually runs the rule and responds with the results.
	 *
	 * @param Blackbox_Data $data The data used for the DataX Validation.
	 * @param Blackbox_State_Data $state_data The state data used to keep state in BB.
	 * 
	 * @return bool TRUE If the rule passes validation.  
	 * 				NULL If the rule is skipped.
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// [#24966] Create rule for a previous customer check on a DataX Fail
		if (!empty($this->datax_dupe_filter_days)
		&& is_numeric($this->datax_dupe_filter_days)
		&& $this->datax_dupe_filter_days != 0
		&& $this->isDataXDupe($data, $state_data))
		{
			return FALSE;
		}

		$this->datax = $this->initDataX();
		$this->authentication = $this->initAuthentication();
		$this->state_data = $state_data;
		
		return parent::isValid($data, $state_data);
	}

	/**
	 * Start the timer.
	 *
	 * @return void
	 */
	protected function startTimer()
	{
		$this->start_time = NULL;
		
		$mtime = microtime();
		$mtime = explode(' ', $mtime); 
		$mtime = $mtime[1] + $mtime[0];
		
		$this->start_time = $mtime; 
	}
	
	/**
	 * Stop the timer.
	 *
	 * @return void
	 */
	protected function stopTimer()
	{
		unset($this->stop_time);
		
		$mtime = microtime(); 
		$mtime = explode(" ", $mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		
		$this->stop_time = $mtime; 
		
		$this->elapsed = ($this->stop_time - $this->start_time); 
	}
	
	/**
	 * Overloaded __get method to get class variables.
	 *
	 * @param string $name The name of the class variable to get
	 * 
	 * @return mixed
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->data_values))
		{
			return $this->data_values[$name];
		}
		else
		{
			throw new InvalidArgumentException("Couldn't get $name, value doesn't exist");
		}
	}
		
	/**
	 * Overloaded __set method to set class variables.
	 *
	 * @param string $name  The name of the class variable to set
	 * @param mixed  $value The value to set
	 * 
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (array_key_exists($name, $this->data_values))
		{
			$this->data_values[$name] = $value;
		}
		else
		{
			throw new InvalidArgumentException("Couldn't set $name, value doesn't exist");
		}
	}
	
	/**
	 * Get the Received packet from DataX and return it
	 *
	 * @return array
	 */
	public function getReceived()
	{
		return $this->datax->Get_Received_Packet();
	}
	
	/**
	 * Get the Sent packet from DataX and return it
	 *
	 * @return array
	 */
	public function getSent()
	{
		return $this->datax->Get_Sent_Packet();
	}
	
	/**
	 * This function will hit the AA stat that is passed in
	 *
	 * @param string $denial_reason 			The adverse action stat we want to hit
	 * @param Blackbox_Data $blackbox_data 		The data passed to the rule
	 * @param Blackbox_IStateData $state_data 	The state data
	 * 
	 * @return void
	 */
	protected function adverseAction($denial_reason, Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		//Find the property short
		$name = EnterpriseData::resolveAlias($state_data->campaign_name);
		
		switch ($denial_reason)
		{
			case 'aa_denial_impact':
			case 'aa_denial_datax_impact':
			case 'aa_denial_clk':
			/* Added new LCS Ent Client for GF#9883 */
			case 'aa_denial_lcs':
			case 'aa_denial_qeasy':
			case 'aa_denial_datax':
			case 'aa_denial_hms':
				$stat = 'aa_mail_generic_'; 
				break;
			case 'aa_denial_teletrack':	
				$stat = 'aa_mail_teletrack_'; 
				break;
		}
		
		if (!empty($name) && !empty($stat))
		{
			//Let's hit it
			$this->hitTargetStat($stat.$name, $blackbox_data, $state_data);
		}
		
		if (!$this->isAdverseAction())
		{
			$this->hitTargetStat($denial_reason, $blackbox_data, $state_data);
			
			$this->aa_hit = TRUE;
		}
	}
	
	/**
	 * Retrieves a date for a number of days prior adjusting by ->datax_dupe_filter_days
	 * @return string date as Y-m-d
	 */
	protected function getDupeDate()
	{
		$days = $this->datax_dupe_filter_days;
		$date = date_create("-$days days");
		$query_date = $date->format('Y-m-d');
		return $query_date;
	}
	
	/**
	 * Checks for recent failed DataX validations
	 *
	 * @param Blackbox_Data $data Blackbox_Data object
	 * @param Blackbox_IStateData $state_data Blackbox_IStateData
	 * @return boolean TRUE if this ssn has failed a DataX hit in the past $days days
	 * @see [#24966] Create rule for a previous customer check on a DataX Fail
	 */
	public function isDataXDupe(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		if ($config->allow_datax_rework
		&& $config->do_datax_rework)
		{
			// don't do the de-duping for rework-able apps currently being reworked
			return FALSE;
		}

		$ssn_encrypted = $data->social_security_number_encrypted;

		$prop_short = EnterpriseData::resolveAlias($state_data->name);

		$query = sprintf("
			SELECT 
				count(*) as cnt
			FROM 
				`datax_fail_queue` AS df 
			WHERE 
				df.date_created >= '%s' 
				AND df.social_security_number = '%s'
				AND df.property_short = '%s'",
			$this->getDupeDate(),
			$ssn_encrypted,
			$prop_short
		);

		try
		{
			$result = $this->getDbInstance()->Query($this->getDbName(), $query);
			if (($row = $this->getDbInstance()->Fetch_Row($result)))
			{
				if ($row[0] >= 1)
				{
					// This is a dupe DataX fail, write to the event log
					$this->logEvent(
					"DATAX_DUPE_FAIL_" . $prop_short,
					'fail',
					$state_data->campaign_name,
					$data->application_id,
					$config->blackbox_mode
					);
						
					return TRUE;
				}
			}
		}
		catch (Exception $e)
		{
			$this->getConfig()->applog->Write(
			sprintf("%s:%s - DataX dupe fail filter query failed", __CLASS__, __METHOD__)
			);
		}

		return FALSE;
	}

	/**
	 * Hits the event log
	 * @param string $event
	 * @param string $pass
	 * @param string $campaign
	 * @param string $app_id
	 * @param string $mode blackbox_mode rc/local/live
	 * @return boolean TRUE if successful
	 */
	protected function logEvent($event, $pass, $campaign, $app_id, $mode)
	{
		return $this->getConfig()->event_log->Log_Event($event, $pass, $campaign, $app_id, $mode);
	}

	/**
	 * Writes an entry into the datax_fail_queue table if the specific company
	 * has a value set for $datax_dupe_filter_days
	 * @param Blackbox_Data $data Blackbox_Data object
	 * @param Blackbox_IStateData $state_data Blackbox_IStateData object
	 * @return boolean TRUE if written successfully
	 */
	public function logDataXDupe(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
			
		if (empty($this->datax_dupe_filter_days)
		|| !is_numeric($this->datax_dupe_filter_days)
		|| $this->datax_dupe_filter_days == 0)
		{
			return FALSE;
		}

		if ($config->allow_datax_rework // allowed to do rework
		&& !$config->do_datax_rework     // ...and not currently doing a rework
		&& $this->responseIsIDVFailure() // AND failure is an IDV failure
		)
		{
			// don't do the de-duping for rework-able apps currently being reworked
			return FALSE;
		}

		$ssn_encrypted = $data->social_security_number_encrypted;
		$prop_short = EnterpriseData::resolveAlias($state_data->name);

		$query = sprintf("
			INSERT INTO datax_fail_queue (
			date_modified,
			date_created,
			social_security_number,
			property_short
			)
			VALUES (
			NOW(), NOW(), '%s', '%s'
			) ON DUPLICATE KEY UPDATE date_modified = NOW()",
		$ssn_encrypted,
		$prop_short
		);

		try
		{
				
			$result = $this->getDbInstance()->Query($this->getDbName(), $query);
			if (($this->getDbInstance()->Insert_Id() > 0))
			{
				return TRUE;
			}
		}
		catch (Exception $e)
		{
			$this->getConfig()->applog->Write(
			sprintf("%s:%s - logDataXDupe query failed", __CLASS__, __METHOD__)
			);
		}
		return FALSE;
	}
	
	/**
	 * Checks if it's an adverse action
	 *
	 * @return bool
	 */
	protected function isAdverseAction()
	{
		return $this->aa_hit;
	}
	
	/**
	 * Initializes DataX Object.
	 *
	 * @return Data_X
	 */
	protected function initDataX()
	{
		// Disable MiniXML in DataX
		return new Data_X(FALSE);
	}
	
	/**
	 * Sets and returns the Config Data object
	 *
	 * @return OLPBlackbox_Config
	 */
	protected function initConfigData()
	{
		return $this->config_data = $this->getConfig();
	}
	
	/**
	 * Sets and returns the Authentication Object
	 *
	 * @return object
	 */
	protected function initAuthentication()
	{
		return $this->authentication = new Authentication(
			$this->config_data->olp_db,
			$this->config_data->olp_db->db_info['name'],
			$this->config_data->applog
		);
	}
	
	/**
	 * Sets and returns the account value, also if it's 
	 * a pw account apparently the track_hash gets reset
	 *
	 * @param string $account 	The account value
	 * @return string $account 	The account value
	 */
	protected function checkAccount($account = NULL)
	{
		if (is_string($account))
		{
			$this->account = $account;
	
			if (strtolower($account) == 'pw')
			{
				// Can't use the same track hash for a different account
				$this->track_hash = NULL;
			}
		}
		elseif (is_null($account))
		{
			$account = $this->account;
		}
		else
		{
			$account = FALSE;
		}
	
		return $account;
	}
	
	/**
	 * This is where the history of the calls made are stored, indexed by call_type
	 *
	 * @param string $call_type The type of DataX call 
	 * @return bool $decision 
	 */
	protected function getHistory($call_type = NULL)
	{
		$decision = NULL;
	
		if ((!is_null($call_type)) && (isset(self::$history[$call_type]) || array_key_exists($call_type, self::$history)))
		{
			$decision = self::$history[$call_type];
		}
		elseif (is_null($call_type))
		{
			$decision = (self::$history) ? self::$history : NULL;
		}
	
		return $decision;
	}
	
	/**
	 * Returns adverse actions that have been delayed and need to be hit
	 *
	 * @return array
	 */
	public static function getAdverseActions()
	{
		return self::$aa_queue;
	}
	
	/**
	 * This is for setting up the DataX Call Type.
	 *
	 * @param string $call_type The type of call being made.
	 * 
	 * @return void
	 */
	protected function setCallType($call_type)
	{
		if (in_array($call_type, $this->call_type_list))
		{
			$this->call_type = $call_type;
		}
		else 
		{
			throw new OLPBlackbox_Rule_DataXException("Couldn't set $call_type, it wasn't found in the Call_Type array");
		}
	}
	
	/**
	 * This will check the values that are passed in to make sure we can actually make the call.
	 *
	 * @param Blackbox_Data $blackbox_data the data to check if we can run this rule
	 * @param Blackbox_IStateData $state_data the state data to check if we can run this rule
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		switch ($this->call_type)
		{
			case OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV_PREQUAL:
				$ssn = $blackbox_data->ssn_part_3;
				
				break;
			default:
				$ssn = $blackbox_data->ssn_part_1.$blackbox_data->ssn_part_2.$blackbox_data->ssn_part_3;
				
				if (is_null($blackbox_data->bank_account) && is_null($blackbox_data->bank_name))
				{
					return FALSE;
				}
				break;
		}
				
		if (!is_null($ssn) &&
			!is_null($blackbox_data->name_first) &&
			!is_null($blackbox_data->name_last) &&
			!is_null($blackbox_data->date_dob_y) &&
			!is_null($blackbox_data->date_dob_m) &&
			!is_null($blackbox_data->date_dob_d) &&
			!is_null($blackbox_data->home_street) &&
			!is_null($blackbox_data->home_city) &&
			!is_null($blackbox_data->home_state) &&
			!is_null($blackbox_data->home_zip) &&
			!is_null($blackbox_data->bank_aba))
		{
			return TRUE;
		}
		else 
		{
			return FALSE;
		}
	}
	
	/**
	 * Go through all the data passed in and build the IDV Packet for DataX
	 *
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @return array
	 */
	protected function buildQuery(Blackbox_Data $blackbox_data)
	{
		// Prepare the data
		$query = array(
			'trackid'			=> $blackbox_data->application_id,
			'ssn' 				=> ($blackbox_data->social_security_number) ? $blackbox_data->social_security_number : trim($blackbox_data->ssn_part_1.$blackbox_data->ssn_part_2.$blackbox_data->ssn_part_3),
			'namefirst' 		=> $blackbox_data->name_first,
			'namelast'  		=> $blackbox_data->name_last,
			'street1'			=> preg_replace("/[&#;]/", " ", $blackbox_data->home_street),
			'city'				=> $blackbox_data->home_city,
			'state'				=> $blackbox_data->home_state,
			'zip'				=> $blackbox_data->home_zip,
			'homephone'			=> preg_replace('/[\-]/', '', $blackbox_data->phone_home),
			'cellphone'			=> $blackbox_data->phone_cell,
			'email'				=> $blackbox_data->email_primary,
			'ipaddress'			=> $blackbox_data->client_ip_address,
			'legalid'			=> $blackbox_data->state_id_number,
			'legalstate'		=> ($blackbox_data->state_issued_id) ? $blackbox_data->state_issued_id : $blackbox_data->home_state,
			'dobyear'			=> $blackbox_data->date_dob_y,
			'dobmonth'			=> $blackbox_data->date_dob_m,
			'dobday'			=> $blackbox_data->date_dob_d,
			'namemiddle'		=> $blackbox_data->name_middle,
			'street2'			=> $blackbox_data->home_unit,
			'phonework'			=> $blackbox_data->phone_work,
			'workext'			=> $blackbox_data->ext_work,
			'bankname'			=> $blackbox_data->bank_name,
			'bankaba'			=> $blackbox_data->bank_aba,
			'bankacct'			=> $blackbox_data->bank_account,
			'banktype'			=> $blackbox_data->bank_account_type,
			'directdeposit' 	=> (int)(strtoupper($blackbox_data->income_direct_deposit) == 'TRUE'),
			'employername'		=> $blackbox_data->employer_name,
			'promo'				=> $this->config_data->promo_id,
			'payperiod'			=> $blackbox_data->income_frequency,
			'track_key'			=> $this->config_data->track_key,
			'monthlyincome'		=> $blackbox_data->income_monthly_net,
			'amountrequested'	=> $this->state_data->qualified_loan_amount,
		);
		// Get the source URL
		$url = $blackbox_data->client_url_root;
		
		// Remove the http://, if it exists
		$matched = array();
		if (preg_match('/^(https?:\/\/)/', $url, $matched))
		{
			$url = substr($url, strlen($matched[1]));
		}
		
		$query['source'] = $url;
		
		return $query;
	}
	
	/**
	 * Here is where we build the performance query for DataX.
	 *
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @return array
	 */
	protected function buildPerformance(Blackbox_Data $blackbox_data)
	{
		$query = NULL;
		
		// We should always have a previous hash
		if ($this->track_hash)
		{
			$query = array('ssn' => ($blackbox_data->social_security_number) 
							? $blackbox_data->social_security_number 
							: trim($blackbox_data->ssn_part_1.$blackbox_data->ssn_part_2.$blackbox_data->ssn_part_3));
		}
		
		return $query;
	}
	
	/**
	 * Makes the actual DataX Call
	 *
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @return string
	 */
	protected function call(Blackbox_Data $blackbox_data)
	{
		$decision = NULL;
		$query = NULL;

		// Reset these
		$this->last_response = '';
		$this->decision = '';
		$this->score = '';
		$this->reason = '';

		if ($this->account)
		{
			$query = $this->buildQuery($blackbox_data);
			
			if ($query)
			{
				// Re-use our trackhash, if we have one
				if ($this->track_hash)
				{
					$query['trackhash'] = $this->track_hash;
				}

				// Time the calls
				$this->startTimer();
				
				// Actually do the calling
				$this->result = $this->datax->Datax_Call($this->call_type, $query, $this->config_data->mode, $this->account, NULL);
				
				// Stop timer
				$this->stopTimer();
				
				// Store packets
				$this->last_response = $this->result;
				$this->xml_sent = new DataX_Parser($this->datax->Get_Sent_Packet());
				$this->xml_received = new DataX_Parser($this->datax->Get_Received_Packet());
				
				if (!$this->xml_received->isValid())
				{
					throw new OLPBlackbox_Rule_DataXException('Invalid DataX response.');
				}
				elseif ($datax_error = $this->xml_received->getError())
				{
					$this->reason = $datax_error;
					
					$m = array();
					$experian_error = preg_match('/^E-(\d{3})/', $datax_error, $m);
					$valid_experian_errors = array(5, 8, 57);
					
					if ($experian_error == 1 && !in_array((int)$m[1], $valid_experian_errors))
					{
						throw new OLPBlackbox_Rule_DataXProviderException('ERROR: ' . $datax_error);
					}
					else
					{
						throw new OLPBlackbox_Rule_DataXException('ERROR: ' . $datax_error);
					}
				}
				else
				{
					$this->findDecision($blackbox_data, $this->state_data);
				}
				
				if (!is_null($this->decision))
				{
					$decision = ($this->decision == 'Y');
				}
				else
				{
					throw new OLPBlackbox_Rule_DataXException('Error while making DataX call');
				}
			}
			else
			{
				throw new OLPBlackbox_Rule_DataXException('Could not build a DataX query.');
			}
			
			// Save these values
			self::$history[$this->call_type] = $decision;
			$this->last_call = $this->call_type;
		}
		else
		{
			throw new OLPBlackbox_Rule_DataXProviderException('No account was found.');
		}

		return $decision;
	}
	
	/**
	 * Run the DataX rule.
	 *
	 * @param Blackbox_Data $blackbox_data 		Data the rule is running against
	 * @param Blackbox_IStateData $state_data 	State data the rule is running against
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		// Set a few things up
		$this->checkAccount($this->account);
		
		// reset result for each run
		$this->result = '';

		// If we have a track hash, use it
		if ($state_data->track_hash)
		{
			$this->track_hash = $state_data->track_hash;
		}

		// startCall can return NULL
		$valid = (bool)$this->startCall($blackbox_data, $state_data);
		
		$this->result = $valid ? OLPBlackbox_Config::EVENT_RESULT_PASS : 
								OLPBlackbox_Config::EVENT_RESULT_FAIL;
		
		return $valid;
	}
	
	/**
	 * We only want to run datax IDV once per app
	 *
	 * @return bool
	 */
	protected function canCallDataX()
	{
		return is_null($this->getHistory($this->call_type));
	}

	/**
	 * This function is for checking if the call has been made previously, if it has been then we return the results for that call in the history
	 * Otherwise we do a new call and return the results.
	 *
	 * @param Blackbox_Data $blackbox_data 		Data the rule is running against
	 * @param Blackbox_IStateData $state_data 	State data
	 * 
	 * @return bool $valid 
	 */
	protected function startCall(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		$valid = NULL;

		// only make datax call if we need to
		if ($this->canCallDataX())
		{
			try
			{
				// Make the call
				$valid = $this->call($blackbox_data);
			}
			catch (OLPBlackbox_Rule_DataXException $e)
			{
				// Let us pass
				$this->result = 'ERROR';

				$valid = $this->getExceptionDecision();
				
				$this->reason = $e->getMessage();
			}
			catch(OLPBlackbox_Rule_DataXProviderException $e)
			{
				// If we fail because of the OLPBlackbox_Rule_DataXProviderException, let's write
				// an error to the applog so we can see the problem occurring.
				$this->config_data->applog->Write('DATAX EXPERIAN ' . $e->getMessage());
				$valid = FALSE;
				
			}
			
			// Insert our Authentication record
			$this->logAuthenticationRecord($blackbox_data);
			
			$state_data->uw_decision = $this->decision;
			$state_data->track_hash = $this->track_hash;
			$state_data->loan_amount_decision = $this->loan_amount_decision;

			//depreciated, we no longer care about these failure stats 
			//$this->hitCustomStats($valid, $blackbox_data, $state_data);
		}
		elseif (is_null($valid))
		{
			// Get our old response
			$valid = $this->getHistory($this->call_type);
		}

		return $valid;
	}
	
	/**
	 * Run when the rule returns as invalid.
	 *
	 * @throws OLPBlackbox_ReworkException
	 * @param Blackbox_Data $blackbox_data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		$this->logDataXDupe($blackbox_data, $state_data);
		parent::onInvalid($blackbox_data, $state_data);
	}

	/**
	 * Inserts a record into the authentication log
	 * 
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @return void
	 */
	protected function logAuthenticationRecord(Blackbox_Data $blackbox_data)
	{
		$this->authentication->Insert_Record(
				$blackbox_data->application_id,
				$this->getSourceID($this->call_type),
				$this->getSent(),
				$this->getReceived(),
				$this->decision,
				$this->reason,
				$this->elapsed,
				$this->score,
				0
			);
	}

	/**
	 * Based on Call Type and the result of the $valid var we hit stats
	 *
	 * @param bool $valid 					Was the call successfull?
	 * @param Blackbox_Data $blackbox_data 	Blackbox data
	 * @param Blackbox_IStateData $state_data 	State data
	 * 
	 * @return void
	 */
	protected function hitCustomStats($valid, Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		if (!$valid)
		{
			$bucket_stats = array(
				'idv-l1' => array(
					'C_ASL_OFAC_FAIL_1' => 'ofac_results1',
					'C_ASL_SSNFORMAT_FAIL_1' => 'ssn_valid_format',
					'C_ASL_SSN_DECEASED_FAIL_1' => 'ssn_deceased',
					'C_ASL_SSN_ISSUE_RESULT_FAIL_1' => 'ssn_issued_or_opened',
					'D_ASL_SSN_RESULT_DOB_FAIL_1' => 'ssn_matches',
					'D_ASL_SSN_RESULT_DOB_FAIL_2' => 'ssn_matches',
					// Yes, these below are the number 1 and not the letter L.
					'C_AS1_ADDRESS_VERIFICATION_FAIL' => 'address_verification',
					'C_AS1_ADDRESS_TYPE_FAIL' => 'address_type',
					'C_AS1_PHONE_VERIFICATION_FAIL' => 'phone_verification',
				),
				
				'idv-l3' => array(
					'C_ASL_OFAC_FAIL_1' => 'ofac_results1',
					'C_ASL_SSNFORMAT_FAIL_1' => 'ssn_valid_format',
					'C_ASL_SSN_DECEASED_FAIL_1' => 'ssn_deceased',
					'C_ASL_SSN_ISSUE_RESULT_FAIL_1' => 'ssn_issued_or_opened',
					'D_ASL_SSN_RESULT_DOB_FAIL_1' => 'ssn_matches',
					'D_ASL_SSN_RESULT_DOB_FAIL_2' => 'ssn_matches',
					'CRA_INQ4MO_FAIL_1' => 'inquiries_cra',
					'C_CRA_3ABA_7DAY_FAIL' => 'back_accounts',
					'C_CRA_RETURN_3DAY_FAIL' => 'payment_returns',
					'C_CRA_BANK_ADDRESS_CONFLICT_FAIL' => 'bank_account_address',
					'C_CRA_RETURN_REASON_FAIL' => 'last_return_fatal',
					'TLT_INQCA4MO_FAIL_1' => 'inquiries_tt',
					'TLT_OCOCA_INQCA4MO_FAIL_1' => 'chargeoffs',
					'TLT_BK_OCOCA_FAIL_1' => 'bankruptcies_chargeoffs',
					// Yes, these below are the number 1 and not the letter L.
					'C_AS1_ADDRESS_VERIFICATION_FAIL' => 'address_verification',
					'C_AS1_ADDRESS_TYPE_FAIL' => 'address_type',
					'C_AS1_PHONE_VERIFICATION_FAIL' => 'phone_verification',
					'C_CRA_NONCLK_CO_INQ4MO_FAIL' => 'nonclk_inq4mo',
				),
				
				'idv-l5' => array(
					'C_ASL_OFAC_FAIL_1' => 'ofac_results5',
					'DOBMATCH_RESULT_FAIL_1' => 'dob_match',
					'D_ASL_SSN_RESULT_FAIL_1' => 'ssn_match',
				),
				
				'perf-l3' => array(
					'CRA_INQ4MO_FAIL_1' => 'inquiries_cra',
					'C_CRA_3ABA_7DAY_FAIL' => 'back_accounts',
					'C_CRA_RETURN_3DAY_FAIL' => 'payment_returns',
					'C_CRA_BANK_ADDRESS_CONFLICT_FAIL' => 'bank_account_address',
					'C_CRA_RETURN_REASON_FAIL' => 'last_return_fatal',
					'TLT_INQCA4MO_FAIL_1' => 'inquiries_tt',
					'TLT_OCOCA_INQCA4MO_FAIL_1' => 'chargeoffs',
					'TLT_BK_OCOCA_FAIL_1' => 'bankruptcies_chargeoffs',
					'C_CRA_NONCLK_CO_INQ4MO_FAIL' => 'nonclk_inq4mo',
				),
			);
			
			$result_array = explode(',', $this->xml_received->getDecisionCode());
			
			// Hit a stat for the failure reason
			foreach ($result_array AS $bucket)
			{
				if (isset($bucket_stats[strtolower($this->call_type)][strtoupper($bucket)]))
				{
					$this->hitBBStat($bucket_stats[strtolower($this->call_type)][strtoupper($bucket)] . '_fail', $blackbox_data, $state_data);
				}
			}
			
			// Hit global failure
			$this->hitBBStat('datax_' . str_replace('-', '_', $this->call_type) . '_fail', $blackbox_data, $state_data);
		}
	}
	
	/**
	 * Get the source id for the call type provided.
	 *
	 * @param string $call_type DataX call type
	 * @return int source id
	 */
	protected function getSourceID($call_type = NULL)
	{
		$call_type = (empty($call_type)) ? $this->call_type : $call_type;
		$source_id = DataX_Config::getSourceId($call_type);
		if ($source_id === FALSE)
		{
			throw new Blackbox_Exception(sprintf(
				'source id for unknown call type (%s) requested',
				strval($call_type))
			);
		}
		return $source_id;
	}
	
	/**
	 * Get's the exception decision
	 * 
	 * @return bool $valid Returns TRUE
	 */
	protected function getExceptionDecision()
	{
		if ($this->fail_on_error)
		{
			$this->decision = 'N';
			$valid = FALSE;
		}
		else
		{
			$this->decision = 'Y';
			$valid = TRUE;
			$this->addDataXExceptionLoanActions();
		}
		
		return $valid;
	}

	/**
	 * Hits any loan actions that need to happen when a DataX call throws an 
	 * exception during execution.
	 * 
	 * Note: "DATAX_PERF" loan action gets added EVEN FOR straight IDV calls 
	 * according to MP/CB. The reasoning is that if DataX goes down we don't
	 * want to deny a ton of apps for CLK, but CLK still needs to know that
	 * they have to verify these apps themselves. [DO]
	 * 
	 * @return void
	 */
	protected function addDataXExceptionLoanActions()
	{
		// see method comment
		$this->addDataItem('DATAX_PERF');
	}
	
	/**
	 * This will figure out where to pull the decision from based on the call type.
	 * 
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @param Blackbox_IStateData $state_data 	State data
	 *
	 * @return void
	 */
	protected function findDecision(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		// Save our track hash for re-use
		$this->track_hash = $this->xml_received->searchOneNode('//TrackHash');
		
		// Get the DataX decision
		$this->decision = $this->xml_received->getDecisionResult();
		$this->reason = $this->xml_received->getDecisionCode();
		$this->score = $this->xml_received->searchOneNode('//AuthenticationScoreSet/AuthenticationScore');
	}
	
	/**
	 * Add Loan Action to the loan actions object in the state data
	 *
	 * @param string $name Loan action name
	 * @param bool $exception Throw a blackbox exception on failure. Throwing the exeption
	 * will fail the rule.  If writing the loan action isn't required for using the target,
	 * set the value to false.
	 * @return void
	 */
	protected function addLoanAction($name, $exception = TRUE)
	{
		try
		{
			if (!$this->state_data->loan_actions instanceof OLPBlackbox_Enterprise_LoanActions)
			{
				$this->state_data->loan_actions = new OLPBlackbox_Enterprise_LoanActions();
			}
			$this->state_data->loan_actions->addDataItem($name);
		}
		catch (Exception $e)
		{
			if ($exception)
			{
				throw new Blackbox_Exception('Unable to add loan action: '.$e->getMessage());
			}
		}
	}

	/**
	 * Determine if the response failure from DataX is an IDV failure (vs. CRA)
	 *
	 * @pre $this->xml_received is a valid DataX_Parser object.
	 * 
	 * @return bool TRUE if the response is an IDV failure
	 */
	protected function responseIsIDVFailure()
	{
	    $result = $this->xml_received->searchOneNode(
	    	'//ConsumerIDVerificationSegment/CustomDecision/Result'
	   	);
	   	
	   	// if we don't find the result in the new response format, look at the global decision bucket
	   	if (is_null($result))
	   	{
	   		$result = $this->xml_received->searchOneNode('//GlobalDecision/IDVBucket');
	   		if (preg_match('/^d/i', $result))
			{
					$result = 'N';
			}
		}

	   	// return true only if it's a hard fail
	    return $result == 'N';
	}
	
	/**
	 * Return a database connection
	 *
	 * @return void
	 */
	protected function getDbInstance()
	{
		return $this->getConfig()->olp_db;
	}

	/**
	 * Returns the database name.
	 *
	 * @return string
	 */
	protected function getDbName()
	{
		return $this->getConfig()->olp_db->db_info['db'];
	}
}

?>
