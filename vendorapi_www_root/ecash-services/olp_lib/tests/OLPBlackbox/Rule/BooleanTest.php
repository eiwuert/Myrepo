<?php

/**
 * Test the boolean rule class, which is essentially a noop which just returns
 * the value set in the rule.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_BooleanTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Used during isValid() calls.
	 * @see setUp()
	 * @var Blackbox_Data
	 */
	protected $blackbox_data;
	
	/**
	 * Used during isValid() calls.
	 * @see setUp()
	 * @var OLPBlackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * @return void
	 */
	public function setUp()
	{
		$this->blackbox_data = new Blackbox_Data();
		$this->state_data = new OLPBlackbox_StateData();
	}
	
	/**
	 * @dataProvider isValidProvider
	 * @param bool $expected_result The result from isValid we'd like.
	 * @param bool $rule_value The value to set on the rule.
	 * @return void
	 */
	public function testIsValid($expected_result, $rule_value)
	{
		$rule = $this->freshBooleanRule();
		$rule->setRuleValue($rule_value);
		
		$result = $rule->isValid($this->blackbox_data, $this->state_data);
		$this->assertEquals(
			$expected_result, 
			$result,
			"Result ($result) of isValid was wrong."
		);
	}
	
	/**
	 * @see testIsValid()
	 * @return array
	 */
	public static function isValidProvider()
	{
		return array(
			array(TRUE, TRUE),
			array(FALSE, FALSE),
		);
	}
	
	/**
	 * A default Boolean rule should return true.
	 * @return void
	 */
	public function testIsValidNoSetup()
	{
		$this->assertEquals(
			TRUE,
			$this->freshBooleanRule()->isValid($this->blackbox_data, $this->state_data),
			'Without configuration, rule did not return TRUE as expected.'
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Make a new Boolean rule.
	 * @return OLPBlackbox_Rule_Boolean
	 */
	protected function freshBooleanRule()
	{
		return new OLPBlackbox_Rule_Boolean();
	}
}

?>