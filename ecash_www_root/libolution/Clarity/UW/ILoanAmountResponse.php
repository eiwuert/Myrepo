<?php
/**
 * Interface for Clarity responses that need to check for loan amount decisions.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface Clarity_UW_ILoanAmountResponse extends Clarity_UW_IPerformanceResponse
{
	/**
	 * Checks for the LoanAmount decision in the Clarity packet.
	 *
	 * @return bool
	 */
	public function getLoanAmountDecision();
}
