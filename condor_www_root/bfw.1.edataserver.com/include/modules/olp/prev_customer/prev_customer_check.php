<?php

/**
 * Abstract class implementing common functions
 * for previos customer checks
 *
 */
abstract class Previous_Customer_Check
{
		const STATUS_REACT		= 'INACTIVE';
		const STATUS_ACTIVE		= 'ACTIVE';
		const STATUS_HOLD		= 'HOLD';
		const STATUS_COLLECTION	= 'COLLECTION';
		const STATUS_SCANNED	= 'SCANNED';
		const STATUS_DENIED		= 'DENIED';
		const STATUS_BAD		= 'BAD';
		const STATUS_PENDING	= 'PENDING';
		const STATUS_BANKRUPTCY	= 'BANKRUPTCY';
		const STATUS_DNL		= 'DO_NOT_LOAN';
		const STATUS_PROSPECT	= 'PROSPECT_CONFIRMED';
		const STATUS_DNLO		= 'DO_NOT_LOAN_OVERRIDE';

		const RESULT_DNL			= 'do_not_loan';
		const RESULT_OVERACTIVE		= 'overactive';
		const RESULT_UNDERACTIVE	= 'underactive';
		const RESULT_BAD			= 'bad';
		const RESULT_DENIED			= 'denied';
		const RESULT_NEW			= 'new';
		const RESULT_ERROR			= 'failed_query';
		const RESULT_REACT			= 'new/react';

		// Types of checks to make, since we have grown beyond just SSN and email
		const TYPE_SSN				= 1;
		const TYPE_BANK_ACCOUNT		= 2;
		const TYPE_HOME_PHONE		= 3;
		const TYPE_EMAIL			= 4;
		const TYPE_DRIVERS_LICENSE	= 5;
		const TYPE_SSN_DOB			= 6;
		const TYPE_EMAIL_DOB		= 7;
		const TYPE_EMAIL_SSN		= 8;
		const TYPE_BANK_ACCOUNT_DOB	= 9;		// added for CLK, mantis 0012508
		const TYPE_HOME_PHONE_DOB	= 10;		// added for CLK, mantis 0012508

		// maps Cashline statuses to eCash 3.0 statuses (Status_Glob() compatible)
		public static $status_map = array(

			Previous_Customer_Check::STATUS_BAD      => '/customer/collections/:/customer/collections/>:/external_collections/pending:/external_collections/sent:/customer/servicing/past_due',
			Previous_Customer_Check::STATUS_DENIED   => '/applicant/denied',
			Previous_Customer_Check::STATUS_REACT    => '/customer/paid:/external_collections/recovered',
			Previous_Customer_Check::STATUS_ACTIVE   => '/customer/servicing/active',
			Previous_Customer_Check::STATUS_PENDING  => '/customer/servicing/approved:/customer/servicing/hold:/prospect/agree:/prospect/pending:/applicant/underwriting/>:/applicant/verification/>',
			Previous_Customer_Check::STATUS_PROSPECT => '/prospect/confirmed:/prospect/preact_confirmed:/prospect',

		);
		
		// Mantis #13973 - Not all statuses are expirable. Cannot include in normal list, as then
		// it will take away from other statuses that are used elsewhere. [RM]
		const STATUS_EXPIRABLE = '/prospect/confirmed:/prospect/pending:/prospect/preact_confirmed';

		// eventually holds 3.0 status IDs=>Cashline status mapping
		protected static $status_map_cache = array();

		protected $sql;
		protected $ldb;
		protected $olp_mysql;
		protected $db;
		protected $result;
		protected $results;
		protected $application_id; // current application ID
		protected $property;
		protected $properties = array();

		protected $single_company = NULL;

		protected $db_mode;
		protected $blackbox_mode;

		protected $active_threshold = 1;
		protected $denied_threshold = '30 days';
				
		protected $olp_active_statuses = array('AGREED', 'CONFIRMED', 'PENDING');
		
