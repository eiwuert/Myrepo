<?php

class AALM_DataX_Responses_Perf extends TSS_DataX_Response implements TSS_DataX_IPerformanceResponse, TSS_DataX_ILoanAmountResponse, TSS_DataX_IAutoFundResponse
{

	const LOAN_AMOUNT_INCREASE = 'INCREASE';

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#isValid()
	 */
	public function isValid()
	{
		return $this->getDecision() == 'Y';
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#getDecision()
	 */
	public function getDecision()
	{
		return $this->findNode('//GlobalDecision/Result');
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#getScore()
	 */
	public function getScore()
	{
		return 0;
	}

	/**
	 * Returns an array of the buckets and their decisions
	 * @see code/ECash/DataX/TSS_DataX_IResponse#getDecisionBuckets()
	 * @return array
	 */
	public function getDecisionBuckets()
	{
		return $this->getGlobalDecisionBuckets();
	}

	/**
	 * Gets the decision specific to a segment
	 *
	 * NOTE: The IDV segment is named 'ConsumerIDVerification', and this doesn't work for it
	 * @param string $segment_name
	 * @return null|string
	 */
	public function getSegmentDecision($segment_name)
	{
		switch ($segment_name)
		{
			case 'IDV':
				return $this->findNode('//IDVSegment/CustomDecision/Result');
		}
		return $this->findNode('//'.$segment_name.'Segment/Decision/Result');
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#isIDVFailure()
	 */
	public function isIDVFailure()
	{
		$result = $this->findNode('//IDVSegment/CustomDecision/Result');
		return $result != 'Y';
	}

	/**
	 * Used to determine whether or not the loan amount increase applies
	 */
	public function getLoanAmountDecision()
	{
		$decision = $this->findNode('//GlobalDecision/LoanAmount');
		if(strcasecmp($decision, self::LOAN_AMOUNT_INCREASE) == 0)
		{
			return TRUE;	
		}
		else if(is_numeric($decision) && $decision > 0)
		{
			return $decision;		
		}
		else
		{
			return FALSE;
		}
		
	}
	/**
	 * Used to determine whether or not the loan should be auto funded
	 */
	public function getAutoFundDecision()
	{
		$decision = $this->findNode('//AutoFundDecision/Result');
		return $decision == 'Y';
	}

}

?>
