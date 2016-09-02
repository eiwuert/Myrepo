<?php
/**
 * Interface for FactorTrust responses that need to check for loan amount decisions.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface FactorTrust_UW_ILoanAmountResponse extends FactorTrust_UW_IPerformanceResponse
{
	/**
	 * Checks for the LoanAmount decision in the FactorTrust packet.
	 *
	 * @return bool
	 */
	public function getLoanAmountDecision();
}
