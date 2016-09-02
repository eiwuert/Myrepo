<?php
/**
 * OLPBlackbox_Enterprise_Impact_Rule_DataX class file.
 * 
 * @package OLPBlackbox
 * @author  Rob Voss <rob.voss@sellingsource.com>
 * 
 * @desc Impact Specific Rules can go in here.
 * 
 */
class OLPBlackbox_Enterprise_Impact_Rule_DataX extends OLPBlackbox_Rule_DataX
{
	const TYPE_IDVE_IMPACT	= 'impact-idve';
	const TYPE_IDVE_IFS		= 'impactfs-idve';
	const TYPE_IDVE_IPDL	= 'impactpdl-idve';
	const TYPE_IDVE_ICF		= 'impactcf-idve';
	const TYPE_PDX_REWORK	= 'pdx-impactrework';
	
	/**
	 * Array of call_types
	 * 
	 * @var Array $call_type_list
	 */
	protected $call_type_list = Array(
		// Normal Calls
		self::TYPE_IDVE_IMPACT,
		self::TYPE_IDVE_IFS,
		self::TYPE_IDVE_IPDL,
		self::TYPE_IDVE_ICF,
		self::TYPE_PDX_REWORK
	);

	/**
	 * Make DataX calls specific to Impact.
	 *
	 * @param Blackbox_Data $data info about the application being looked at
	 * @param Blackbox_IStateData $state_data info about calling ITarget
	 * 
	 * @return bool TRUE if the rule is run properly, FALSE otherwise
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = parent::runRule($data, $state_data);
		
		// if "do_datax_rework" is involved it means that when the parent ran it 
		// was already in "rework" mode so we can't throw another ReworkException
		if (!$valid 
			&& $this->call_type != $this->getConfig()->do_datax_rework
			&& $this->getConfig()->allow_datax_rework)
		{
			/*
			 * Rework exceptions happen because Impact wants to let applicants
			 * to have "another chance" to fill out information if the IDVE call
			 * fails in case they made mistakes. [DO]
			 */
			$info = array(
				'company' => $state_data->campaign_name,
				'call_type' => $this->call_type,
				'tier' => $state_data->tier_number
			);
			throw new OLPBlackbox_ReworkException(
				'impact company failed idve call, but has not been reworked.',
				$info
			);
		}
		
		return $valid;
	}
	
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
			case OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_PDX_REWORK:
			case OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_IMPACT:
				$aa_denial = 'aa_denial_datax_impact';
				break;
			case OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_IFS:
				$aa_denial = 'aa_denial_datax_ifs';
				break;
			case OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_IPDL:
				$aa_denial = 'aa_denial_datax_ipdl';
				break;
			case OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_ICF:
				$aa_denial = 'aa_denial_datax_icf';
				break;
		}
		
		if (empty($aa_denial))
		{
			$aa_denial = 'aa_denial_impact';
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
		
		if ($this->decision == 'N')
		{
			if (preg_match('/(CRA|IDV)\-d\d+/i', $this->reason, $matches))
			{
				$this->fail_type = strtoupper($matches[1]);
			}
		}
	}
	
	/**
	 * Makes the actual DataX Call
	 *
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * 
	 * @return string
	 */
	protected function call(Blackbox_Data $blackbox_data)
	{
		$decision = parent::call($blackbox_data);
		
		// If the datax type used was a rework, set the rework_ran flag so we don't run reworks again. GForge [#5732] [DW]
		if ($this->call_type == self::TYPE_PDX_REWORK)
		{
			$this->rework_ran = TRUE;
		}
		
		return $decision;
	}
	
	/**
	 * This function is for checking if the call has been made previously, if it has been then we return the results for that call in the history
	 * Otherwise we do a new call and return the results.
	 *
	 * @param Blackbox_Data $blackbox_data 		The data the rule is running on
	 * @param Blackbox_IStateData $state_data 	State data the rule is running with
	 * @return bool
	 */
	protected function startCall(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		$valid = parent::startCall($blackbox_data, $state_data);
		
		parent::$decisions['DATAX_IDV_IMPACT_ALL'] = $this->decision;
		parent::$decisions['FAIL_TYPE'] = $this->fail_type;
		
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
			case self::TYPE_IDVE_IMPACT:	$source_id = 5; break;
			case self::TYPE_PDX_REWORK:		$source_id = 6; break;
			case self::TYPE_IDVE_IFS:		$source_id = 12; break;
			case self::TYPE_IDVE_IPDL:		$source_id = 13; break;
			case self::TYPE_IDVE_ICF:		$source_id = 14; break;
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
		
		switch ($this->call_type)
		{
			case self::TYPE_PDX_REWORK:
				if ($this->data->return_visitor)
				{
					$stat[] = ($valid) ? 'pdx_rework_return' : 'pdx_rework_fail_return';
				}
				else
				{						
					$stat[] = ($valid) ? 'pdx_rework_pass' : 'pdx_rework_fail';
				}
				break;
				
			case self::TYPE_IDVE_IMPACT:
				$stat[] = ($valid) ? 'idve_ic_pass' : 'idve_ic_fail';
				break;
			
			case self::TYPE_IDVE_IFS:
				$stat[] = ($valid) ? 'idve_ifs_pass' : 'idve_ifs_fail';
				break;
				
			case self::TYPE_IDVE_IPDL:
				$stat[] = ($valid) ? 'idve_ipdl_pass' : 'idve_ipdl_fail';
				break;
				
			case self::TYPE_IDVE_ICF:
				$stat[] = ($valid) ? 'idve_icf_pass' : 'idve_icf_fail';
				break;
		}

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
