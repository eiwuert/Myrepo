<?php
/**
 * OLPBlackbox_Rule_DataXGeneric class.
 * 
 * @package OLPBlackbox
 * @author  Rob Voss <rob.voss@sellingsource.com>
 * 
 * @desc For dealing with PW etc call types
 * 
 */
class OLPBlackbox_Enterprise_Generic_Rule_DataX extends OLPBlackbox_Rule_DataX 
{
	const TYPE_IDV_PW 	= 'idv-l7';
	const TYPE_IDV_CCRT = 'idv-compucredit';
	const TYPE_DF_PHONE = 'df-phonetype';
	
	/**
	 * Array of call_types
	 * 
	 * @var Array $call_type_list
	 */
	protected $call_type_list = array(
		self::TYPE_IDV_PW,
		self::TYPE_IDV_CCRT,
		self::TYPE_DF_PHONE
	);

	/**
	 * This function is for checking if the call has been made previously, if it has been then we return the results for that call in the history
	 * Otherwise we do a new call and return the results.
	 *
	 * @param Blackbox_Data $blackbox_data 		Data the rule is running against
	 * @param Blackbox_IStateData $state_data 	State data
	 * 
	 * @return bool $valid 
	 */
	protected function startCall(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		$valid = NULL;
		
		$config = $this->getConfig();

		// We can reuse the CLK call
		if ($this->call_type == self::TYPE_IDV_PW)
		{
			// Check if rework is being used and a rework hasn't already been run yet. GForge [#5732] [DW]
			if ($config->allow_datax_rework && $config->do_datax_rework)
			{
				$valid = $this->getHistory(OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV_REWORK);
			}
			else
			{
				$valid = $this->getHistory(OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV);
			}
		}
		
		if (empty($valid))
			$valid = parent::startCall($blackbox_data, $state_data);
		
		return $valid;
	}
	
	/**
	 * Get's the exception decision
	 *
	 * @return bool
	 */
	protected function getExceptionDecision()
	{
		// Log this correctly
		if ($this->call_type == self::TYPE_DF_PHONE || 
			$this->site_config->site_type == 'ecash_yellowbook')
		{
			$this->decision = 'N';
			$valid = FALSE;
		}
		else
		{
			$valid = parent::getExceptionDecision();
		}
		
		return $valid;
	}
	
	/**
	 * Gets the Source ID from the current call_type
	 *
	 * @return int
	 */
	protected function getSourceID()
	{
		$source_id = NULL;
		
		switch ($this->call_type)
		{
			case self::TYPE_IDV_PW:			$source_id = 3; break;
			case self::TYPE_DF_PHONE:		$source_id = 7; break;
			case self::TYPE_IDV_CCRT:		$source_id = 8; break;
		}
		
		return $source_id;
	}

	/**
	 * Based on Call Type and the result of the $valid var we hit stats
	 *
	 * @param bool $valid 					Was the call successfull?
	 * @param Blackbox_Data $blackbox_data 	Blackbox data
	 * @param Blackbox_IStateData $state_data 	State data
	 * 
	 * @return void
	 */
	protected function hitCustomStats($valid, Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		parent::hitCustomStats($valid, $blackbox_data, $state_data);
		
		$stat = array();
		
		$stat[] = ($valid) ? 'pw_idv_pass' : 'pw_idv_fail';

		// Don't hit stats if we're in mode_online_confirmation because
		// we're using the enterprise config and these are bb stats
		if (!empty($stat) && $this->config_data->blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION)
		{
			// Hit the stat
			for ($st = 0; $st < count($stat); $st++)
			{
				$this->hitSiteStat($stat[$st], $blackbox_data, $state_data);
			}
		}
	}	
}

?>
