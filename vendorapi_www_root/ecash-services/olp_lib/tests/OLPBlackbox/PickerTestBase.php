<?php

/**
 * Base Test class for pickers.  All pickers must pass certain tests to be valid.
 * This class creates a single code base form which to execute those tests.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
abstract class OLPBlackbox_PickerTestBase extends PHPUnit_Framework_TestCase
{
	/**
	 * Set up the test
	 *
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();
		// set up the allowSnapshot flag and debug object
		$config = OLPBlackbox_Config::getInstance();
		if (empty($config->debug))
		{
			$config->debug = new OLPBlackbox_DebugConf();
		}
		if (empty($config->allowSnapshot))
		{
			unset($config->allowSnapshot);
			$config->allowSnapshot = TRUE;
		}
	}

	/**
	 * Get the class name for the picker
	 *
	 * @return string
	 */
	abstract protected function getPickerClassName();

	/**
	 * Get a mocked picker object
	 *
	 * @param int $random random() return
	 * @param bool $repick
	 * @return OLPBlackbox_Picker
	 */
	protected function getPicker($random = 10, $repick = TRUE)
	{
		$picker = $this->getMock(
			$this->getPickerClassName(),
			array('random', 'incrementFrequencyScore', 'log'),
			array($repick)
		);
		
		$picker->expects($this->any())
			->method('random')
			->will($this->returnValue(array($random, 1)));
		
		return $picker;
	}

	/**
	 * Get a configured Blackbox Data object
	 *
	 * @return OLPBlackbox_Data
	 */
	protected function getBlackboxData()
	{
		$data = new OLPBlackbox_Data();
		$data->email_primary = $this->getPickerClassName() . '.unittest@tssmasterd.com';
		return $data;
	}

	/**
	 * Get a configured Blackbox Data object
	 *
	 * @return Blackbox_Data
	 */
	protected function getStateData()
	{
		return new OLPBlackbox_StateData();
	}
	
	/**
	 * Tests the default pickTarget call, with no targets passed.
	 *
	 * @return void
	 */
	public function testPickTargetDefault()
	{
		$state_data = new OLPBlackbox_StateData();
		$picker = $this->getPicker();
		$winner = $picker->pickTarget(
			$this->getBlackboxData(),
			$this->getStateData(),
			array()
		);

		$this->assertFalse($winner);
	}

	/**
	 * Tests that when there is a single target to pick, it returns that target.
	 *
	 * @return void
	 */
	public function testPickTargetSingleTarget()
	{
		$data = $this->getBlackboxData();
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

		$picker = $this->getPicker();
		$winner = $picker->pickTarget($data, $state_data, array($campaign));

		$this->assertType('Blackbox_IWinner', $winner);
	}

	/**
	 * Data provider for pickTargetMultipleTargetsDataProvider.
	 *
	 * @return array
	 */
	abstract public function pickTargetMultipleTargetsDataProvider();


	/**
	 * Data provider that returns two campaigns and an expected result
	 * 
	 * @return array
	 */
	public function twoCampaignDataProvider()
	{
		return array(
			array(
				array(
					'test' => array('weight' => 1, 'mock' => TRUE, 'return_value' => FALSE),
					'test2' => array('weight' => 2),
				),
				'test2',
				10
			)
		);
	}

	
	/**
	 * Returns an array of mocked campaigns from twoCampaignDataProvider
	 *
	 * @param array $targets
	 * @return array
	 */
	protected function getTwoCampaignData(array $targets)
	{
		$campaigns = array();
		foreach ($targets as $target => $data)
		{
			if (isset($data['mock']))
			{
				$campaign = $this->getMock(
					'OLPBlackbox_Campaign',
					array('pickTarget'),
					array(
						$target,
						0,
						$data['weight'],
						new OLPBlackbox_Target((isset($data['target_name'])) ? $data['target_name'] : $target, 0))
				);
				
				if ($data['return_value'] === FALSE)
				{
					$campaign->expects($this->any())->method('pickTarget')->will($this->returnValue(FALSE));
				}
			}
			else
			{
				$campaign = $this->getStandardCampaign($target, $data['weight']);
			}
			
			$campaigns[] = $campaign; 
		}
		
		return $campaigns;
	}
	
	/**
	 * Data provider that returns two campaigns and an expected result
	 * 
	 * @return array
	 */
	public function twoCampaignDataProviderSnapshot()
	{
		return array(
			array(
				array(
					'test' => array('weight' => 1, 'target_name' => 'test'),
					'test2' => array('weight' => 2, 'target_name' => 'test'),
				),
				'test',
				10
			)
		);
	}
	
	/**
	 * Test that when allowSnapshot is provided that an accurate snapshot is put in the winner.
	 *
	 * @param array $targets
	 * @param mixed $expected
	 * @param int $random
	 * @dataProvider twoCampaignDataProviderSnapshot
	 * @return void
	 */
	public function testTwoTargetsSnapshot(array $targets, $expected, $random)
	{
		$campaigns = $this->getTwoCampaignData($targets);
		
		$state_data = $this->getStateData();
		
		$picker = $this->getPicker($random, TRUE);
		$picker->pickTarget(
			$this->getBlackboxData(),
			$state_data,
			$campaigns
		);

		$snapshot = $state_data->snapshot;
		$this->assertEquals($expected, $snapshot->stack[0]['winner']);
	}

	/**
	 * Tests pick target with two targets.
	 *
	 * @dataProvider pickTargetMultipleTargetsDataProvider
	 * @param array $campaign_defs Associative array of campaign definitions (name, weight, lead_count)
	 * @param string $expected_name Expected campaign name of winner
	 * @param string $random Value to return from the mocked random function
	 * @return void
	 */
	public function testPickTargetMultipleTargets($campaign_defs, $expected_name, $random)
	{
		$campaigns = array();
		foreach ($campaign_defs as $campaign_def)
		{
			$campaign = $this->getStandardCampaign($campaign_def['name'], $campaign_def['weight']);
			$campaign->getStateData()->current_leads = $campaign_def['lead_count'];
			$campaigns[] = $campaign;
		}

		$picker = $this->getPicker($random, TRUE);
		$winner = $picker->pickTarget($this->getBlackboxData(), $this->getStateData(), $campaigns);

		if (empty($winner)) $this->fail('Expecting target and got none');
		$this->assertEquals($expected_name, $winner->getCampaign()->getStateData()->campaign_name);
		$this->assertEquals($expected_name, $winner->getCampaign()->getTarget()->getStateData()->target_name);
	}
	
	/**
	 * Tests that we return a valid target even if one of the target's pickTarget functions
	 * returns FALSE.
	 * 
	 * @dataProvider twoCampaignDataProvider
	 * @param array $targets
	 * @param mixed $expected
	 * @param int $random
	 * @return void
	 */
	public function testPickTargetFailOnTarget(array $targets, $expected, $random)
	{
		$campaigns = $this->getTwoCampaignData($targets);

		$picker = $this->getPicker($random);
		$winner = $picker->pickTarget(
			$this->getBlackboxData(),
			$this->getStateData(),
			$campaigns
		);

		$this->assertEquals(
			$expected,
			$winner->getCampaign()->getStateData()->campaign_name
		);
		$this->assertEquals(
			$expected,
			$winner->getCampaign()->getTarget()->getStateData()->target_name
		);
	}
	
	/**
	 * Tests that we return FALSE if we turn the repick off.
	 *
	 * @dataProvider twoCampaignDataProvider
	 * @param array $targets Associative array of campaign definitions (name, weight, lead_count)
	 * @param string $expected Expected campaign name of winner
	 * @param string $random Value to return from the mocked random function
	 * @return void
	 */
	public function testPickTargetNoRepick(array $targets, $expected, $random)
	{
		$campaigns = $this->getTwoCampaignData($targets);

		// Mock the picker object and overload random() to return a value we expect
		$picker = $this->getPicker($random, FALSE);
		$winner = $picker->pickTarget(
			$this->getBlackboxData(),
			$this->getStateData(),
			$campaigns
		);

		$this->assertFalse($winner);
	}

	/**
	 * Test the sleep functionality
	 *
	 * @return void
	 */
	public function testSleep()
	{
		$campaigns = array();
		for ($i = 1; $i <= 3; $i++)
		{
			$campaigns[] = $this->getStandardCampaign('C' . $i, $i);
		}

		$picker = $this->getPicker(1, FALSE);
		$winner = $picker->pickTarget($this->getBlackboxData(), $this->getStateData(), $campaigns);
		if (!$winner) $this->fail("Winner expected but not returned from picker");
		$winner_name = $winner->getCampaign()->getName();

		$sleep_data = $picker->sleep();
		
		// Since the winner would be the last target processes it should be the same as the winner
		$this->assertEquals($winner_name, $sleep_data['current_target']);
		// The winner should have been picked, see if it's in the picked array
		$this->assertContains($winner_name, $sleep_data['picked']);
		// The winner should not 
		$this->assertNotContains($winner_name, $sleep_data['pickable']);
		$this->assertAttributeEquals($sleep_data['repick_on_fail'], 'repick_on_fail', $picker);
	}
	
	/**
	 * Test the sleep functionality
	 *
	 * @return void
	 */
	public function testWakeup()
	{
		$campaigns = array();
		for ($i = 1; $i <= 3; $i++)
		{
			$campaigns[] = $this->getStandardCampaign('C' . $i, $i);
		}

		$restore_data = array(
			'repick_on_fail' => TRUE,
			'picked' => array('C2', 'C3'),
			'pickable' => array('C1'),
			'current_target' => 'C3',
		);
		
		$picker = $this->getPicker(1, FALSE);

		// Verify that the restore variables are not set before calling wakeup
		$this->assertAttributeEquals(FALSE, 'restore_needed', $picker);
		$this->assertAttributeEquals(NULL, 'restore_data', $picker);
		
		// Call wakeup
		$picker->wakeup($restore_data);
		
		// Verify that the restore variables are set
		$this->assertAttributeEquals(TRUE, 'restore_needed', $picker);
		$this->assertAttributeEquals($restore_data, 'restore_data', $picker);

		// No log should be written as all campaign names are accounted for 
		$picker->expects($this->never())->method('log');
		// No target should be picked and call incrementFrequencyScore
		$picker->expects($this->never())->method('incrementFrequencyScore');
		
		// Pick a winner
		$winner = $picker->pickTarget($this->getBlackboxData(), $this->getStateData(), $campaigns);

		// Verify that the restore variables have been restored to the proper state
		$this->assertAttributeEquals(FALSE, 'restore_needed', $picker);
		$this->assertAttributeEquals(NULL, 'restore_data', $picker);
		
		// Verify the attributes were set correctly
		$this->assertEquals($winner, new OLPBlackbox_Winner($campaigns[2]));
		$this->assertAttributeEquals($campaigns[2], 'current_target', $picker);
		$this->assertAttributeEquals(array($campaigns[1], $campaigns[2]), 'picked', $picker);
		$this->assertAttributeEquals(array($campaigns[0]), 'pickable', $picker);
		$this->assertAttributeEquals($restore_data['repick_on_fail'], 'repick_on_fail', $picker);

		$picker = $this->getPicker(1, FALSE);
		// Try restoring with a missing target
		$picker->wakeup($restore_data);
		// Target C3 should try to be restored 2 times and not succeed.  It should log that event 2 times and continue
		$picker->expects($this->exactly(2))->method('log');
		// A target should be picked as the restore winner is not a valid target for the picker 
		$picker->expects($this->once())->method('incrementFrequencyScore');
		$bad_campaigns = array($campaigns[0], $campaigns[1]);
		
		$winner = $picker->pickTarget($this->getBlackboxData(), $this->getStateData(), $bad_campaigns);
		
		// C1 should have won because the winner was not valid and it was the only pickable target left
		$this->assertEquals($winner, new OLPBlackbox_Winner($campaigns[0]));
	}
	
	/**
	 * Returns a standard campaign with target
	 * 
	 * @param string $name Property short
	 * @param int $weight Weight
	 * @param string $target_name Optional
	 * @return OLPBlackbox_Campaign
	 */
	protected function getStandardCampaign($name, $weight, $target_name = '')
	{
		return new OLPBlackbox_Campaign(
			$name,
			1,
			$weight,
			new OLPBlackbox_Target(
				(!empty($target_name) ? $target_name : $name),
				1
			)
		);
	}
}
?>
