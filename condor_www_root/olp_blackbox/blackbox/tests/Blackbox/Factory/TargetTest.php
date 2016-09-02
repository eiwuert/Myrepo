<?php
/**
 * TargetTest PHPUnit test file.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the default Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Factory_TargetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that the getTargetCollection function returns a new TargetCollection object.
	 *
	 * @return void
	 */
	public function testGetTargetCollection()
	{
		$collection = Blackbox_Factory_Target::getTargetCollection();
		
		$this->assertType('Blackbox_TargetCollection', $collection);
	}
}
?>
