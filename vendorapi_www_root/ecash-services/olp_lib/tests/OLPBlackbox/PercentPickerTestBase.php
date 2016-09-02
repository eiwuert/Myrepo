<?php
/**
 * Test class for OLPBlackbox_PercentPicker class.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
abstract class OLPBlackbox_PercentPickerTestBase extends OLPBlackbox_PickerTestBase
{
	/**
	 * Get the class name for the picker
	 *
	 * @return string
	 */
	protected function getPickerClassName()
	{
		return 'OLPBlackbox_PercentPicker';
	}

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
			array('getRandomNumber', 'incrementFrequencyScore', 'log'),
			array($repick)
		);
		
		$picker->expects($this->any())
			->method('getRandomNumber')
			->will($this->returnValue($random));
		
		return $picker;
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
				// Five equal targets
				array( // Campaign definitions
					array('name' => 'ufc', 'weight' => 15, 'lead_count' => 0), 
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 0), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 0), 
					array('name' => 'ucl', 'weight' => 25, 'lead_count' => 0), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 0), 
				),
				'ufc', // $expected_name
				10      // $random
			),
			array(
				// Five unequal targets
				array( // Campaign definitions
					array('name' => 'ufc', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 24), 
					array('name' => 'ucl', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 20), 
				),
				'ca', // $expected_name
				200      // $random
			),
			array(
				// Five unequal targets
				array( // Campaign definitions
					array('name' => 'ufc', 'weight' => 15, 'lead_count' => 14), 
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'ucl', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 20), 
				),
				'ufc', // $expected_name
				200      // $random
			),
			array(
				// Five unequal targets
				array( // Campaign definitions
					array('name' => 'ufc', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 14), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'ucl', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 20), 
				),
				'd1', // $expected_name
				200      // $random
			),
			array(
				// Five unequal targets
				array( // Campaign definitions
					array('name' => 'ufc', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'ucl', 'weight' => 25, 'lead_count' => 24), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 20), 
				),
				'ucl', // $expected_name
				200      // $random
			),
			array(
				// Five unequal targets
				array( // Campaign definitions
					array('name' => 'ufc', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 15), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'ucl', 'weight' => 25, 'lead_count' => 25), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 19), 
				),
				'pcl', // $expected_name
				200      // $random
			),
			array(
				// Five unequal targets
				array( // Campaign definitions
					array('name' => 'ufc', 'weight' => 15, 'lead_count' => 543), 
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 543), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 843), 
					array('name' => 'ucl', 'weight' => 25, 'lead_count' => 843), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 674), 
				),
				'ca', // $expected_name
				25      // $random
			),
			array(
				// Three unequal targets
				array( // Campaign definitions
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 543), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 843), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 674), 
				),
				'ca', // $expected_name
				200      // $random
			),
			array(
				// Three unequal targets
				array( // Campaign definitions
					array('name' => 'd1', 'weight' => 15, 'lead_count' => 16), 
					array('name' => 'ca', 'weight' => 25, 'lead_count' => 24), 
					array('name' => 'pcl', 'weight' => 20, 'lead_count' => 20), 
				),
				'ca', // $expected_name
				200      // $random
			),
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
					'test' => array('weight' => 10, 'mock' => TRUE, 'return_value' => FALSE),
					'test2' => array('weight' => 20),
				),
				'test2',
				10
			)
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
					'test' => array('weight' => 10, 'target_name' => 'test'),
					'test2' => array('weight' => 20, 'target_name' => 'test'),
				),
				'test',
				10
			)
		);
	}
}
?>