		public function __construct($sql, $db, $property, $mode, $bb_mode = null)
		{
			$this->sql = $sql;
			$this->db = $db;
			$this->db_mode = ($_SESSION['config']->use_new_process) ? $mode . '_READONLY' : $mode;
			$this->Setup_Db($property);
			$this->property = Enterprise_Data::resolveAlias($property);
			$this->blackbox_mode = $bb_mode;
			$this->applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $property, APPLOG_ROTATE, APPLOG_UMASK);
		
		}
		
		protected function Setup_Db($property)
		{
			$this->ldb = Setup_DB::Get_Instance('mysql', $this->db_mode, $property);
			$this->olp_mysql = OLP_LDB::Get_Object($property, $this->ldb);
		}

		public function Result()
		{
			return $this->result;
		}

		public function Results()
		{
			return $this->results;
		}
		
		protected function Fetch_Status_Map()
		{
			if(!self::$status_map_cache)
			{
				// unfortunately, we can't fetch all of the status IDs at once,
				// because each entry in the map can return more than one ID
				foreach(self::$status_map as $status => $path)
				{
					// fetch the status IDs for the given path
					$fetch = $this->olp_mysql->Status_Glob($path);

					// set up the mappings in the "cache"
					foreach($fetch as $id)
					{
						self::$status_map_cache[$id] = $status;
					}
				}
			}

			$ids = self::$status_map_cache;

			if(!is_array($ids) || empty($ids))
			{
				throw new General_Exception('Could not retrieve Cashline statuses.');
			}

			return $ids;
		}

		protected function Count($cashline)
		{
			$count = array();

			// initialize these values
			$count[self::STATUS_REACT] = array();
			$count[self::STATUS_ACTIVE] = array();
			$count[self::STATUS_DENIED] = array();
			$count[self::STATUS_BAD] = array();
			$count[self::STATUS_PENDING] = array();
			$count[self::STATUS_DNL] = array();
			$count[self::STATUS_DNLO] = array();

			// calculate this now, instead of calculating it each iteration
			$denied_threshold = strtotime('-' . $this->denied_threshold);

			if (count($cashline))
			{
				foreach ($cashline as $data)
				{
					$name = strtoupper($data['name']);
					$key = $name.':'.$data['id'];
					$status = $data['status'];

					// skip records that aren't for the company we're checking for --
					// perhaps this should be implemented at the query level?
					if (is_null($this->single_company) || ($name == $this->single_company))
					{
						switch ($status)
						{
							case self::STATUS_HOLD:
							case self::STATUS_SCANNED:
							case self::STATUS_COLLECTION:
							case self::STATUS_BANKRUPTCY:
								$status = self::STATUS_BAD;
								break;

							// we'll consider "pending" loans (i.e., loans from
							// OLP's database, not cashline) active loans
							case self::STATUS_PENDING:
							case self::STATUS_ACTIVE:
								$status = self::STATUS_ACTIVE;
								break;

							case self::STATUS_DENIED:

								// get the last date the status changed
								$status_date = $data['last_payoff_date'];
								if(!$status_date) $status_date = $data['date_customer_added'];

								if($status_date < $denied_threshold)
								{
									$status = NULL;
								}

								break;

							case self::STATUS_REACT:

								if(isset($_SESSION['data']['enterprise']))
								{
									$_SESSION['react'] = array('transaction_id' => $data['id']);
								}

								break;
						}

						// add to our count
						if ($status)
						{
							$count[$status][$key] = $name;
						}

						// any record can have the DO NOT LOAN flag
						if ($data['do_not_loan'])
						{
							$count[self::STATUS_DNL][$key] = $name;
						}
						
						// add do not loan override to the count
						if ($data['do_not_loan_override'])
						{
							$count[self::STATUS_DNLO] = $data['do_not_loan_override'];
						}
					}
				}
			} // end if

			return $count;
		}
		
		

		/**
		 * Check if it is a Do not loan application
		 * tag any applications are "Do Not Loan" per clk client
		 */
		protected function Dnl_Check($results, $targets)
		{
			// moved this into its own function -- Bug #6327 [AuMa]	
			if(count($results[self::STATUS_DNL]))
			{
				$new_targets = array_diff($targets, $results[self::STATUS_DNL]);
				$result = self::RESULT_DNL;

				$targets = $new_targets;
				$_SESSION['is_DNL'] = TRUE;
				$_SESSION['DNL_shorts'] = $results[self::STATUS_DNL]; 

				// Loop through DNLs and tag them all related to the current app
				if(isset($_SESSION['application_id']))
				{
					foreach($results[self::STATUS_DNL] as $dnl)
					{
						$this->Tag_Application($_SESSION['application_id'], 'dnl_'.strtolower($dnl));			
					}
				}
			}

			//readd do not loan override targets back into the list [tp] mantis #13007
			$targets = array_unique(array_merge($targets,$results[self::STATUS_DNLO]));
			return $targets;
		}
		
		/**
		 * Tags an application with the given tag
		 * For Do Not Loan, these tags are later imported into ldb by import_ldb
		 *
		 * @param int $app_id The application ID to be tagged
		 * @param string $tag_name The name of the application tag
		 * @return bool 
		*/
		public function Tag_Application($app_id, $tag_name)
		{
			$ret_val = FALSE;
			
			$id_query = "SELECT
						tag_id
					FROM
						application_tag_details
					WHERE
						tag_name='{$tag_name}'
						";
			$id_result = $this->sql->Query($this->db, $id_query);
			$row = $this->sql->Fetch_Array_Row($id_result);
			$id = $row['tag_id'];
			
			// First check for duplicate entries where application ID and tag_id combinations already exist.
			// This should exclude the subsequent attempts to set a particular tag for this app_id for the same company.
			
			$dupe_query = "
				SELECT
					*
				FROM
					application_tags
				WHERE
					application_id = '{$app_id}'
				AND
					tag_id = '{$id}'
				";

			$dupe_result = $this->sql->Query($this->db, $dupe_query);

			// If the duplicate check returns nothing, then go ahead and set the tag.
			if(!$this->sql->Row_Count($dupe_result))
			{

				$tag_query = "
					INSERT INTO 
						application_tags
						(tag_id,
						application_id,
						date_created)
					VALUES(
						{$id},
						{$app_id},
						NOW())
					";
				//Did the Tag succeed? 
				$ret_val = ($tag_result = $this->sql->Query($this->db, $tag_query)) ? TRUE : FALSE;
			}
			return $ret_val;
		}
		
		public static function Permutate_Account($account_number)
		{
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
				for ($i = strlen($account); $i < 18; $i++)
				{
					$acct_array[] = str_pad($account, $i, '0', STR_PAD_LEFT);
				}
			}
			
			return $acct_array;
		}
		
		/**
		 * Merges two sets of results from Check() together.
		 *
		 * @param array $new The first set
		 * @param array $previous The set to merge in
		 * @return array
		 */
		protected function Merge_Results($new, $previous)
		{
			$results = array();

			foreach ($previous as $name=>$old)
			{
				if (is_array($old))
				{
					$results[$name] = isset($new[$name]) ? ($new[$name] + $old) : $old;
				}
			}

			return $results;
		}
		/**
		 * Sets Cashline to only check the company given.
		 *
		 * @param string $company The property short of the company to check.
		 */
		public function Set_Single_Company($company)
		{
			$this->single_company = strtoupper($company);
		}

		/**
		 * Restricts to or excludes a company from Cashline results. Coupled with
		 * the new Decide() function, this can be used to simulate the
		 * Set_Single_Company() behavior after the fact.
		 *
		 * @param array $results Results from the Check() function
		 * @param mixed $company A company short, or array of company shorts
		 * @param bool $restrict_to Whether we should restrict to or exclude $company
		 * @return array
		 */
		public function Filter_Company($results, $company, $restrict_to = TRUE)
		{
			if (!is_array($company)) $company = array($company);

			// assume this in the format returned by Check()
			foreach($results as $result => $companies)
			{
				// are we restricting or excluding?
				if ($restrict_to)
				{
					$results[$result] = array_intersect($companies, $company);
				}
				else
				{
					$results[$result] = array_diff($companies, $company);
				}
			}
			return $results;
		}
		
		/**
		 * Determines whether or not this object supports the type mentioned.
		 *
		 * This method is designed to be overridden by child classes in the event that
		 * they don't offer a particular check. (Implemented for mantis #12508, CLK)
		 *
		 * @param int $type constant type depicting the type of check desired
		 * @return bool whether or not this object implements said check
		 */
		public function Offers_Check($type)
		{
			return !in_array($type, array(self::TYPE_BANK_ACCOUNT_DOB,
										self::TYPE_HOME_PHONE_DOB));
		}
		
		/**
		 * Performs multiple checks against cashline and the OLP database
		 * to see if there are already active applications.
		 *
		 * @throws NotImplementedException
		 * @param mixed $check_var A string or array of values to check
		 * @param array $targets An array of targets
		 * @param int $type Defined constants that determine what kind of check to do
		 * @param array $previous Optional array of a previous run (for cumulative checks)
		 * @param int $exclude_app_id Optional application ID to exclude
		 * @return mixed Returns status
		 */
		public function Check($check_var, $targets, $type = NULL, $previous = NULL, $exclude_app_id = NULL, $ecash_app = false)
		{
			$result = FALSE;
			$cashline = FALSE;

			// does this check return react results? currently, only
			// SSN and DL matches qualify as reacts
			$react = FALSE;

			// save this so our query functions can access it
			$this->application_id = $exclude_app_id;

			// decide which function we need to run
			switch ($type)
			{

				case self::TYPE_SSN:
					$function = 'Find_By_SSN';
					// Per bug ticket #2856, eCash3.0 apps will be marked as a react
					// if we match SSN only (not SSN and DOB anymore)
					$react = TRUE;
					break;

				case self::TYPE_BANK_ACCOUNT:
					$function = 'Find_By_Account';
					break;

				case self::TYPE_HOME_PHONE:
					$function = 'Find_By_Home_Phone';
					break;

				case self::TYPE_DRIVERS_LICENSE:
					$function = 'Find_By_Drivers_License';
					break;

				case self::TYPE_EMAIL:
					$function = 'Find_By_Email';
					break;

				case self::TYPE_SSN_DOB:
					$function = 'Find_By_SSN_DoB';
					$react = TRUE;
					break;

				case self::TYPE_EMAIL_DOB:
					$function = 'Find_By_Email_DoB';
					$react = TRUE;
					break;
					
				case self::TYPE_EMAIL_SSN:
					$function = 'Find_By_Email_SSN';
					$react = TRUE;
					break;

				default:
					// ghetto way to decide which check to run
					$function = (is_numeric($check_var) ? 'Find_By_SSN' : 'Find_By_Email');
					break;

			}

			// actually run the function now
			$cashline = $this->Run($function, $check_var);

			if($cashline !== FALSE)
			{
				// this will count up the number of active, denied, etc.
				$results = $this->Count($cashline);

				// nuke react results if we're not allowed to use them
				if(!$react)
				{
					unset($results[self::STATUS_REACT]);
				}

				// merge in previous results, if provided
				if(is_array($previous))
				{
					$results = $this->Merge_Results($results, $previous);
				}

				// save our results
				$this->results = $results;

				// make our actual decision
				$result = $this->Decide($results, $targets);
			}
			else
			{
				throw new Exception('ERROR!');
			}

			$this->result = $result;
			return $targets;
		}

		/**
		 * Runs a Cashline function, and it's eCash 3.0 equivalent, if available, then
		 * merges the results. The result is suitable for the Count() function.
		 *
		 * @param string $function The function name
		 * @param mixed $check_var The parameters to the function
		 * @return array
		 */
		protected function Run($function, $check_var)
		{
			// proper format for call_user_func_array(...)
			if(!is_array($check_var))
			{
				$check_var = array($check_var);
			}

			// run the Legacy version
			if(method_exists($this, $function))
			{
				$cashline = call_user_func_array(array($this, $function), $check_var);
			}

			if(!isset($cashline))
			{
				$cashline = array();
			}

			return $cashline;
		}
			
			
			

		
		/**
		 * Make a Cashline decision on a result-set. The array $targets is modified
		 * in-line to contain acceptable choices.
		 *
		 * @param array $results Results returned from Check()
		 * @param array $targets Array of acceptable targets
		 * @return string Result constant
		 */
		public function Decide($results, &$targets)
		{															
			if(count($results[self::STATUS_BAD])) 
			{
				$result = self::RESULT_BAD;
				$targets = array();
			}   
			elseif(count($results[self::STATUS_DENIED]))
			{
				$result = self::RESULT_DENIED;
				$targets = array();
			}
			elseif(count($results[self::STATUS_ACTIVE]))
			{
				$targets = $this->Dnl_Check($results, $targets);

				$result = $this->Decide_Active($results, $targets);
			}
			elseif (count($results[self::STATUS_REACT]))
			{
				$targets = $this->Dnl_Check($results, $targets);
				$result = self::RESULT_REACT;
			}
			else
			{
				$targets = $this->Dnl_Check($results, $targets);
				$result = self::RESULT_NEW;
			}

			return $result;
		}
		
		protected function Decide_Active($results, &$targets)
		{
			if(count($results[self::STATUS_ACTIVE]) > $this->active_threshold)
			{
				$result = self::RESULT_OVERACTIVE;
				$targets = array();
			}
			else
			{
				$result = self::RESULT_UNDERACTIVE;
				$targets = array_diff($targets, $results[self::STATUS_ACTIVE]);
			}
			
			return $result;
		}

		
		
		
		/**
		 * Find applications by the applicant's drivers license
		 *
		 * @param string $drivers_license The applicant's drivers license
		 * @return array
		 */
		protected function Find_By_Drivers_License($drivers_license)
		{
			$where = "legal_id_number = '" . mysql_escape_string($drivers_license) . "'";
			return $this->From_Query($where);
		}

		protected function Find_By_SSN($ssn)
		{
			$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			$ssn_encrypted 	= $crypt_object->encrypt($ssn);
			
			$where = "ssn = '{$ssn}'";
			$where_olp = "personal_encrypted.social_security_number = '{$ssn_encrypted}'";
			return $this->From_Query($where, $where_olp, 'idx_ssn');
		}

		protected function Find_By_Email($email)
		{
			$where = "email = '{$email}'";
			$where_olp = "personal_encrypted.email = '{$email}'";
			return $this->From_Query($where, $where_olp, 'idx_email');
		}

		/**
		 * Find applications by bank account number, from ECash3
		 *
		 * @param string $account_number The account number of the application
		 * @param string $aba The bank routing number
		 * @param string $ssn The SSN 
		 * @return array
		 */
		protected function Find_By_Account($account_number, $aba, $ssn)
		{
			// get an array of all possible leading zero permutations
			$accounts = implode("', '", self::Permutate_Account($account_number));
			
			$where = "
				ssn = '{$ssn}'
				AND bank_aba = '{$aba}'
				AND bank_account IN ('{$accounts}')
			";
			
			return $this->From_Query($where);
		}

		/**
		 * Find applications by the applicants home phone number
		 *
		 * @param string $home_phone The applicants home phone number
		 * @return array
		 */
		protected function Find_By_Home_Phone($home_phone)
		{
			$where = "phone_home = '{$home_phone}'";
			return $this->From_Query($where);
		}

		/**
		 * Find applications by the customer's SSN and birth date.
		 *
		 * @param string $ssn
		 * @param string $dob
		 * @return array
		 */
		protected function Find_By_SSN_DoB($ssn, $dob)
		{
			$where = "ssn = '{$ssn}' AND dob = '" . date('Y-m-d', strtotime($dob)) . "'";
			return $this->From_Query($where);
		}

		/**
		 * Find applications by the customer's email address and birth date.
		 *
		 * @param string $email
		 * @param string $dob
		 * @return array
		 */
		protected function Find_By_Email_DoB($email, $dob)
		{
			$where = "email = '{$email}' AND dob = '" . date('Y-m-d', strtotime($dob)) . "'";
			return $this->From_Query($where);
		}

		/**
		 * Dummy function. We only do this check for eCash 3.0 reacts.
		 *
		 * @param string $email
		 * @param string $ssn
		 * @return array
		 */
		protected function Find_By_Email_SSN($email, $ssn)
		{
			return array();
		}
		
		protected function From_Query($where, $where_olp = NULL, $index = NULL)
		{
			try
			{
				$cashline = array();
				$dnlo_data = array();
	
				foreach($this->properties as $property)
				{
					//Check to see if we've already run the query on this table.
					$this->Setup_Db($property);
					
					// get an ID=>Status mapping: this is cached in a class variable, so
					// that we can fetch it once an re-use it for multiple calls
					$map = $this->Fetch_Status_Map();

					//gforge #5071 make sure we connect to the proper database before 
					//running Company_ID [TP] moved here since it needed Setup_Db
					// get company IDs and convert them to SQL-compatible syntax
					$this->prop_converted_sql = array_flip($this->olp_mysql->Company_ID($this->properties));

					$query = "
						SELECT
							application.application_id,
							application.company_id,
							application_status_id,
							application.date_application_status_set AS status_date,
								application.last_paydate AS last_payoff_date,
							application.date_created AS date_customer_added,
							application.olp_process,
							application.ssn
						FROM
							application
							LEFT JOIN application_column USING (application_id)
						WHERE
							{$where}
							AND application.application_status_id IN (".implode(', ', array_keys($map)).")
							AND application.company_id IN (".implode(', ', array_keys($this->prop_converted_sql)).")
					";

					// exclude the current application
					if(is_numeric($this->application_id))
					{
						$query .= ' AND application.application_id != ' . $this->application_id;
					}

					// run the query
					$result = $this->ldb->Query($query);
					$dnl_query = "
						SELECT
							app.application_id,
							dnl.dnl_flag_id,
							dnl.category_id
						FROM
							application app
						LEFT JOIN 
							do_not_loan_flag dnl USING (ssn)
						WHERE
							{$where}
						AND
							dnl.active_status = 'active'
						";
				
					$dnl_result = $this->ldb->Query($dnl_query);
					$dnl_data = $dnl_result->Fetch_Object_Row();

					$dnlo_query = "
						SELECT app.application_id,
							dnlo.override_id,
							co.name_short as company
						FROM application app
						LEFT JOIN do_not_loan_flag_override dnlo USING (ssn)
						JOIN company co
							ON co.company_id = dnlo.company_id
						WHERE {$where}";
					$dnlo_result = $this->ldb->Query($dnlo_query);
					while($dnlo_tmp = $dnlo_result->Fetch_Object_Row())
					{
						$dnlo_data[] = strtoupper($dnlo_tmp->company);
					}

					while($row = $result->Fetch_Object_Row())
					{
						if(isset($map[$row->application_status_id]))
						{
							// store this information
							$prop = array();
							$prop['name'] = strtoupper($this->prop_converted_sql[$row->company_id]);
							$prop['status'] = $map[$row->application_status_id];
							$prop['last_payoff_date'] = (trim($row->last_payoff_date)) ? strtotime($row->last_payoff_date) : NULL;
							$prop['date_customer_added'] = (trim($row->date_customer_added)) ? strtotime($row->date_customer_added) : NULL;
							$prop['status_date'] = strtotime($row->status_date);
							$prop['olp_process'] = $row->olp_process;

							// because we're in eCash 3.0 now, we use the application_id
							// NOTE: property short is added in Count()!
							$prop['id'] = $row->application_id;
		
							// Check DNL data
							$prop['do_not_loan'] = ($dnl_data) ? TRUE : FALSE;
							$prop['do_not_loan_override'] = array_unique($dnlo_data);

							$cashline[] = $prop;
						}
					}

					if(!is_null($where_olp))
					{
						$prop_converted = implode("', '", array_merge($this->prop_converted_sql, Enterprise_Data::getAliases($property)));
	
						//Removed the confirmed and confirmed_disagreed statuses from this check, and
						//reduced the check time to one hour to help solve Mantis #4566. [BF] */

						$query_date = date('Y-m-d H:i:s', strtotime('-1 hour'));
						$active_statuses = implode("','", $this->olp_active_statuses);
						
						$query = "
							SELECT
								social_security_number AS id,
								'".self::STATUS_PENDING."' AS status,
								NOW() AS status_date,
								'' AS last_payoff_date,
								'' AS date_customer_added,
								UCASE(property_short) AS property,
								olp_process
							FROM
								personal_encrypted USE INDEX ($index)
								INNER JOIN application USE INDEX (PRIMARY)
									ON personal_encrypted.application_id = application.application_id
								INNER JOIN target
									ON application.target_id = target.target_id
							WHERE
								{$where_olp}
								AND application.application_type IN ('{$active_statuses}')
								AND application.created_date > '{$query_date}'
								AND target.property_short IN ('{$prop_converted}')";

						if(is_numeric($this->application_id))
						{
							$query .= "AND application.application_id != {$this->application_id}";
						}

						// run the query
						$result = $this->sql->Query($this->db, $query);
						while (($row = $this->sql->Fetch_Array_Row($result)))
						{
							$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
							$crypt_object		= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
							$row['id'] 		= $crypt_object->decrypt($row['id']);
							// get the target name
							$name = trim($row['property']);
							$status = preg_replace('/[^A-Z]/', '', strtoupper($row['status']));
							// store this information
							$prop = array();
							$prop['name'] = $name;
							$prop['status'] = $status;
							$prop['last_payoff_date'] = (trim($row['last_payoff_date'])) ? strtotime($row['last_payoff_date']) : NULL;
							$prop['date_customer_added'] = (trim($row['date_customer_added'])) ? strtotime($row['date_customer_added']) : NULL;
							$prop['status_date'] = strtotime($row['status_date']);
							$prop['olp_process'] = $row['olp_process'];
							// the property short + social security number is considered our UID for the
							// loan: this prevents duplicates from the myriad of checks we're now doing
							// NOTE: property short is added in Count()!
							$prop['id'] = $row['id'];
							$dnl_query = "
								SELECT
									app.application_id,
									dnl.dnl_flag_id,
									dnl.category_id,
									dnl.company_id
								FROM
									application app
								LEFT JOIN 
									do_not_loan_flag dnl USING (ssn)
								JOIN
									company co ON co.company_id = dnl.company_id
								WHERE
									ssn = '{$row['id']}'
								AND
									dnl.active_status = 'active'
								AND
									co.name_short = '".strtolower($name)."'
									";
								$dnl_result = $this->ldb->Query($dnl_query);
								$dnl_data = $dnl_result->Fetch_Object_Row();
								//$prop['do_not_loan'] = FALSE;
								$prop['do_not_loan'] = ($dnl_data) ? TRUE : FALSE;
								
								// Mantis #13007 - Do not loan override [TP]
								$dnlo_query = "SELECT co.name_short as company
									FROM do_not_loan_flag_override
									JOIN company co USING (company_id)
									WHERE ssn = '{$row['id']}'
									AND co.name_short = '".strtolower($name)."'";
								$dnlo_result = $this->ldb->Query($dnlo_query);
								
								while($dnlo_tmp = $dnlo_result->Fetch_Object_Row())
								{
									$dnlo_data[] = strtoupper($dnlo_tmp->company);
								}
								$prop['do_not_loan_override'] = array_unique($dnlo_data);
								
								// store this
								$cashline[] = $prop;
						}
					}
				}
			}
			catch (Exception $e)
			{
				$cashline = FALSE;
			}

			return $cashline;

		}

		public static function Get_Object($sql, $db, $prop_short, $mode, $restrict = NULL)
		{
			if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $prop_short))
			{
				require_once('prev_customer_impact.php');
				$class = 'Previous_Customer_Impact';
			}
			elseif(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_AGEAN, $prop_short))
			{
				require_once('prev_customer_agean.php');
				$class = 'Previous_Customer_Agean';
			}
			elseif(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_GENERIC, $prop_short))
			{
				require_once('prev_customer_entgen.php');
				$class = 'Previous_Customer_Entgen';
			}
			else
			{
				require_once('prev_customer_clk.php');
				$class = 'Previous_Customer_CLK';
			}

			return new $class($sql, $db, $prop_short, $mode, $restrict);
		}
}


/**
 * Class to indicate something is not implemented.
 *
 * I'm aware this is kind of a generic thing to include here, but
 * at the time of this writing (2008/02/08) libolution does not have this
 * exception class and the olp module doesn't really use libolution much anyhow.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class NotImplementedException extends Exception
{ 
}
