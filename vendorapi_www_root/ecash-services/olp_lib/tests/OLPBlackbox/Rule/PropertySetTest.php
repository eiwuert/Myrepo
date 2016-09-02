<?php

/**
 * Test the PropertySet rule which checks a data source for a property being set. 
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_PropertySetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @see setUp()
	 * @var OLPBlackbox_Config
	 */
	protected $blackbox_config;
	
	/**
	 * @see setUp()
	 * @var Blackbox_Data
	 */
	protected $blackbox_data;
	
	/**
	 * @see setUp()
	 * @var OLPBlackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * Set up data for the tests.
	 * @return void
	 */
	protected function setUp()
	{
		$this->blackbox_config = new OLPBlackbox_Config();
		$this->blackbox_config->promo_id = 1238;
		
		$this->blackbox_data = new Blackbox_Data();
		$this->blackbox_data->target = 'ABC';
		
		$this->state_data = new OLPBlackbox_StateData();
	}
	
	/**
	 * Test that if a string is passed in for a data source and it's not a valid
	 * class from the class constants of OLPBlackbox_Rule_PropertySet that an
	 * InvalidArgumentException is thrown.
	 * 
	 * @return void
	 */
	public function testInvalidArguments()
	{
		$this->setExpectedException('InvalidArgumentException');
		$this->freshPropertySetRule('Property', 'Invalid Source Type');
	}
	
	/**
	 * Test basic functionality of this rule which is checking data source objects
	 * for properties being set.
	 * 
	 * @dataProvider isValidProvider
	 * @param string $property The property name to test using isset()
	 * @param string $source_type Flag indicating whether to use Blackbox_Data,
	 * Blackbox_Config or Blackbox_StateData as the location of the flag.
	 * @return void
	 */
	public function testIsValid($property, $source_type, $expected_valid)
	{
		$rule = $this->freshPropertySetRule($property, $source_type);
		
		$this->assertEquals(
			$expected_valid,
			$rule->isValid($this->blackbox_data, $this->state_data),
			'Result of isValid() was not correct.'
		);
	}
	
	/**
	 * @see testIsValid()
	 * @return array
	 */
	public static function isValidProvider()
	{
		return array(
			array('target', OLPBlackbox_Config::DATA_SOURCE_BLACKBOX, TRUE),
			array('banana_code', OLPBlackbox_Config::DATA_SOURCE_BLACKBOX, FALSE),
			array('promo_id', OLPBlackbox_Config::DATA_SOURCE_CONFIG, TRUE),
			array('banana_id', OLPBlackbox_Config::DATA_SOURCE_CONFIG, FALSE),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Create a new PropertySet rule.
	 * @return OLPBlackbox_Rule_PropertySet
	 */
	protected function freshPropertySetRule($flag, $source_type)
	{
		$rule = $this->getMock('OLPBlackbox_Rule_PropertySet',
			array('getConfig'),
			array($flag, $source_type)
		);
		$rule->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($this->blackbox_config));
		
		return $rule;
	}
}

?>