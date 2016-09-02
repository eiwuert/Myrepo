<?php
/**
 * Interface for DataX responses that need to check for loan amount decisions.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface TSS_DataX_ILoanAmountResponse extends TSS_DataX_IPerformanceResponse
{
	/**
	 * Checks for the LoanAmount decision in the DataX packet.
	 *
	 * @return bool
	 */
	public function getLoanAmountDecision();
}
