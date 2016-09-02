<?php

/**
 * OLPBlackbox_Rule_CLK_DataX class file.
 *
 * @package OLPBlackbox
 * @author  Rob Voss <rob.voss@sellingsource.com>
 *
 * @desc Place to put CLK specific rules for datax
 */
class OLPBlackbox_Enterprise_CLK_Rule_DataX extends OLPBlackbox_Rule_DataX
{
	const TYPE_IDV 			= 'idv-l1';
	const TYPE_PERF 		= 'perf-l3';
	const TYPE_IDV_REWORK 	= 'idv-rework';
	const TYPE_IDV_PREQUAL 	= 'idv-l5';

	/**
	 * Array of call_types
	 *
	 * @var Array $call_type_list
	 */
	protected $call_type_list = array(
		self::TYPE_IDV_REWORK,
		self::TYPE_IDV_PREQUAL,
		self::TYPE_IDV,
		self::TYPE_PERF
	);

	/**
	 * Run when the rule returns as invalid.
	 * 
	 * The odd bit about this class is that if a PERF call fails for CLK the whole collection fails.
	 * Therefore, this function is overridden to accomodate that, by throwing a FailException if
	 * the call that's failed is a DataX PERF call.
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
			case OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV_REWORK:
			case OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV:
				$aa_denial = 'aa_denial_datax';
				break;
			case OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_PERF:
				$aa_denial = 'aa_denial_teletrack';
				break;
			default:
				$aa_denial = NULL;
				break;
		}

		if (!is_null($aa_denial))
		{
			// Hit our adverse action
			$this->adverseAction($aa_denial, $blackbox_data, $state_data);
		}

		parent::onInvalid($blackbox_data, $state_data);
		
		// fail the whole collection on a PERF call, see function comment
		if ($this->call_type == self::TYPE_PERF)
		{
			throw new OLPBlackbox_FailException('DataX Performance failed.');
		}
	}

	/**
	 * Decide whether it's a Perf call or other, and build the query for DataX call.
	 *
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 *
	 * @return string
	 */
	protected function buildQuery(Blackbox_Data $blackbox_data)
	{
		if ($this->call_type == self::TYPE_PERF)
		{
			$query = $this->buildPerformance($blackbox_data);
		}
		else
		{
			$query = parent::buildQuery($blackbox_data);
		}

		return $query;
	}

	/**
	 * Run the DataX rule.
	 *
	 * @param Blackbox_Data $data Data the rule is running against
	 * @param Blackbox_IStateData $state_data State data the rule is running against
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		//If it's an IDV call, make sure IDV calls are enabled before making it.
		if ($this->call_type != self::TYPE_PERF 
			&& (!defined('USE_DATAX_IDV') || USE_DATAX_IDV == FALSE))
		{
			return TRUE;
		}

		$return = parent::runRule($data, $state_data);
		
		if (!$return
			&& $this->getConfig()->allow_datax_rework 
			&& $this->call_type == self::TYPE_IDV)
		{
			/*
			 * Rework exceptions happen because CLK wants to let applicants
			 * to have "another chance" to fill out information if the IDVE call
			 * fails in case they made mistakes. [DO]
			 */
			$info = array(
				'company' => $state_data->campaign_name,
				'call_type' => $this->call_type,
				'tier' => $state_data->tier_number
			);
			throw new OLPBlackbox_ReworkException(
				'IDV call failed for CLK company',
				$info
			);
		}

		return $return;
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
			case self::TYPE_IDV_PREQUAL:	$source_id = 0; break;
			case self::TYPE_IDV:			$source_id = 1; break;
			case self::TYPE_PERF:			$source_id = 2; break;
			case self::TYPE_IDV_REWORK:		$source_id = 4; break;
		}

		return $source_id;
	}

	/**
	 * Based on Call Type and the result of the $valid var we hit stats
	 *
	 * @param bool $valid 					Was the call successfull?
	 * @param Blackbox_Data $blackbox_data 	The data used for the DataX Validation.
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
			case self::TYPE_IDV_REWORK:
				if ($this->getConfig()->return_visitor)
				{
					$stat[] = ($valid) ? 'idv_rework_return' : 'idv_rework_fail_return';
				}
				else
				{
					$stat[] = ($valid) ? 'idv_rework_pass' : 'idv_rework_fail';
				}
				break;

			case self::TYPE_IDV:
				$stat[] = ($valid) ? 'idv_l1_pass' : 'idv_l1_fail';
				break;

			case self::TYPE_IDV_PREQUAL:
				$stat[] = ($valid) ? 'prequal_clv_pass' : 'prequal_clv_fail';
				$stat[] = ($valid) ? 'idv_l5_pass' : 'idv_l5_fail';
				break;

			case self::TYPE_PERF:
				$stat[] = ($valid) ? 'bb_clv_pass' : 'bb_clv_fail';
				$stat[] = ($valid) ? 'perf_l3_pass' : 'perf_l3_fail';
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

	/**
	 * Overloaded onError function.
	 * 
	 * This may not be needed anymore as the PERF exception was moved to onInvalid.
	 *
	 * @param Blackbox_Exception $e 				The exceptional condition that caused us to fail.
	 * @param Blackbox_Data $blackbox_data 		Information about the application being processed
	 * @param Blackbox_IStateData $state_data 	Information about the ITarget running this rule
	 *
	 * @return bool TRUE if we should pass when erroring, FALSE otherwise
	 */
	protected function onError(Blackbox_Exception $e, Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		$this->hitRuleEvent(OLPBlackbox_Config::EVENT_RESULT_ERROR, $blackbox_data, $state_data);
		$this->hitRuleStat(OLPBlackbox_Config::STAT_RESULT_FAIL, $blackbox_data, $state_data);
		
		// fail the whole collection on a PERF call, see function comment
		if ($this->call_type == self::TYPE_PERF)
		{
			throw new OLPBlackbox_FailException(sprintf(
				"CLK %s call failed: %s", $this->call_type,	$e->getMessage())
			);
		}
	}
}
?>
