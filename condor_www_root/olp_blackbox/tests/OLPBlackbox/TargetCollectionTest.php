<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_TargetCollection class.
 *
 * @todo Add tests for new repick flag.
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_TargetCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The OLPBlackbox_TargetCollection object used in tests.
	 *
	 * @var OLPBlackbox_TargetCollection
	 */
	protected $target_collection;

	/**
	 * State data to pass around to tests.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;

	/**
	 * Sets up the tests OLPBlackbox_TargetCollection object.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->state_data = new OLPBlackbox_StateData();
		$this->target_collection = new OLPBlackbox_TargetCollection('test');
	}

	/**
	 * Destroys the target collection at the end of each test.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->target_collection);
	}

	/**
	 * Tests that the constructor throws an InvalidArgumentException when it receives and invalid
	 * name.
	 *
	 * @expectedException InvalidArgumentException
	 * @return void
	 */
	public function testConstructorException()
	{
		new OLPBlackbox_TargetCollection(12345);
	}

	/**
	 * Test that pickTarget with no picker defined returns FALSE by default.
	 *
	 * @return void
	 */
	public function testPickTargetNoPicker()
	{
		$data = new Blackbox_Data();

		$target = $this->target_collection->pickTarget($data);
		$this->assertFalse($target);
	}

	/**
	 * Test that pickTarget returns the correct target whith a picker.
	 *
	 * @return void
	 */
	public function testPickTargetWithPicker()
	{
		$data = new Blackbox_Data();
		$winning_target = $this->getMock('OLPBlackbox_Campaign', array('isValid'), array('test', 0, 1));
		$winning_target->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$picker = $this->getMock('OLPBlackbox_PriorityPicker', array('pickTarget'));
		$picker->expects($this->any())->method('pickTarget')->will($this->returnValue($winning_target));

		$this->target_collection->addTarget($winning_target);
		$this->target_collection->setPicker($picker);
		$this->assertTrue($this->target_collection->isValid($data, $this->state_data));

		$target = $this->target_collection->pickTarget($data);
		$this->assertEquals($winning_target, $target);
	}

	/**
	 * Tests that addTarget throws an exception when the passed in target is not a
	 * OLPBlackbox_Campaign.
	 *
	 * @expectedException Blackbox_Exception
	 * @return void
	 */
	public function testAddTargetException()
	{
		$target = new Blackbox_Target();

		$this->target_collection->addTarget($target);
	}

	/**
	 * Simply tests that we can pass a campaign to the target collection and not get an
	 * exception.
	 *
	 * @return void
	 */
	public function testAddTarget()
	{
		$campaign = new OLPBlackbox_Campaign('test', 0, 100);

		$this->target_collection->addTarget($campaign);
	}

	/**
	 * Tests that pickTarget returns just from the valid_list, not from the entire target_list
	 * when a picker is set.
	 *
	 * There was a small bug where we were passing $this->target_list instead of $this->valid_list
	 * to the picker, so it could return an invalid target.
	 *
	 * @return void
	 */
	public function testPickTargetInvalidTargets()
	{
		$data = new Blackbox_Data();

		$campaign_one = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test', 0, 1)
		);
		$campaign_one->expects($this->any())->method('isValid')
			->will($this->returnValue(FALSE));
		$winner_one = new OLPBlackbox_Winner($campaign_one);
		$campaign_one->expects($this->any())->method('pickTarget')
			->will($this->returnValue($winner_one));

		$campaign_two = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test2', 0, 10)
		);
		$winner_two = new OLPBlackbox_Winner($campaign_two);
		$campaign_two->expects($this->any())->method('isValid')
			->will($this->returnValue(TRUE));
		$campaign_two->expects($this->any())->method('pickTarget')
			->will($this->returnValue($winner_two));

		$picker = $this->getMock('OLPBlackbox_PriorityPicker', array('random'));
		$picker->expects($this->any())->method('random')->will($this->returnValue(array(1, 10)));

		$this->target_collection->addTarget($campaign_one);
		$this->target_collection->addTarget($campaign_two);

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		$this->target_collection->setPicker($picker);
		$winner = $this->target_collection->pickTarget($data);
		$this->assertTrue($winner->getCampaign()->isValid($data, $this->state_data));
	}

	/**
	 * Tests the target collection's setInvalid function.
	 *
	 * @return void
	 */
	public function testSetInvalid()
	{
		$data = new OLPBlackbox_Data();

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test', 0, 1)
		);
		$campaign->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));
		$this->target_collection->addTarget($campaign);

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		// Set the rules to return TRUE and verify that isValid is TRUE
		$this->target_collection->setRules($rules);
		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		// Set the collection to be invalid and see that it now returns FALSE
		$this->target_collection->setInvalid();
		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertFalse($valid);
	}

	/**
	 * Data provider for testPickTargetRules.
	 *
	 * @return array
	 */
	public static function pickTargetRulesDataProvider()
	{
		return array(
			array(TRUE, TRUE), // rules return TRUE, we expect the winner to evalutate to true
			array(FALSE, FALSE) // rules return FALSE, expect pickTarget to be FALSE
		);
	}

	/**
	 * Tests that the pickTargetRules affects the outcome.
	 *
	 * @param bool $rule_is_valid validity of the rule
	 * @param bool $expected what we expect from pickTarget
	 * @dataProvider pickTargetRulesDataProvider
	 * @return void
	 */
	public function testPickTargetRules($rule_is_valid, $expected)
	{
		$data = new Blackbox_Data();

		// Mock rule that we'll add to the target collection's pickTargetRules
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue($rule_is_valid));

		$campaign_one = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test', 0, 1)
		);
		$campaign_one->expects($this->any())->method('isValid')
			->will($this->returnValue(TRUE));
		$winner_one = new OLPBlackbox_Winner($campaign_one);
		$campaign_one->expects($this->any())->method('pickTarget')
			->will($this->returnValue($winner_one));

		$this->target_collection->addTarget($campaign_one);
		$this->target_collection->setPickTargetRules($rules);

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		$winner = $this->target_collection->pickTarget($data);

		if ($expected)
		{
			$this->assertType('Blackbox_IWinner', $winner);
		}
		else
		{
			$this->assertFalse($winner);
		}
	}

	/**
	 * Data provider function for testPostTargetRules.
	 *
	 * @return array
	 */
	public static function postTargetRulesDataProvider()
	{
		return array(
			array(TRUE, TRUE),
			array(FALSE, FALSE)
		);
	}

	/**
	 * Tests that the post targets rules run.
	 *
	 * There is no test to check that it works without being set, since this is effectively tested above.
	 *
	 * @param bool $is_valid if the rule returns valid
	 * @param bool $expected what we expect from the collection's isValid call
	 * @dataProvider postTargetRulesDataProvider
	 * @return void
	 */
	public function testPostTargetRules($is_valid, $expected)
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->once())->method('isValid')->will($this->returnValue($is_valid));

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test', 0, 1)
		);
		$campaign->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$this->target_collection->addTarget($campaign);
		$this->target_collection->setPostTargetRules($rule);

		$valid = $this->target_collection->isValid($data, $state_data);
		$this->assertEquals($expected, $valid);
	}
}
?>
