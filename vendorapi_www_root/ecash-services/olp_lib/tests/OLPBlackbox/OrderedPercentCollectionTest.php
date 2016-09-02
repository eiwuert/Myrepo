<?php

/**
 * Test class for the OLPBlackbox_OrderedPercentCollection
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_OrderedPercentCollectionTest extends OLPBlackbox_OrderedCollectionTestBase
{
	/**
	 * Returns a new collection
	 *
	 * @param string $name 
	 * @return OLPBlackbox_OrderedPercentCollection
	 */
	protected function getCollection($name = 'test')
	{
		return new OLPBlackbox_OrderedPercentCollection($name);
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
		$this->target_collection = $this->getCollection();
	
		$campaigns = array();
		foreach ($targets as $target => $weight)
		{
			$campaigns[$target] = $this->getCampaign(array('isValid' => TRUE), $target, $weight);
			$this->target_collection->addTarget($campaigns[$target]);
		}

		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);
		foreach ($expected as $expected_campaign)
		{
			if (is_array($expected_campaign))
			{
				$count = count($expected_campaign);

				for ($i = 0; $i < $count; $i++)
				{
					$winner = $this->target_collection->pickTarget($this->blackbox_data);
					$name = $winner->getCampaign()->getStateData()->campaign_name;

					$this->assertContains($name, $expected_campaign);
					
					unset($expected_campaign[array_search($name, $expected_campaign)]);
					unset($campaigns[$name]);
				}
			}
			else
			{
				$winner = $this->target_collection->pickTarget($this->blackbox_data);
				$name = $winner->getCampaign()->getStateData()->campaign_name;

				$this->assertEquals($name, $expected_campaign);
				unset($campaigns[$name]);
			}
		}
	}
	
	/**
	 * Data provider for testWakeupDefault test
	 * @return void
	 */
	public function dataProviderSleepWakeup()
	{
		return array(
			array(
				array(
					'valid' => NULL,
					'state_data' => new Blackbox_StateData(),
					'pick_target_rules_result' => NULL,
					'previous_target' => 0,
					'children' => array('test' => array('I slept with the campaign')),
					'weights' => array(10 => array('test')),
					'current_weight' => NULL
					
				),
				'test'
			)
		);
	}
	
		/**
	 * Test the return of the sleep method
	 *
	 * @dataProvider dataProviderSleepWakeup
	 * @param array $sleep_data
	 * @param string $campaign_name
	 * @return array
	 */
	public function testSleep(array $sleep_data, $campaign_name)
	{
		$sleep_data = parent::testSleep($sleep_data, $campaign_name);
		
		$this->assertArrayHasKey('weights', $sleep_data);
		$this->assertArrayHasKey('current_weight', $sleep_data);
		$this->assertType('array', $sleep_data['weights']);

		$this->assertEquals($campaign_name, $sleep_data['weights'][10][0]);
		
		// Test to make sure it maintains the weights/current_weight after an isValid is called, too
		$this->target_collection->isValid($this->blackbox_data, $this->state_data);
		$sleep_data = $this->target_collection->sleep();
		
		$this->assertArrayHasKey('weights', $sleep_data);
		$this->assertArrayHasKey('current_weight', $sleep_data);
		$this->assertType('array', $sleep_data['weights']);

		$this->assertArrayHasKey($sleep_data['current_weight'], $sleep_data['weights']);
		$this->assertEquals($campaign_name, $sleep_data['weights'][$sleep_data['current_weight']][0]);
				
		return $sleep_data;
	}
}
