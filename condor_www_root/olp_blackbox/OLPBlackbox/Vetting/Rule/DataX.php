<?php

/**
 * DataX IDV rule for Vetting process from gforge 9922
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_Rule_DataX extends OLPBlackbox_Rule_DataX
{
	/**
	 * Call type for vetting IDV DataX calls (duh).
	 *
	 * @var string
	 */
	const TYPE_IDV = 'idv-vetting';
	
	/**
	 * Account type to setup parent with.
	 * 
	 * Since this rule is only called 
	 * 
	 * @var string
	 */
	const ACCOUNT = 'pw-vetting';
	
	/**
	 * Sets up a OLPBlackbox_Vetting_Rule_DataXIDV rule with stat name, etc.
	 *
	 * @param string $account Campaign (or target) name for this DataX rule to use.
	 * 
	 * @return void 
	 */
	public function __construct()
	{
		// parent needs a list of valid call types (kind of silly, I know)
		$this->call_type_list = array(self::TYPE_IDV);
		
		// get parent set up
		parent::__construct(self::TYPE_IDV, self::ACCOUNT);
		
		// just reuse the stat name
		$this->setEventName(OLPBlackbox_Config::STAT_VETTING_IDV);
		
		// mostly for toString()
		$this->name = __CLASS__;
	}
	
	/**
	 * Return the source ID based on the call type.
	 *
	 * @return int source ID
	 */
	protected function getSourceID()
	{
		if ($call_type == self::TYPE_IDV)
		{
			// hooray! hard coding!
			// eventually when branch for gforge [#10745] is merged in,
			// we won't have to do this it will be centralized
			return 17;
		}
		else
		{
			throw new Blackbox_Exception(sprintf(
				'source id for unknown call type (%s) requested',
				strval($call_type))
			);
		}
	}

	/**
	 * Unused hitCustomStats, must be implemented for 
	 *
	 * @param bool $is_valid Whether the rule passed/failed.
	 * @param Blackbox_Data $data Info about the app being processed.
	 * 
	 * @return void
	 */
	protected function hitCustomStats($is_valid, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$stat_name = sprintf(
			'%s_%s',
			OLPBlackbox_Config::STAT_VETTING_IDV,
			$is_valid ? OLPBlackbox_Config::EVENT_RESULT_PASS : OLPBlackbox_Config::EVENT_RESULT_FAIL
		);
		
		$this->hitSiteStat($stat_name, $data, $state_data);
	}
}

?>
