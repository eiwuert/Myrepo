<?php
	/**
	 * Container for BlackBox filters. BlackBox can add any number of filters and then execute
	 * them by calling Run()
	 * 
	 * @file_creator Brian Feaver
	 * @author Chris Barmonde
	 */
	class BlackBox_Filters implements Iterator
	{
		const MAX_TIME_ALLOWED = 60; //in seconds
		
		private $config;	//BB config
		private $tier;		//tier the queries will run as
		
		private $filters;	//iBlackBox_Filters that will run
		private $failed;	//Why this failed, if it did at all
		private $results;	//Array of results
		private $last_run;	//Last time the queries were run
				
		public function __construct(&$config, $tier = 2)
		{
			$this->config = &$config;
			$this->tier = intval($tier);
			

			$this->filters = array();
			$this->failed = NULL;
			$this->Sync_Session();
		}
		
		public function __destruct()
		{
			unset($this->filters);
			unset($this->config);
		}
		
		/**
			@desc Add a filter
		*/
		public function Add(&$filter)
		{
			if($filter instanceof iBlackBox_Filter)
			{
				$this->filters[$filter->Name()] = $filter;
			}
		}
		
		/**
			@desc Run the filters
		*/
		public function Run($data, $property, $clear = FALSE)
		{
			$valid = TRUE;
			
			//We're either going to clear the session or sync it
			if($clear)
			{
				$this->Clear();
			}
			else
			{
				$this->Sync_Session();
			}
			
			foreach($this->filters as $name => &$filter)
			{
				//If we've already run this one, let's just grab the cached result.
				if(isset($this->results[$name]) && $this->Validate_Time())
				{
					$valid = $this->results[$name];
				}
				else
				{
					$valid = $filter->Check_Filter($data, $this->tier);
					$this->results[$name] = $valid;

					$_SESSION['blackbox_filters']['last_run'] = time();
				}
				
				//Log the outcome
				$outcome = ($valid === FALSE) ? EVENT_FAIL : EVENT_PASS;
				$this->config->Log_Event('FILTER_' . strtoupper($name), $outcome, $property);
				
				//If we failed, there's no point in checking the rest.
				if(!$valid)
				{
					$this->failed = $name;
					break;
				}
			}

			//Store the results in the session
			if(!empty($this->results))
			{
				$_SESSION['blackbox_filters'][$this->tier] = $this->results;
			}
			
			return $valid;
		}

		/**
			@desc Make sure we're under the allowed time between checks.
		*/
		public function Validate_Time()
		{
			if(self::MAX_TIME_ALLOWED > 0 && intval($this->last_run) > 0)
			{
				return ((time() - intval($this->last_run)) < self::MAX_TIME_ALLOWED);
			}
		}
		
		
		/**
			@desc Because of the way this works, where the queries run on a tier-level but the
				BB checks are run on a target level, we should probably only run the queries once,
				cache the results and use those for every other target in the specified tier.  So
				to facilitate this, we'll store the result in a session, then sync it before the
				check is run in case the check for the tier has already been run before.
		*/
		public function Sync_Session()
		{
			$this->results = (isset($_SESSION['blackbox_filters'][$this->tier])) ? $_SESSION['blackbox_filters'][$this->tier] : array();
			$this->last_run = (isset($_SESSION['blackbox_filters']['last_run'])) ? intval($_SESSION['blackbox_filters']['last_run']) : 0;
		}
		
		public function Clear()
		{
			$this->results = array();
			$this->last_run = 0;
			$this->failed = NULL;
			unset($_SESSION['blackbox_filters']);
		}
		
		public function Results()
		{
			return $this->results;
		}
		
		public function Failed()
		{
			return $this->failed;
		}
		
		public function Has_Filters()
		{
			return !empty($this->filters);
		}
		
		public function Get_Filter_Array()
		{
			return array_keys($this->filters);
		}
		
		
	
		/*** ITERATOR INTERFACE ***/
		public function rewind()
		{
			reset($this->filters);
		}
	
		public function current()
		{
			return current($this->filters);
		}
	
		public function key()
		{
			return key($this->filters);
		}
	
		public function next()
		{
			return next($this->filters);
		}
	
		public function valid()
		{
			return ($this->current() !== false);
		}
		/*** END INTERATOR INTERFACE ***/
	}
	

	
	
	/**
	*	Abstract class because I'm lazy and don't want to write
	*	like three whole constructors
	*/
	abstract class BlackBox_Filter implements iBlackBox_Filter
	{
		protected $name;
		protected $config;
		
		public function __construct($name, &$config)
		{
			$this->name = $name;
			$this->config = &$config;
		}
		
		public function __destruct()
		{
			unset($this->config);
		}
		
		public function Name()
		{
			return $this->name;
		}
		
		//Need to declare this here even if we'll never use it.  Lame.
		public function Check_Filter($data, $tier = NULL)
		{
			return NULL;
		}
	}
	
	
	/**
		Checks to see if we have a completed app with the same email address
		but a different SSN within the past 30 days.
	*/
	class BlackBox_Filter_Email extends BlackBox_Filter
	{
		public function __destruct()
		{
			parent::__destruct();
		}
		
		public function Check_Filter($data, $tier = NULL)
		{
			
			if(!empty($data['email_primary']))
			{
				$email = mysql_real_escape_string($data['email_primary']);
				$ssn_encrypted = mysql_real_escape_string($data['social_security_number_encrypted']);
								
				$query = "SELECT a.application_id
					FROM personal_encrypted p USE INDEX (idx_email)
						JOIN application a ON p.application_id = a.application_id
						JOIN target t ON a.target_id = t.target_id
						JOIN tier ON t.tier_id = tier.tier_id
					WHERE p.email = '{$email}'
						AND a.application_type IN ('COMPLETED')
						AND a.created_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
						AND tier.tier_number = {$tier}
						AND p.social_security_number != '{$ssn_encrypted}'";
				
				if(!empty($data['application_id']))
				{
					$query .= " AND a.application_id != {$data['application_id']}";
				}
		
				$result = $this->config->sql->Query($this->config->database, $query);
	
				return ($this->config->sql->Row_Count($result) == 0);
			}
			
			return FALSE;
		}
	}
	
	
	
	/**
		Checks to see if we have a completed app with the same driver's license
		and license state but a different SSN within the past 30 days.
	*/
	class BlackBox_Filter_Drivers_License extends BlackBox_Filter
	{
		public function __destruct()
		{
			parent::__destruct();
		}
		
		public function Check_Filter($data, $tier = NULL)
		{
			if(!empty($data['state_id_number']))
			{
				$dl_num = strtolower($data['state_id_number']);
				
				//Don't run the query if they enter crap like this, since apparently
				//it takes forever to run.
				if($dl_num != 'none' && $dl_num != 'n/a' && $dl_num != 'na')
				{
					$license_number = mysql_real_escape_string($data['state_id_number']);
					$ssn_encrypted = mysql_real_escape_string($data['social_security_number_encrypted']);
					$license_state = NULL;
					
					$query = "SELECT a.application_id, p.drivers_license_number, p.drivers_license_state
						FROM personal_encrypted p USE INDEX (idx_drivers)
							JOIN application a ON p.application_id = a.application_id
							JOIN target t ON a.target_id = t.target_id
							JOIN tier ON t.tier_id = tier.tier_id
						WHERE p.drivers_license_number = '{$license_number}'
							AND a.application_type IN ('COMPLETED')
							AND a.created_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
							AND tier.tier_number = {$tier}
							AND p.social_security_number != '{$ssn_encrypted}'";
		
					if(!empty($data['state_issued_id']))
					{
						$license_state = mysql_real_escape_string($data['state_issued_id']);
						$query .= " AND p.drivers_license_state = '{$license_state}'";
					}
					
					if(!empty($data['application_id']))
					{
						$query .= " AND a.application_id != {$data['application_id']}";
					}
	
					$result = $this->config->sql->Query($this->config->database, $query);
					
		
					//If we have rows, they've got a loan, so deny 'em
					return ($this->config->sql->Row_Count($result) == 0);
				}
			}
			
			return TRUE;
		}
	}
	
	
	/**
		Checks to see if we have a completed app with the same bank info
		but a different SSN within the past 30 days.
	*/
	class BlackBox_Filter_MICR extends BlackBox_Filter
	{
		public function __destruct()
		{
			parent::__destruct();
		}
		
		public function Check_Filter($data, $tier = NULL)
		{
			if(!empty($data['bank_aba']) && !empty($data['bank_account']))
			{
				$bank_aba_encrypted = mysql_real_escape_string($data['bank_aba_encrypted']);
				$bank_acct_encrypted = implode("','", $this->Permutate_Account($data['bank_account']));
				$ssn_encrypted = mysql_real_escape_string($data['social_security_number_encrypted']);
				
				$query = "
					SELECT STRAIGHT_JOIN
						a.application_id
					FROM bank_info_encrypted b
						JOIN personal_encrypted p ON b.application_id = p.application_id
						JOIN application a ON b.application_id = a.application_id
						JOIN target t ON a.target_id = t.target_id
						JOIN tier ON t.tier_id = tier.tier_id
					WHERE b.routing_number = '$bank_aba_encrypted'
						AND b.account_number IN ('$bank_acct_encrypted')
						AND a.application_type IN ('COMPLETED')
						AND a.created_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
						AND tier.tier_number = $tier
						AND p.social_security_number != '$ssn_encrypted'";
				
				if(!empty($data['application_id']))
				{
					$query .= " AND a.application_id != {$data['application_id']}";
				}

				$result = $this->config->sql->Query($this->config->database, $query);
	
				//If we have rows, they've got a loan, so deny 'em
				return ($this->config->sql->Row_Count($result) == 0);
			}
			
			return TRUE;
		}
		
		
		/**
			@desc Taken (and slightly modified for my own pedantic tendencies) from olp/cashline.php
		*/
		protected function Permutate_Account($account_number)
		{
			$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object		= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			
			// Setup an array of account numbers with prefixed 0's
			// Remove any leading 0's
			$account = ltrim($account_number, '0');
			
			$acct_array = array();
			
			if (strlen($account) == 17)
			{
				$acct_array[] = $account;
			}
			else
			{
				// create all possible leading zero combinations for the bank account
				// only if the account number is not 17 digits
				for ($i = strlen($account); $i <= 17; $i++)
				{
					$acct_array[] = sprintf("%0{$i}d", $account);
				}
			}

			foreach($acct_array as $index => $acct)
			{
				$acct_array[$index]  = $crypt_object->encrypt($acct);
			}
			
			return $acct_array;
		}
	}
	
	
	
	/**
		Checks to see if a specific cell number has been used in the last 30 days.
		This is a PER-SITE filter, not per-tier like the others, hence it
		uses the current site's license key.
	*/
	class BlackBox_Filter_CellPhone extends BlackBox_Filter
	{
		public function __destruct()
		{
			parent::__destruct();
		}
		
		public function Check_Filter($data, $tier = NULL)
		{
			if(!empty($data['phone_cell']))
			{
				$cell = mysql_real_escape_string($data['phone_cell']);
				$license_key = $this->config->config->license;
				
				$query = "SELECT p.application_id
					FROM personal_encrypted p USE INDEX (idx_cell_phone)
						INNER JOIN campaign_info ci USING (application_id)
					WHERE p.cell_phone = '{$cell}'
						AND ci.license_key = '{$license_key}'
						AND ci.created_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
				
				if(!empty($data['application_id']))
				{
					$query .= " AND p.application_id != {$data['application_id']}";
				}

				$result = $this->config->sql->Query($this->config->database, $query);
	
				return ($this->config->sql->Row_Count($result) == 0);
			}
			
			return FALSE;
		}
	}


?>
