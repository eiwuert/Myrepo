<?php
/**
 * Class definition for OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

/**
 * A rule that checks for previously used information pieces.
 *
 * This rule object checks to see if, for example, the application has a
 * bank account that has been used by someone with a different SSN already.
 * In that sense, this is a 'fraudulent information fragment check' but that
 * doesn't make a good class name and Used_Info is the old legacy name.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck extends OLPBlackbox_Rule
{
	/**
	 * The names that we will check for used info.
	 * 
	 * These should be names that can be passed to Setup_DB. In legacy,
	 * these
	 *
	 * @var unknown_type
	 */
	protected $resolved_names = array();
	
	/**
	 * The reference time for the queries in this Rule.
	 *
	 * @var int
	 */
	private $timestamp = FALSE;
	
	/**
	 * The number of times the bank account has been used with different SSNs
	 * 
	 * @var int
	 */
	private $used_count = 0;
	
	/**
	 * String name to use to log the target for this rule passing/failing.
	 *
	 * If this rule is inspecting multiple databases (different companies) to
	 * determine if the bank account for the application has already been used
	 * too many times, we need a single name to log to the event log for clarity.
	 * This is where the name is stored.
	 * 
	 * @var string
	 */
	protected $event_log_name = '';

	/**
	 * Constructs a new used info rule.
	 * 
	 * Note: The target names that are passed in should all belong to the same company because
	 * the ABA check that is done with this rule is CUMULATIVE. This is done, primarily, for
	 * gforge #8421, but it makes sense anyhow.
	 * 
	 * Also, the $event_log_name is also added for CLK so we don't log each
	 * subcompany individually.
	 * 
	 * @param array $names list of names to do used info checks on (these should be legacy property_shorts)
	 * @param string $event_log_name Name to use for the target when logging.
	 *
	 * @return void
	 */
	public function __construct($names, $event_log_name = NULL)
	{		
		if (!is_array($names) && !$names instanceof Traversable)
		{
			throw new InvalidArgumentException('names passed in have to be an array or traversable object');
		}
		
		foreach ($names as $name)
		{
			$resolved_name = EnterpriseData::resolveAlias($name);
			if (!in_array($resolved_name, $this->resolved_names))
			{
				$this->resolved_names[] = $resolved_name;
			}
		}
		
		if (empty($this->resolved_names))
		{
			throw new InvalidArgumentException(
				'names passed to rule must be non-empty.'
			);
		}
		
		// this single rule should log one single pass/fail/error
		if ($event_log_name)
		{
			$this->event_log_name = $event_log_name;
		}
		elseif (count($this->resolved_names) == 1)
		{
			$this->event_log_name = $this->resolved_names[0];
		}
		else 
		{
			throw new Blackbox_Exception(sprintf(
				'multiple companies to use in %s, but no event_log_name provided.',
				__CLASS__)
			);
		}
		
		$this->setStatName(strtolower(OLPBlackbox_Config::EVENT_USED_ABA_CHECK));
		
		parent::__construct();
	}
	
	/**
	 * Set the timestamp the class will use to run time based queries.
	 * 
	 * This is completely for the purpose of running PHPUnit tests. Do NOT 
	 * use this function otherwise, unless you know exactly what you're doing
	 * and have a darn good reason for it.
	 *
	 * @param mixed $now valid date string or timestamp
	 * 
	 * @return void
	 */
	public function setNow($now)
	{
		if (is_int($now))
		{
			$this->timestamp = $now;
		}
		elseif (is_string($now))
		{
			$this->timestamp = strtotime($now);
		}
		
		if ($this->timestamp === FALSE)
		{
			throw new InvalidArgumentException("could not parse $now into an acceptable date.");
		}
	}

	/**
	 * Determines whether or not this rule can run at all.
	 *
	 * @param Blackbox_Data $data data specific to the application we're processing.
	 * @param Blackbox_IStateData $state_data data specific to the ITarget running this rule.
	 *
	 * @return bool whether or not this rule can run.
	 */
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return isset($data->bank_aba) 
			&& isset($data->bank_account) 
			&& isset($data->social_security_number);
	} 

	/**
	 * Actually run the rule and determine whether the application contains data which has been used elsewhere.
	 * 
	 * @param Blackbox_Data $data Data about the application we're considering.
	 * @param Blackbox_IStateData $state_data Data about the state of the ITarget requesting we run.
	 * 
	 * @return bool Whether or not the rule passes.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// this will eventually be an API call, hopefully.
		$config = $this->getConfig();
		$debug = $config->debug;
		$event_log = $config->event_log;
		
		// reset the used aba counter so that every isValid run does all checks
		// this is used in $this->abaCheck();
		$this->used_count = 0;
		
		$valid = TRUE;
		
		/**
		 * NOTE: This is a very odd rule in that it can run for multiple targets.
		 * When this happens it means that this rule has been added to a 
		 * TargetCollection instead of a Target. The reason this rule is 
		 * multipurpose is that the alternative is to have this be a target rule 
		 * and throw the failed status into the state data. Then, a post target 
		 * rule would have to be added to the CLK collection which checks that 
		 * status. When I discussed this with brianf and andrewm, it was decided 
		 * this was the better way. [DO]
		 */
		if ($debug->debugSkipRule(OLPBlackbox_DebugConf::USED_INFO))
		{
			$result = OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP;
		}
		else 
		{
			foreach ($this->resolved_names as $resolved_name)
			{
				if (!$this->abaCheck($resolved_name, $data, $state_data))
				{
					$valid = FALSE;
					break;
				}
			}
	
			$result = $valid ?
				OLPBlackbox_Config::EVENT_RESULT_PASS :
				OLPBlackbox_Config::EVENT_RESULT_FAIL; 
		}
		
		$event_log->Log_Event(
			OLPBlackbox_Config::EVENT_USED_ABA_CHECK, 
			$result,
			$this->event_log_name,
			$data->application_id,
			$config->blackbox_mode
		);
		
		return $valid;
	}
	
	/**
	 * Runs an ABA check for an enterprise customer.
	 * 
	 * @param string $resolved_name The short name of the company (campaign) to run the ABA check for. (pre-resolved)
	 * @param Blackbox_Data $data information related to the application being processed by blackbox
	 * @param Blackbox_IStateData $state_data data related to the ITarget running this rule.
	 * 
	 * @return bool whether or not the check passes
	 */
	protected function abaCheck($resolved_name, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// allow PHPUnit tests to override the target date we check from
		if (!$this->timestamp)
		{
			$this->timestamp = time();
		}
				
		// ldb database connection, since this is enterprise customers only
		$ldb = $this->getLdb($resolved_name);

		// remove all leading zeros
		$account = preg_replace('/^0*/', '', $data->bank_account);

		// the bank account numbers to check.
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
				$acct_array[] = "'".str_pad($account, $i, '0', STR_PAD_LEFT)."'";
			}
		}

		$mysql_query = sprintf(
			"SELECT
				COUNT(DISTINCT ssn) AS ssn_count
			FROM
				application
			WHERE
				bank_aba = '%s'
				AND bank_account IN (%s)
				AND ssn <> '%s'
				AND date_created > DATE_SUB(CAST(FROM_UNIXTIME(%s) AS DATE), INTERVAL 1 YEAR ) ",
			$data->bank_aba,
			implode(", ", $acct_array),
			$data->social_security_number,
			$this->timestamp);

		try
		{
			$sqli_result = $ldb->Query($mysql_query);
			$sqli_count = $sqli_result->Fetch_Object_Row();
			$this->used_count += $sqli_count->ssn_count;
		}
		catch (Exception $e)
		{
			throw new Blackbox_Exception($e->getMessage());
		}

		// 1 other ssn+aba+bank account allowed for joint accounts
		return $this->used_count < 2;
	}
	
	/**
	 * Handler for Blackbox_Exceptions being thrown in isValid().
	 *
	 * @param Blackbox_Exception $e cause of the error.
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 *
	 * @return bool TRUE to treat errors as a pass, FALSE to treat as fail.
	 */
	protected function onError(Blackbox_Exception $e, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$log_msg = sprintf(
			'error in rule %s: %s',
			__CLASS__,
			$e->getMessage()
		);
		trigger_error($log_msg);
		$config->applog->Write($log_msg); 
		
		$config = $this->getConfig();
		$config->event_log->Log_Event(
			OLPBlackbox_Config::EVENT_USED_ABA_CHECK,
			OLPBlackbox_Config::EVENT_RESULT_ERROR,
			$this->event_log_name,
			$data->application_id,
			$config->blackbox_mode
		);
		
		return FALSE;
	}
	
	/**
	 * Get an LDB database for queries, which is easily mocked for testing.
	 *
	 * @param string $resolved_name Name which can be used to get a ldb database.
	 * 
	 * @return MySQLi_1 object
	 */
	protected function getLdb($resolved_name)
	{
		$config = $this->getConfig();
		return Setup_DB::Get_Instance('mysql', $config->mode, $resolved_name);
	}
}
?>
