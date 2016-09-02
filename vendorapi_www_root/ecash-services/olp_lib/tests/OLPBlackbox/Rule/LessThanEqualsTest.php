<?php
/**
 * LessThanEqualsTest PHPUnit test file.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_LessThanEquals class.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Rule_LessThanEqualsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testLessThanEquals.
	 *
	 * @return array
	 */
	public static function lessThanEqualsDataProvider()
	{
		return array(
			array(30, 20, FALSE), // greater
			array(10, 20, TRUE), // less than
			array(20, 20, TRUE) // equals
		);
	}
	
	/**
	 * Test for a value that's greater than the rule; should return TRUE
	 *
	 * @param bool $data_value the data value used
	 * @param bool $rule_value the rule value used
	 * @param bool $expected   the expected return
	 * @dataProvider lessThanEqualsDataProvider
	 * @return void
	 */
	public function testLessThanEquals($data_value, $rule_value, $expected)
	{
		$data = new Blackbox_DataTestObj(array('test' => $data_value));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_LessThanEquals', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			OLPBlackbox_Rule::PARAM_FIELD => 'test',
			OLPBlackbox_Rule::PARAM_VALUE  => $rule_value,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals($expected, $v);
	}
}
?>
