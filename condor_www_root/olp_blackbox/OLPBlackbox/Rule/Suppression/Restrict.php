<?php
/**
 * Restrict type suppression list implementation.
 * 
 * Restrict suppression lists require that the value submitted in the application exist in the
 * list.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Suppression_Restrict extends OLPBlackbox_Rule_Suppression
{
	/**
	 * OLPBlackbox_Rule_Suppression_Restrict constructor.
	 *
	 * @param Suppress_List $list the suppression list object
	 */
	public function __construct(Suppress_List $list)
	{
		parent::__construct($list);
		$this->event_name = sprintf(
			'LIST_RESTRICT_%s_%u',
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
		
		$this->valid = $this->list->Match($this->getDataValue($data));
		$this->result = $this->valid ? OLPBlackbox_Config::EVENT_RESULT_PASS : OLPBlackbox_Config::EVENT_RESULT_FAIL;
		return $this->valid;
	}
}
?>
