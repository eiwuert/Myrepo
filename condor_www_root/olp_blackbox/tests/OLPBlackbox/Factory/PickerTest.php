<?php
/**
 * OLPBlackbox_Factory_PickerTest PHPUnit test file.
 * 
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the olp picker factory class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Factory_PickerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that the getPicker function in OLPBlackbox_Factory_Picker
	 * returns the correct generic OLPBlackbox picker.
	 *
	 * @return void
	 */
	public function testGetPicker()
	{
		$picker = OLPBlackbox_Factory_Picker::getPicker('PERCENT');
		$this->assertType('OLPBlackbox_PercentPicker', $picker);
		
		$picker = OLPBlackbox_Factory_Picker::getPicker('PRIORITY');
		$this->assertType('OLPBlackbox_PriorityPicker', $picker);
	}
	
}
?>
