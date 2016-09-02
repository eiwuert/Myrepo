<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_OrderedRuleCollection.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_OrderedRuleCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test to see that if the first rule in an ordered rule collection returns
	 * true, we dont try to run any other rules.
	 *
	 * @return void
	 */
	public function testStopAfterValid()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_TargetStateData();

		$ordered_rule_collection = new OLPBlackbox_OrderedRuleCollection();
		$rule_collection1 = new Blackbox_RuleCollection();
		$rule_collection2 = new Blackbox_RuleCollection();

		// This rule passes, so rule collection 2 should never run.
		$rule_one = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_one->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		$rule_collection1->addRule($rule_one);

		// This rule should never be ran since the previous on passed.
		$rule_two = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_two->expects($this->never())->method('isValid');
		$rule_collection2->addRule($rule_two);

		$ordered_rule_collection->addRule($rule_collection1);
		$ordered_rule_collection->addRule($rule_collection2);

		$valid = $ordered_rule_collection->isValid($data, $state_data);
		$this->assertTrue($valid);
	}

	/**
	 * Test to see that if the first rule in the ordered collection fails
	 * that we continue processing the rules.
	 *
	 * @return void
	 */
	public function testFirstFailsRunSecond()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_TargetStateData();

		$ordered_rule_collection = new OLPBlackbox_OrderedRuleCollection();
		$rule_collection1 = new Blackbox_RuleCollection();
		$rule_collection2 = new Blackbox_RuleCollection();

		// This rule doesnt pass, so rule collection 2 should be ran.
		$rule_one = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_one->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$rule_collection1->addRule($rule_one);

		// This rule should never be ran since the previous on passed.
		$rule_two = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_two->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		$rule_collection2->addRule($rule_two);

		$ordered_rule_collection->addRule($rule_collection1);
		$ordered_rule_collection->addRule($rule_collection2);

		$valid = $ordered_rule_collection->isValid($data, $state_data);
		$this->assertTrue($valid);
	}

	/**
	 * Test to see that if the first rule in the ordered collection fails
	 * that we continue processing the rules.
	 *
	 * @return void
	 */
	public function testFirstFailsRunSecondFailOnThird()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_TargetStateData();

		$ordered_rule_collection = new OLPBlackbox_OrderedRuleCollection();
		$rule_collection1 = new Blackbox_RuleCollection();
		$rule_collection2 = new Blackbox_RuleCollection();

		// This rule doesnt pass, so rule collection 2 should be ran.
		$rule_one = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_one->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$rule_collection1->addRule($rule_one);

		// This rule should never be ran since the previous on passed.
		$rule_two = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_two->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		$rule_collection2->addRule($rule_two);

		$rule_three = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_three->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$rule_collection2->addRule($rule_three);

		$ordered_rule_collection->addRule($rule_collection1);
		$ordered_rule_collection->addRule($rule_collection2);

		$valid = $ordered_rule_collection->isValid($data, $state_data);
		$this->assertFALSE($valid);
	}

	/**
	 * Test to see that if there are no valid rules an isValid response
	 * of FALSE is returned.
	 *
	 * @return void
	 */
	public function testNoValidRules()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_TargetStateData();

		$ordered_rule_collection = new OLPBlackbox_OrderedRuleCollection();
		$rule_collection1 = new Blackbox_RuleCollection();
		$rule_collection2 = new Blackbox_RuleCollection();

		// This rule passes, so rule 2 should never run.
		$rule_one = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_one->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$rule_collection1->addRule($rule_one);

		// This rule should never be ran since the previous on passed.
		$rule_two = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rule_two->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$rule_collection2->addRule($rule_two);

		$ordered_rule_collection->addRule($rule_collection1);
		$ordered_rule_collection->addRule($rule_collection2);

		$valid = $ordered_rule_collection->isValid($data, $state_data);
		$this->assertFalse($valid);
	}
}
?>
