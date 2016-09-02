<?php
/**
 * Commercial Qualify interface
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface ECash_IQualify
{
	/**
	 * Sets the business rules for Qualify.
	 *
	 * @param array $rules
	 * @return void
	 */
	public function setBusinessRules(array $rules);
	
	/**
	 * Sets the loan type name.
	 *
	 * @param string $loan_type_name
	 * @return void
	 */
	public function setLoanTypeName($loan_type_name);

	/**
	 * Sets the rate calculator on the qualify object.
	 * Not used by IMPACT
	 * 
	 * @param ECash_Transactions_IRateCalculator $rate_calculator
	 * @return void
	 */
	public function setRateCalculator(ECash_Transactions_IRateCalculator $rate_calculator);
}
