<?php 
/**
 * Verify paydates within 5 days of eachother
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_VerifyPaydateProximity extends VendorAPI_Blackbox_VerifyRule 
{
	protected $days;
	/**
	 * Define the action name for this
	 * verify rule
	 *
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, $days)
	{
		parent::__construct($log);
		$this->days = $days;
		$this->addActionToStack('VERIFY_PAYDATES');
	}

	protected function getEventName()
	{
		return 'VERIFY_PAYDATES';
	}

	/**
	 * Always run?
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return unknown
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Add a verify rule to the thing
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (strtolower($data->income_frequency) == 'twice_monthly' && is_array($data->paydates))
		{
			$count = count($data->paydates);
			
			for ($i = 0; $i < $count; ++$i)
			{
				if (!empty($data->paydates[$i + 1])
					&& $data->paydates[$i + 1] < strtotime('+ '.$this->days.' days', $data->paydates[$i]))
				{
					return FALSE;	
				}
			}
		}
		return TRUE;
	}
}

?>
