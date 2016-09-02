<?php
/**
 * Defines the OLPBlackbox_DebugConfTest
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit class for testing the OLPBlackbox_DebugConf class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_DebugConfTest extends PHPUnit_Framework_TestCase
{
	/**
	 * OLPBlackbox_DebugConf object to run tests on.
	 *
	 * @var OLPBlackbox_DebugConf
	 */
	protected $debug_conf = NULL;

	/**
	 * Set up the test by creating a new OLPBlackbox_DebugConf object.
	 * 
	 * @return void
	 */
	public function setUp()
	{
		$this->debug_conf = new OLPBlackbox_DebugConf();
	}

	/**
	 * Test that passing a flag that does not exist throws an exception.
	 *
	 * @return void
	 */
	public function testBadFlag()
	{
		$this->setExpectedException('InvalidArgumentException');
		$this->debug_conf->setFlag('asdflkasdf!');
	}

	/**
	 * Tests that good arguments work properly.
	 *
	 * @return void
	 */
	public function testGoodArgument()
	{
		$this->debug_conf->setFlag(OLPBlackbox_DebugConf::TARGETS_RESTRICT, array('ufc', 'ca'));

		$this->debug_conf->setFlag(OLPBlackbox_DebugConf::PREV_CUSTOMER);
		$this->assertTrue($this->debug_conf->getFlag(OLPBlackbox_DebugConf::PREV_CUSTOMER));
		$this->assertTrue($this->debug_conf->flagTrue(OLPBlackbox_DebugConf::PREV_CUSTOMER));

		$this->debug_conf->unsetFlag(OLPBlackbox_DebugConf::PREV_CUSTOMER);
		$this->assertEquals(NULL, $this->debug_conf->getFlag(OLPBlackbox_DebugConf::PREV_CUSTOMER));
		$this->assertFalse($this->debug_conf->flagFalse(OLPBlackbox_DebugConf::PREV_CUSTOMER));
	}
}
?>
