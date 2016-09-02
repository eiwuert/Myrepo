<?php
/** Tests VendorAPI Blackbox Rule.
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Blackbox_RuleTest extends PHPUnit_Framework_TestCase
{
	protected $_data;
	protected $_state;
	protected $_rule;

	protected function setUp()
	{
		//$this->rule = new RuleTest();
		$this->_data = new VendorAPI_Blackbox_Data();
		$this->_state = new VendorAPI_Blackbox_StateData();

		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		$this->_rule = $this->getMock('VendorAPI_Blackbox_Rule', array('canRun', 'runRule'), array($log));
	}

	protected function tearDown()
	{
		$this->_rule = NULL;
		$this->_data = NULL;
		$this->_state = NULL;
	}

	public function testIsValidReturnsTrueWhenSkippableIsTrue()
	{
		$this->_rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(FALSE));

		$this->_rule->setSkippable(TRUE);

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertTrue($valid);
	}

	public function testIsValidReturnsFalseWhenSkippableIsFalse()
	{
		$this->_rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(FALSE));

		$this->_rule->setSkippable(FALSE);

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertFalse($valid);
	}
}
