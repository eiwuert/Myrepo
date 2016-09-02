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
	 * Set up this function
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!file_exists(BFW_CODE_DIR . 'accept_ratio_singleton.class.php'))
		{
			// Hack remove me once cruise control works with bfw!
			$this->markTestSkipped('Could not access bfw.1 to get accept ratio singleton.');
		}

		include_once BFW_CODE_DIR . 'accept_ratio_singleton.class.php';
	}

	/**
	 * Data provider for frequency score test.
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		return array(
			array(TRUE, TRUE),
			array(FALSE, FALSE)
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
	public function testFrequencyScore($freq_return, $expected)
	{
		$data = new OLPBlackbox_Data();
		$data->email_primary = 'test@test.com';
		$state_data = new OLPBlackbox_TargetStateData();

		$freq_obj = $this->getMock('FakeFreqScore', array('testLimits', 'addPost'));
		$freq_obj->expects($this->once())->method('testLimits')->will($this->returnValue($freq_return));

		$rule = $this->getMock(
			'OLPBlackbox_Rule_FrequencyScore',
			array('hitStat', 'hitEvent', 'getDbInstance', 'getFrequencyScoreInstance'),
			array(array())
		);
		$rule->expects($this->once())->method('getFrequencyScoreInstance')->will($this->returnValue($freq_obj));

		$rule->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'email_primary',
				Blackbox_StandardRule::PARAM_VALUE => ''
			)
		);

		$this->assertEquals($expected, $rule->isValid($data, $state_data));
	}
}

/**
 * Fake frequency scoring object.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class FakeFreqScore
{
	/**
	 * Fake testLimits function.
	 *
	 * @return bool
	 */
	public function testLimits()
	{
		// Does nothing
	}

	/**
	 * Fake addPost function.
	 *
	 * @return void
	 */
	public function addPost()
	{
		// Does nothing
	}
}
?>