<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the OLPBlackbox_Rule_WithheldTargets class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_WithheldTargetsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testIsValid.
	 *
	 * @return array
	 */
	public static function isValidDataProvider()
	{
		return array(
			array(array(), 'test', TRUE),
			array(array('test'), 'test', FALSE),
			array(array('test2', 'test'), 'test', FALSE),
			array(array('test2', 'test'), 'notin', TRUE)
		);
	}

	/**
	 * Tests the isValid to make sure runRule runs correctly.
	 *
	 * @param array $withheld_targets the withheld targets list
	 * @param string $campaign_name the current campaign name
	 * @param bool $expected the expected return value of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($withheld_targets, $campaign_name, $expected)
	{
		$data = new OLPBlackbox_Data();
		$data->withheld_targets = $withheld_targets;
		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));
		
		$rule = $this->getMock('OLPBlackbox_Rule_WithheldTargets', array('hitStat', 'hitEvent'));
		
		$valid = $rule->isValid($data, $state_data);
		$this->assertEquals($expected, $valid);
	}
}
?>
