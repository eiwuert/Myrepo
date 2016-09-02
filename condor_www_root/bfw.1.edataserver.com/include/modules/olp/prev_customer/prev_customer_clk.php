<?php

	require_once('prev_customer_check.php');

	/**
	 * Checks the Cashline database and approves a customer for CLK based
	 * on their status in Cashline.
	 *
	 * If you're shocked because a bunch of your code has been changed,
	 * you only have me (Andrew) to blame. Hopefully you're not too
	 * dismayed. (Adding ECash 3.0 Changes)
	 *
	 * Added fraud changes to the ECash 3.0 changes. This includes the new functions
	 * Find_By_Account, Find_By_Home_Phone, and Find_By_Drivers_License and their
	 * ECash3 equivalents. [BF]
	 *
	 * @publicsection
	 * @public
	 * @author Kevin Kragenbrink
	 * @version 1.2.0 Andrew Minerd
	 *
	 */
	class Previous_Customer_CLK extends Previous_Customer_Check
	{

		protected $prop_legacy = array();
		protected $prop_converted = array();
		
		protected $preact = false;

		protected $prop_legacy_sql = '';
		protected $prop_converted_sql = '';
		protected $ldb_connections;
		
		public function __construct($sql, $db, $property, $mode, $bb_mode = null)
		{
			$this->properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_CLK);
			parent::__construct($sql, $db, $property, $mode, $bb_mode);
			// we use this for some stuff
			

			// list of all properties, and those on 3.0
			$converted = array_map('strtoupper', $_SESSION['config']->ecash3_prop_list);

			// separate these out
			$this->prop_legacy = array_diff($this->properties, $converted);
			$this->prop_converted = array_intersect($this->properties, $converted);

			/*
				Added this back as a reactable status, per Mantis #4831. [BF]
			*/
			/* We only want to add recovered status to reacts if it's an ecashapp react
			if(isset($_SESSION['data']['ecashapp']))
			{
				self::$status_map[Cashline::STATUS_REACT] .= ':/external_collections/recovered';
			}
			*/
		}
		
		/**
		 * Keeps a list of all the LDB connections 
		 *
		 * @param string $property
		 */
		protected function Setup_Db($property)
		{
			if(!isset($this->ldb_connections[$property]) ||
				!is_object($this->ldb_connections[$property]))
			{
				$this->ldb_connections[$property] = new stdClass();
				$this->ldb_connections[$property]->ldb = Setup_DB::Get_Instance('mysql', $this->db_mode, $property);
				$this->ldb_connections[$property]->olp_mysql = OLP_LDB::Get_Object($property, $this->ldb_connections[$property]->ldb); 
			}

			$this->ldb = $this->ldb_connections[$property]->ldb;
			$this->olp_mysql = $this->ldb_connections[$property]->olp_mysql;
		}

		public function __destruct()
		{

			// kill 'em all!
			unset($this->sql);
			unset($this->ldb);
			unset($this->olp_mysql);

			return;

		}

		protected function Find_By_SSN($ssn)
		{
			$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object		= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			$ssn_encrypted	= $crypt_object->encrypt($ssn); 
			
			$where = "social_security_number = '{$ssn}'";
			$where_olp = "personal_encrypted.social_security_number = '{$ssn_encrypted}'";

			$cashline = $this->From_Query($where, $where_olp, 'idx_ssn');

			return $cashline;

		}

		protected function Find_By_Email($email)
		{

			$where = "email_address = '{$email}'";
			$where_olp = "personal_encrypted.email = '{$email}'";

			$cashline = $this->From_Query($where, $where_olp, 'idx_email');

			return $cashline;

		}

		/**
		 * Find applications by bank account number and aba (routing) number
		 *
		 * @param string $account_number The account number of the application
		 * @param string $aba The routing number of the application
		 * @param string $ssn The SSN 
		 * @return array
		 */
		protected function Find_By_Account($account_number, $aba, $ssn)
		{
			// Added in SSN Check per request in #8554

			// get all possible leading zero permutations
			$accounts = "'".implode("', '", self::Permutate_Account($account_number))."'";

			$where = " 
				social_security_number = '{$ssn}'
				AND routing_number = '{$aba}'
				AND account_number IN ({$accounts})
			";
			$cashline = $this->From_Query($where);

			return $cashline;

		}

		/**
		 * Finds any previous customer records by bank number, aba and dob.
		 *
		 * Added for Mantis 12508
		 *
		 * @param int $account_number bank account number
		 * @param int $aba bank aba number
		 * @param string birth date string
		 * @return array same as returned from From_Query
		 */
		protected function Find_By_Account_DoB_Ecash3($account_number, $aba, $dob)
		{
			// get all possible leading zero permutations
			$accounts = "'".implode("', '", self::Permutate_Account($account_number))."'";
			$dob = date('Y-m-d', strtotime($dob));

			$where = " 
				dob = '{$dob}'
				AND bank_aba = '$aba'
				AND bank_account IN ($accounts)
			";
			$cashline = $this->From_Query_Ecash3($where);

			return $cashline;
		}

		/**
		 * Find previous customer information by home phone and dob.
		 *
		 * Added for Mantis 12508
		 *
		 * @param string $home_phone phone number of the applicant
		 * @param string $dob date of the birth of the applicant
		 * @return array same as returned from From_Query
		 */
		protected function Find_By_Home_Phone_DoB_Ecash3($home_phone, $dob)
		{
			$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object = Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			$dob_encrypted = $crypt_object->encrypt(date('Y-m-d', strtotime($dob)));

			$ecash_where = "
				phone_home = '{$home_phone}'
				AND dob = '{$dob}'
			";
			$olp_where = "
				home_phone = '{$home_phone}'
				AND date_of_birth = '{$dob_encrypted}'
			";
			$cashline = $this->From_Query_Ecash3($ecash_where, $olp_where, 'idx_home_phone');

			return $cashline;
		}

		/**
		 * Find applications by the applicants home phone number
		 *
		 * @param string $home_phone The applicants home phone number
		 * @return array
		 */
		protected function Find_By_Home_Phone($home_phone)
		{

			$where = "home_phone = '{$home_phone}'";
			$cashline = $this->From_Query($where);

			return $cashline;

		}

		/**
		 * Find applications by the applicant's drivers license
		 *
		 * @param string $drivers_license The applicant's drivers license
		 * @return array
		 */
		protected function Find_By_Drivers_License($drivers_license)
		{

			$where = "drivers_license_number = '".mysql_escape_string($drivers_license)."'";
			$cashline = $this->From_Query($where);

			return $cashline;

		}

		protected function From_Query($where_cashline, $where_olp = NULL, $index = NULL)
		{

			try
			{

				if (!$this->prop_legacy_sql)
				{
					$this->prop_legacy_sql = "'".implode("', '", $this->prop_legacy)."'";
				}

				$query = '';

				foreach($this->prop_legacy as $prop)
				{

					// Q: What's this for??? It's just trim()'d later
					// A: When performing a UNION query, the field types and lengths for query-defined
					// columns (i.e., 'test' AS test) are determined by the first query in the UNION
					// (fixed in 4.1.1, see: http://bugs.mysql.com/bug.php?id=96)
					$property = str_pad(strtoupper($prop), 3);

					if ($query) $query .= ' UNION ';

					// account_number may not be the final column name
					$query .= "
						SELECT
							social_security_number AS id,
							status,
							now() AS status_date,
							last_payoff_date,
							date_customer_added,
							'$property' AS property
						FROM
							sync_cashline_" . strtolower($prop) . ".cashline_customer_list
						WHERE
							{$where_cashline}
					";

				}
				

				$result = $this->sql->Query($this->db, $query);
				
				$sync_cashline_data = array();
				while ($row = $this->sql->Fetch_Array_Row($result))
				{
					$sync_cashline_data[] = $row;
				}
				
				$data = $sync_cashline_data;
				
				if ($where_olp !== NULL)
				{
					$statuses = array('PENDING', 'AGREED', 'CONFIRMED', 'CONFIRMED_DISAGREED', 'DISAGREED');
					$olp_process = '';
					
					if($this->blackbox_mode === MODE_AGREE)
					{
						$statuses = array('AGREED');
						$olp_process = "AND olp_process NOT IN ('ecashapp_react', 'ecashapp_preact', 'cs_react', 'mail_react')";
					}

					$statuses = implode("', '", $statuses);

					$query = "
						SELECT
							social_security_number AS id,
							'".self::STATUS_PENDING."' AS status,
							NOW() AS status_date,
							'' AS last_payoff_date,
							'' AS date_customer_added,
							UCASE(property_short) AS property
						FROM
							personal_encrypted USE INDEX ({$index})
							INNER JOIN application USE INDEX (PRIMARY)
								ON personal_encrypted.application_id = application.application_id
							INNER JOIN target
								ON application.target_id = target.target_id
						WHERE
							{$where_olp}
							AND application.application_type IN ('{$statuses}')
							AND application.created_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
							AND target.property_short IN ({$this->prop_legacy_sql})
							{$olp_process}
						";

					if (is_numeric($this->application_id))
					{
						$query .= "AND application.application_id != {$this->application_id}";
					}
					// run the query
					$result = $this->sql->Query($this->db, $query);
					
					$olp_data = array();
					while ($row = $this->sql->Fetch_Array_Row($result))
					{
						$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
						$crypt_object		= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
						$row['id'] 		= $crypt_object->decrypt($row['id']); 
						$olp_data[] = $row;
					}
				

					$data = array_merge($data,$olp_data);
				}

				$cashline = array();

				foreach($data as $row)
				{

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
						ssn = {$row['id']}
					AND
						dnl.active_status = 'active'
					AND
						co.name_short = '".strtolower($name)."'
						";
						
					try
					{
						$this->Setup_Db($name);
						$dnl_result = $this->ldb->Query($dnl_query);
						$dnl_data = $dnl_result->Fetch_Object_Row();
					}
					catch(Exception $e)
					{
						$dnl_data = NULL;
					}
					
					//$prop['do_not_loan'] = FALSE;
					$prop['do_not_loan'] = ($dnl_data) ? TRUE : FALSE;
					
					// Mantis #13007 - Do not loan override [TP]
					$dnlo_query = "
						SELECT
							co.name_short AS company
						FROM
							do_not_loan_flag_override
						JOIN
							company AS co USING(company_id)
						WHERE
							ssn = '{$row['id']}'
							AND co.name_short = '".strtolower($name)."'";
					
					try
					{
						$dnlo_result = $this->ldb->Query($dnlo_query);
						
						while ($dnlo_tmp = $dnlo_result->Fetch_Object_Row())
						{
							$dnlo_data[] = strtoupper($dnlo_tmp->company);
						}
						$prop['do_not_loan_override'] = array_unique($dnlo_data);
					}
					catch(Exception $e)
					{
					}
					
					// the property short + social security number is considered our UID for the
					// loan: this prevents duplicates from the myriad of checks we're now doing
					// NOTE: property short is added in Count()!
					$prop['id'] = $row['id'];

					/*//Search for latest app id on that ssn so we can put it into the session
					$q = "SELECT application_id 
						  FROM application 
						  WHERE ssn = '" . $row['id'] . "'
						  ORDER BY date_created DESC";
					$temp = $this->sql->Query($q);
					
					$r = $this->sql->Fetch_Array_Row($temp);
					$prop['application_id'] = $r['application_id'];*/

					// store this
					$cashline[] = $prop;
				}

			}
			catch (Exception $e)
			{
				$cashline = FALSE;
			}

			return $cashline;

		}

		/**
		 * Find applications by the applicant's drivers license
		 *
		 * @param string $drivers_license The applicant's drivers license
		 * @return array
		 */
		protected function Find_By_Drivers_License_ECash3($drivers_license)
		{

			$where = "legal_id_number = '".mysql_escape_string($drivers_license)."'";
			$cashline = $this->From_Query_Ecash3($where);

			return $cashline;

		}

		protected function Find_By_SSN_ECash3($ssn)
		{
			$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			$ssn_encrypted	= $crypt_object->encrypt($ssn); 
			
			$where = "ssn = '$ssn'";
			$where_olp = "personal_encrypted.social_security_number = '{$ssn_encrypted}'";

			$cashline = $this->From_Query_Ecash3($where, $where_olp, 'idx_ssn');

			return $cashline;

		}

		protected function Find_By_Email_ECash3($email)
		{

			// find by email address
			$where = "email = '$email'";
			$where_olp = "personal_encrypted.email = '{$email}'";

			$cashline = $this->From_Query_Ecash3($where, $where_olp, 'idx_email');

			return $cashline;

		}

		/**
		 * Find applications by bank account number, from ECash3
		 *
		 * @param string $account_number The account number of the application
		 * @param string $aba The bank routing number
		 * @param string $ssn The SSN 
		 * @return array
		 */
		protected function Find_By_Account_ECash3($account_number, $aba, $ssn)
		{
			// Added in SSN Check per request in #8554

			// get an array of all possible leading zero permutations
			$accounts = "'".implode("', '", self::Permutate_Account($account_number))."'";

			$where = "
				ssn = '{$ssn}'
				AND bank_aba = '$aba'
				AND bank_account IN ($accounts)
			";
			$cashline = $this->From_Query_Ecash3($where);

			return $cashline;

		}

		/**
		 * Find applications by the applicants home phone number
		 *
		 * @param string $home_phone The applicants home phone number
		 * @return array
		 */
		protected function Find_By_Home_Phone_ECash3($home_phone)
		{

			$where = "phone_home = '$home_phone'";
			$cashline = $this->From_Query_Ecash3($where);

			return $cashline;

		}

		/**
		 * Dummy function. We only do this check for eCash 3.0 reacts.
		 *
		 * @param string $ssn
		 * @param string $dob
		 * @return array
		 */
		protected function Find_By_SSN_DoB($ssn, $dob)
		{
			return array();
		}

		/**
		 * Dummy function. We only do this check for eCash 3.0 reacts.
		 *
		 * @param string $ssn
		 * @param string $dob
		 * @return array
		 */
		protected function Find_By_Email_DoB($email, $dob)
		{
			return array();
		}


		/**
		 * Find applications by the customer's SSN and birth date.
		 *
		 * @param string $ssn
		 * @param string $dob
		 * @return array
		 */
		protected function Find_By_SSN_DoB_ECash3($ssn, $dob)
		{
			$where = "ssn = '$ssn' AND dob='".date('Y-m-d', strtotime($dob))."'";
			return $this->From_Query_Ecash3($where);
		}

		/**
		 * Find applications by the customer's email address and birth date.
		 *
		 * @param string $email
		 * @param string $dob
		 * @return array
		 */
		protected function Find_By_Email_DoB_ECash3($email, $dob)
		{
			$where = "email = '$email' AND dob = '".date('Y-m-d', strtotime($dob))."'";
			return $this->From_Query_Ecash3($where);
		}


		/**
		 * Find applications by the customer's email address and social number.
		 *
		 * @param string $email
		 * @param string $ssn
		 * @return array
		 */
		protected function Find_By_Email_SSN_ECash3($email, $ssn)
		{
			$where = "email = '$email' AND ssn = '$ssn'";
			return $this->From_Query_Ecash3($where);
		}

		protected function From_Query_Ecash3($where, $where_olp = NULL, $index = NULL)
		{
			try
			{
				//If we're running for a single company
				//Don't actually query every database
				if(!empty($this->single_company))
				{
					$prop_list = array($this->single_company);
				}
				else
				{
					$prop_list = $this->prop_converted;
				}
				$cashline = array();

				
				// Need encryption to decrypt social security number from OLP.
				$crypt = Crypt_Singleton::Get_Instance();
				
				// GForge #4277: An array of application ids to expire for OLP and LDB. [RM]
				$expire_ldb = array();
				$expire_olp = array();
				
				// Mantis #13007 - Do not loan override [TP]
				$dnlo_data = array();
				
				foreach($prop_list as $property)
				{
					
					//Check to see if we've already run the query on this table.
					$this->Setup_Db($property);

					// get an ID=>Status mapping: this is cached in a class variable, so
					// that we can fetch it once an re-use it for multiple calls
					$map = $this->Fetch_Status_Map();
					
					// Mantis #13973 - Grab the values for expirable apps. [RM]
					$expirable_statuses = $this->olp_mysql->Status_Glob(Previous_Customer_Check::STATUS_EXPIRABLE);
					
					
					/// START: OLP SECTION
					// GForge #4277 requires OLP to be checked before LDB. [RM]
					
					/*
						We don't want to check OLP when we're doing our check during agree
						The reason we're doing this check on agree is to prevent customers from getting
						a bunch of apps past confirm and then agreeing them all at once.  If we leave this
						in, then the apps they've agreed to will count TWICE (once in the check above and
						then once again in this one), which will automatically mark them overactive.
					*/
					if(!is_null($where_olp) && $this->blackbox_mode !== MODE_AGREE)
					{
						$prop_converted = "'" . implode("', '", $this->prop_converted) . "'";
						
						//Removed the confirmed and confirmed_disagreed statuses from this check, and
						//reduced the check time to one hour to help solve Mantis #4566. [BF] */
						
						$query_date = date('Y-m-d H:i:s', strtotime('-1 hour'));
						
						// Modify query to include application_type as expired_check_status
						// to check if the app needs to be expired. Mantis #12472 [DW]
						$query = "
							SELECT
								application.application_id,
								social_security_number AS id,
								'".self::STATUS_PENDING."' AS status,
								application.application_type AS expired_check_status,
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
								$where_olp
								AND application.application_type IN ('AGREED', 'CONFIRMED', 'PENDING')
								AND application.created_date > '{$query_date}'
								AND target.property_short IN ($prop_converted)";
						
						if (is_numeric($this->application_id))
						{
							$query .= " AND application.application_id != {$this->application_id}";
						}
						// run the query
						$result = $this->sql->Query($this->db, $query);
						
						while ($row = $this->sql->Fetch_Array_Row($result))
						{
							// get the target name
							$name = trim($row['property']);
							$status = preg_replace('/[^A-Z]/', '', strtoupper($row['status']));
							
							// store this information
							$prop = array();
							$prop['application_id'] = $row['application_id'];
							$prop['name'] = $name;
							$prop['status'] = $status;
							$prop['expired_check_status'] = $row['expired_check_status'];
							$prop['last_payoff_date'] = (trim($row['last_payoff_date'])) ? strtotime($row['last_payoff_date']) : NULL;
							$prop['date_customer_added'] = (trim($row['date_customer_added'])) ? strtotime($row['date_customer_added']) : NULL;
							$prop['status_date'] = strtotime($row['status_date']);
							$prop['olp_process'] = $row['olp_process'];
							
							// the property short + social security number is considered our UID for the
							// loan: this prevents duplicates from the myriad of checks we're now doing
							// NOTE: property short is added in Count()!
							$prop['id'] = $crypt->decrypt($row['id']);
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
									ssn = '{$prop['id']}'
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
							$dnlo_query = "
								SELECT
									co.name_short AS company
								FROM
									do_not_loan_flag_override
								JOIN
									company AS co USING (company_id)
								WHERE
									ssn = '{$prop['id']}'
									AND
										co.name_short = '".strtolower($name)."'";
							$dnlo_result = $this->ldb->Query($dnlo_query);
							
							while ($dnlo_tmp = $dnlo_result->Fetch_Object_Row())
							{
								$dnlo_data[] = strtoupper($dnlo_tmp->company);
							}
							$prop['do_not_loan_override'] = array_unique($dnlo_data);
							
							// Modify to check expired_check_status instead of status. Mantis #12472 [DW]
							switch ($prop['expired_check_status'])
							{
								case 'CONFIRMED':
								case 'PENDING':
									// GForge #4277: Set confirmed/pending to expired for ecash_reacts only
									// Modify to include cs_reacts. Changed to use blackbox_mode to check if 
									// it's a react. Mantis #12472 [DW]
									if ($this->blackbox_mode == MODE_ECASH_REACT)
									{
										$expire_olp[] = $row['application_id'];
										break;
									}
									// else
									//     fall through to storing this application
								
								default:
									$cashline[] = $prop;
									break;
							}
						}
					}
					/// END: OLP SECTION
					
					
					
					/// START: LDB SECTION
					/*
						Mantis #9493 - CLK - Changed it so that this query only checks for the
						company for which database we're on. There were lingering UCL applications
						on the OCC/UFC database that were causing reacts to fail. [BF]
					*/
					
					// Get the company_id's for the converted eCash 3 companies
					$company_id_list = $this->olp_mysql->Company_ID($prop_list);
					// Flip around so the key is the company_id and the value is the property short
					$this->prop_converted_sql = array_flip($company_id_list);
					
					$query_companies = $company_id_list[$property];
					
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
						WHERE
							{$where}
							AND application.application_status_id IN (".implode(', ', array_keys($map)).")
							AND application.company_id IN ({$query_companies})";
					
					// exclude the current application
					if (is_numeric($this->application_id))
					{
						$query .= '
							AND application.application_id != '.$this->application_id;
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
					
					// Mantis #13007 - Do not loan override [TP]
					$dnlo_query = "
						SELECT
							app.application_id,
							dnlo.override_id,
							co.name_short AS company
						FROM
							application AS app
						LEFT JOIN
							do_not_loan_flag_override AS dnlo USING(ssn)
						JOIN
							company AS co ON co.company_id = dnlo.company_id
						WHERE
							{$where}";
					$dnlo_result = $this->ldb->Query($dnlo_query);
					
					while ($dnlo_tmp = $dnlo_result->Fetch_Object_Row())
					{
						$dnlo_data[] = strtoupper($dnlo_tmp->company);
					}
					$prop['do_not_loan_override'] = array_unique($dnlo_data);
					
					while ($row = $result->Fetch_Object_Row())
					{
						
						if (isset($map[$row->application_status_id]))
						{
							// store this information
							$prop = array();
							$prop['application_id'] = $row->application_id;
							$prop['name'] = strtoupper($this->prop_converted_sql[$row->company_id]);
							$prop['status'] = $map[$row->application_status_id];
							$prop['last_payoff_date'] = (trim($row->last_payoff_date)) ? strtotime($row->last_payoff_date) : NULL;
							$prop['date_customer_added'] = (trim($row->date_customer_added)) ? strtotime($row->date_customer_added) : NULL;
							$prop['status_date'] = strtotime($row->status_date);
							//$prop['do_not_loan'] = ($row->do_not_loan === 'on');
							$prop['olp_process'] = $row->olp_process;
							
							// because we're in eCash 3.0 now, we use the application_id
							// NOTE: property short is added in Count()!
							$prop['id'] = $row->application_id;
							
							// Check DNL data
							$prop['do_not_loan'] = ($dnl_data) ? TRUE : FALSE;
							$prop['do_not_loan_override'] = array_unique($dnlo_data);
							
							// GForge #4277: Set expirable apps to expired for ecash_react only
							// GForge #8638: Set expirable apps to expired when performing customer service reacts 
							//   '$this->blackbox_mode == MODE_ECASH_REACT' includes both ecash and customer service reacts [DW]
							if (($this->blackbox_mode == MODE_ECASH_REACT
									&& in_array($row->application_status_id, $expirable_statuses))
								|| in_array($row->application_id, $expire_olp)
							)
							{
								$expire_ldb[] = $row->application_id;
							}
							//Check preact amounts
							elseif ($this->preact && $prop['status'] == self::STATUS_ACTIVE)
							{
								if (!$this->Is_Preactable($prop['id']))
								{
									$cashline[] = $prop;
								}
							}
							else
							{
								$cashline[] = $prop;
							}
						}
					}
					/// END: LDB SECTION
					
					// If it's found as expirable in one database, it should be expired in both databases.
					// Merge both lists of expirable apps into one and expire in both OLP and LDB databases. GForge [#8636] [DW]
					$expire_apps = array_unique(array_merge($expire_olp, $expire_ldb));
					
					if (count($expire_apps))
					{
						// Expire ldb applications for this DB/company
						$this->expireEcashApplications($expire_apps);
						
						// Reset the list of expire applications for LDB.
						$expire_ldb = array();
					}
				}
				
				// Expire OLP applications
				if (count($expire_apps))
				{
					$this->expireOLPApplications($expire_apps);
				}
			}
			catch (Exception $e)
			{
				$cashline = FALSE;
			}

			return $cashline;

		}

		/**
		 * Determines whether or not this object supports the type mentioned.
		 *
		 * @param int $type constant type depicting the type of check desired
		 * @return bool whether or not this object implements said check
		 */
		public function Offers_Check($type)
		{
			// per mantis 0012508, do not allow these 3 checks.
			return !in_array($type, array(self::TYPE_HOME_PHONE,
										  self::TYPE_EMAIL_SSN,
										  self::TYPE_BANK_ACCOUNT));
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
		public function Check($check_var, $targets, $type = NULL, $previous = NULL, $exclude_app_id = NULL, $ecash_app = false, $preact = false)
		{
			if (!$this->Offers_Check($type))
			{
				throw new NotImplementedException("Check type not available.");
			}

			$result = FALSE;
			$cashline = FALSE;

			//Set Preact
			$this->preact = $preact;

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
					$react = TRUE;
					// Per bug ticket #2856, eCash3.0 apps will be marked as a react
					// if we match SSN only (not SSN and DOB anymore)
					$ecash3_react = TRUE;
					break;

				case self::TYPE_BANK_ACCOUNT:
					$function = 'Find_By_Account';
					break;

				case self::TYPE_HOME_PHONE:
					$function = 'Find_By_Home_Phone';
					break;

				case self::TYPE_BANK_ACCOUNT_DOB:
					$function = 'Find_By_Account_DoB';
					break;

				case self::TYPE_HOME_PHONE_DOB:
					$function = 'Find_By_Home_Phone_DoB';
					break;

				case self::TYPE_DRIVERS_LICENSE:
					$function = 'Find_By_Drivers_License';
					$react = TRUE;
					break;

				case self::TYPE_EMAIL:
					$function = 'Find_By_Email';
					$react = TRUE;
					break;

				case self::TYPE_SSN_DOB:
					$function = 'Find_By_SSN_DoB';
					$ecash3_react = TRUE; // ?
					break;

				case self::TYPE_EMAIL_DOB:
					$function = 'Find_By_Email_DoB';
					$ecash3_react = TRUE;
					break;
					
				case self::TYPE_EMAIL_SSN:
					$function = 'Find_By_Email_SSN';
					$ecash3_react = TRUE;
					break;

				default:
					// ghetto way to decide which check to run
					$function = (is_numeric($check_var) ? 'Find_By_SSN' : 'Find_By_Email');
					break;

			}

			// actually run the function now
			$cashline = $this->Run($function, $check_var);

			if ($cashline !== FALSE)
			{

				// this will count up the number of active, denied, etc.
				$results = $this->Count($cashline);

				// nuke react results if we're not allowed to use them
				if(!$react && !$ecash3_react)
				{
					unset($results[self::STATUS_REACT]);
				}

				// Clear out any eCash 3.0 properties if this is a eCash 2.7/Cashline react rule
				if ($react && !$ecash3_react && !empty($results[self::STATUS_REACT]) && !$ecash_app)
				{
					$results[self::STATUS_REACT] = array_intersect($results[self::STATUS_REACT], $this->prop_legacy);
				}

				// Clear out any eCash 2.7/Cashline properties if this is a eCash 3.0 react rule
				if ($ecash3_react && !$react && !empty($results[self::STATUS_REACT]))
				{
					$results[self::STATUS_REACT] = array_intersect($results[self::STATUS_REACT], $this->prop_converted);
				}

				// merge in previous results, if provided
				if (is_array($previous))
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
			$cashline = array();

			// proper format for call_user_func_array(...)
			if (!is_array($check_var)) $check_var = array($check_var);

			// run the Legacy version
			if (method_exists($this, $function) && count($this->prop_legacy))
			{
				$cashline = call_user_func_array(array($this, $function), $check_var);
			}

			// now check for the eCash 3.0 version
			$function .= '_Ecash3';

			if (method_exists($this, $function) && count($this->prop_converted))
			{
				// run the eCash 3.0 version
				$temp = call_user_func_array(array($this, $function), $check_var);

				// merge the results together
				$cashline = (isset($cashline) && is_array($cashline) && is_array($temp)) ? array_merge($cashline, $temp) : $temp;
			}
			elseif (!isset($cashline))
			{
				$cashline = array();
			}

			return $cashline;
		}


		/**
		 * Is Preactable
		 * 
		 * Determines whether the app is preactable or not
		 * <b>Note</b>: I'm sure right now you are looking at this function
		 * because ECash decided to change the rules a bit. Well I just wanted
		 * to let you know that I saw this coming from far off but was
		 * completely unable to stop it. If this rule were in the ECash API, you
		 * would not need to be editing this function and instead could be
		 * quietly enjoying some ice tea on a beach somewhere. Have a nice day!
		 * 
		 * @param int App ID
		 * @return boolean True if preactable
		 */
		public function Is_Preactable($app_id)
		{
			$payments_left = Scheduled_Payments_Left($app_id, $this->ldb);
			$payment_info = Pending_Payment_Info($app_id, $this->ldb);
			
			//If both conditions are there, simply don't add it to the result
			if($payment_info->pending_amount == -65.00 && $payments_left == 0 && $payment_info->pending_payments == 2)
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * Sets the provided list of application ID's to the /prospect/expired status in LDB.
		 *
		 * @param array $applications an array of application ID's to expire
		 * @return void
		 */
		protected function expireEcashApplications(array $applications)
		{
			if ($expired_status_id = array_pop($this->olp_mysql->Status_Glob('/prospect/expired')))
			{
				// We need to get another instance here because we're connection to the MASTER, not the
				// OLP read-only slave.
				$ldb_writer = Setup_DB::Get_Instance('mysql', BFW_MODE, $property);
				
				$query = "
					UPDATE
						application
					SET
						modifying_agent_id = (
							SELECT agent_id
							FROM agent
							WHERE login = 'olp' AND active_status = 'active'
						),
						application_status_id = {$expired_status_id}
					WHERE
						application_id IN (" . implode(', ', $applications) . ")";
				
				$result = $ldb_writer->Query($query);
			}
		}
		
		/**
		 * Sets the provided list of application ID's to the EXPIRED status in OLP.
		 *
		 * @param array $applications an array of application ID's to expire
		 * @return void
		 */
		protected function expireOLPApplications(array $applications)
		{
			$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY);
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->db, $applog);
					
			foreach ($applications as $app_id)
			{
				$applog->Write("Expiring App ID: {$app_id}, for App ID: {$this->application_id}");
				$app_campaign_manager->Update_Application_Status($app_id, 'EXPIRED');
			}
		}
		
	}

?>
