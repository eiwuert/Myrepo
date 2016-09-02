<?php

/**
 * Test class for OLPBlackboxOrderedPicker class.
 *
 *  @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_OrderedPickerTest extends OLPBlackbox_PickerTestBase
{
	/**
	 * Get the class name for the picker
	 *
	 * @return string
	 */
	protected function getPickerClassName()
	{
		return 'OLPBlackbox_OrderedPicker';
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
				200      // $random
			),
			array(
				array( // Campaign definitions
					array('name' => 'test1', 'weight' => 100, 'lead_count' => 0), 
					array('name' => 'test2', 'weight' => 100, 'lead_count' => 99999), 
				),
				'test1', // $expected_name
				200      // $random
			),
			array(
				array( // Campaign definitions
					array('name' => 'test1', 'weight' => 100, 'lead_count' => 0), 
					array('name' => 'test2', 'weight' => 100, 'lead_count' => 0), 
				),
				'test1',  // $expected_name
				1005      // $random
			),
		);
	}
}
?>