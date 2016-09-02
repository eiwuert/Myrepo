<?php

/**
 * @group requires_blackbox
 */
class OLPBlackbox_Rule_NoBillRateDecoratorTest extends PHPUnit_Framework_TestCase
{
	protected $_mock_rule;
	protected $_decorator;
	
	public function setUp()
	{
		$this->_mock_rule = $this->getMock('BillRateTestRule', array(), array(), '', FALSE);
		$this->_decorator = new OLPBlackbox_Rule_NoBillRateDecorator($this->_mock_rule);
		$this->_decorator->setStatHit(FALSE);
	}
	
	
	public static function badBillRateProvider()
	{
		return array(
			array(NULL),
			array(FALSE),
		);
	}
	
	/**
	 * @dataProvider badBillRateProvider
	 */
	public function testNoBillRateStatHit($price_point_value)
	{
		$state_data = new OLPBlackbox_CampaignStateData();
		$state_data->price_point = $price_point_value;

		$bbx_data = new OLPBlackbox_Data();
		$this->_mock_rule->expects($this->once())->method('hitTargetStat')
			->with('no_bill_rate', $this->isInstanceOf('Blackbox_Data'), $this->isInstanceOf('Blackbox_IStateData'));
			
		$this->_decorator->isValid($bbx_data, $state_data);	
	}
	
	public static function goodBillRateProvider()
	{
		return array(
			array(0),
			array(12),
			array(80),
		);
	}

	/**
	 * @dataProvider goodBillRateProvider
	 */
	public function testNoBillRateStatNotHit($price_point_value)
	{
		$state_data = new OLPBlackbox_CampaignStateData();
		$state_data->price_point = $price_point_value;

		$bbx_data = new OLPBlackbox_Data();
		$this->_mock_rule->expects($this->never())->method('hitTargetStat');
			
		$this->_decorator->isValid($bbx_data, $state_data);
	}

	public function testStaticVariablePreventsStatHit()
	{
		$state_data = new OLPBlackbox_CampaignStateData();

		$bbx_data = new OLPBlackbox_Data();
		$this->_mock_rule->expects($this->never())->method('hitTargetStat');

		$this->_decorator->setStatHit(TRUE);
		$this->_decorator->isValid($bbx_data, $state_data);
	}

	public static function isValidReturnProvider()
	{
		return array(
			array(TRUE),
			array(FALSE)
		);
	}
	
	/**
	 * @dataProvider isValidReturnProvider
	 */
	public function testNoBillRateIsValidReturnsChildIsValid($expected)
	{
		$state_data = new OLPBlackbox_CampaignStateData();

		$bbx_data = new OLPBlackbox_Data();
		
		$this->_mock_rule->expects($this->once())->method('isValid')
			->with($this->isInstanceOf('Blackbox_Data'), $this->isInstanceOf('Blackbox_IStateData'))
			->will($this->returnValue($expected));
			
		$this->assertEquals($expected, $this->_decorator->isValid($bbx_data, $state_data));
	}
}

class BillRateTestRule extends OLPBlackbox_Rule
{
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
}
