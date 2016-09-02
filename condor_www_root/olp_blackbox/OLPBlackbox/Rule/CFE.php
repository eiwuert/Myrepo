<?php
/**
 * Run CFE for an application.
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class OLPBlackbox_Rule_CFE extends OLPBlackbox_Rule
{
	/**
	 * The event to hit for this rule
	 */
	const EVENT_NAME = 'CFE_RULES';
	
	/**
	 * The stat to hit for this rule. Empty for
	 * no stat.
	 */
	const STAT_NAME = 'cfe_rules';
	
	/**
	 * Data keys that are required for CFE to be able to run.
	 *
	 * @var Array
	 */
	protected static $required_data_fields = array(
		'name_first', 'name_last', 'social_security_number', 'home_city',
		'home_state','income_direct_deposit', 'application_id'
	);
	
	/**
	 * Call the parent constructor and 
	 * setup event and stat names for CFE rules.
	 *
	 */
	public function __construct()
	{
			parent::__construct();
			$this->setEventName(self::EVENT_NAME);
			$this->setStatName(self::STAT_NAME);
	}
	
	/**
	 * Run the CFE rule. Returns if the rule passes or fails.
	 *
	 * @param Blackbox_Data $data Application data
	 * @param Blackbox_IStateData $state_data Target state data
	 * @return boolean
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$cfe_application = new OLPBlackbox_CFE_Application($data, $state_data);
		$cfe_application->runCFEEngine();

		return $state_data->asynch_object->getIsValid();
	}
	
	/**
	 * See if we have enough data to run CFE.
	 *
	 * @param Blackbox_Data $data Application data
	 * @param Blackbox_IStateData $state_data Target StateData
	 * @return boolean can it run? 
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// incase it's attached to a non cfe company? 
		if (EnterpriseData::isCFE($state_data->target_name))
		{
			foreach (self::$required_data_fields as $field)
			{
				if (!isset($data->$field) && !isset($state_data->$field))
				{
					return FALSE;
				}
			}
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}
?>