<?php
/**
 * Defines the OLPBlackbox_Config class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

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
	 * Property ID for hitting Blackbox Rules.
	 *
	 * @var int
	 */
	const STATS_BBRULES = -889275714;

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
	 * Filter events.
	 */
	const EVENT_FILTER_CHECK = 'FILTER_CHECK';
	const EVENT_FILTER_EMAIL = 'FILTER_EMAIL';
	const EVENT_FILTER_MICR = 'FILTER_MICR';
	const EVENT_FILTER_DRIVERS_LICENSE = 'FILTER_DRIVERS_LICENSE';

	/**
	 * Stat for the previous customer check
	 *
	 * @var string
	 */
	const STAT_PREV_CUSTOMER = 'prevcust';

	/**
	 * DataX Events
	 *
	 * @var string
	 */
	const EVENT_DATAX_IDV 			= 'DATAX_IDV';
	const EVENT_DATAX_IC_IDVE 		= 'DATAX_IDVE_IMPACT';
	const EVENT_DATAX_IFS_IDVE 		= 'DATAX_IDVE_IFS';
	const EVENT_DATAX_IDVE_IPDL 	= 'DATAX_IDVE_IPDL';
	const EVENT_DATAX_IDVE_ICF 		= 'DATAX_IDVE_ICF';
	const EVENT_DATAFLUX_PHONE 		= 'DATAFLUX_PHONETYPE';

	const EVENT_DATAX_IDV_REWORK 	= 'DATAX_IDV_REWORK';
	const EVENT_DATAX_PDX_REWORK 	= 'DATAX_PDX_REWORK';

	const EVENT_DATAX_PERF 			= 'DATAX_PERF';
	const EVENT_DATAX_AALM 			= 'DATAX_AALM_PERF';
	/* Added LCS for GForge ticket #9883 */
	const EVENT_DATAX_LCS 			= 'DATAX_LCS_PERF';
	const EVENT_DATAX_AGEAN_TITLE 	= 'DATAX_AGEAN_TITLE';
	const EVENT_DATAX_AGEAN_PERF 	= 'DATAX_AGEAN_PERF';

	/**
	 * ABA Check added to root collection.
	 *
	 * @var string
	 */
	const EVENT_ABA_BAD = 'ABA_BAD';

	/**
	 * Verify checks for same work/home phone.
	 *
	 * @var string
	 */
	const EVENT_VERIFY_SAME_WH_PHONE = 'VERIFY_SAME_WH';

	/**
	 * Verify event.
	 *
	 * @see OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatus
	 * @var string
	 */
	const EVENT_VERIFY_DATAX_REFERRAL = 'CHECK_DATAX_REFERRAL';

	/**
	 * Verify check for if the entire income of the applicant is benefits.
	 *
	 * @see OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatus
	 * @var string
	 */
	const EVENT_VERIFY_BENEFITS_CHECK = 'BENEFITS_CHECK';

	/**
	 * Used by {@see OLPBlackbox_Vetting_Rule_SSNCheck} for gforge 9922.
	 *
	 * @var string
	 */
	const EVENT_SSN_VETTING = 'SSN_VETTING';

	/**
	 * Record the total number of leads identified for vetting.
	 * 
	 * Used by {@see OLPBlackbox_Vetting_ReactCollection} for gforge 9922.
	 *
	 * @var string
	 */
	const STAT_VETTING_REACT_IDENTIFIED = 'vetting_react_identified';
	
	/**
	 * Record the total number of react leads sold for vetting.
	 * 
	 * Used by {@see OLPBlackbox_Vetting_Rule_IsReact} for gforge 9922.
	 * 
	 * @var string
	 */
	const STAT_VETTING_REACT_SOLD = 'vetting_react_sold';
	
	/**
	 * Record nmber of leads that passed 'Data Quality Prequalification' checks.
	 * 
	 * This is for vetting (gforge 9922).
	 * 
	 * @var string
	 */
	const STAT_VETTING_DATA_QUALITY_PASS = 'vetting_data_quality_pass';
	
	/**
	 * Record number of leads that failed 'Data Quality Prequalification' checks.
	 * 
	 * This is for vetting (gforge 9922).
	 * 
	 * @var string
	 */
	const STAT_VETTING_DATA_QUALITY_FAIL = 'vetting_data_quality_fail';
	
	/**
	 * Record leads that pass/fail DataX IDV vetting checks.
	 * 
	 * Note: {@see OLPBlackbox_Rule} will append "_pass" or "_fail" to the
	 * actual stat hit.
	 * 
	 * @var string
	 */
	const STAT_VETTING_IDV = 'vetting_idv';
	
	/**
	 * Record leads that entirely fail business rules for vetting.
	 * 
	 * "Vetting" refers to the vetting done in gforge 9922.
	 * 
	 * @var string
	 */
	const STAT_VETTING_LEAD_FAILED = 'vetting_lead_failed';
	
	/**
	 * Stat called when the vetting process' SSN time check passes.
	 * 
	 * "vetting" here refers to the vetting done for gforge 9922.
	 *
	 * @var string
	 */
	const STAT_VETTING_TIME_LIMIT_PASS = 'vetting_time_limit_pass';
		
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

		if ($this->bypass_limits && !empty($this->bb_force_winner))
		{
			$bb_force_winner = array_map('trim', explode(',', $this->bb_force_winner));
			$result = in_array(strtolower($property), $bb_force_winner);
		}

		return $result;
	}
	
	/**
	 * Hits stats (using batching).
	 *
	 * @param string $stat_name The stat name to hit.
	 * @param Blackbox_IStateData $state_data
	 * 
	 * @return void
	 */
	public function hitSiteStat($stat_name, Blackbox_IStateData $state_data)
	{		
		if ($this->hit_stats_site)
		{
			$statpro = Stats_StatPro::getInstance($this->mode, $this->property_id);
			$statpro->hitStat($stat_name, NULL, NULL, $this->track_key, $this->space_key);
		}
	}
}

?>
