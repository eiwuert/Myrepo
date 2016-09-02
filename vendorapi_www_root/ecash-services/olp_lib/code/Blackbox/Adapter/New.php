<?php

/**
 * An adapter that allows you to hook into the new Blackbox.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class Blackbox_Adapter_New extends Blackbox_Adapter
{
	const TIMEOUT_EVENT = OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT;
	
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
	 * This is a hack for the CS run rule hack. Sometimes we need the state data.
	 *
	 * @var Blackbox_IStateData
	 */
	protected $state_data;
	
	/**
	 * Introduced to set a flag when the blackbox timeout event is sent.
	 * @var BFW_Subscriber_AppFlag
	 */
	protected $app_flag_subscriber;
	
	/**
	 * Introduced to hit an event on blackbox timeout events.
	 * @var BFW_Subscriber_Stat
	 */
	protected $stat_subscriber;

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
	 * Rework "stack" variable.
	 *
	 * This is just to record when exceptions are thrown for debugging. TEMPORARY.
	 * TODO: Remove this when debugging on live is done with.
	 *
	 * @var Session_Stack object
	 */
	protected $rework_stack;

	/**
	 * Another stack variable, but this time to change the process_rework flag in session.
	 *
	 * @var Session_Stack object
	 */
	protected $rework_exception_stack;


	/**
	 * New adapater's constructor.
	 *
	 * Changes the mode to MODE_BROKER if the mode is set to NULL. Otherwise it does the same
	 * thing as the parent constructor.
	 *
	 * @param string $mode Blackbox mode for this run
	 * @param object $config_data class containing the site config
	 */
	public function __construct($mode, $config_data)
	{
		if (is_null($mode))
		{
			$mode = OLPBlackbox_Config::MODE_BROKER;
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
		$this->rework_stack = new Session_Stack('process_rework', $this->config->applog);
		$this->rework_exception_stack = new Session_Stack('rework_exceptions', $this->config->applog);
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

		$this->config->application_id = $this->config_data->application_id;
		$this->config->blackbox_mode = $this->mode;
		$this->config->debug = new OLPBlackbox_DebugConf();
		$this->config->title_loan = $this->config_data->title_loan;
		$this->config->olp_db = $this->config_data->sql;
		$this->config->event_log = $this->config_data->log;
		$this->config->event_bus_log_file = $this->config_data->event_bus_log_file;
		$this->config->event_timer = $this->config_data->event_timer;
		$this->config->session = $this->config_data->session;
		$this->config->app_flags = $this->config_data->app_flags;
		$this->config->allowSnapshot = TRUE;
		$this->config->allow_datax_rework = $this->config_data->config->enable_rework;
		$this->config->do_datax_rework = !empty($_SESSION['do_datax_rework']);
		$this->config->return_visitor = !empty($_SESSION['return_visitor']);
		$this->config->track_key = Stats_OLP_Client::getInstance()->getTrackKey();
		$this->config->space_key = Stats_OLP_Client::getInstance()->getSpaceKey();
		$this->config->hit_stats_bb = defined('STAT_SYSTEM_2') ? STAT_SYSTEM_2 : FALSE;
		$this->config->hit_stats_site = TRUE;
		$this->config->bypass_withheld_targets = isset($_SESSION['bypass_withheld_targets']) ? $_SESSION['bypass_withheld_targets'] : FALSE;
		
		// Used to log individual suppression lists in the event log
		$this->config->log_lists = $this->config_data->log_lists;
		$this->config->log_stats = $this->config_data->log_stats;

		$this->config->preferred_tier = (defined('PREFERRED_TIER')) ? PREFERRED_TIER : 'preferred';

		// whether we're on the enterprise site; the actual
		// enterprise company is available via bb_force_winner?
		$this->config->is_enterprise = $this->config_data->is_enterprise;
		$this->config->react_company = $this->getReactCompany();
		
		$this->config->is_cs_react = $this->config_data->is_cs_react;

		$this->config->applog = OLP_Applog_Singleton::Get_Instance(
			'blackbox',
			1000000000,
			20,
			NULL,
			FALSE,
			002
		);
		
		$this->config->memcache = Cache_Memcache::getInstance();
		
		$this->config->capped_stats = $this->config_data->capped_stats;
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
		elseif ($this->mode == OLPBlackbox_Config::MODE_ECASH_REACT || $_SESSION['config']->ecash_react)
		{
			return $this->config->property_short;
		}
		elseif (
			($this->mode == OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION
				|| $this->mode == OLPBlackbox_Config::MODE_AGREE)
			&& $this->config_data->is_react)
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

			// loan_amount_desired isn't required, so if its empty it will cause
			// issues with the min_loan_amount_requested rule, just unset it from data.
			if (strcasecmp($key, 'loan_amount_desired') == 0
				&& empty($bb_data[$key]))
			{
				$this->data->$key = NULL;
			}
		}

		OLP_Data_Normalizer::deNormalize($this->data);

		$this->data->track_key = $this->config->track_key;

		if (is_numeric($_SESSION['react']['transaction_id']))
		{
			$this->data->react_app_id = $_SESSION['react']['transaction_id'];
		}

		$this->data->application_id = $this->config_data->application_id;
		$this->data->session_id = session_id();

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
		//****
		// Adding these keys manually because the
		// it is not available in the bb_data array
		//****
		$this->data->promo_id = $this->config->promo_id;
		$this->data->site_name = $this->config->site_name;
		$this->data->promo_sub_code = $this->config->promo_sub_code;
		if (empty($this->config->promo_sub_code))
		{
			$this->data->promo_and_sub_code = $this->config->promo_id;
		}
		else
		{
			$this->data->promo_and_sub_code = $this->config->promo_id . '-' . $this->config->promo_sub_code;
		}

		if (!empty($_SESSION['data']['fund_amount']))
		{
			$this->data->loan_amount_desired = $_SESSION['data']['fund_amount'];
		}

		// Adding card loan from session for non-CFE companies
		$this->data->card_loan =
			($_SESSION['data']['loan_type'] == 'card' || $_SESSION['cs']['loan_type'] == 'card');

		$this->data->test_app = $this->config->app_flags->flagExists(OLP_ApplicationFlag::TEST_APP);
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
		
		$factory = new OLPBlackbox_Factory_OLPBlackbox();
		
		$timeout = isset(SiteConfig::getInstance()->asObject()->blackbox_timeout)
			? SiteConfig::getInstance()->asObject()->blackbox_timeout
			: FALSE;
		
		// if we're already over our timeout, don't even assemble blackbox
		if ($timeout && $this->requestTimePassed() >= $timeout)
		{
			$this->blackbox = $this->getTimeoutBlackbox($factory);
		}
		else 
		{
			$this->blackbox = $factory->getBlackbox($this->getRootPropertyShort());
			
			if ($this->blackbox instanceof OLPBlackbox_Root)
			{
				if ($timeout)
				{
					// re-requestTimePassed() since building blackbox takes roughly 9 years
					$this->blackbox->setTimeout($timeout - $this->requestTimePassed());
				}
				
				if ($this->blackbox->getEventBus() instanceof OLP_IEventBus)
				{
					$this->setupEventListeners($this->blackbox->getEventBus());
				}
			}
		}
	}
	
	/**
	 * Return an empty version of OLPBlackbox_Root if there's not enough time
	 * to actually run regular blackbox.
	 * 
	 * We also "trick" our event subscribers into hitting timeout stats/flags.
	 * 
	 * @param OLPBlackbox_Factory_OLPBlackbox $factory The factory to use to
	 * assemble an empty blackbox.
	 * @return OLPBlackbox_Root
	 */
	protected function getTimeoutBlackbox(OLPBlackbox_Factory_OLPBlackbox $factory)
	{
		$blackbox = $factory->getEmptyBlackbox();
		$bus = new OLP_EventBus();
		$this->setupEventListeners($bus);
		$bus->notify(new OLPBlackbox_Event(self::TIMEOUT_EVENT));
		
		return $blackbox;
	}
	
	/**
	 * Set up any subscribers that need to listen for notifications on the 
	 * blackbox event bus.
	 * @param OLP_IEventBus $bus The bus to listen to for blackbox events.
	 * @return void
	 */
	protected function setupEventListeners(OLP_IEventBus $bus)
	{
		$this->app_flag_subscriber = new BFW_Subscriber_AppFlag($this->config_data->app_flags);
		$this->app_flag_subscriber->writeFlagOnEvent('lead_sell_timeout', self::TIMEOUT_EVENT);
		
		/**
		 * register to the event miliatary total failure
		 */
		$this->app_flag_subscriber->writeFlagOnEvent(
			'military_total_fail',
			OLPBlackbox_Event::EVENT_GLOBAL_MILITARY_FAILURE,
			array(
				OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_RULE,
				'class' => 'OLPBlackbox_Rule_AllowMilitary',
			)
		);
		$bus->subscribeTo(OLPBlackbox_Event::EVENT_GLOBAL_MILITARY_FAILURE, $this->app_flag_subscriber);

		$this->stat_subscriber = new BFW_Subscriber_Stat(Stats_OLP_Client::getInstance());
		$this->stat_subscriber->writeStatOnEvent('lead_sell_timeout', self::TIMEOUT_EVENT);
		
		// technically, we don't have to retain copies of these subscribers since
		// they're referenced by the event bus, but it's visibility I guess.
		$bus->subscribeTo(self::TIMEOUT_EVENT, $this->app_flag_subscriber);
		$bus->subscribeTo(self::TIMEOUT_EVENT, $this->stat_subscriber);
	}
	
	/**
	 * How many seconds have passed since the HTTP request started.
	 * @return int
	 */
	protected function requestTimePassed()
	{
		return time() - $_SERVER['REQUEST_TIME'];
	}

	/**
	 * Returns the root property short.
	 *
	 * @return string
	 */
	public function getRootPropertyShort()
	{
		// only CLK campaigns should run rules in prequal mode [#22684]
		if ($this->mode() == OLPBlackbox_Config::MODE_PREQUAL)
		{
			return 'clk_prequal';
		}

		if ($this->debug->getFlag(OLPBlackbox_DebugConf::ROOT_PROPERTY_SHORT))
		{
			return $this->debug->getFlag(OLPBlackbox_DebugConf::ROOT_PROPERTY_SHORT);
		}
		
		if (isset($this->config->root_property_short))
		{
			return $this->config->root_property_short;
		}

		return 'pw';
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
		foreach ($debug_opt as $option => $value)
		{
			$this->debug->setFlag($option, $value);
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
		// If we don't have a FIND or ROOT index, we're only restricting tiers.
		if (empty($targets['FIND']) && empty($targets['ROOT']))
		{
			$this->restrictTiers($targets, $restrict);
		}
		// If we only have a FIND index, we're debug restricting
		elseif (count($targets) == 1 && isset($targets['FIND']))
		{
			$restrict_type = ($restrict) ? OLPBlackbox_DebugConf::TARGETS_RESTRICT : OLPBlackbox_DebugConf::TARGETS_EXCLUDE;

			$targets['FIND'] = is_array($targets['FIND']) ? array_map('strtoupper', $targets['FIND']) : array(strtoupper($targets['FIND']));

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
		// If we have a ROOT index and not restricting by anything else,
		//   Restrict by root property short.
		elseif (isset($targets['ROOT']) && $restrict)
		{
			$this->restrictions[OLPBlackbox_DebugConf::ROOT_PROPERTY_SHORT] = $targets['ROOT'];
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
	 * Reset all restrictions.
	 *
	 * @param string $type Only reset specific restriction
	 * @return void
	 */
	public function resetRestrictions($type = NULL)
	{
		if ($type === NULL)
		{
			$this->restrictions = array(
				OLPBlackbox_DebugConf::TARGETS_RESTRICT => array(),
				OLPBlackbox_DebugConf::TARGETS_EXCLUDE => array(),
				OLPBlackbox_DebugConf::USE_TIER => array(),
				OLPBlackbox_DebugConf::EXCLUDE_TIER => array(),
			);
		}
		elseif (!empty($this->restrictions[$type]))
		{
			$this->restrictions[$type] = array();
		}
	}
	
	/**
	 * Get the blackbox data
	 *
	 * @return OLPBlackbox_Data
	 */
	public function getData()
	{
		if (!$this->data instanceof OLPBlackbox_Data)
		{
			$this->setupData();
		}
		return $this->data;
	}

	/**
	 * Returns the DataX decision for the last winner produced.
	 *
	 * @return array DataX decision (or empty array if there is no winner)
	 */
	public function getDataXDecision()
	{
		$uw_decision = array();

		if ($this->winnerExists())
		{
			$uw_decision = $this->winner->getStateData()->uw_decision;
		}

		return $uw_decision;
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

		if (FALSE == $this->blackbox)
		{
			return FALSE;
		}

		try
		{
			$this->winner = $this->blackbox->pickWinner($this->data);
		}
		catch (OLPBlackbox_ReworkException $e)
		{
			// olp uses this session variable to know that it must reprocess
			// the application.
			$this->rework_stack->setTrue();

			$this->winner = FALSE;

			// save so that when sleep() is called the correct info is saved
			$this->rework_info = $e->Info;

			// record that we caught an event
			$this->rework_exception_stack->setTrue();
		}

		if ($this->winnerExists())
		{
			$this->winners[] = array(
				'campaign_name' => $this->winner->getStateData()->campaign_name,
				'target_name' => $this->winner->getStateData()->target_name
			);
			$this->winner->getCampaign()->getTarget()->setInvalid();

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
			// If failed on a global rule, store it in the session
			if (!empty($this->blackbox->getStateData()->global_rule_failure))
			{
				$_SESSION['global_rule_failure'] = $this->blackbox->getStateData()->global_rule_failure;
			}

			if ($this->blackbox->getStateData()->failure_reasons instanceof OLPBlackbox_FailureReasonList)
			{
				// we should not hit this code more than once since failure_reasons are only set up during
				// ecash reacts (or possibly CS reacts eventually), so there should only be one target.
				$_SESSION['ECASH_REACT_ERROR'] = array();
				foreach ($this->blackbox->getStateData()->failure_reasons as $reason)
				{
					$_SESSION['ECASH_REACT_ERROR'][] = $reason->getDescription();
				}
			}

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
			if (EnterpriseData::isEnterprise($property_short))
			{
				$tier = 1;
			}

			// NOTE: Please do not add to this array if possible, the same
			// information can be gotten in olp via {@see OLP::getWinnerData()}
			$winner = array(
				'tier'            => $tier,
				'original_tier'   => $this->winner->getStateData()->tier_number,
				'winner'          => $this->winner->getStateData()->campaign_name,
				'fund_amount'     => $this->winner->getStateData()->qualified_loan_amount,
				'failure_reasons' => $this->winner->getStateData()->failure_reasons,
				'loan_actions'    => $this->winner->getStateData()->getCombined('loan_actions'),
				'target_tags'     => array_unique($this->winner->getStateData()->getCombined('target_tags')->getData()),
			);

			if (!empty($this->winner->getStateData()->is_react))
			{
				$winner['react'] = (bool)$this->winner->getStateData()->is_react;
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
	 * Runs a legacy state check
	 *
	 * @param string $property_short
	 * @return boolean
	 */
	protected function runLegacyStateCheck($property_short)
	{
		static $excluded_states = array('WV', 'VA', 'GA',);
		$coll = new OLPBlackbox_RuleCollection();
		$state_data = new OLPBlackbox_StateData();
		$state_data->addStateData(
			new OLPBlackbox_CampaignStateData(
				array('campaign_name' => $property_short)
			)
		);

		foreach ($excluded_states as $state)
		{
			$coll->addRule(new OLPBlackbox_Rule_LegacyStateExclude($state));
		}

		return $coll->isValid($this->data, $state_data);
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
		$this->state_data = NULL;

		if ($rule == 'cashline')
		{
			$initial_data = array(
				'name' => $property_short, // Technically, CLK should be used instead of the property to be consistent
			);
			$this->state_data = new OLPBlackbox_Enterprise_TargetStateData($initial_data);

			$rule_factory = OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer::getInstance($property_short, $this->config);
			$rule = $rule_factory->getPreviousCustomerRule();

			$result = $rule->isValid($this->data, $this->state_data);

			return $result;
		}
		elseif (!strcasecmp($rule, 'cfe'))
		{
			$rule = new OLPBlackbox_Enterprise_Generic_Rule_CFE();
			$state_data_values = array('campaign_name' => $property_short);
			$this->state_data = new OLPBlackbox_CampaignStateData($state_data_values);
			$valid = $rule->isValid($this->data, $this->state_data);
			// If we're valid, make sure CFE didn't invalidate us.
			if (!$valid)
			{
				OLPBlackbox_Config::getInstance()->applog->Write("CFE Returned invalid for {$data->application_id}");
			}
			return $valid;
		}
		elseif (!strcasecmp($rule, 'legacy_state_exclude'))
		{
			return $this->runLegacyStateCheck($property_short);
		}

		// Grab the expected value for the rule
		$rule_value = $this->getRuleValue($rule, $property_short);

		$rule_factory = OLPBlackbox_Factory_Legacy_Rule::getInstance($property_short);
		$rule = $rule_factory->getRule($rule, $rule_value);

		$state_data_values = array(
			'campaign_name' => $property_short,
		);
		$this->state_data = new OLPBlackbox_CampaignStateData($state_data_values);

		if ($rule_name == 'suppression_lists')
		{
			$this->state_data->addStateData(new OLPBlackbox_StateData(array('failure_reasons' => new OLPBlackbox_FailureReasonList())));
		}

		if ($rule instanceof Blackbox_IRule)
		{
			$valid = $rule->isValid($this->data, $this->state_data);
			// If we're the suppression_lists rule, add it ti the failurereasons
			if ($rule_name == 'suppression_lists')
			{
				$failures = $state_data->failure_reasons;
				if ($failures instanceof OLPBlackbox_FailureReasonList && !$failures->isEmpty())
				{
					foreach ($failures as $key => $val)
					{
						if ($val instanceof OLPBlackbox_FailureReason_Suppression)
						{
							$_SESSION['SUPPRESSION_LIST_FAILURE'][strtoupper($val->field)] = $val->type;
						}
					}
				}
			}
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
	 * Returns the state data that was used in the previous runRule() instance.
	 *
	 * @return Blackbox_IStateData
	 */
	public function getLastRunRuleStateData()
	{
		return $this->state_data;
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
	public function addWithheldTargets()
	{
		if ($this->winnerExists())
		{
			$this->winner->getTarget()->addWithheldTargets($this->data);
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

	/**
	 * Returns the state data from the blackbox object.
	 *
	 * @return Blackbox_IStateData object or NULL.
	 */
	public function getStateData()
	{
		return $this->blackbox->getStateData();
	}
	
	/**
	 * Returns the lender post persistent data from the state data
	 *
	 * @return array
	 */
	public function getLenderApiPersistentData()
	{
		$persitent_data = array();
		if ($this->blackbox instanceof Blackbox_Root
			&& is_array($this->blackbox->getStateData()->lender_post_persistent_data))
		{
			$persitent_data = $this->blackbox->getStateData()->lender_post_persistent_data;
		}
		return $persitent_data;
	}
	
	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		$sleep_data = parent::sleep();
		if ($this->winnerExists())
		{
			$sleep_data['winner'] = $this->winner->getCampaign()->sleep();
		}
		return $sleep_data;
	}

	/**
	 * Restore the runtime state to from a previous sleep 
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data)
	{
		parent::wakeup($data);
		if (!empty($data['winner']))
		{
			$target = new OLPBlackbox_Target('winner', NULL);
			$campaign = new OLPBlackbox_Campaign('winner', NULL, NULL, $target);
			$campaign->wakeup($data['winner']);
			$this->winner = new OLPBlackbox_Winner($campaign);
		}
	}

	/**
	 * Get the event log from the adapter
	 *
	 * @return Event_Log
	 */
	protected function getEventLog()
	{
		return isset($this->config_data->log) ? $this->config_data->log : NULL;
	}
}

?>
