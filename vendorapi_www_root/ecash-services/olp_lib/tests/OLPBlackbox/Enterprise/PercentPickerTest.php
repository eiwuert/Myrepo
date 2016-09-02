<?php
/**
 * Test class for the Enterprise percent picker
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Enterprise_PercentPickerTest extends OLPBlackbox_PercentPickerTestBase
{
	/**
	 * Tests the pickWinner function with the same winner
	 *
	 * @dataProvider pickWinnerSameTargetDataProvider
	 * @param array $picked_campaigns Campaigns that have already been picked
	 * @param array $target_list Campaigns about to be picked
	 * @param string $expected_winner Expected winner
	 * @return void
	 */
	public function testPickWinnerSameTarget($picked_campaigns, $target_list, $expected_winner)
	{
		$winner = $this->getWinner($picked_campaigns, $target_list);
		$this->assertEquals($winner->getStateData()->name, $expected_winner);
	}
	
	/**
	 * Tests the pickWinner function with a new winner
	 *
	 * @dataProvider pickWinnerNewTargetDataProvider
	 * @param array $picked_campaigns Campaigns that have already been picked
	 * @param array $target_list Campaigns about to be picked
	 * @param string $previous_winner Last winner
	 * @return void
	 */
	public function testPickWinnerNewTarget($picked_campaigns, $target_list, $previous_winner)
	{
		$winner = $this->getWinner($picked_campaigns, $target_list);
		$this->assertNotEquals($winner->getStateData()->name, $previous_winner);
	}
	
	/**
	 * Returns a winner from the picker
	 *
	 * @param array $picked_campaigns
	 * @param array $target_list
	 * @return OLPBlackbox_Winner
	 */
	protected function getWinner($picked_campaigns, $target_list)
	{
		$picker = $this->getMock('OLPBlackbox_Enterprise_PercentPicker', array('getPickedTargets', 'addPickedTarget'));
		
		$picker->expects($this->any())
			->method('getPickedTargets')
			->will($this->returnValue($this->buildCampaignList($picked_campaigns)));
			
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		return $picker->pickTarget($data, $state_data, $this->buildCampaignList($target_list));
	}
	
	/**
	 * Tests to ensure winner is maintained
	 *
	 * @dataProvider pickWinnerMaintainsWinnerDataProvider
	 * @return void
	 */
	public function testPickWinnerMaintainsWinner()
	{
		$target_lists = array(
			array('ca', 'd1', 'pcl', 'ucl', 'ufc'),
			array('ufc_wd', 'ca_wd', 'd1_wd', 'pcl_wd', 'ucl_wd'),
			array('ucl_we', 'ufc_we', 'ca_we', 'd1_we', 'pcl_we'),
			array('pcl2', 'ucl2', 'ufc2', 'ca2', 'd12'),
			array('ufc3', 'ca3', 'd13', 'pcl3', 'ucl3'),
		);
		
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		$config = OLPBlackbox_Config::getInstance();
		$config->olp_db = TEST_GET_DB_INFO(TEST_OLP);
		
		$original_winner = NULL;
		foreach ($target_lists as $list)
		{
			$picker = $this->getMock('OLPBlackbox_Enterprise_PercentPicker', array('incrementFrequencyScore'));
			$winner = $picker->pickTarget(
				$data,
				$state_data,
				$this->buildCampaignList($list)
			);
			if (empty($original_winner) && !empty($winner))
			{
				$original_resolved_winner = EnterpriseData::resolveAlias($winner->getTarget()->getName());
			}
			elseif (!empty($winner))
			{
				$resolved_winner = EnterpriseData::resolveAlias($winner->getTarget()->getName());
				$this->assertEquals($original_resolved_winner, $resolved_winner);
			}
			else
			{
				$this->fail("Pick winner didn't return a winner!");
			}
		}
	}
	
	/**
	 * Builds a list of campaigns.
	 *
	 * @param array $campaigns
	 * @return array
	 */
	protected function buildCampaignList($campaigns)
	{
		$list = array();
		
		foreach ($campaigns as $campaign)
		{
			$list[] = new OLPBlackbox_Campaign(
				$campaign,
				0,
				mt_rand(1, count($campaigns)),
				new OLPBlackbox_Target($campaign, 1)
			);
		}
		
		return $list;
	}
	
	/**
	 * Data provider for testPickWinnerSameTarget()
	 *
	 * @return array
	 */
	public static function pickWinnerSameTargetDataProvider()
	{
		return array(
			array(
				array('d1_st1'),
				array('ca2', 'd12', 'pcl2', 'ucl2', 'ufc2'),
				'd12'
			),
			array(
				array('d1_st1', 'pcl2'),
				array('ca3', 'd13', 'pcl3', 'ucl3', 'ufc3'),
				'pcl3'
			),
			array(
				array('ufc_st1','ca2','ufc3'),
				array('ca4', 'd14', 'pcl4', 'ucl4', 'ufc4'),
				'ufc4'
			)
		);
	}
	
	/**
	 * Data provider for testPickWinnerNewTarget()
	 *
	 * @return array
	 */
	public static function pickWinnerNewTargetDataProvider()
	{
		return array(
			array(
				array('d1_st1'),
				array('ca2', 'pcl2', 'ucl2', 'ufc2'),
				'd12'
			),
			array(
				array('d1_st1', 'pcl2'),
				array('ca3', 'd13', 'ucl3', 'pcl3', 'ufc3'),
				'd13'
			),
		);
	}

	public function testFailException()
	{
		// Set up the list for the first run...only need one target
		$target1 = $this->getMock('Blackbox_ITarget', array('pickTarget', 'isValid', 'setRules', 'getStateData'));
		$target1->expects($this->once())
			->method('pickTarget')
			->will($this->throwException(new OLPBlackbox_FailException()));
		$campaign1 = new OLPBlackbox_Campaign('campaign1', NULL, 0, $target1);
		$list1 = array($campaign1);

		// Set up the list for the second run...needs multiple targets with a match to the first
		// for the resolved alias at the last position to ensure it's picking properly
		// The only target in list 2 that should hot pick target is the match
		// Set the weights up so that the match has the highest number.  As there are no leads
		// specified in the state data for the campaign, it will use the lowest weight...strange
		// but true
		$target2a = $this->getMock('Blackbox_ITarget', array('pickTarget', 'isValid', 'setRules', 'getStateData', 'hasSellRule'));
		$target2a->expects($this->never())
			->method('pickTarget');
		$campaign2a = new OLPBlackbox_Campaign('campaign2a', NULL, 0, $target2a);
		
		$target2b = $this->getMock('Blackbox_ITarget',array('pickTarget', 'isValid', 'setRules', 'getStateData', 'hasSellRule'));
		$target2b->expects($this->never())
			->method('pickTarget');
		$campaign2b = new OLPBlackbox_Campaign('campaign2b', NULL, 0, $target2b);
			
		$target2c = $this->getMock('Blackbox_ITarget', array('pickTarget', 'isValid', 'setRules', 'getStateData', 'hasSellRule'));
		$target2c->expects($this->once())
			->method('pickTarget')
			->will($this->returnValue($target2c));
		$campaign2c = new OLPBlackbox_Campaign('campaign2c', NULL, 100, $target2c);
		
		$list2 = array($campaign2a, $campaign2b, $campaign2c);
			
		$state_data = new OLPBlackbox_StateData;
		$data = new OLPBlackbox_Data();
		
		$picker = $this->getMock(
			'OLPBlackbox_Enterprise_PercentPicker',
			array('incrementFrequencyScore', 'getBaseTarget'));
		
		$picker->expects($this->any())
			->method('getBaseTarget')
			->will($this->returnCallBack(array($this, 'getBaseTarget')));

		// Try pickTarget using list1.  It should throw a fail exception as expected
		try 
		{
			$picker->pickTarget($data, $state_data, $list1);
			$this->fail('OLPBlackbox_FailException was expected to be thrown but was not');
		}
		catch (OLPBlackbox_FailException $e)
		{
			// Fail exception was expected
		}
		
		$winner = $picker->pickTarget($data, $state_data, $list2);
		$this->assertEquals($campaign2c, $winner->getTarget());
	}

	/**
	 * Helper function for stubbing the percent pickers getBaseTarget function
	 *
	 * @param string $property_short
	 * @return string
	 */
	public function getBaseTarget($property_short)
	{
		 $match = array(
		 	'campaign1' => 'MATCH',
		 	'campaign2a' => 'NO_MATCH1',
		 	'campaign2b' => 'NO_MATCH2',
		 	'campaign2c' => 'MATCH'
		 );
		 
		 return $match[$property_short];
	}
}