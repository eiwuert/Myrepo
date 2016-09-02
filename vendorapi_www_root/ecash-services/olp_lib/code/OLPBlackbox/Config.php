<?php
/**
 * Subclass of Blackbox_Config that adds some constants specific to OLP.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Config extends Blackbox_Config
{
	/**
	 * Main selling mode for OLP Blackbox.
	 *
	 * @var string
	 */
	const MODE_BROKER = 'BROKER';

	/**
	 * Pre-qualification mode.
	 *
	 * Pre-qualification is a mode wherein we try to determine if
	 * CLK might be interested in the app and it determines if
	 * CLK pays the front end vendors for the potential to buy the lead.
	 * At least... that's kind of what I remember from talking to Andrew.
	 *
	 * @var string
	 */
	const MODE_PREQUAL = 'PREQUAL';

	/**
	 * Confirmation mode for blackbox.
	 *
	 * @var string
	 */
	const MODE_CONFIRMATION = 'CONFIRMATION';

	/**
	 * Online Confirmation mode for blackbox.
	 *
	 * @var string
	 */
	const MODE_ONLINE_CONFIRMATION = 'ONLINE_CONFIRMATION';

	/**
	 * Agree mode for blackbox.
	 *
	 * @var string
	 */
	const MODE_AGREE = 'AGREE';

	/**
	 * Ecash React Mode.
	 *
	 * @var string
	 */
	const MODE_ECASH_REACT = 'ECASH_REACT';

	/**
	 * Username for hitting Blackbox Rules.
	 *
	 * @var int
	 */
	const STATS_BBRULES = 'bbrule';

	/**
	 * Constant for PASS stats.
	 */
	const STAT_RESULT_PASS = 'pass';

	/**
	 * Constant for FAIL stats.
	 */
	const STAT_RESULT_FAIL = 'fail';

	/**
	 * Signifies a rule result was valid.
	 *
	 * Stored in the event log in the database.
	 *
	 * @var string
	 */
	const EVENT_RESULT_PASS = 'PASS';

	/**
	 * Signifies failure for a rule.
	 *
	 * Stored in the event log in the database.
	 *
	 * @var string
	 */
	const EVENT_RESULT_FAIL = 'FAIL';

	/**
	 * Signifies that a rule was skipped.
	 *
	 * Stored in the event log in the database.
	 *
	 * @var
	 */
	const EVENT_RESULT_DEBUG_SKIP = 'debug_skip';

	/**
	 * Signifies the rule was skipped due to a condition
	 *
	 * @var string
	 */
	const EVENT_RESULT_CONDITIONAL_SKIP = 'conditional_skip';
	
	/**
	 * Skip result for event log entries.
	 */
	const EVENT_RESULT_SKIP = 'skip';

	/**
	 * Signifies that a rule could not complete.
	 *
	 * Stored in the event log in the database.
	 *
	 * @var string
	 */
	const EVENT_RESULT_ERROR = 'ERROR';

	/**
	 * Event for overall rules.
	 */
	const EVENT_RULES = 'RULE_CHECK';

	/**
	 * Event for limit check.
	 */
	const EVENT_LIMITS = 'STAT_CHECK';

	/**
	 * Stat cap limit
	 */
	const EVENT_STAT_CAP = 'STAT_CAP';

	/**
	 * Actually DOW limit
	 */
	const EVENT_DAILY_LIMIT = 'DAILY_LEADS';

	/**
	 * Event for hourly limit
	 */
	const EVENT_HOURLY_LIMIT = 'HOURLY_LEADS';

	/**
	 * Suppression list event.
	 *
	 * This event is hit for the suppression list rule collection.
	 */
	const EVENT_SUPPRESSION = 'SUPPRESS_LISTS';

	/**
	 * The frequency score rule event.
	 */
	const EVENT_FREQUENCY_SCORE = 'FREQ_SCORE';

	/**
	 * The withheld targets rule event.
	 */
	const EVENT_WITHHELD_TARGETS = 'WITHHELD_TARGETS';

	/**
	 * Event for the ABA check which replaced UsedInfo.
	 *
	 * @var string
	 */
	const EVENT_USED_ABA_CHECK = 'USED_ABA_CHECK';

	/**
	 * Event for the previous customer check
	 */
	const EVENT_PREV_CUSTOMER = 'CASHLINE_CHECK';

	/**
	 * Event log entry for qualify rules.
	 *
	 * @var string
	 */
	const EVENT_QUALIFY = 'QUALIFY';

	/**
	 * Stat for the previous customer check
	 *
	 * @var string
	 */
	const STAT_PREV_CUSTOMER = 'prevcust';

	
	/**
	 * ABA Check added to root collection.
	 *
	 * @var string
	 */
	const EVENT_ABA_BAD = 'ABA_BAD';
	
	/**
	 * This is the lender post price point rule 
	 *
	 * @var string
	 */
	const EVENT_PRICE_POINT = 'PRICE_POINT';
	
	/**
	 * 
	 * @var string
	 */
	const EVENT_PRICE_POINT_RECUR = 'PRICE_POINT_RECUR';

	/**
	 * Global rules defined in BbxAdmin.
	 * 
	 * @var string
	 */
	const EVENT_GLOBAL_MILITARY_RULES = 'GLOBAL_MILITARY_RULES';

	/**
	 * Valid data sources for rule conditions.
	 */
	const DATA_SOURCE_CONFIG = 'OLPBlackbox_Config';
	const DATA_SOURCE_BLACKBOX = 'Blackbox_Data';
	const DATA_SOURCE_STATE = 'Blackbox_IStateData';
	
	/**
	 * Determines if the string passed in names a valid rule conditional source
	 * class.
	 * 
	 * @param string $source The data source by class.
	 * @return bool TRUE if it's a valid data source class.
	 */
	public static function isValidDataSource($source)
	{
		$class_constants = new ClassConstants(__CLASS__);
		return in_array($source, $class_constants->keyStartsWith('DATA_SOURCE_'));
	}
	
	/**
	 * Array of Blackbox_Config instances.
	 *
	 * @var array
	 */
	private static $instance;

	/**
	 * Returns an instance of OLPBlackbox_Config.
	 *
	 * @return OLPBlackbox_Config
	 */
	public static function getInstance()
	{
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Checks for the bypass_limits flag and to see if the current target is in bb_force_winner.
	 *
	 * @param string $property a string of the property short
	 * @return bool
	 */
	public function bypassLimits($property)
	{
		$result = FALSE;
		if ($this->bypass_limits && (!empty($this->bb_force_winner) || !empty($this->organic_lead_site)))
		{
			$bb_force_winner = array_map('trim', explode(',', $this->bb_force_winner));
			$organic_lead_site = array_map('trim', explode(',', $this->organic_lead_site));
			$targets = array_merge($bb_force_winner, $organic_lead_site);
			$result = in_array(strtolower($property), $targets);
		}

		return $result;
	}
	
	/**
	 * Hits stats for the frontend site.
	 *
	 * @param string $stat_name The stat name to hit.
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	public function hitSiteStat($stat_name, Blackbox_IStateData $state_data)
	{
		if ($this->hit_stats_site)
		{
			$statpro = Stats_StatPro::getInstance($this->mode, Stats_ClientList::getStatClient('property_id', $this->property_id));
			$statpro->hitStat($stat_name, NULL, NULL, $this->track_key, $this->space_key);
		}
	}
	
	/**
	 * Creates a new OLPECash_VendorAPI object for the given property short.
	 *
	 * @param string $property_short a string of the property short for the eCash company
	 * @param string $mode a string of the mode OLP is running in
	 * @param int $application_id an int for the application ID
	 * @return OLPECash_VendorAPI
	 */
	public function createECashVendorAPI($property_short, $mode, $application_id = NULL)
	{
		return new OLPECash_VendorAPI($property_short, $mode, $application_id);
	}
}

?>
