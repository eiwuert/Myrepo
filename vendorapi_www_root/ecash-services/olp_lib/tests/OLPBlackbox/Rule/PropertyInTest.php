<?php

class OLPBlackbox_Rule_PropertyInTest extends PHPUnit_Framework_TestCase
{
	protected $blackbox_data;
	protected $config;
	protected $state_data;
	
	public function setUp()
	{
		$this->config = new OLPBlackbox_Config();
		$this->config->promo_id = 3113;
		$this->blackbox_data = new OLPBlackbox_Data();
		$this->blackbox_data->name_first = 'Timmy';
		$this->state_data = new OLPBlackbox_StateData();
	}
	
	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid($property, $value, $data_source, $expected_result)
	{
		$rule = $this->getFreshPropertyInRule($property, $value, $data_source);
		$this->assertEquals(
			$expected_result, 
			$rule->isValid($this->blackbox_data, $this->state_data),
			'Rule outcome unexpected'
		);
	}
	
	public static function isValidProvider()
	{
		return array(
			array('promo_id', array(3113, 9090), OLPBlackbox_Config::DATA_SOURCE_CONFIG, TRUE),
			array('promo_id', array(1111, 2222), OLPBlackbox_Config::DATA_SOURCE_CONFIG, FALSE),
			array('missing_key', array(3113), OLPBlackbox_Config::DATA_SOURCE_CONFIG, FALSE),
			array('name_first', array('Timmy', "O'hare"), OLPBlackbox_Config::DATA_SOURCE_BLACKBOX, TRUE),
			array('name_first', array('Brian'), OLPBlackbox_Config::DATA_SOURCE_BLACKBOX, FALSE),
			array('weird_key', array('Tommy', 'Timmy'), OLPBlackbox_Config::DATA_SOURCE_BLACKBOX, FALSE),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * @return OLPBlackbox_Rule_PropertyIn
	 */
	protected function getFreshPropertyInRule($property, $value, $data_source)
	{
		$rule = $this->getMock(
			'OLPBlackbox_Rule_PropertyIn', 
			array('getConfig'), 
			array($property, $value, $data_source)
		);
		$rule->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($this->config));
		return $rule;
	}
}

?>