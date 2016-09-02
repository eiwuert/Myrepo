<?php

/**
 * DataX rule that additionally checks a price point
 *
 * This rule implements price point decisioning on top of the
 * standard DataX rule. For price point campaigns, we send the price
 * point (what AMG must pay PW for the lead) in the DataX request, and
 * DataX sends an estimated lead value (as lead_cost, go figure) in
 * its response.
 *
 * In essence, the lead should be bought any time the lead value is
 * greater than (or equal to) the price point. Conversely, DataX should
 * not be contacted again until the lead cost is lower than the reported
 * lead value. This rule caches the reported lead value in
 * state_data->lead_cost, which is eventually stored in the state object.
 *
 * Price point decisioning differentiates between so called "hard"
 * and "soft" failures: a hard failure will never be bought at any price
 * point, while a soft failure stands to be bought at a lower price
 * point (assuming it isn't bought by someone else first). As the base rule
 * caches its decision in the state data (and state object), we must clear
 * that cache on any soft failure to ensure that DataX is called again when
 * we reach an acceptable price point.
 *
 * DataX will only run as many rules as are necessary to determine that the
 * lead should not be bought at the current price point (or it reaches a
 * hard failure). Consequently, it is VITAL that the track hash returned from
 * the first call be reused on ALL subsequent calls to ensure that DataX knows
 * which rules still need to be run.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_PricedDataX extends VendorAPI_Blackbox_Rule_DataX
{
	/**
	 * @var int
	 */
	protected $price_point;

	/**
	 * @var int
	 */
	protected $lead_cost;

	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param TSS_DataX_Call $call
	 * @param int $price_point
	 * @param bool $rework
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, TSS_DataX_Call $call, $price_point, $rework = FALSE)
	{
		$this->price_point = $price_point;
		parent::__construct($log, $call, $rework, TRUE);
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/VendorAPI_Blackbox_Rule#onValid()
	 */
	public function onValid(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		parent::onValid($data, $state);

		if ($this->response instanceof TSS_DataX_IPricedResponse)
		{
			// save the lead cost reported by DataX
			$state->lead_cost = $this->response->getLeadCost();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/Rule/VendorAPI_Blackbox_Rule_DataX#onInvalid()
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		parent::onInvalid($data, $state);

		if ($this->response instanceof TSS_DataX_IPricedResponse)
		{
			// save the lead cost reported by DataX
			$state->lead_cost = $this->response->getLeadCost();
		}
	}

	/**
	 * Returns how this failure should be reported to lead sources.
	 * @return int
	 */
	protected function determineFailType()
	{
		if (
			($this->response instanceof TSS_DataX_IPricedResponse && $this->response->isSoftFailure())
			|| (isset($this->lead_cost) && $this->price_point > $this->lead_cost)
		)
		{
			return VendorAPI_Blackbox_FailType::FAIL_CAMPAIGN;
		}
		else
		{
			return parent::determineFailType();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/Rule/VendorAPI_Blackbox_Rule_DataX#runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		$this->lead_cost = $state->lead_cost;
		// if we've already called DataX at another price point
		// and received a cost, we won't call them again until
		// the cost is lower than our price point
		if (isset($this->lead_cost)
			&& $this->price_point > $this->lead_cost)
		{
			return FALSE;
		}

		$valid = parent::runRule($data, $state);

		// have to clear the cache for a soft failure
		// to make sure we call again
		if ($this->response instanceof TSS_DataX_IPricedResponse
			&& $this->response->isSoftFailure())
		{
			$state->uw_decision = NULL;
		}

		return $valid;
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/Rule/VendorAPI_Blackbox_Rule_DataX#getRequestData()
	 */
	protected function getRequestData(VendorAPI_Blackbox_Data $data)
	{
		$request = parent::getRequestData($data);
		$request['lead_cost'] = $this->price_point;
		return $request;
	}
}

?>
