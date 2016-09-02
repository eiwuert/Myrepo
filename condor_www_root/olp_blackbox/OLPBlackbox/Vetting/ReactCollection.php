<?php

/**
 * Holds react campaigns for DataX Vetting (gforge 9922)
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_ReactCollection extends OLPBlackbox_TargetCollection
{
	/**
	 * Override the parent isValid to hit a custom stat.
	 *
	 * @param Blackbox_Data $data Info about the application being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 * 
	 * @return bool TRUE the collection is valid, FALSE otherwise.
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = parent::isValid($data, $state_data);
		
		if (!$this->collection_id)
		{
			OLPBlackbox_Config::getInstance()->applog->Write(sprintf(
				'react collection %s had no collection_id set for stat hit.',
				$this->name)
			);
		}
		if ($this->valid_list)
		{
			// hit stat for gforge 9922
			OLPBlackbox_Config::getInstance()->hitStat(
				OLPBlackbox_Config::STAT_VETTING_REACT_IDENTIFIED,
				$state_data, 
				$this->collection_id
			);
		}
		
		return $valid;
	}
}

?>
