<?php
/**
 * Unit tests for OLPECash_LoanType
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 *
 */
class OLPECash_LoanTypeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testLoanTypeShort()
	 *
	 * @return array
	 */
	public static function loanTypeShortDataProvider()
	{
		return array(
			array('generic', OLPECash_LoanType::TYPE_PAYDAY, 'payday_loan'),
		);
	}
	
	/**
	 * Ensures that loan type shorts are returning properly
	 *
	 * @param string $property Property short
	 * @param string $loan_type Constant from the OLPECash_LoanType class 
	 * @param string $expected Expected value
	 * @dataProvider loanTypeShortDataProvider
	 * @return NULL
	 */
	public function testLoanTypeShort($property, $loan_type, $expected)
	{
		$this->assertEquals($expected, OLPECash_LoanType::getLoanTypeShort($property, $loan_type));
	}
}

?>
