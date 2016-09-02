<?php

/**
 * An adapter that allows you to hook into the new Blackbox.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class Blackbox_Adapter_New extends Blackbox_Adapter
{
	/**
	 * Blackbox class
	 *
	 * @var Blackbox
	 */
	protected $blackbox;
	
	/**
	 * OLPBlackbox_DebugConf class
	 *
	 * @var OLPBlackbox_DebugConf
	 */
	protected $debug;

	/**
	 * OLPBlackbox_Data class
	 *
	 * @var OLPBlackbox_Data
	 */
	protected $data;

	/**
	 * OLPBlackbox_Config object
	 *
	 * @var OLPBlackbox_Config
	 */
	protected $config;

	/**
	 * The current winner from Blackbox
	 *
	 * @var OLPBlackbox_Winner
	 */
	protected $winner;

	/**
	 * These are the force winner targets provided by bb_force_winner.
	 *
	 * @var array
	 */
	protected $force_winner;

	/**
	 * Holds the current restrictions for the class.  This is necessary
	 * because of the way OLP currently sets up the restrictions.  Old
	 * Blackbox would create ALL the targets and tiers, and then systematically
	 * restrict them afterwards.  But new Blackbox requires the restrictions
	 * ahead of time, so we need to store them all here as they're created
	 * before we finally create the Blackbox class.
	 *
	 * @var array
	 */
	protected $restrictions = array(
		OLPBlackbox_DebugConf::TARGETS_RESTRICT => array(),
		OLPBlackbox_DebugConf::TARGETS_EXCLUDE => array(),
		OLPBlackbox_DebugConf::USE_TIER => array(),
		OLPBlackbox_DebugConf::EXCLUDE_TIER => array(),
	);

	/**
	 * New adapater's constructor.
	 *
	 * Changes the mode to MODE_DEFAULT if the mode is set to NULL. Otherwise it does the same
	 * thing as the parent constructor.
	 *
	 * @param string $mode Blackbox mode for this run
	 * @param object $config_data class containing the site config
	 */
	public function __construct($mode, $config_data)
	{
		if (is_null($mode))
		{
			$mode = MODE_DEFAULT;
		}

		parent::__construct($mode, $config_data);
	}

	/**
	 * Configures a new Blackbox object.
	 *
	 * @return void
	 */
	protected function preConfigure()
	{
		$this->setupConfig();
		$this->setupData();

		$this->debug = $this->config->debug;
	}

	/**
	 * Sets up variables in the OLPBlackbox_Config
	 *
	 * @return void
	 */
	protected function setupConfig()
	{
		$this->config = OLPBlackbox_Config::getInstance();
		$site_config = SiteConfig::getInstance()->asObject();

		//Add site config data to the OLPBlackbox_Config
		foreach ($site_config as $key => $value)
		{
			$this->config->$key = $value;
		}

		$this->config->blackbox_mode = $this->mode;
		$this->config->debug = new OLPBlackbox_DebugConf();
		$this->config->title_loan = $this->config_data->title_loan;
		$this->config->olp_db = $this->config_data->sql;
		$this->config->event_log = $this->config_data->log;
		$this->config->session = $this->config_data->session;
		$this->config->allowSnapshot = TRUE;
		$this->config->allow_datax_rework = $this->config_data->config->enable_rework;
		$this->config->do_datax_rework = !empty($_SESSION['do_datax_rework']);
		$this->config->return_visitor = !empty($_SESSION['return_visitor']);
		$this->config->track_key = $_SESSION['statpro']['track_key'];
		$this->config->space_key = $_SESSION['statpro']['space_key'];
		$this->config->hit_stats_bb = defined('STAT_SYSTEM_2') ? STAT_SYSTEM_2 : FALSE;
		$this->config->hit_stats_site = TRUE;

		// whether we're on the enterprise site; the actual
		// enterprise company is available via bb_force_winner?
		$this->config->is_enterprise = $this->config_data->is_enterprise;
		$this->config->react_company = $this->getReactCompany();

		$this->config->applog = OLP_Applog_Singleton::Get_Instance(
			'blackbox',
			1000000000,
			20,
			NULL,
			FALSE,
			002
		);
	}

	/**
	 * Looks at various sources to determine the company that's being reacted
	 *
	 * @return string
	 */
	protected function getReactCompany()
	{
		if (isset($this->config_data->data['ecashapp'])
			|| (isset($_SESSION['react_target'])
				&& $_SESSION['react_target'] !== FALSE))
		{
			$single_company = (isset($this->config_data->data['ecashapp']))
				? $this->config_data->data['ecashapp']
				: $_SESSION['react_target'];
			return $single_company;
		}
		elseif ($this->mode == MODE_ECASH_REACT)
		{
			return $this->config->property_short;
		}
		return NULL;
	}

	/**
	 * Sets up data for Blackbox_Data
	 *
	 * @return void
	 * @todo Check to make sure all the DOB variables are set correctly
	 * @todo Check the rest of the variables to make sure they are set if present.
	 * @todo DataX event types/source/account??
	 */
	protected function setupData()
	{
		$this->data = new OLPBlackbox_Data();

		$keys = $this->data->getKeys();
		$bb_data = $this->config_data->data;

		// Check for all of the valid keys and see
		// if we already have them in our data.
		foreach ($keys as $key)
		{
			if (isset($bb_data[$key]))
			{
				$this->data->$key = $bb_data[$key];
			}
			
			// If we say we don't have an account, set the account_type to NONE
			if (strcasecmp($key, 'bank_account_type') == 0 && isset($bb_data['dep_account'])
				&& strcasecmp($bb_data['dep_account'], 'NO_ACCOUNT') == 0)
			{
				$this->data->$key = 'NONE';
			}
			
			// Whenever we have bbx, we almost always have a direct deposit answer
			if (strcasecmp($key, 'income_direct_deposit') == 0
				&& ((isset($bb_data[$key]) && strcasecmp($bb_data[$key], '') == 0)
				|| !isset($bb_data[$key])))
			{
				$this->data->$key = 'NONE';
			}

			/**
			 * The income and direct deposit values, when running qualify in ONLINE_CONFIRMATION
			 * mode, will exist in the CS array in the session.
			 */
			if (strcasecmp($key, 'income_monthly_net') == 0
				&& !isset($this->data->$key)
				&& isset($_SESSION['cs']['income_monthly']))
			{
				$this->data->$key = $_SESSION['cs']['income_monthly'];
			}

			if (strcasecmp($key, 'income_direct_deposit') == 0
				&& !isset($this->data->$key)
				&& isset($_SESSION['cs']['income_direct_deposit']))
			{
				$this->data->$key = $_SESSION['cs']['income_direct_deposit'];
			}
			
			if (strcasecmp($key, 'income_frequency') == 0
				&& !isset($this->data->$key)
				&& isset($_SESSION['cs']['paydate']['frequency']))
			{
				$this->data->$key = $_SESSION['cs']['paydate']['frequency'];
			}
		}

		$this->data->application_id = $this->config_data->application_id;

		// We need to pass in all the permutations for a the bank account
		if (!empty($bb_data['bank_account']))
		{
			$this->data->permutated_bank_account_encrypted = $this->permutateAccount($bb_data['bank_account'], TRUE);
			$this->data->permutated_bank_account = $this->permutateAccount($bb_data['bank_account']);
		}

		// Flatten out the paydate model
		if (!empty($bb_data['paydate_model']))
		{
			foreach ($bb_data['paydate_model'] as $key => $value)
			{
				if (in_array($key, $keys))
				{
					$this->data->$key = $value;
				}
			}
		}
	}

	/**
	 * Generates all possible variations for a bank account.  A bank
	 * account number can have up to 17 numbers, potentially left-filled
	 * with zeroes.
	 *
	 * @param int $account The bank account number
	 * @param bool $encrypted TRUE if returning an encrypted list
	 * @return array A list of account number permutations
	 *
	 * @todo Probably should move Crypt stuff into olp_lib
	 */
	private function permutateAccount($account, $encrypted = FALSE)
	{
		$accounts = array();

		if ($encrypted)
		{
			$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object = Crypt_Singleton::Get_Instance($crypt_config['KEY'], $crypt_config['IV']);
		}

		// Setup an array of account numbers with prefixed 0's
		// Remove any leading 0's
		$account = ltrim($account, '0');

		// create all possible leading zero combinations for the bank account
		// only if the account number is not 17 digits
		for ($i = strlen($account); $i <= 17; $i++)
		{
			$new = sprintf("%0{$i}d", $account);
			$accounts[] = $encrypted
				? $crypt_object->encrypt($new)
				: $new;
		}

		return $accounts;
	}

	/**
	 * Function that is run after all the standard Blackbox processing
	 * has been completed.  This is necessary for the new Blackbox since
	 * it requires all the processing to be done before being created.
	 *
	 * @return void
	 */
	public function postConfigure()
	{
		foreach ($this->restrictions as $type => $restrictions)
		{
			if (!empty($restrictions))
			{
				$this->debug->setFlag($type, $restrictions);
			}
		}

		$this->config->force_winner = $this->force_winner;

		$factory = new OLPBlackbox_Factory_Legacy_OLPBlackbox();
		$this->blackbox = $factory->getBlackbox();
	}


	/**
	 * Gets or sets the current mode for Blackbox.
	 *
	 * @param string $mode Use the constants defined in blackbox.php
	 * @return string
	 */
	public function mode($mode = NULL)
	{
		if (!is_null($mode))
		{
			unset(OLPBlackbox_Config::getInstance()->blackbox_mode);
			OLPBlackbox_Config::getInstance()->blackbox_mode = $mode;
		}

		return OLPBlackbox_Config::getInstance()->blackbox_mode;
	}

	/**
	 * Sets up specific debug options inside of Blackbox.  These
	 * are flags passed in during the running of the application
	 * that can alter the behavior of Blackbox, such as bypassing
	 * rules checks.
	 *
	 * @param array $debug_opt An array of debug flags.
	 * @return void
	 */
	public function setDebugOptions($debug_opt)
	{
		$debug_map = array(
			DEBUG_RUN_CASHLINE	=> OLPBlackbox_DebugConf::PREV_CUSTOMER,
			DEBUG_RUN_USEDINFO	=> OLPBlackbox_DebugConf::USED_INFO,
			DEBUG_RUN_DATAX_IDV	=> OLPBlackbox_DebugConf::DATAX_IDV,
			DEBUG_RUN_DATAX_PERF=> OLPBlackbox_DebugConf::DATAX_PERF,
			DEBUG_RUN_RULES		=> OLPBlackbox_DebugConf::RULES,
			DEBUG_RUN_STATS => OLPBlackbox_DebugConf::LIMITS,
			DEBUG_RUN_ABA		=> OLPBlackbox_DebugConf::ABA,
			DEBUG_RUN_FILTERS	=> OLPBlackbox_DebugConf::FILTERS,
			'fraud_scan'		=> OLPBlackbox_DebugConf::FRAUD_SCAN,
			'no_checks'			=> OLPBlackbox_DebugConf::NO_CHECKS,

			// @todo This should not be set in the _debug_ configuration...
			DEBUG_RUN_PREACT_CHECK => OLPBlackbox_DebugConf::PREACT_CHECK,
		);

		foreach ($debug_opt as $option => $value)
		{
			if (isset($debug_map[$option]))
			{
				$this->debug->setFlag($debug_map[$option], $value);
			}
		}
	}

	/**
	 * Restrict or excludes targets and tiers.  To restrict or exclude
	 * specific targets, include a 'FIND' => array('prop1', 'prop2')
	 * list of targets.
	 *
	 * @param array $targets An array of targets/tiers to restrict.
	 * @param bool $restrict TRUE if you want to restrict to only the
	 * 	targets specified, FALSE if you want to exclude those targets.
	 *
	 * @return void
	 */
	public function restrict($targets, $restrict = TRUE)
	{
		// If we don't have a FIND index, we're only restricting tiers.
		if (empty($targets['FIND']))
		{
			$this->restrictTiers($targets, $restrict);
		}
		// If we only have a FIND index, we're debug restricting
		elseif (count($targets) == 1 && isset($targets['FIND']))
		{
			$restrict_type = ($restrict) ? OLPBlackbox_DebugConf::TARGETS_RESTRICT : OLPBlackbox_DebugConf::TARGETS_EXCLUDE;

			$targets['FIND'] = array_map('strtoupper', $targets['FIND']);

			if ($restrict_type == OLPBlackbox_DebugConf::TARGETS_EXCLUDE)
			{
				// We'll allow excluded targets to keep building up
				$this->restrictions[$restrict_type] = array_merge($this->restrictions[$restrict_type], $targets['FIND']);
			}
			else
			{
				$this->restrictions[$restrict_type] = $targets['FIND'];
			}
			unset($targets['FIND']);
		}
		// If we have tiers and a FIND index, we're doing our normal restrictions
		elseif (isset($targets['FIND']))
		{
			// We only expect to get here for bb_force_winner
			$this->force_winner = array_map('strtoupper', $targets['FIND']);
			unset($targets['FIND']);

			if (!empty($targets))
			{
				$this->restrictTiers($targets, $restrict);
			}
		}
	}

	/**
	 * Restricts and excludes tiers only.
	 *
	 * @param array $tiers An array of tiers to restrict.
	 * @param bool $restrict TRUE to restrict, FALSE to exclude.
	 *
	 * @return void
	 */
	public function restrictTiers($tiers, $restrict = TRUE)
	{
		if (is_array($tiers) && !empty($tiers))
		{
			// tiers either comes in as array(2 => 2) or array(2 => TRUE)
			// because of the way legacy olp works. normalize this.
			$tiers = array_keys($tiers);
		}

		// if you're "including" no tiers, realistically you're 
		// excluding all tiers and vice versa.
		if (is_array($tiers) && empty($tiers))
		{
			$tiers = $this->getTiers();
			$restrict = !$restrict;
		}

		if (!is_null($tiers))
		{
			$restrict_type = ($restrict) ? OLPBlackbox_DebugConf::USE_TIER : OLPBlackbox_DebugConf::EXCLUDE_TIER;
			$this->restrictions[$restrict_type] = $tiers;
		}
	}

	/**
	 * Returns the DataX decision for the last winner produced.
	 *
	 * @return array DataX decision (or empty array if there is no winner)
	 */
	public function getDataXDecision()
	{
		$datax_decision = array();
		
		if ($this->winnerExists())
		{
			$datax_decision = $this->winner->getStateData()->datax_decision;
		}

		return $datax_decision;
	}

	/**
	 * Returns the DataX track hash from the last winner produced.
	 *
	 * @return string Track hash (or empty string if there is no winner)
	 */
	public function getDataxTrackHash()
	{
		$track_hash = '';
		
		if ($this->winnerExists())
		{
			$track_hash = $this->winner->getStateData()->track_hash;
		}

		return $track_hash;
	}

	/**
	 * Picks a winner from Blackbox.
	 *
	 * @param bool $reset Reset the Blackbox object.
	 * @param bool $bypass_used_info Bypass the used_info check.
	 *
	 * @return OLPBlackbox_Winner|bool Will return FALSE if no valid winners are found.
	 */
	public function pickWinner($reset = FALSE, $bypass_used_info = FALSE)
	{
		//Blackbox_Data
		if ($bypass_used_info)
		{
			$this->debug->setFlag(OLPBlackbox_DebugConf::USED_INFO, FALSE);
		}

		try
		{
			$this->winner = $this->blackbox->pickWinner($this->data);
		}
		catch (OLPBlackbox_ReworkException $e)
		{
			// olp uses this session variable to know that it must reprocess
			// the application.
			$_SESSION['process_rework'] = TRUE;
			$this->winner = FALSE;
		}
		
		if ($this->winnerExists())
		{
			$this->winners[] = array(
				'campaign_name' => $this->winner->getStateData()->campaign_name,
				'target_name' => $this->winner->getStateData()->target_name
			);
			$this->winner->getCampaign()->getTarget()->setInvalid();
			
			if ($this->winner->getStateData()->partner_weekly_vetting_lead)
			{
				// gforge 9922, make sure that pw is the first look [DO]
				if ($this->winners[0] != 'pw')
				{
					array_unshift($this->winners, 'pw');
				}
			}

			OLPBlackbox_Config::getInstance()->event_log->Log_Event(
				'PICK_WINNER',
				'PASS',
				$this->winner->getStateData()->campaign_name
			);

			// do all session-related updates
			$this->updateSession($this->winner);
		}
		else
		{
			if ($this->blackbox->getStateData()->legacy_state_fail == TRUE)
			{
				// used by GForge #6972 [DY]
				$_SESSION['failure_reason'] = 'WVVAGA_CHECK';
			}
			
//			// We need to store the suppresion list failures for denial reasons sent to eCash
//			if (!empty($this->blackbox->getStateData()->suppression_list_failure))
//			{
//				$failure_list_obj = $this->blackbox->getStateData()->suppression_list_failure;
//				$failure_list = $failure_list_obj->get();
//				
//				foreach ($failure_list as $failure)
//				{
//					 Currently OLP is looking for this array type in the session. Once we feel
//					 more comfortable moving this out of the adapter, OLP can work with this directly
//					$_SESSION['SUPPRESSION_LIST_FAILURE'][$failure->field] = $failure->type;
//				}
//			}
			
			OLPBlackbox_Config::getInstance()->event_log->Log_Event(
				'PICK_WINNER',
				'FAIL'
			);
		}

		return $this->winner;
	}

	/**
	 * Gets info about the winner from Blackbox.
	 *
	 * @return array
	 * 	array(
	 * 		tier			//Tier for this target (Will be 1 if an enterprise company)
	 * 		original_tier	//The original tier for this target
	 * 		winner			//Property short of the winner
	 * 		fund_amount		//Fund amount qualified for
	 * 		react			//TRUE if this app is a react.
	 * 	)
	 */
	public function winner()
	{
		$winner = array();

		if ($this->winnerExists())
		{
			$property_short = $this->winner->getStateData()->campaign_name;

			/*
			 * Hacks ahoy.  OLP requires all enterprise customers
			 * to return tier 1 as of right now.
			 */
			$tier = $this->winner->getStateData()->tier_number;
			if (Enterprise_Data::isEnterprise($property_short))
			{
				$tier = 1;
			}

			$winner = array(
				'tier'			=> $tier,
				'original_tier'	=> $this->winner->getStateData()->tier_number,
				'winner'		=> $this->winner->getStateData()->campaign_name,
				'fund_amount'	=> $this->winner->getStateData()->qualified_loan_amount,
				'state_data' 	=> $this->winner->getStateData(),
			);
			
			if (!empty($this->winner->getStateData()->is_react))
			{
				$winner['react'] = (bool) $this->winner->getStateData()->is_react;
			}
		}

		return $winner;
	}
	
	
	/**
	 * Returns the current winner's property short.
	 * 
	 * @return string The property short of the winner
	 */
	public function getPropertyShort()
	{
		$property_short = NULL;
		
		if ($this->winnerExists())
		{
			$property_short = $this->winner->getStateData()->campaign_name;
		}
		
		return $property_short;
	}
	
	/**
	 * Determines whether the current winner is valid.
	 * 
	 * @return bool TRUE if winner object is valid.
	 */
	protected function winnerExists()
	{
		return ($this->winner instanceof Blackbox_IWinner);
	}

	/**
	 * Grabs the expected value for the rule and returns it.
	 *
	 * @param string $rule
	 * @param string $property_short
	 *
	 * @return mixed
	 */
	public function getRuleValue($rule, $property_short)
	{
		// Just for readability
		$sql_db = $this->config->olp_db;

		$query = "
			SELECT
				r.{$rule} AS rule_value
			FROM
				rules r
				INNER JOIN target t ON r.target_id = t.target_id
			WHERE
				t.property_short = '{$property_short}'
				AND t.deleted = 'FALSE'
				AND r.status = 'ACTIVE'";

		try
		{
			$row = $sql_db->Fetch_Array_Row($sql_db->Query($sql_db->db_info['db'], $query));

			return $row['rule_value'];
		}
		catch (Exception $e)
		{
			$this->config->applog->Write("Failed to get the expected rule value for ".$property_short."(".$rule.")");

			throw new Blackbox_Exception($e->getMessage());
		}
	}

	/**
	 * Runs an individual rule for a specific target.
	 *
	 * @param string $property_short The target you want to run the rule against.
	 * @param string $rule The name of the rule (as defined in Rules_From_Row in blackbox.target.php)
	 * @param mixed $value The value the rule needs to check against.
	 *
	 * @return bool The result of the rule.
	 */
	public function runRule($property_short, $rule, $value = NULL)
	{
		$rule_name = $rule;
		
		if ($rule == 'cashline')
		{
			$initial_data = array(
				'customer_history' => new OLPBlackbox_Enterprise_CustomerHistory(),
			);
			$state_data = new OLPBlackbox_Enterprise_TargetStateData($initial_data);

			$rule_factory = OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer::getInstance($property_short, $this->config);
			$rule = $rule_factory->getPreviousCustomerRule();

			return $rule->isValid($this->data, $state_data);
		}

		// Grab the expected value for the rule
		$rule_value = $this->getRuleValue($rule, $property_short);

		$rule_factory = OLPBlackbox_Factory_Legacy_Rule::getInstance($property_short);
		$rule = $rule_factory->getRule($rule, $rule_value);

		$state_data_values = array('campaign_name' => $property_short);
		$state_data = new OLPBlackbox_CampaignStateData($state_data_values);
		
		if ($rule instanceof Blackbox_IRule)
		{
			$valid = $rule->isValid($this->data, $state_data);
		}
		else
		{
			OLPBlackbox_Config::getInstance()->applog->Write(
				"Failed to instantiate rule $rule_name with $rule_value. Unserialized: "
				. print_r(unserialize($rule_value), TRUE)
			);
			$valid = TRUE;
		}
		
		return $valid;
	}

	/**
	 * Returns an individual target.
	 *
	 * @param string $name The property short of the target.
	 * @return OLPBlackBox_Target
	 */
	public function getTarget($name)
	{
		return ($this->winnerExists()) ? $this->winner->getTarget() : NULL;
	}

	/**
	 * Withholds targets from being chosen.  Certain vendors
	 * are set up so that if they reject a lead, we will not
	 * attempt to sell to some other vendors.  This is that
	 * process.
	 *
	 * @return void
	 */
	public function withholdTargets()
	{
		if ($this->winnerExists() && !empty($this->winner->getStateData()->withheld_targets))
		{
			if (empty($this->data->withheld_targets))
			{
				$this->data->withheld_targets = array();
			}

			$this->data->withheld_targets = array_unique(array_merge($this->data->withheld_targets, $this->winner->getStateData()->withheld_targets));
		}
	}

	/**
	 * Returns whether or not the current winner will allow
	 * its leads to be sold to list management
	 *
	 * @return bool
	 */
	public function sellToListManagement()
	{
		$sell = FALSE;

		if ($this->winnerExists())
		{
			$sell = $this->winner->getStateData()->list_mgmt_nosell;
		}

		return $sell;
	}

	/**
	 * Returns the current Blackbox snapshot
	 *
	 * @return stdClass The snapshot data
	 */
	public function getSnapshot()
	{
		$snapshot = NULL;

		if ($this->blackbox instanceof Blackbox)
		{
			$snapshot = $this->blackbox->getStateData()->snapshot;
		}

		return $snapshot;
	}

	/**
	 * Does all session updates after we've gotten a winner
	 *
	 * @param OLPBlackbox_Winner $winner
	 * @return void
	 */
	protected function updateSession(OLPBlackbox_Winner $winner)
	{
		$state = $this->winner->getStateData();

		// Horrible hack for is_fraud
		if (!empty($state->set_session))
		{
			foreach ($state->set_session as $key => $val)
			{
				$_SESSION[$key] = $val;
			}
		}

		if ($winner instanceof OLPBlackbox_Enterprise_Winner)
		{
			/* @var $winner OLPBlackbox_Enterprise_Winner */
			$history = $winner->getCustomerHistory();
			$_SESSION['CASHLINE_RESULTS'] = $history->getResults();
			$_SESSION['react_properties'] = $history->getPaidCompanies();
			$_SESSION['calculated_react'] = $winner->getIsReact();

			$dnl = $history->getDoNotLoan();
			$_SESSION['DNL_shorts'] = array_values($dnl);
			$_SESSION['is_DNL'] = (count($dnl) > 0);
		}
	}

	/**
	 * Gets the tier number for the current winner.
	 *
	 * @return string The tier for the current winner
	 */
	public function getWinnerTier()
	{
		return ($this->winnerExists()) ? $this->winner->getStateData()->tier_number : NULL;
	}
}

?>
