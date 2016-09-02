<?php
class OLPBlackbox_Enterprise_CFETarget extends OLPBlackbox_Enterprise_Target implements OLPBlackbox_Enterprise_ICFETarget
{
	
	/**
	 * Call the parent valid and then run CFE 
	 * if we need to.
	 *
	 * @param Blackbox_Data $data Application data
	 * @param Blackbox_IStateData $state_data Targets state data
	 * @return boolean isValid?
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = parent::isValid($data, $state_data);
		$bb_mode =OLPBlackbox_Config::getInstance()->blackbox_mode;
		// If it's a valid target AND we're not in online_confirmation, run CFE
		// Also run CFE if we're an ECASH_REACT regardless of whether we're
		// valid or not.
		if (($valid && $bb_mode != OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION) || 
			$bb_mode == OLPBlackbox_Config::MODE_ECASH_REACT)
		{
			$cfe_application = new OLPBlackbox_CFE_Application($data, $this->state_data);
			$cfe_application->runCFEEngine();
			// If we're valid, make sure CFE didn't invalidate us.
			if ($valid)
			{
				$valid = $this->state_data->asynch_object->getIsValid();
				if (!$valid)
				{
					OLPBlackbox_Config::getInstance()->applog->Write("CFE Returned invalid for {$data->application_id}");
				}
			}
		}
		return $valid;
	}
}
?>
