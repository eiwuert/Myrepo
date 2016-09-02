<?php
/**
 * OLPBlackbox_Rule_BankAccountTypeTest PHPUnit test file.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_BankAccountType class.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_Rule_BankAccountTypeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for the test cases we want to run..
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		// Expected Return, Rule Value, Data Value
		return array(
			array(TRUE, 'CASECHECK', 'CASECHECK'), // Upper case test
			array(TRUE, 'casecheck', 'casecheck'), // Lower case test
			array(TRUE, 'Casecheck', 'Casecheck'), // Init Cap test
			array(TRUE, 'CASECHECK', 'casecheck'), // Mixed Case test #1
			array(TRUE, 'casecheck', 'CASECHECK'), // Mixed Case test #2
			array(TRUE, 'CASECHECK', 'Casecheck'), // Mixed Case test #3
			
			// Savings rule value tests
			array(TRUE, 'SAVINGS', 'SAVINGS'), // Savings/Savings test
			array(FALSE, 'SAVINGS', 'CHECKING'), // Savings/Checking test
			array(FALSE, 'SAVINGS', 'NONE'), // Savings/None test
			
			// Checking rule value tests
			array(TRUE, 'CHECKING', 'CHECKING'), // Checking/Checking test
			array(FALSE, 'CHECKING', 'SAVINGS'), // Checking/Savings test
			array(FALSE, 'CHECKING', 'NONE'), // Checking/None test

			// "Both" rule value tests
			array(TRUE, '', 'CHECKING'), // Checking/Checking test
			array(TRUE, '', 'SAVINGS'), // Checking/Savings test
			array(FALSE, '', 'NONE'), // Checking/None test
		);
	}

	/**
	 * Run all of our test cases to make sure the different data combinations
	 * return the expected result.
	 *
	 * @param bool $expected The expected result of the test
	 * @param string $rule_value Rule Value - allowed account type
	 * @param string $data_value Data Value - customer account type
	 *
	 * @return void
	 *
	 * @dataProvider dataProvider
	 */
	public function testBankAccountType($expected, $rule_value, $data_value)
	{
		$data = new OLPBlackbox_Data();
		$data->bank_account_type = $data_value;
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock(
			'OLPBlackbox_Rule_BankAccountType',
			array('hitStat', 'hitEvent')
		);
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'bank_account_type',
			Blackbox_StandardRule::PARAM_VALUE => $rule_value,
		));

		$v = $rule->isValid($data, $state_data);

		$this->assertEquals($expected, $v);
	}

}
?>
