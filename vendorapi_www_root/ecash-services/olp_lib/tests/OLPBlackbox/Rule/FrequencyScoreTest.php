<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the FrequencyScore rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_FrequencyScoreTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for frequency score test.
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		return array(
			array(1, array('min_freq' => 0, 'max_freq' => 4), TRUE),
			array(1, array('min_freq' => 1, 'max_freq' => 4), TRUE),
			array(1, array('min_freq' => 2, 'max_freq' => 4), FALSE),
			array(5, array('min_freq' => 0, 'max_freq' => 4), FALSE),
			array(5, array('min_freq' => 0, 'max_freq' => 5), TRUE),
			array(5, array('min_freq' => 0, 'max_freq' => 6), TRUE),
			array(1, array(0, 0, 0, 0, 0, 0), TRUE),  // make sure incorrectly formatted limits will pass
		);
	}

	/**
	 * Tests that the frequency score rule runs correctly.
	 *
	 * @param bool $freq_return the return value of testLimit
	 * @param bool $expected the expected return from isValid
	 * @dataProvider dataProvider
	 * @return void
	 */
	public function testFrequencyScore($freq_return, $limits, $expected)
	{
		$data = new OLPBlackbox_Data();
		$data->email_primary = 'test@test.com';
		$state_data = new OLPBlackbox_TargetStateData();

		$freq_obj = $this->getMock(
			'OLP_FrequencyScore',
			array('getRejectsByHistory'),
			array(),
			'',
			FALSE
		);
		$freq_obj->expects($this->any())->method('getRejectsByHistory')->will($this->returnValue($freq_return));

		$rule = $this->getMock(
			'OLPBlackbox_Rule_FrequencyScore',
			array('hitStat', 'hitEvent', 'getDbInstance', 'getFrequencyScoreInstance', 'getRuleValue'),
			array(array())
		);
		$rule->expects($this->once())->method('getRuleValue')->will($this->returnValue($limits));
		$rule->expects($this->any())->method('getFrequencyScoreInstance')->will($this->returnValue($freq_obj));

		$rule->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'email_primary',
				Blackbox_StandardRule::PARAM_VALUE => ''
			)
		);

		$this->assertEquals($expected, $rule->isValid($data, $state_data));
	}
}

?>