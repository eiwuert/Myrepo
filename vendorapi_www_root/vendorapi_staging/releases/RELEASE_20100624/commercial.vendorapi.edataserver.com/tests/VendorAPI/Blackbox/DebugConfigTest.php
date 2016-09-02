<?php

class VendorAPI_Blackbox_DebugConfigTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->_config = new VendorAPI_Blackbox_DebugConfig();
	}

	public function testFlagTrueIsTypeStrict()
	{
		$this->_config->setFlag('TEST', 1);
		$this->assertFalse($this->_config->flagTrue('TEST'));
	}

	public function testFlagTrueIsFalseForUnsetFlags()
	{
		$this->assertFalse($this->_config->flagTrue('TEST'));
	}

	public function testFlagFalseIsFalseForUnsetFlags()
	{
		$this->assertFalse($this->_config->flagFalse('TEST'));
	}

	public function testFlagFalseIsTypeStrict()
	{
		$this->_config->setFlag('TEST', 0);
		$this->assertFalse($this->_config->flagFalse('TEST'));
	}

	public function testHasFlagIsFalseForUnsetFlags()
	{
		$this->assertFalse($this->_config->hasFlag('TEST'));
	}

	public function testHasFlagIsTrueForSetFlags()
	{
		$this->_config->setFlag('TEST', 1);
		$this->assertTrue($this->_config->hasFlag('TEST'));
	}

	public function testHasFlagIsFalseAfterUnsettingFlag()
	{
		$this->_config->setFlag('TEST', 1);
		$this->_config->unsetFlag('TEST');

		$this->assertFalse($this->_config->hasFlag('TEST'));
	}

	public function testGetFlagReturnsNullForUnsetFlags()
	{
		$this->assertNull($this->_config->getFlag('BOO'));
	}

	public function testFlagsAreSetInConstructor()
	{
		$c = new VendorAPI_Blackbox_DebugConfig(array('TEST' => TRUE));
		$this->assertTrue($c->flagTrue('TEST'));
	}

	public function testFalseFlagTakesPrecedenceOverNoChecks()
	{
		$this->_config->setFlag('TEST', FALSE);
		$this->_config->setFlag('NO_CHECKS', TRUE);

		$this->assertTrue($this->_config->skipRule('TEST'));
	}

	public function testTrueFlagTakesPrecedenceOverNoChecks()
	{
		$this->_config->setFlag('TEST', TRUE);
		$this->_config->setFlag('NO_CHECKS', FALSE);

		$this->assertFalse($this->_config->skipRule('TEST'));
	}

	public function testTrueFlagTakesPrecedenceOverRulesFlag()
	{
		$this->_config->setFlag('TEST', TRUE);
		$this->_config->setFlag('RULES', FALSE);

		$this->assertFalse($this->_config->skipRule('TEST'));
	}

	public function testFalseFlagTakesPrecedenceOverRulesFlag()
	{
		$this->_config->setFlag('TEST', FALSE);
		$this->_config->setFlag('RULES', TRUE);

		$this->assertTrue($this->_config->skipRule('TEST'));
	}

	public function testRulesFlagTakesPrecedenceOverNoChecks()
	{
		$this->_config->setFlag('RULES', TRUE);
		$this->_config->setFlag('NO_CHECKS', FALSE);

		$this->assertFalse($this->_config->skipRule('HI'));
	}

	public function testSkipRuleDefaultsToRules()
	{
		$this->_config->setFlag('RULES', FALSE);
		$this->assertTrue($this->_config->skipRule());
	}

	public function testSkipRuleDefaultsToNoChecks()
	{
		$this->_config->setFlag('NO_CHECKS', TRUE);
		$this->assertTrue($this->_config->skipRule('TEST'));
	}
}

?>