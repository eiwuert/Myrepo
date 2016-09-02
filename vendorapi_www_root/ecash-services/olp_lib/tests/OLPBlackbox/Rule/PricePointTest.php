<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the Price point rule.
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class OLPBlackbox_Rule_PricePointTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for price point test.
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		return array(
			array(40, 10, FALSE),
			array(20, 30, TRUE),
			array(20, 20, TRUE),
		);
	}

	/**
	 * Tests that the price point rule runs correctly.
	 *
	 * @param numeric $price
	 * @param numeric $offered_at
	 * @param bool $expected
	 * @par
	 * @dataProvider dataProvider
	 * @return void
	 */
	public function testPricePoint($price, $offered_at, $expected)
	{
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		$group = 'zip';
		$state_data->lender_post_persistent_data = array(
			'offer' => array($group => array('price' => $offered_at))
		);

		$rule = $this->getMock(
			'OLPBlackbox_Rule_PricePoint',
			array('getRuleValue', 'getCampaignPricePoint'),
			array(array())
		);
		$rule->expects($this->once())->method('getRuleValue')->will($this->returnValue(array('group' => $group)));
		$rule->expects($this->once())->method('getCampaignPricePoint')->will($this->returnValue((float)$price));

		$this->assertEquals($rule->isValid($data, $state_data), $expected);
	}
}
?>