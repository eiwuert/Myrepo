<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * PHPUnit test class for the olp picker factory class.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Factory_PickerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that the getPicker function in OLPBlackbox_Factory_Picker
	 * returns the correct generic OLPBlackbox picker.
	 *
	 * @dataProvider getPickerDataProvider
	 * @param string $picker_name
	 * @param string $picker_class
	 * @return void
	 */
	public function testGetPicker($picker_name, $picker_class)
	{
		$picker = OLPBlackbox_Factory_Picker::getPicker($picker_name);
		$this->assertType($picker_class, $picker);
	}

	/**
	 * Data provider for testGetPicker
	 *
	 * @return array
	 */
	public static function getPickerDataProvider()
	{
		return array(
			array('PERCENT', 'OLPBlackbox_PercentPicker'),
			array('PRIORITY', 'OLPBlackbox_PriorityPicker'),
			array('ENTERPRISE_PERCENT', 'OLPBlackbox_Enterprise_PercentPicker'),
		);
	}
}
?>
