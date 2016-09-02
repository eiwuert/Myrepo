<?php

/**
 * Test class for OLPBlackbox_PriorityPicker class.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_PriorityPickerTest extends OLPBlackbox_PickerTestBase
{
	/**
	 * Get the class name for the picker
	 *
	 * @return string
	 */
	protected function getPickerClassName()
	{
		return 'OLPBlackbox_PriorityPicker';
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
					array('name' => 'test1', 'weight' => 100, 'lead_count' => 0), 
					array('name' => 'test2', 'weight' => 100, 'lead_count' => 0), 
				),
				'test1', // $expected_name
				50
			),
			array(
				array( // Campaign definitions
					array('name' => 'test1', 'weight' => 100, 'lead_count' => 0), 
					array('name' => 'test2', 'weight' => 100, 'lead_count' => 99999), 
				),
				'test1', // $expected_name
				50
			),
			array(
				array( // Campaign definitions
					array('name' => 'test1', 'weight' => 100, 'lead_count' => 0), 
					array('name' => 'test2', 'weight' => 200, 'lead_count' => 0), 
				),
				'test2',  // $expected_name
				150
			),
		);
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
					'test' => array('weight' => 200, 'target_name' => 'test'),
					'test2' => array('weight' => 100, 'target_name' => 'test'),
				),
				'test',
				100
			)
		);
	}
	
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
					'test' => array('weight' => 200, 'mock' => TRUE, 'return_value' => FALSE),
					'test2' => array('weight' => 100),
				),
				'test2',
				100
			)
		);
	}
}
?>
