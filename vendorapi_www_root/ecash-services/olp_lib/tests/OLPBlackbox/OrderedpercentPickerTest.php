<?php

/**
 * Test class for OLPBlackbox_OrderedpercentPicker class.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_OrderedpercentPickerTest extends OLPBlackbox_PickerTestBase
{
	/**
	 * Get the class name for the picker
	 *
	 * @return string
	 */
	protected function getPickerClassName()
	{
		return 'OLPBlackbox_OrderedpercentPicker';
	}
	
	/**
	 * Data provider for pickTargetMultipleTargetsDataProvider.
	 *
	 * @return array
	 */
	public function pickTargetMultipleTargetsDataProvider()
	{
		return array(
			array(
				array( // Campaign definitions
					array('name' => 'a', 'weight' => 1, 'lead_count' => 0), 
					array('name' => 'b', 'weight' => 2, 'lead_count' => 0),
					array('name' => 'c', 'weight' => 3, 'lead_count' => 0), 
					array('name' => 'd', 'weight' => 4, 'lead_count' => 0),
				),
				'a', // $expected_name
				200      // $random
			),
			array(
				array( // Campaign definitions
					array('name' => 'a', 'weight' => 1, 'lead_count' => 10000), 
					array('name' => 'b', 'weight' => 2, 'lead_count' => 100),
					array('name' => 'c', 'weight' => 3, 'lead_count' => 200), 
					array('name' => 'd', 'weight' => 4, 'lead_count' => 50), 
				),
				'a', // $expected_name
				200      // $random
			),
		);
	}
	
	/**
	 * Data provider
	 * 
	 * @return array
	 */
	public function dataProviderTestPickOrder()
	{
		return array(
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
					'd' => 4,
				),
				array('a', 'b', 'c', 'd')
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 2,
					'd' => 3,
				),
				array('a', array('b', 'c'), 'd')
			),
			array(
				array(
					'a' => 1,
					'b' => 1,
					'c' => 1,
					'd' => 1,
					'e' => 10,
					'f' => 25,
					'g' => 50,
					'h' => 100,
					'i' => 100,
					'j' => 100
				),
				array(array('a', 'b', 'c', 'd'), 'e', 'f', 'g', array('h', 'i', 'j'))
			)
		);
	}
	
	/**
	 * Tests to ensure that it will pick the proper targets in the proper order.  If two or
	 * more targets have the same weight, it should pick through them randomly in order until
	 * all of those targets of the same weight are gone.
	 * 
	 * @dataProvider dataProviderTestPickOrder
	 * @param array $targets List of targets with weights
	 * @param array $expected List of expected pick order
	 * @return void
	 */
	public function testPickOrder(array $targets, array $expected)
	{
		$campaigns = array();
		foreach ($targets as $target => $weight)
		{
			$campaigns[$target] = $this->getStandardCampaign($target, $weight);
		}
		
		$picker = $this->getPicker();
		
		$data = $this->getBlackboxData();
		$state_data = $this->getStateData();

		foreach ($expected as $expected_campaign)
		{
			if (is_array($expected_campaign))
			{
				$count = count($expected_campaign);

				for ($i = 0; $i < $count; $i++)
				{
					$winner = $picker->pickTarget($data, $state_data, $campaigns);
					$name = $winner->getCampaign()->getStateData()->campaign_name;

					$this->assertContains($name, $expected_campaign);
					
					unset($expected_campaign[array_search($name, $expected_campaign)]);
					unset($campaigns[$name]);
				}
			}
			else
			{
				$winner = $picker->pickTarget($data, $state_data, $campaigns);
				$name = $winner->getCampaign()->getStateData()->campaign_name;

				$this->assertEquals($name, $expected_campaign);
				unset($campaigns[$name]);
			}
		}
	}
}
?>
