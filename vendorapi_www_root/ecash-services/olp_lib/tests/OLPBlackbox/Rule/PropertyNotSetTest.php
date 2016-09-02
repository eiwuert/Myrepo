<?php
/**
 * OLPBlackbox_Rule_PropertyNotSet test case.
 * @todo All the "Not" rules should possibly be converted to use a "Not" RuleDecorator.
 */
class OLPBlackbox_Rule_PropertyNotSetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var OLPBlackbox_Data
	 */
	protected $blackbox_data;
	
	/**
	 * @var Blackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * @var OLPBlackbox_Config
	 */
	protected $config;
	
	protected function setUp()
	{
		$this->blackbox_data = new OLPBlackbox_Data();
		$this->blackbox_data->application_id = 123;
		
		$this->state_data = new Blackbox_StateData(array('name' => 'statedata'));
		
		$this->config = new OLPBlackbox_Config();
		$this->config->present_flag = 'hooray';
	}
	
	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid($expected_validity, $flag, $source)
	{
		$rule = $this->freshPropertyNotSetRule($flag, $source);
		
		$this->assertEquals(
			$expected_validity, 
			$rule->isValid($this->blackbox_data, $this->state_data), 
			"Rule with flag $flag produced the wrong results."
		);
	}
	
	public static function isValidProvider()
	{
		return array(
			array(FALSE, 'application_id', OLPBlackbox_Config::DATA_SOURCE_BLACKBOX),
			array(TRUE, 'missing_flag', OLPBlackbox_Config::DATA_SOURCE_BLACKBOX),
			array(FALSE, 'present_flag', OLPBlackbox_Config::DATA_SOURCE_CONFIG),
			array(TRUE, 'missing_flag', OLPBlackbox_Config::DATA_SOURCE_CONFIG),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * @return OLPBlackbox_Rule_PropertyNotSet
	 */
	protected function freshPropertyNotSetRule($flag, $source)
	{
		$mock = $this->getMock(
			'OLPBlackbox_Rule_PropertyNotSet', 
			array('getConfig'), 
			array($flag, $source)
		);
		$mock->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($this->config));
		return $mock;
	}
}

