<?php
/**
 * Verify type suppression list implementation.
 * 
 * Verify suppression lists always return valid. Their result however will be VERIFY if a match is
 * found and VERIFIED if a match is not found. The purpose of a verify list is to catch values that
 * need to be verified by hand by an agent.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Suppression_Verify extends OLPBlackbox_Rule_Suppression
{
	const VERIFY   = 'VERIFY';
	const VERIFIED = 'VERIFIED';
	
	/**
	 * OLPBlackbox_Rule_Suppression_Exclude constructor.
	 *
	 * @param Suppress_List $list the suppression list object
	 */
	public function __construct(Suppress_List $list)
	{
		parent::__construct($list);
		$this->event_name = sprintf(
			'LIST_VERIFY_%s_%u',
			strtoupper($this->list->Field()),
			$this->list->ID()
		);
	}
	
	/**
	 * Runs this suppression list.
	 *
	 * @param Blackbox_Data       $data       the data to run this list on
	 * @param Blackbox_IStateData $state_data the state data of the target
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (is_bool($this->valid)) return $this->valid;
		
		// Verify lists are always valid, we don't want to fail them on this rule
		$this->valid = TRUE;
		$verify = $this->list->Match($this->getDataValue($data));
		$this->result = $verify ? self::VERIFY : self::VERIFIED;
		return $this->valid;
	}
}
?>
