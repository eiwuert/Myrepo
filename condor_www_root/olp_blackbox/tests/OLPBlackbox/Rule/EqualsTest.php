<?php
/**
 * EqualsTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_Equals class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_EqualsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testEqual.
	 *
	 * @return void
	 */
	public static function equalDataProvider()
	{
		return array(
			array('woot', 'woot', TRUE), // matches
			array('woot', 'blah', FALSE) // doesn't match
		);
	}
	
	/**
	 * Test for the equal rule.
	 *
	 * @param bool $data_value the data value used
	 * @param bool $rule_value the rule value used
	 * @param bool $expected   the expected return
	 * @dataProvider equalDataProvider
	 * @return void
	 */
	public function testEqual($data_value, $rule_value, $expected)
	{
			$data = new Blackbox_DataTestObj(array('test' => $data_value));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock(
				'OLPBlackbox_Rule_Equals',
				array('hitStat', 'hitEvent')
			);
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'test',
				OLPBlackbox_Rule::PARAM_VALUE  => $rule_value,
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertEquals($expected, $v);
	}
}
?>
