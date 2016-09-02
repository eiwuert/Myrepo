<?php
/**
 * Catch type suppression list implementation.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Suppression_Catch extends OLPBlackbox_Rule_Suppression
{
	const CAUGHT = 'CAUGHT';
	const MISS   = 'MISS';
		
	/**
	 * Required to implement {@see OLPBlackbox_Rule_Suppression}.
	 *
	 * @return string
	 */
	public function listType()
	{
		return 'CATCH';
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
		
		/**
		 * What is the validity of a CATCH? Assuming always TRUE, we don't want to fail...
		 */
		$this->valid = TRUE;
		
		$caught = $this->list->Match($this->getDataValue($data));
		$this->result = $caught ? self::CAUGHT : self::MISS;
		$this->valid = !$caught;
		
		/**
		 * This whole implementation is crap. I don't have time to redo it now, but it should
		 * be redone.
		 * 
		 * @todo Rework this to return this information more better
		 */
		$m = array();
		if ($caught && preg_match('/(\w*)_(\w*)_(.*)/i', $this->list->Name(), $m))
		{
			$target_name = strtolower($m[1]);
			$store       = strtolower($m[2]);
			$store_id    = (int)$m[3];
			
			/**
			 * This is the utter crap part. In order to fix it, you have to fix it in
			 * the post to vendor code in bfw.
			 */
			$store_array = array(
				'ref' => $store_id,
				'desc' => $this->list->Description()
			);
			
			$target_array = array($store => $store_array);
			$catch_array  = array($target_name => $target_array);
			
			/**
			 * This is overriding anything else we may be keeping in the suppression_lists value,
			 * so this will need to be relooked at later.
			 * 
			 * @todo Do something better with this so it doesn't override anything else
			 */
			$state_data->suppression_lists = array('CATCH' => $catch_array);
		}
		
		return $this->valid;
	}
}
?>
