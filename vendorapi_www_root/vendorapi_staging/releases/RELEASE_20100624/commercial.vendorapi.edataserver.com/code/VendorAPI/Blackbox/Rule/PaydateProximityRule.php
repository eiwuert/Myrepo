<?php 
/**
 * Verify paydates within 5 days of eachother
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_PaydateProximityRule extends VendorAPI_Blackbox_Rule 
{
	/**
	 * @var integer
	 */
	protected $days;

	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param integer $days
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, $days)
	{
		parent::__construct($log);
		$this->days = $days;
	}

	/**
	 * @return string
	 */
	public function getEventName()
	{
		return 'PAYDATES';
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
		if (is_array($data->paydates))
		{
			$count = count($data->paydates);
			for ($i = 0; $i < $count; ++$i)
			{
				if (!empty($data->paydates[$i + 1])
					&& strtotime($data->paydates[$i + 1]) < strtotime('+ '.$this->days.' days', strtotime($data->paydates[$i])))
				{
					return FALSE;	
				}
			}
		}
		return TRUE;
	}
}

?>