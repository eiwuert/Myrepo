<?php

/**
 * Check the SSN table created for the Vetting spec.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_Rule_SSNCheck extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * Number of days after which SSNs will not be considered for the check.
	 *
	 * @var int
	 */
	protected $expires_after;
	
	/**
	 * Construct a OLPBlackbox_Vetting_Rule_SSNCheck object.
	 * 
	 * @param int $expires_after The number of days SSNs which exist in the 
	 * 	database will not be considered for the check.
	 * 
	 * @return void
	 */
	public function __construct($expires_after = 120)
	{
		$this->expires_after = $expires_after;
		$this->setEventName(OLPBlackbox_Config::EVENT_SSN_VETTING);
		parent::__construct();
	}
	
	/**
	 * Determines if there's enough information to run this check.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return unknown
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !empty($data->social_security_number_encrypted);
	}
	
	/**
	 * Checks to see if the SSN in the application has been used recently.
	 * 
	 * The spec driving this rule (DataX Vetting Process) says that if the SSN
	 * has been used for an application in the last 120 days, use the normal
	 * application process. "Use the normal application process" means fail
	 * the collection for the Vetting process and fall back to regular 
	 * OLPBlacbkox. Since applications are not currently held for 120 days, a 
	 * new table will be used to store the ssn and the "last seen date."
	 *
	 * @param Blackbox_Data $data Info about the application being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget
	 * 
	 * @return bool Whether the rule succeeds.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = OLPBlackbox_Config::getInstance();
		
		$vetting = new Vetting_SSN(
			$this->getDb(), 
			$this->expires_after 
		);
		try 
		{
			return $vetting->ssnOverused(
				$data->social_security_number_encrypted,
				$data->application_id,
				$this->getNow()
			);
		}
		catch (DB_MySQL4AdapterException_1 $e)
		{
			$msg = sprintf(
				'could not query ssn table, problem with mysql query %s',
				$e->getMessage()
			);
		}
		catch (Exception $e)
		{
			$msg = sprintf('unknown error when querying db: %s',$e->getMessage());
		}
		
		$config->applog->Write($msg);
		throw new Blackbox_Exception($msg);
	}
	
	/**
	 * Get the current date in MySQL acceptable format.
	 * 
	 * This is separated out into it's own function for mocking purposes.
	 *
	 * @return string like YYYY-MM-DD
	 */
	protected function getNow()
	{
		return date('Y-m-d');
	}
	
	/**
	 * Mostly for PHPUnit testing purposes this function returns a wrapper.
	 *
	 * @return DB_MySQL4Adapter_1 object.
	 */
	protected function getDb()
	{
		$config = OLPBlackbox_Config::getInstance();
		return new DB_MySQL4Adapter_1($config->olp_db->getConnection(), $config->olp_db->db_info['name']);
	}
	
	/**
	 * When this rule is valid, we want to hit a special vetting stat.
	 *
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 * 
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// we hit this custom stat when the rule passes, but not when it fails.
		// (see DataX Vetting stat/gforge 9922) 
		$this->setStatName(OLPBlackbox_Config::STAT_VETTING_TIME_LIMIT_PASS);
		parent::onValid($data, $state_data);
	}	
}

?>
