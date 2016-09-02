<?php
/**
 *AbaCheckTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_AbaCheck class.
 *
 * @group rules
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_AbaCheckTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for the test cases we want to run..
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		// Expected Return, ABA, Account Number
		return array(
			array(FALSE, '114924742', '13245'), // ABA is on list
			array(FALSE, '044000037', '635858443'), // ABA/account combo is on list
			array(TRUE, '123456780', '13245'), // No bad aba match found
		);
	}
	
	/**
	 * Run all of our test cases to make sure the different data combinations
	 * return the expected result.
	 *
	 * @param bool $expected The expected result of the test
	 * @param string $aba The bank aba
	 * @param string $account The bank account number
	 *
	 * @return void
	 *
	 * @dataProvider dataProvider
	 */
	public function testAbaCheck($expected, $aba, $account)
	{
		$data = new Blackbox_DataTestObj(array('bank_aba'=>$aba,'bank_account'=>$account));
		$state_data = new OLPBlackbox_StateData();
		
		$rule = $this->getMock('OLPBlackbox_Rule_AbaCheck', array('hitStat', 'hitEvent'));
		
		$v = $rule->isValid($data, $state_data);
		
		$this->assertSame($expected, $v);
	}
	
}
?>
