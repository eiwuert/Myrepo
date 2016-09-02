<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_NoFailRuleCollection.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_NoFailRuleCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test to see that we always get back a TRUE with isValid.
	 *
	 * @return void
	 */
	public function testIsValid()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_TargetStateData();

		// This rule passes, processing will continue on to rule 2
		$rule_one = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_one->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		// Fail this rule, processing should stop here
		$rule_two = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_two->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));

		// This rule should never be run, since we failed on the second rule
		$rule_three = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_three->expects($this->never())->method('isValid');

		$rule_collection = new OLPBlackbox_NoFailRuleCollection();
		$rule_collection->addRule($rule_one);
		$rule_collection->addRule($rule_two);

		$valid = $rule_collection->isValid($data, $state_data);
		$this->assertTrue($valid);
	}
}
?>
