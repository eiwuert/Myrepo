<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_PercentPicker class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_PercentPickerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that when there is a single target to pick, it returns that target.
	 *
	 * @return void
	 */
	public function testPickTargetSingleTarget()
	{
		$data = new Blackbox_Data();
		$target = new OLPBlackbox_Target('Test', 0);
		$state_data = new OLPBlackbox_StateData();

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('pickTarget'),
			array('test', 0, 100, $target)
		);
		$campaign->expects($this->any())
			->method('pickTarget')
			->will($this->returnValue($target->pickTarget($data)));

		$percent_picker = new OLPBlackbox_PercentPicker();
		$winner = $percent_picker->pickTarget($data, $state_data, array($campaign));

		$this->assertType('Blackbox_IWinner', $winner);
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
		$percent_picker = new OLPBlackbox_PercentPicker();
		$percent_picker->pickTarget($data, $state_data, array($campaign1, $campaign2));

		$snapshot = $state_data->snapshot;
		$this->assertEquals($campaign1->getStateData()->name, $snapshot->stack[0]['winner']);
		$this->assertEquals('percent', $snapshot->stack[0]['picker_type']);
	}

	/**
	 * Tests pickTarget with two targets, expecting the first one to be picked.
	 *
	 * @return void
	 */
	public function testPickTargetFiveEqual()
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		$first_target = new OLPBlackbox_Target('ufc', 0);
		$second_target = new OLPBlackbox_Target('d1', 0);
		$third_target = new OLPBlackbox_Target('ca', 0);
		$fourth_target = new OLPBlackbox_Target('ucl', 0);
		$fifth_target = new OLPBlackbox_Target('pcl', 0);

		$first_campaign = new OLPBlackbox_Campaign('ufc', 0, 15, $first_target);
		$second_campaign = new OLPBlackbox_Campaign('d1', 0, 15, $second_target);
		$third_campaign = new OLPBlackbox_Campaign('ca', 0, 25, $third_target);
		$fourth_campaign = new OLPBlackbox_Campaign('ucl', 0, 25, $fourth_target);
		$fifth_campaign = new OLPBlackbox_Campaign('pcl', 0, 20, $fifth_target);

		$percent_picker = new OLPBlackbox_PercentPicker();
		$winner = $percent_picker->pickTarget(
			$data,
			$state_data,
			array($first_campaign, $second_campaign, $third_campaign, $fourth_campaign, $fifth_campaign)
		);

		// Verify that the campaign and the target are the one's we expected
		$this->assertType('Blackbox_IWinner', $winner);
		$this->assertEquals('ufc', $winner->getCampaign()->getStateData()->campaign_name);
		$this->assertEquals('ufc', $winner->getCampaign()->getTarget()->getStateData()->target_name);
	}
	
	/**
	 * Data provider for testPickTargetFiveTargetsUnequal.
	 *
	 * @return array
	 */
	public static function pickTargetFiveTargetsUnequalDataProvider()
	{
		return array(
			array(array(15, 15, 24, 25, 20), 'ca'),
			array(array(14, 15, 25, 25, 20), 'ufc'),
			array(array(15, 14, 25, 25, 20), 'd1'),
			array(array(15, 15, 25, 24, 20), 'ucl'),
			array(array(15, 15, 25, 25, 19), 'pcl'),
			array(array(543, 543, 843, 843, 674), 'ca'),
		);
	}

	/**
	 * Tests pickTarget with two targets, expecting the second one to be picked.
	 *
	 * @dataProvider pickTargetFiveTargetsUnequalDataProvider
	 * @return void
	 */
	public function testPickTargetFiveTargetsUnequal($weights, $expected_winner)
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		$first_target = new OLPBlackbox_Target('ufc', 0);
		$second_target = new OLPBlackbox_Target('d1', 0);
		$third_target = new OLPBlackbox_Target('ca', 0);
		$fourth_target = new OLPBlackbox_Target('ucl', 0);
		$fifth_target = new OLPBlackbox_Target('pcl', 0);

		$first_campaign = new OLPBlackbox_Campaign('ufc', 0, 15, $first_target);
		$first_campaign->getStateData()->current_leads = $weights[0];
		
		$second_campaign = new OLPBlackbox_Campaign('d1', 0, 15, $second_target);
		$second_campaign->getStateData()->current_leads = $weights[1];
		
		$third_campaign = new OLPBlackbox_Campaign('ca', 0, 25, $third_target);
		$third_campaign->getStateData()->current_leads = $weights[2];
		
		$fourth_campaign = new OLPBlackbox_Campaign('ucl', 0, 25, $fourth_target);
		$fourth_campaign->getStateData()->current_leads = $weights[3];
		
		$fifth_campaign = new OLPBlackbox_Campaign('pcl', 0, 20, $fifth_target);
		$fifth_campaign->getStateData()->current_leads = $weights[4];

		$percent_picker = new OLPBlackbox_PercentPicker();
		$winner = $percent_picker->pickTarget(
			$data,
			$state_data,
			array($first_campaign, $second_campaign, $third_campaign, $fourth_campaign, $fifth_campaign)
		);

		// Verify that the campaign and the target are the one's we expected
		$this->assertEquals($expected_winner, $winner->getCampaign()->getStateData()->campaign_name);
	}

	/**
	 * Data provider for testPickTargetFiveTargetsUnequal.
	 *
	 * @return array
	 */
	public static function pickTargetThreeTargetsUnequalDataProvider()
	{
		return array(
			array(array(543, 843, 674), 'ca'),
			array(array(16, 24, 20), 'ca')
		);
	}

	/**
	 * Tests pickTarget with three valid targets, but in a collection of 5 targets.
	 *
	 * @dataProvider pickTargetThreeTargetsUnequalDataProvider
	 * @return void
	 */
	public function testPickTargetThreeTargetsUnequal($weights, $expected_winner)
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		$second_target = new OLPBlackbox_Target('d1', 0);
		$third_target = new OLPBlackbox_Target('ca', 0);
		$fifth_target = new OLPBlackbox_Target('pcl', 0);

		$second_campaign = new OLPBlackbox_Campaign('d1', 0, 15, $second_target);
		$second_campaign->getStateData()->current_leads = $weights[0];
		
		$third_campaign = new OLPBlackbox_Campaign('ca', 0, 25, $third_target);
		$third_campaign->getStateData()->current_leads = $weights[1];
		
		$fifth_campaign = new OLPBlackbox_Campaign('pcl', 0, 20, $fifth_target);
		$fifth_campaign->getStateData()->current_leads = $weights[2];

		$percent_picker = new OLPBlackbox_PercentPicker();
		$winner = $percent_picker->pickTarget(
			$data,
			$state_data,
			array($second_campaign, $third_campaign, $fifth_campaign)
		);

		// Verify that the campaign and the target are the one's we expected
		$this->assertEquals($expected_winner, $winner->getCampaign()->getStateData()->campaign_name);
	}

	/**
	 * Tests that if the first target is picked, but returns FALSE from it's pickTarget, it will
	 * move on to the second target.
	 *
	 * @return void
	 */
	public function testPickTargetFailTargetPick()
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$first_target = new OLPBlackbox_Target('test', 0);
		$second_target = new OLPBlackbox_Target('test2', 0);

		// First campaign object
		$first_campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('pickTarget'),
			array('test', 0, 50, $first_target)
		);
		$first_campaign->expects($this->any())->method('pickTarget')->will($this->returnValue(FALSE));
		$first_campaign->getStateData()->current_leads = 49;

		// Second campaign object
		$second_campaign = new OLPBlackbox_Campaign('test2', 0, 50, $second_target);
		$second_campaign->getStateData()->current_leads = 50;

		$percent_picker = new OLPBlackbox_PercentPicker();
		$winner = $percent_picker->pickTarget($data, $state_data, array($first_campaign, $second_campaign));

		// Verify that the campaign and the target are the one's we expected
		$this->assertType('Blackbox_IWinner', $winner);
		$this->assertEquals('test2', $winner->getCampaign()->getStateData()->campaign_name);
		$this->assertEquals('test2', $winner->getCampaign()->getTarget()->getStateData()->target_name);
	}

	/**
	 * Tests that we get back a FALSE if we turn repick off on the picker.
	 *
	 * @return void
	 */
	public function testPickTargetRepickOff()
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$first_target = new OLPBlackbox_Target('test', 0);
		$second_target = new OLPBlackbox_Target('test2', 0);

		// First campaign object
		$first_campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('pickTarget'),
			array('test', 0, 50, $first_target)
		);
		$first_campaign->expects($this->any())->method('pickTarget')
			->will($this->returnValue(FALSE));

		$first_campaign->getStateData()->current_leads = 49;

		// Second campaign object
		$second_campaign = new OLPBlackbox_Campaign('test2', 0, 50, $second_target);
		$second_campaign->getStateData()->current_leads = 50;

		$percent_picker = new OLPBlackbox_PercentPicker(FALSE);
		$winner = $percent_picker->pickTarget($data, $state_data, array($first_campaign, $second_campaign));

		// We're going to fail
		$this->assertFalse($winner);
	}
	
	
}
?>
