<?php
	/**
	 * @name BlackBox
	 * @version 0.2.0
	 * @author Andrew Minerd
	 * 
	 * @desc
	 * 	A new objectimified BlackBox structure. Before
	 * 	making any hacks, PLEASE examine the structure
	 * 	that is already built: it was designed to be
	 * 	pretty robust, and may already be capable	of
	 * 	what you need to do.
	 * 
	 * @todo
	 * 	- Clean up the constructors for BlackBox_Tier and
	 * 		BlackBox_Target
	*/

	// BLACKBOX STATS
	define('STAT_DIRECT_DEPOSIT', 'DIRECT_DEPOSIT');
	define('STAT_NO_DIRECT_DEPOSIT', 'NO_DIRECT_DEPOSIT');
	define('STAT_DAILY_LEADS', 'DAILY_LEADS');
	define('STAT_HOURLY_LEADS', 'HOURLY_LEADS');
	define('STAT_TOTAL_LEADS', 'TOTAL_LEADS');
	define('STAT_OVERFLOW_LEADS', 'OVERFLOW_LEADS');

	// BLACKBOX EVENTS
	define('EVENT_RULE_CHECK', 'RULE_CHECK');
	define('EVENT_STAT_CHECK', 'STAT_CHECK');
	define('EVENT_CASHLINE_CHECK', 'CASHLINE_CHECK');
	define('EVENT_USEDINFO_CHECK', 'USEDINFO_CHECK');
	define('EVENT_FILTER_CHECK', 'FILTER_CHECK');
	define('EVENT_DATAX_IDV', 'DATAX_IDV');
	define('EVENT_DATAX_IDV_REWORK', 'DATAX_IDV_REWORK');
	define('EVENT_DATAX_IC_IDVE', 'DATAX_IDVE_IMPACT');
	define('EVENT_DATAX_IFS_IDVE','DATAX_IDVE_IFS');
	define('EVENT_DATAX_PDX_REWORK', 'DATAX_PDX_REWORK');
	define('EVENT_DATAX_FUNDUPD_IMP','DATAX_FUNDUPD_IMP');
	define('EVENT_DATAX_IDVE_IPDL','DATAX_IDVE_IPDL');
	define('EVENT_DATAX_IDVE_ICF','DATAX_IDVE_ICF');
	define('EVENT_DATAX_PERF', 'DATAX_PERF');
	define('EVENT_DATAX_AALM','DATAX_AALM_PERF');
	define('EVENT_DATAFLUX_PHONE', 'DATAFLUX_PHONETYPE');
	define('EVENT_SOAP_NO_REF', 'SOAP_NO_REF');
	define('EVENT_PICK_WINNER', 'PICK_WINNER');
	define('EVENT_QUALIFY', 'QUALIFY');
	define('EVENT_TIER', 'TIER');
	define('EVENT_PASS', 'PASS');
	define('EVENT_FAIL', 'FAIL');
	define('EVENT_SKIP', 'DEBUG_SKIP');
	define('EVENT_START', 'START');
	define('EVENT_OVER_LIMIT', 'OVER_LIMIT');
	define('EVENT_WINNER', 'WINNER');
	define('EVENT_PREFIX_SIMULATE', 'REACT_');
	define('EVENT_VERIFY_POST', 'VERIFY_POST');
	define('EVENT_SECOND_LOAN', 'SECOND_LOAN');
	define('EVENT_NO_CHECK_SOCIAL', 'NO_CHECK_SOCIAL');
	define('EVENT_CFE_CHECK','CFE_RULES');

	// PROPERTIES
	//define('TARGET_RATE', 'Rate');

	// DEBUGGING OPTIONS
	define('DEBUG_RUN_CASHLINE', 'RUN_CASHLINE');
	define('DEBUG_RUN_USEDINFO', 'RUN_USEDINFO');
	define('DEBUG_RUN_DATAX_IDV', 'RUN_DATAX_IDV');
	define('DEBUG_RUN_DATAX_PERF', 'RUN_DATAX_PERF');
	define('DEBUG_RUN_RULES', 'RUN_RULES');
	define('DEBUG_RUN_STATS', 'RUN_STATS');
	define('DEBUG_RUN_FILTERS', 'RUN_FILTERS');
	define('DEBUG_RUN_ABA', 'RUN_ABA');
	define('DEBUG_RUN_PREACT_CHECK', 'RUN_PREACT_CHECK');
	define('DEBUG_RUN_CFE','RUN_CFE_CHECK');

	define('MODE_DEFAULT', 'BROKER');
	define('MODE_PREQUAL', 'PREQUAL');
	// email confirmation mode
	define('MODE_CONFIRMATION', 'CONFIRMATION');
	// online confirmation mode
	define('MODE_ONLINE_CONFIRMATION', 'ONLINE_CONFIRMATION');
	// Run during agree
	define('MODE_AGREE', 'AGREE');

	// ECash React Mode
	define('MODE_ECASH_REACT','ECASH_REACT');

	// Partner Weekly Vendor ID -- here so we don't hourly cap partner weekly
	define('PW_VENDOR_ID', 12881);


	require_once('blackbox.interfaces.php');

	// sub classes required by BlackBox
	require_once('blackbox.tier.php');
	require_once('blackbox.tier.1.php');
	require_once('blackbox.target.php');
	require_once('blackbox.parent.php');
	require_once('blackbox.rules.php');
	require_once('blackbox.stats.php');
	require_once('blackbox.datax.php');
	require_once('blackbox.datax.parser.php');

	require_once('blackbox.debug.php');
	require_once('blackbox.config.php');
	require_once('blackbox.picker.php');
	require_once('blackbox.collection.php');
	require_once('blackbox.tier.collection.php');
	require_once('blackbox.target.collection.php');
	require_once('blackbox.validation.php');
	require_once('blackbox.filters.php');
	require_once('Blackbox_CFE.php');

	// classes required for the various checks
	require_once(OLP_DIR . 'prev_customer/prev_customer_check.php');
	require_once(OLP_DIR . 'used_info.php');
	require_once(OLP_DIR . 'authentication.php');
	require_once(OLP_DIR . 'adverse_action.php');

	// library files
	require_once(BFW_CODE_DIR . 'Memcache_Singleton.php');
	require_once(BFW_CODE_DIR . 'Cache_Suppression_List.php');
	require_once BFW_CODE_DIR . 'OLP_Applog_Singleton.php';
	require_once('datax.2.php');

	class BlackBox_OldSchool implements iBlackBox_Serializable
	{

		private $tiers = NULL;	//BlackBox_Tier_Collection

		// strict rules: fail rules that
		// don't have the fields needed to run
		private $strict = TRUE;
		private $mode = MODE_DEFAULT;

		// winner information
		private $winner;
		private $tier;
		private $fund_amount;

		// previous lenders
		private $react;

		// used for the data_x calls
		private $datax;

		// Used to pass back the decision by datax module
		private $datax_decision;

		private $config;	//BlackBox_Config

		/**

			@desc BlackBox constructor

			@param $config stdClass Objects needed for BlackBox's operation:
				in most instances, this will consist of:

					$config->sql MySQL connection: required to retrieve the
						tier and target information
					$config->db2 DB2 connection: required for Check_Cashline
					$config->log Event_Log object
					$config->data Array of normalized form data

			@return None

		*/
		public function __construct(&$config = NULL, $load_tiers = TRUE, $preferred = NULL, $mode = NULL)
		{
			$this->Mode($mode);

			$this->react = array();

			//Create the BB_Config object
			$this->config = new BlackBox_Config_OldSchool($config, $this->mode, $this->react);

			if ($config)
			{
				$this->tiers = new BlackBox_Tier_Collection($this->config);

				if($load_tiers)
				{
					if (!is_array($preferred)) $preferred = NULL;

					$this->Get_All_Targets_and_Rules($preferred);
				}
			}
		}

		public function __destruct()
		{
			//Force call destructors instead of just unsetting
			$this->tiers->__destruct();
			$this->config->__destruct();
		}

		public function Mode($mode = NULL)
		{
			if (!is_null($mode)) $this->mode = $mode;
			return $this->mode;
		}

		public function Config()
		{
			return $this->config;
		}



		/**

			@desc Set whether BlackBox should apply
				strict rule checking: that is, whether
				or not rules should run if their data
				is missing.

		*/
		public function Strict_Rules($strict = NULL)
		{

			if (is_bool($strict)) $this->strict = $strict;
			return $this->strict;

		}

		public function Log_Event($name, $result, $target = NULL)
		{
			$this->config->Log_Event($name, $result, $target);
		}

		/**

			@desc Return winner information, if a winner
				has been selected

			@return array Winner information, or FALSE if
				a winner has not been selected.

					$winner['tier'] = The tier in which the winner lives
					$winner['target'] = The winner
					$winner['fund_amount'] = The amount we've been funded -
						currently, this will only be returned for a tier 1
						winner.

		*/
		public function Winner()
		{

			if ($this->winner)
			{

				$winner = array();

				if ($this->winner instanceof BlackBox_Preferred)
				{
					$winner['tier'] = $this->winner->Original_Tier();
				}
				else
				{
					$winner['tier'] = $this->tier;
				}
				
				//Since we override this for enterprise customers, we'll
				//store it here so that we can hit submitlevel stats properly.
				$winner['original_tier'] = $winner['tier'];

				if(Enterprise_Data::isEnterprise($this->winner->Name()))
				{
					$winner['tier'] = 1;
				}

				$winner['winner'] = $this->winner->Name();
				$fund_amount = $this->winner->Get_Fund_Amount();

				// only add this if it exists
				if ($fund_amount)
				{
					//For the new soap ecashapp, if the agent specifies a loan amount, and that amount
					//is less than the fund_amount, it will supercede the fund_amount.
					if(isset($_SESSION['data']['ecashapp']) && isset($_SESSION['data']['fund_amount'])
						&& intval($_SESSION['data']['fund_amount']) < $fund_amount)
					{
						$fund_amount = $_SESSION['data']['fund_amount'];
					}

					$winner['fund_amount'] = $fund_amount;
				}

				// If React or Testing a Ecash React App
				if ((is_array($this->config->react) && in_array(Enterprise_Data::resolveAlias($this->winner->Name()), $this->config->react)) ||
					($_SESSION['config']->ecash_react && (strtoupper($_SESSION['config']->mode) != "LIVE")))
				{
					$winner['react'] = TRUE;
				}

			}
			else
			{
				$winner = FALSE;
			}

			return $winner;
		}

		// return previous lenders
		public function React()
		{
			$react = $this->react;

			if ((!$react) || empty($react))
			{
				$react = FALSE;
			}

			return $react;

		}


		public function Snapshot($num = NULL)
		{
			return $this->config->debug->Snapshot($num);
		}



		/**
			@desc Return an array of tier objects

			@return array Array of tier objects
		*/
		public function Tiers()
		{
			return $this->tiers->Get_Tiers();
		}

		/**
			@desc Return a _reference_ to one tier
				object, or FALSE on error

			@param $tier_name integer The tier number

			@return  object BlackBox_Tier or FALSE
				upon failure
		*/
		public function Tier($tier_name)
		{

			return $this->tiers->Tier($tier_name);
		}


		/**
		 * @desc Finds a target from all the tiers and returns it.
		 *
		 * @param $target_name string
		 *
		 * @return object BlackBox_Target or FALSE if none are found.
		 */
		public function Get_Target($target_name)
		{
			return $this->tiers->Find_Target($target_name);
		}


		/**

			@desc Set BlackBox restrictions or exclusions.

				Depending on the value of $exclude, this function can
				serve two purposes. When $exclude = FALSE, you are
				setting restrictions, and ONLY the listed tiers AND
				targets, if they exist, will be used. When
				$exclude = TRUE, you are setting exclusions, and the
				listed tiers OR targets, if they exist,	will NEVER
				be used.

				It is important to note that, when restricting to
				certain targets, you are also restricting yourself to
				the tiers they exist within. Because of the nature
				of an exclusion, this does not occur (see examples #1
				and #4 below for more information). Also, when
				specifiying	a tier without targets, the actual
				value of the tier does not matter: it must simply
				exist as a key within $tier_names. Lastly, the
				wildcard '*' is supported (as a key) to	indicate
				that any tiers not listed are fair game.

				Example:

					// #1. restrict to UCL ONLY - this will remove
					// ALL other tiers and targets, including other
					// targets within tier 1
					$tier_names = array(1 => array('ucl'));
					$this->Restrict($tier_names, FALSE)

					// #2. restrict to all targets within tier 1 -
					// ALL other tiers will be removed
					$tier_names = array(1 => TRUE);
					$this->Restrict($tier_names, FALSE)

					// #3. restrict to ONLY UCL in tier 1, but
					// allow all other tiers
					$tier_names = array(1 => array('ucl'), '*');
					$this->Restrict($tier_names, FALSE);

					// #4. exclude tier 2: remove ONLY tier 2,
					// and use all other tiers
					$tier_names = array(2 => TRUE);
					$this->Restrict($tier_names, TRUE);

					// #5. exclude tier 2 target VP2 - ONLY VP2 is
					// removed, and other targets within teir 2 are
					// still available
					$tier_names = array(2 => array('VP2'));
					$this->Restrict($tier_names, TRUE);

			@param $tier_names array Associative array of tier names,
				as keys, and, optionally, target names in sub arrays.
			@param $exclude boolean Sets the function mode:
				restriction (FALSE) or exclusion (TRUE).

			@return None.

		*/
		public function Restrict($tier_names, $exclude = FALSE)
		{
			$this->tiers->Restrict_Tiers($tier_names, $exclude);
		}



		public function Withhold_Targets($targets = NULL)
		{
			if(!empty($targets))
			{
				$restrict_array = $this->tiers->Find_Targets($targets);
				
				if(!empty($restrict_array))
				{
					$this->tiers->Restrict_Tiers($restrict_array, TRUE);
				}
			}
		}




		/**
			@desc Pick a winning target: this is where all
				the logic comes together. The process essentially
				progresses from simpler (less intensive) to more
				difficult checks (requiring database access, etc.)
				as it loops	through all the tiers in the Use array.

					1. Run all rules for the open targets
					3. (Tier 1) Run the cashline check
					2. Check all the stats
					4. (Tier 1) Run the "used-info" check

				At this point, we are (hopefully) left with a few
				highly probable choices. However, if at any time
				during the above process we run out of target
				choices, the following checks will be skipped and
				the loop will move on to the next available tier.

				Should we have remaining open targets, we simply:

					5. Weigh remaining targets
					6. Pick a winner at random, according to weight

			@return string The winning target name

		*/
		public function Pick_Winner($reset = FALSE, $bypass_used_info = FALSE)
		{

			$winner = NULL;
			$valid = FALSE;

			if ($reset)
			{
				// reset
				$this->tiers->Reset();
				$this->winner = NULL;
				$this->tier = NULL;
				$this->fund_amount = NULL;
			}

			// save this for later
			$this->config->debug->Save_Snapshot('debug_opt', $this->config->debug->Get_Options());
			$this->config->debug->Save_Snapshot('use', $this->tiers->Get_Use());

			// time the calls
			$start = microtime(TRUE);

			// If they have a really nasty ABA,
			// we're going to decline them.
			$this->Bad_Aba();

			//Pass it off to the tier collection to find a winner
			//$this->winner and $this->tier are passed by reference
			$valid = $this->tiers->Find_Winner($this->winner, $this->tier, $bypass_used_info);

			$winner_name = NULL;
			if(!empty($this->winner))
			{
				$winner_name = $this->winner->Name();
			}
			
			//Check the winner_name to see if it's a calculated react or not
			//ecash_react check added because calculated_reacts are not ecash_reacts. (So Mail_Confirmation works for ecash_reacts) - GF#8017 [MJ]
			if(!empty($_SESSION['react_properties'])
				&& !$_SESSION['config']->ecash_react)
			{
				$react_props = array_map('strtoupper',$_SESSION['react_properties']);
				if(in_array(Enterprise_Data::resolveAlias($winner_name),$react_props))
				{
					$_SESSION['calculated_react'] = TRUE;
				}
			}

			// stop timer
			$time = (microtime(TRUE) - $start);
			$this->config->debug->Save_Snapshot('elapsed_time', $time);

			// log this
			$outcome = ($valid) ? EVENT_PASS : EVENT_FAIL;
			$this->Log_Event(EVENT_PICK_WINNER, $outcome, $winner_name);


			// create a new snapshot for
			// any subsequent runs
			$this->config->debug->New_Snapshot();

			return (!$valid) ? FALSE : $this->winner;
		}



		/**

			@desc A big-money function: this populates the
				entire Tier->Target->Rules structure.

			@param $sql object Connection to the MySQL server
			@param $tiers array Array of tier names to restrict to
			@param $target_names Array of targets to restrict to

			@return array Array of tier objects

		*/
		private function Get_All_Targets_and_Rules($preferred = NULL)
		{

			$tiers = array();

			if(isset($this->config))
			{
				// this query will probably need more testing: I'm not sure what will happen, for instance, if there
				// are two BY_DATE campaigns that are both ACTIVE... is this even allowed?
				// Need to add vender_qualify_post to the rules table [RL]
				// Add IF Propertry Short for Prefered Tiers
				$preferred_targets = (is_array($preferred) && !empty($preferred)) ? "(property_short IN ('".implode("', '", $preferred)."'))" : "0";

				$query_date = date('Y-m-d'); // Date for inclusion below

				// Changed to generate the date in PHP rather than using CURRENT_DATE(). Using
				// CURRENT_DATE() causes the query not to be cached and it has to re-executed. [BF]
				$query = "
					SELECT
						DISTINCT(target.target_id),
						UCASE( property_short ) AS property_short,
						tier.tier_number,
						{$preferred_targets} AS preferred,
						parent_target_id,
						weight_type,
						weekends,
						non_dates,
						bank_account_type,
						minimum_income,
						minimum_recur AS minimum_recur_email,
						minimum_recur AS minimum_recur_ssn,
						income_direct_deposit,
						excluded_states,
						restricted_states,
						excluded_zips,
						suppression_lists,
						income_frequency,
						force_promo_id,
						force_site_id,
						state_id_required,
						state_issued_id_required,
						minimum_recur,
						dd_check,
						minimum_age,
						identical_phone_numbers,
						identical_work_cell_numbers,
						paydate_minimum,
						filter AS filters,
						required_references,
						withheld_targets,
						operating_hours,
						income_type,
						datax_idv,
						vendor_qualify_post,
						verify_post_type,
						military,
						run_fraud,
						frequency_decline,
						max_loan_amount_requested, 
						min_loan_amount_requested, 
						residence_length, 
						employer_length, 
						residence_type,
						cur_campaign.campaign_id AS cur_id,
						cur_campaign.`limit` AS cur_limit,
						cur_campaign.hourly_limit AS cur_hourly_limit,
						cur_campaign.total_limit AS cur_total_limit,
						cur_campaign.limit_mult AS cur_limit_mult,
						campaign.`limit`,
						campaign.daily_limit,
						campaign.hourly_limit,
						campaign.limit_mult,
						campaign.lead_amount,
						campaign.percentage,
						campaign.dd_ratio,
						campaign.max_deviation,
						campaign.priority,
						campaign.overflow
					FROM
						target
						LEFT JOIN campaign AS cur_campaign
							ON cur_campaign.target_id = target.target_id
							AND cur_campaign.status = 'ACTIVE'
							AND cur_campaign.start_date <= '$query_date'
							AND cur_campaign.end_date >= '$query_date'
						INNER JOIN tier
							ON tier.tier_id = target.tier_id
						INNER JOIN rules
							ON rules.target_id = target.target_id
						INNER JOIN campaign
							ON campaign.target_id = target.target_id
					WHERE
						target.status = 'ACTIVE'
						AND target.deleted = 'FALSE'
						AND tier.status = 'ACTIVE'
						AND rules.status = 'ACTIVE'
						AND campaign.type = 'ONGOING'
						AND campaign.status = 'ACTIVE'
					ORDER BY preferred DESC, tier_number ASC";

				try
				{
					// Check to see if the targets already exist in memcache
					/*
						I don't like using the MD5 of the query as the key, but it seemed better
						than using the date & the preferred variable. [BF]
					*/
					$key = 'BT:' . md5($query);
					$target_list = Memcache_Singleton::Get_Instance()->get($key);
					
					if(!$target_list)
					{
						$result = $this->config->sql->Query($this->config->database, $query);
						
						$target_list = array();
						
						while($target_row = $this->config->sql->Fetch_Array_Row($result))
						{
							$target_list[] = $target_row;
						}
						
						// Store in memcache
						Memcache_Singleton::Get_Instance()->add($key, $target_list);
					}

					$use_overflow = true;
					$tiers = array();

					foreach($target_list as $row)
					{
						//Preferred targets go into tier 0
						$tier = (!empty($row['preferred'])) ? 0 : intval($row['tier_number']);
						
						//Set up the tier if it's not set up
						if(empty($tiers[$tier]))
						{
							$tiers[$tier] = array(
								'weight_type' => null,
								'targets' => array()
							);
						}
						
						// Moved Impact check to first choice in decision tree because ic_t1 is still treated as an ic account (tier 2) 
						// even though it is a tier one vendor. GForge #3034 [DW]
						//Impact
						if($this->config->Is_Impact($row['property_short']))
						{
							$target = new BlackBox_Target_Impact($this->config, $row, $row['tier_number']);
						}
						//GFORGE_3981 OLP/eCash API for generic eCash customers [TF]
						elseif($this->config->Is_Entgen($row['property_short']))
						{
							$target = new BlackBox_Target_Entgen($this->config, $row, $row['tier_number']);
						}
						elseif($this->config->Is_Agean($row['property_short']))
						{
							$target = new BlackBox_Target_Agean($this->config, $row, $row['tier_number']);
						}
						// GFORGE 6011 - blackbox vendors for UK [AuMa]
						elseif (preg_match('/\_uk\d*$/i',$row['property_short']))
						{
							$target = new BlackBox_Target_UK($this->config, $row, $row['tier_number']);
						}
						elseif($row['tier_number'] == 1)
						{
							switch(strtolower($row['property_short']))
							{
								case 'clk':
									$class = 'BlackBox_Parent_CLK';
									break;

								case 'ca':
								case 'd1':
								case 'pcl':
								case 'ucl':
								case 'ufc':
									if (strcasecmp($row['property_short'],'ufc') === 0)
									{
										$class = 'BlackBox_Target_UFC';
									}
									else 
									{
										$class = 'BlackBox_Target_CLK';
									}
									
									//All CLK targets must have overflow enabled in order for it to activate.
									if($use_overflow && $row['overflow'] != 1)
									{
										$use_overflow = false;
										unset($_SESSION['enable_overflow']);
									}
									
									break;
									
								case 'ccrt1':
									$class = 'BlackBox_Target_CCRT';
									break;
									
								default:
									$class = 'BlackBox_Preferred';
									break;
							}

							$target = new $class($this->config, $row, $row['tier_number']);
						}
						elseif($target_row['preferred'])
						{
							// we're a preferred target
							$target = new BlackBox_Preferred($this->config, $row, $row['tier_number']);
						}
						else
						{
							// create the new target object
							$target = new BlackBox_Target_OldSchool($this->config, $row);
						}
						
						$weight_type = (!empty($row['preferred'])) ? 'PRIORITY' : $row['weight_type'];
						$tiers[$tier]['weight_type'] = $weight_type;						
						$tiers[$tier]['targets'][$target->Get_Target_ID()] = $target;
					}


					foreach($tiers as $num => $tier)
					{
						$targets = new BlackBox_Target_Collection($this->config);

						foreach($tier['targets'] as $target_id => &$target)
						{
							if(($parent_id = $target->Get_Parent_ID()) !== 0
								&& isset($tier['targets'][$parent_id]))
							{
								$parent = $tier['targets'][$parent_id];
								$parent->Add_Child($target);
							}
							else
							{
								$targets->Add($target, true);
							}
						}

						switch($num)
						{
							case 1: $class = 'BlackBox_Tier_1'; break;
							default:$class = 'BlackBox_Tier'; break;
						}

						$this->tiers->Add(new $class($num, $targets, $tier['weight_type'], $this->config), TRUE);
					}
					
					//Let's enable overflow if we should, and we're not in customer service
					if($use_overflow && isset($this->config->config->overflow_targets) && !isset($_SESSION['cs']))
					{
						$_SESSION['enable_overflow'] = TRUE;
					}
				}
				catch (Exception $e)
				{
					unset($tiers);
					$tiers = FALSE;
				}

			}

			return $tiers;
		}

		
		public function Get_Target_List($in_use = true, $flat = true, $use_objects = false)
		{
			$list = array();
			foreach($this->tiers as $tier)
			{
				if($this->tiers->In_Use($tier->Name()))
				{
					$list[$tier->Name()] = $tier->Get_Target_List($in_use, $flat, $use_objects);
				}
			}
			return $list;
		}


		/**

			@desc Go to sleep: Slim-Fast for this object. Instead
				of serializing the object, we can serialize the
				returned array. This can save upwards of 2000+ bytes.

			@return array Associative array representing this object.

		*/
		public function Sleep()
		{

			$tiers = $this->tiers->Sleep();

			$blackbox = array();
			$blackbox['use'] = $this->tiers->Get_Use();
			$blackbox['tiers'] = $tiers;
			$blackbox['debug_opt'] = $this->config->debug->Get_Options();

			if ($this->winner)
			{
				$blackbox['winner'] = array(
					'winner' => $this->winner->Name(),
					'tier' => $this->tier,
					'fund_amount' => $this->winner->Get_Fund_Amount(),
				);
			}

			if ($this->config->debug->Get_Snapshot()) $blackbox['snapshot'] = $this->config->debug->Get_Snapshot();

			return($blackbox);

		}

		private function Valid($data)
		{

			$valid = is_array($data);

			if ($valid) $valid = (isset($data['tiers']) && is_array($data['tiers']));
			if ($valid) $valid = (isset($data['use']) && is_array($data['use']));
			if ($valid) $valid = (isset($data['debug_opt']) && is_array($data['debug_opt']));
			if ($valid) $valid = ((!isset($data['winner']) || (isset($data['winner']) && is_array($data['winner']))));
			if ($valid) $valid = ((!isset($data['snapshot']) || (isset($data['snapshot']) && is_array($data['snapshot']))));

			return($valid);

		}

		public function Restore($data, &$config)
		{

			$new_blackbox = FALSE;
			$static = FALSE;

			if (BlackBox_OldSchool::Valid($data))
			{

				if (isset($this) && ($this instanceof Blackbox_OldSchool))
				{

					$new_blackbox = &$this;

					if ($config)
					{
						$new_blackbox->config = new BlackBox_Config_OldSchool($config, $new_blackbox->mode, $new_blackbox->react);
					}

				}
				else
				{

					$new_blackbox = new BlackBox_OldSchool($config, FALSE);
					$static = TRUE;

				}

				if ($new_blackbox instanceof BlackBox_OldSchool)
				{
					$new_blackbox->config->debug->Set_Options($data['debug_opt']);

					if (isset($data['snapshot']))
					{
						$new_blackbox->config->debug->Set_Snapshot($data['snapshot']);
					}

					ksort($data['tiers']);

					$new_blackbox->tiers = BlackBox_Tier_Collection::Restore($data['tiers'], $new_blackbox->config);
					$new_blackbox->tiers->Use_Tier($data['use'], TRUE);

					if (isset($data['winner']))
					{

						$winner = $data['winner'];
						$new_blackbox->tier = $winner['tier'];

						// get our target object
						if($new_blackbox->tiers->Tier($winner['tier']))
						{
							$targets = $new_blackbox->Get_Target_List(false, true, true);
							$new_blackbox->winner = $targets[$winner['tier']][strtoupper($winner['winner'])];
						}

						if (isset($winner['fund_amount']))
						{
							$new_blackbox->fund_amount = $winner['fund_amount'];
						}

					}

				}

			}
			
			// if we didn't call this statically,
			// return TRUE or FALSE
			if (!$static)
			{
				unset($new_blackbox);
				$new_blackbox = ($new_blackbox instanceof BlackBox_OldSchool);
			}

			$blackbox_adapter = Blackbox_Adapter::getInstance(MODE_DEFAULT, $config);
			$blackbox_adapter->setBlackbox($new_blackbox, $new_blackbox->winner);

			return $blackbox_adapter;
			//return($new_blackbox);

		}


		public function Get_Possible_Winners($tier = null)
		{
			$targets = array();
			
			if(!empty($tier) && $this->tiers->In_Use($tier))
			{
				$tier = $this->tiers->Tier($tier);
				$targets = $tier->Get_Target_List();
			}
			else
			{
				$list = $this->Get_Target_List();
				
				foreach($list as $tier => $target_list)
				{
					$targets = array_merge($targets, $target_list);
				}
			}
			
			return $targets;
		}

		


		public function DataX_Track_Hash()
		{

			$track_hash = BlackBox_DataX::Track_Hash();
			if (is_null($track_hash)) $track_hash = FALSE;

			return $track_hash;

		}


		private function Bad_Aba()
		{
			//Bypass Bad_Aba check if social is on the agean nochecks list.
			//added for GForge #6393 [MJ] - modified for GForge #11375 [MJ]
			$bypass = (isset($_SESSION['no_checks_ssn'])) ? $_SESSION['no_checks_ssn'] : FALSE;
			
			if ($this->config->debug->Debug_Option(DEBUG_RUN_ABA) !== FALSE && !$bypass)
			{
				$result = Aba_Bad( @$this->config->data['bank_aba'], @$this->config->data['bank_account'] );
				
				$target_stats = OLPStats_Spaces::getInstance(
					$this->config->mode,
					0, // No specific target id for this check
					$this->config->bb_mode,
					$this->config->config->page_id,
					$this->config->config->promo_id,
					$this->config->config->promo_sub_code
				);
				
				if( $result )
				{
					$this->Use_Tier( NULL, FALSE );
					$this->Log_Event( 'ABA_BAD', 'FAIL' );
					if ($target_stats) $target_stats->hitStat('aba_check_fail');
				}
				else
				{
					$this->Log_Event( 'ABA_BAD', 'PASS' );
				}
			}
			else
			{
				$this->Log_Event('ABA_BAD', EVENT_SKIP);
			}

		}

		/**
		*	@desc simply returns the datax decision array.  Can be expanded to pass back any of the future dataX
		*		decisions
		*	@return 'Y' or 'N'
		*/
		public function Get_DataX_Decision()
		{
			$datax_decision = $this->tiers->Get_DataX_Decision();

			if (isset($_SESSION['data']['adverse']))
			{
				$datax_decision['DATAX_PERF'] = 'N';
			}

			return $datax_decision;
		}

		public function Get_Disallowed_States($target_name)
		{
			$disallowed_states = array();

			$target = &$this->tiers->Find_Target($target_name);

			if($target !== FALSE)
			{
				$disallowed_states = $target->Get_Disallowed_States();
			}

			return $disallowed_states;
		}

		public function Get_Limits($target_name)
		{

			$hourly_limits = array();

			$target = &$this->tiers->Find_Target($target_name);

			if($target !== FALSE)
			{
				$limits = $target->Get_Limits();
			}
			return $limits;
		}

		public function Use_Tier($tier_names = NULL, $use = NULL)
		{
			return $this->tiers->Use_Tier($tier_names, $use);
		}

		public function Debug_Option($name = NULL, $value = NULL)
		{
			return $this->config->debug->Debug_Option($name, $value);
		}



		public function Run_Cashline($prop_short)
		{
			$result = NULL;
			
			// Added target search for entgen targets to run cashline - GForge #7259 [DW]
			if ($this->config->Is_Impact($prop_short))
			{
				$target = &$this->tiers->Find_Target(strtoupper($prop_short));
			}
			elseif (Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, $prop_short))
			{
				$target = &$this->tiers->Tier(1);
			}
			elseif (Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_GENERIC, $prop_short))
			{
				$target = &$this->tiers->Find_Target(strtoupper($prop_short));
			}

			if ($target && method_exists($target, 'Run_Cashline'))
			{
				$result = $target->Run_Cashline();
			}

			return $result;
		}
		
		public function Run_Rule($prop_short, $type, $data)
		{
			$valid = false;
			$target = &$this->tiers->Find_Target(strtoupper($prop_short), true);
			
			if($target instanceof iBlackBox_Target)
			{
				$valid = $target->Run_Rule($type, $data);
			}
			
			return $valid;
		}
		
		public function Run_CFE($prop_short, $data, $config)
		{
			$valid = FALSE;
			$target = $this->tiers->Find_Target(strtoupper($prop_short), true);
			
			if ($target instanceof iBlackBox_Target)
			{
				$valid = $target->Run_CFE($config, $data);
			}
			return $valid;
		}


		/**
			For lifepayday.com
			This runs a check on the cell phone number to see if it's been
			submitted to the current site in the past 30 days, and then a
			check against DataFlux to verify that it is actually a cell number.
		*/
		public function Dupe_Cell_Check()
		{
			$result = false;

			try
			{
				$filter = new BlackBox_Filter_CellPhone('CellPhone', $this->config);
				$result = $filter->Check_Filter($this->config->data);
				$this->config->Log_Event('FILTER_CELLPHONE', ($result) ? EVENT_PASS : EVENT_FAIL);

				if($result)
				{
					$datax = new BlackBox_DataX($this->config);
					$result = $datax->Run(EVENT_DATAFLUX_PHONE, 'pw', BlackBox_DataX::SOURCE_NONE);
				}
			}
			catch(Exception $e)
			{
				$this->config->Applog_Write('Dupe_Cell_Check exception:' . $e->getMessage());
				$result = false;
			}

			$this->config->Log_Event('DUPE_CELL_CHECK', ($result) ? EVENT_PASS : EVENT_FAIL);

			return $result;
		}
		
	}

?>
