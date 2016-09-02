<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_PriorityPicker class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_PriorityPickerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests the default pickTarget call, with no targets passed.
	 *
	 * @return void
	 */
	public function testPickTargetDefault()
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();

		$priority_picker = new OLPBlackbox_PriorityPicker();
		$winner = $priority_picker->pickTarget($data, $state_data, array());

		$this->assertFalse($winner);
	}

	/**
	 * Tests that when there is a single target to pick, it returns that target.
	 *
	 * @return void
	 */
	public function testPickTargetSingleTarget()
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$target = new OLPBlackbox_Target('Test', 0);

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('pickTarget'),
			array('test', 0, 100, $target)
		);
		$campaign->expects($this->any())
			->method('pickTarget')
			->will($this->returnValue($target->pickTarget($data)));

		$priority_picker = new OLPBlackbox_PriorityPicker();
		$winner = $priority_picker->pickTarget($data, $state_data, array($campaign));

		$this->assertType('Blackbox_IWinner', $winner);
	}

	/**
	 * Data provider for testPickTargetTwoTargets.
	 *
	 * @return array
	 */
	public static function pickTargetTwoTargetsDataProvider()
	{
		return array(
			array(
				'test',  // $first_name
				'test2', // $second_name
				'test',  // $expected_name
				200      // $random
			),
			array('test', 'test2', 'test2', 1005)
		);
	}

	/**
	 * Test that when allowSnapshot is provided that an accurate snapshot is put in the winner.
	 *
	 * @return void
	 */
	public function testTwoTargetsSnapshot()
	{
		// set up the allowSnapshot flag and debug object
		$config = OLPBlackbox_Config::getInstance();
		if (!isset($config->debug))
		{
			$config->debug = new OLPBlackbox_DebugConf();
		}
		unset($config->allowSnapshot);
		$config->allowSnapshot = TRUE;

		// set up target collection with two campaigns
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$picker = new OLPBlackbox_PriorityPicker();

		$target1 = new OLPBlackbox_Target('ca', 0);
		$target2 = new OLPBlackbox_Target('ca', 0);


		// set up campaigns to choose between
		$campaign1 = new OLPBlackbox_Campaign('ca1', 0, 100, $target1);
		$campaign2 = new OLPBlackbox_Campaign('ca2', 0, 100, $target2);

		// Mock the priority object and overload random() to return a value we expect
		$random = 10;
		$priority_picker = $this->getMock('OLPBlackbox_PriorityPicker', array('random'));
		$priority_picker->expects($this->any())->method('random')
			->will($this->returnValue(array($random, 10)));
		$priority_picker->pickTarget($data, $state_data, array($campaign1, $campaign2));

		$snapshot = $state_data->snapshot;
		$this->assertTrue($snapshot->stack[0]['targets']['ca1']['run']);
		$this->assertFalse($snapshot->stack[0]['targets']['ca2']['run']);
		$this->assertEquals($random, $snapshot->stack[0]['random']);
		$this->assertEquals('priority', $snapshot->stack[0]['picker_type']);
		$this->assertEquals('ca1', $snapshot->stack[0]['winner']);
	}

	/**
	 * Tests pick target with two targets.
	 *
	 * @param unknown_type $first_name    name of the first target and campaign
	 * @param unknown_type $second_name   name of the second target and campaign
	 * @param unknown_type $expected_name name expected for the winner
	 * @param unknown_type $random        the number the random() function will return
	 * @dataProvider pickTargetTwoTargetsDataProvider
	 * @return void
	 */
	public function testPickTargetTwoTargets($first_name, $second_name, $expected_name, $random)
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$first_target = new OLPBlackbox_Target($first_name, 0);
		$second_target = new OLPBlackbox_Target($second_name, 0);

		// First campaign object
		$first_campaign = new OLPBlackbox_Campaign($first_name, 0, 100, $first_target);

		// Second campaign object
		$second_campaign = new OLPBlackbox_Campaign($second_name, 0, 100, $second_target);

		// Mock the priority object and overload random() to return a value we expect
		$priority_picker = $this->getMock('OLPBlackbox_PriorityPicker', array('random'));
		$priority_picker->expects($this->any())->method('random')
			->will($this->returnValue(array($random, 10)));

		$winner = $priority_picker->pickTarget($data, $state_data, array($first_campaign, $second_campaign));

		$this->assertEquals($expected_name, $winner->getCampaign()->getStateData()->campaign_name);
		$this->assertEquals($expected_name, $winner->getCampaign()->getTarget()->getStateData()->target_name);
	}

	/**
	 * Tests that we return a valid target even if one of the target's pickTarget functions
	 * returns FALSE.
	 *
	 * @return void
	 */
	public function testPickTargetFailOnTarget()
	{
		$first_name = 'test';
		$second_name = 'test2';
		$expected_name = $second_name;
		$random = 100;

		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$first_target = new OLPBlackbox_Target($first_name, 0);
		$second_target = new OLPBlackbox_Target($second_name, 0);

		// First campaign object
		$first_campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('pickTarget'),
			array($first_name, 0, 100, $first_target)
		);
		$first_campaign->expects($this->any())->method('pickTarget')->will($this->returnValue(FALSE));

		// Second campaign object
		$second_campaign = new OLPBlackbox_Campaign($second_name, 0, 100, $second_target);

		// Mock the priority object and overload random() to return a value we expect
		$priority_picker = $this->getMock('OLPBlackbox_PriorityPicker', array('random'));
		$priority_picker->expects($this->any())->method('random')
			->will($this->returnValue(array($random, 10)));

		$winner = $priority_picker->pickTarget($data, $state_data, array($first_campaign, $second_campaign));
		$this->assertEquals($expected_name, $winner->getCampaign()->getStateData()->campaign_name);
		$this->assertEquals($expected_name, $winner->getCampaign()->getTarget()->getStateData()->target_name);
	}

	/**
	 * Tests that we return FALSE if we turn the repick off.
	 *
	 * @return void
	 */
	public function testPickTargetNoRepick()
	{
		$first_name = 'test';
		$second_name = 'test2';
		$expected_name = $second_name;
		$random = 100;

		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$first_target = new OLPBlackbox_Target($first_name, 0);
		$second_target = new OLPBlackbox_Target($second_name, 0);

		// First campaign object
		$first_campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('pickTarget'),
			array($first_name, 0, 100, $first_target)
		);
		$first_campaign->expects($this->any())->method('pickTarget')->will($this->returnValue(FALSE));

		// Second campaign object
		$second_campaign = new OLPBlackbox_Campaign($second_name, 0, 100, $second_target);

		// Mock the priority object and overload random() to return a value we expect
		$priority_picker = $this->getMock(
			'OLPBlackbox_PriorityPicker',
			array('random'),
			array(FALSE)
		);
		$priority_picker->expects($this->any())->method('random')
			->will($this->returnValue(array($random, 10)));

		$winner = $priority_picker->pickTarget($data, $state_data, array($first_campaign, $second_campaign));

		$this->assertFalse($winner);
	}
}
?>
