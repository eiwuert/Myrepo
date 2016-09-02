<?php

/**
 * OLPBlackbox_Enterprise_AALM_Rule_DataX class file.
 * 
 * @package OLPBlackbox
 * @author  Rob Voss <rob.voss@sellingsource.com>
 * 
 * @desc AALM Specific Rules can go in here.
 * 
 */
class OLPBlackbox_Enterprise_AALM_Rule_DataX extends OLPBlackbox_Rule_DataX
{
	const TYPE_PERF_MLS = 'aalm-perf';
	
	/**
	 * Array of call_types
	 * 
	 * @var Array $call_type_list
	 */
	protected $call_type_list = Array(
		self::TYPE_PERF_MLS
	);
	
	/**
	 * Run when the rule returns as invalid.
	 *
	 * @param Blackbox_Data $blackbox_data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		// Figure out the type of aa_denial stat
		switch ($this->call_type)
		{
			case OLPBlackbox_Enterprise_AALM_Rule_DataX::TYPE_PERF_MLS:
				$aa_denial = 'aa_denial_datax_entgen';
				break;
		}
		
		if (empty($aa_denial))
		{
			$aa_denial = 'aa_denial_entgen';
		}
		
		// Hit our adverse action
		$this->adverseAction($aa_denial, $blackbox_data, $state_data);
		
		parent::onInvalid($blackbox_data, $state_data);
	}
	
	/**
	 * This will figure out where to pull the decision from based on the call type.
	 * 
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @param Blackbox_IStateData $state_data State data
	 *
	 * @return void
	 */
	protected function findDecision(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		parent::findDecision($blackbox_data, $state_data);
		
		$this->score = $this->xml_received->searchOneNode('//ConsumerIDVerificationSegment/AuthenticationScore');
		
		//If it's a fail, AND CRA failed, hit adverse action stat
		if ($this->decision == 'N' &&
			$this->xml_received->searchOneNode('//CRASegment/Decision/Result') == 'N')
		{
			$this->hitSiteStat('aa_aalm_cra_denial', $blackbox_data, $state_data);
		}
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
			case self::TYPE_PERF_MLS: $source_id = 11; break;
		}
		
		return $source_id;
	}
		
	/**
	 * Get's the exception decision
	 *
	 * @return bool
	 */
	protected function getExceptionDecision()
	{
		$this->decision = 'N';
		$valid = FALSE;
		
		return $valid;
	}
	
	/**
	 * Based on Call Type and the result of the $valid var we hit stats
	 *
	 * @param bool $valid 					Was the call successfull?
	 * @param Blackbox_IStateData $state_data 	State data
	 * 
	 * @return void
	 */
	protected function hitCustomStats($valid, Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		parent::hitCustomStats($valid, $blackbox_data, $state_data);
		
		$stat = array();
		
		$stat[] = ($valid) ? 'aalm_perf_pass' : 'aalm_perf_fail';

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
