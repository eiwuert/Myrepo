<?php
/**
 * Abstract class for picker.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Picker implements OLPBlackbox_IPicker
{
	/**
	 * Determines whether a picker will re-pick if the target returns FALSE on pickTarget().
	 *
	 * @var bool
	 */
	protected $repick_on_fail;
	
	/**
	 * OLPBlackbox_Picker constructor.
	 *
	 * @param bool $repick whether a picker will re-pick on target failure, on by default
	 */
	public function __construct($repick = TRUE)
	{
		$this->repick_on_fail = $repick;
	}

	/**
	 * Makes sure the appropriate data structure exists in a Blackbox_IStateData object.
	 * 
	 * @param Blackbox_IStateData $state_data state object we'd like to store snapshot info in 
	 *
	 * @return void
	 */
	public function prepSnapshotData(Blackbox_IStateData $state_data)
	{
		$config = OLPBlackbox_Config::getInstance();
		
		// only add snapshot to state data if it hasn't been prepped yet.
		if ($config->allowSnapshot && is_null($state_data->snapshot))
		{
			$state_data->snapshot = new stdClass();
			$state_data->snapshot->debug = OLPBlackbox_Config::getInstance()->debug->getFlags();
			if ($config->force_winner)
			{
				$state_data->snapshot->force_winner = $config->force_winner;
			}
			$state_data->snapshot->stack = new ArrayObject();
		}
	}
}
?>
