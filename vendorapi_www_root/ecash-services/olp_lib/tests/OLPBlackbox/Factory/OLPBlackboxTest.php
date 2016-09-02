<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Tests the actual OLPBlackbox factory which tests mostly construction options.
 * 
 * This incluldes things like restrict targets and bb_force_winner, etc.
 * 
 * @group olpbbx_factory_test
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_OLPBlackboxTest extends OLPBlackbox_Factory_Base
{
	/**
	 * Test the assembly of blackbox when preferred targets are used.
	 * @return void
	 */
	public function testPreferredTargets()
	{
		$this->clearConfig();
		// this is how forcing to a winner ends up in current blackbox.
		OLPBlackbox_Config::getInstance()->bb_force_winner = 'targetb';
		OLPBlackbox_Config::getInstance()->force_winner = array('TARGETB');
		OLPBlackbox_Config::getInstance()->debug = $this->getDefaultDebugConf();
		
		$blackbox = $this->getBlackbox();

		$this->assertFalse($blackbox->getTargetLocation('targetb1'));
		$this->assertTrue(is_array($blackbox->getTargetLocation('targeta1')));
	}
	
	/**
	 * Gets a factory and runs blackbox.
	 * @return OLPBlackbox
	 */
	protected function getBlackbox()
	{
		static $factory = NULL;

		OLPBlackbox_Config::getInstance()->blackbox_mode = OLPBlackbox_Config::MODE_BROKER;

		if (!$factory)
		{
			$factory = new OLPBlackbox_Factory_OLPBlackbox();
			$factory->setDbConnection($this->getFactoryConnection());
		}

		// the base ModelFactory caches a normalized list of restricted targets
		// to prevent it being operated on repeatedly.
		$factory->getFactoryConfig()->clearRestrictAndExcludeCache();
		
		return $factory->getBlackbox('pw');
	}

	/**
	 * Tests that when running with bb_force_winner AND restricted targets that
	 * the correct things happen.
	 * @return void
	 */
	public function testPreferredAndRestrict()
	{
		$this->clearConfig();
		OLPBlackbox_Config::getInstance()->bb_force_winner = 'obb, targeta';
		OLPBlackbox_Config::getInstance()->force_winner = array(
			'TARGETA', 'OBB'
		);
		$debug = $this->getDefaultDebugConf();
		$debug->setFlag(
			OLPBlackbox_DebugConf::TARGETS_RESTRICT, array('OBB', 'NSC')
		);
		OLPBlackbox_Config::getInstance()->debug = $debug;

		$blackbox = $this->getBlackbox();

		$this->assertFalse($blackbox->getTargetLocation('obb_op'));
		$this->assertFalse($blackbox->getTargetLocation('targeta'));
		$this->assertFalse($blackbox->getTargetLocation('nsc2'));
		$this->assertTrue(is_array($blackbox->getTargetLocation('obb')));
	}

	/**
	 * Tests excluding targets and using preferred targets.
	 * @return void
	 */
	public function testPreferredAndExclude()
	{
		$this->clearConfig();
		OLPBlackbox_Config::getInstance()->bb_force_winner = 'obb,targeta';
		OLPBlackbox_Config::getInstance()->force_winner = array(
			'TARGETA', 'OBB'
		);
		$debug = $this->getDefaultDebugConf();
		$debug->setFlag(
			OLPBlackbox_DebugConf::TARGETS_EXCLUDE, array('OBB', 'NSC')
		);
		OLPBlackbox_Config::getInstance()->debug = $debug;

		$blackbox = $this->getBlackbox();

		$this->assertFalse($blackbox->getTargetLocation('obb'));
		$this->assertFalse($blackbox->getTargetLocation('nsc'));
		$this->assertFalse($blackbox->getTargetLocation('targeta1'));
		$this->assertTrue(is_array($blackbox->getTargetLocation('targeta')));
	}

	/**
	 * OLPBlackbox_Config will not allow properties to be set if they are
	 * already set.
	 * 
	 * Therefore, we call this before each test so that no variables last from
	 * test to test.
	 */
	protected function clearConfig()
	{
		unset(OLPBlackbox_Config::getInstance()->force_winner);
		unset(OLPBlackbox_Config::getInstance()->bb_force_winner);
		unset(OLPBlackbox_Config::getInstance()->debug);
		unset(OLPBlackbox_Config::getInstance()->app_flags);
		unset(OLPBlackbox_Config::getInstance()->unit_test);
		OLPBlackbox_Config::getInstance()->unit_test = TRUE;
	}

	/**
	 * Tests what happens when you specify exclude and restrict which overlap.
	 * @return void
	 */
	public function testOverlappingExcludeAndRestrict()
	{
		$this->clearConfig();
		
		$this->setExpectedException('Blackbox_Exception');
		
		new OLPBlackbox_DebugConf(array(
			OLPBlackbox_DebugConf::TARGETS_EXCLUDE => array('targeta', 'obb'),
			OLPBlackbox_DebugConf::TARGETS_RESTRICT => array('obb', 'nsc'))
		);
	}
	
	/**
	 * Tests non-overlapping restrict + exclude options used at the same time.
	 * @return void
	 */
	public function testExcludeAndRestrict()
	{
		$this->clearConfig();
		
		$debug = $this->getDefaultDebugConf();
		$debug->setFlag(OLPBlackbox_DebugConf::TARGETS_EXCLUDE, array('targeta'));
		$debug->setFlag(OLPBlackbox_DebugConf::TARGETS_RESTRICT, array('targeta1'));
		
		OLPBlackbox_Config::getInstance()->debug = $debug;
		
		/* @var $blackbox OLPBlackbox */
		$blackbox = $this->getBlackbox();
		
		$this->assertFalse($blackbox->getTargetLocation('targeta'));
		$this->assertTrue(is_array($blackbox->getTargetLocation('targeta1')));
	}
	
	public function testInactiveBBForceWinnerTarget()
	{
		$this->clearConfig();
		OLPBlackbox_Config::getInstance()->debug = $this->getDefaultDebugConf();
		OLPBlackbox_Config::getInstance()->bb_force_winner = 'mri';
		OLPBlackbox_Config::getInstance()->force_winner = array('MRI');
		OLPBlackbox_Config::getInstance()->debug = $this->getDefaultDebugConf();
		
		$blackbox = $this->getBlackbox();

		// mri is inactive, should not be included
		$this->assertFalse($blackbox->getTargetLocation('mri'));

		// these two are in the same collection with mri, preferred targets
		// should ensure these are skipped.
		$this->assertFalse($blackbox->getTargetLocation('nsc'));
		$this->assertFalse($blackbox->getTargetLocation('nsc2'));
	}

	/**
	 * Test loading up a tree with an inactive target.
	 * @return void
	 */
	public function testInactiveTarget()
	{
		$this->clearConfig();
		OLPBlackbox_Config::getInstance()->debug = $this->getDefaultDebugConf();
		$blackbox = $this->getBlackbox();
		$this->assertFalse($blackbox->getTargetLocation('mri'));
	}
	
	/**
	 * Returns up a default debug conf object.
	 * 
	 * Call this instead of just setting up a new DebugConf and manually setting
	 * it in the OLPBlackbox_Config so that if new default parameters need to be
	 * added, it can be done in one place.
	 * 
	 * @return OLPBlackbox_DebugConf
	 */
	protected function getDefaultDebugConf()
	{
		$debug = new OLPBlackbox_DebugConf(array(
			OLPBlackbox_DebugConf::PREV_CUSTOMER => FALSE
		));
		return $debug;
	}

	/**
	 * Returns the data set for this test.
	 * @see OLPBlackbox_Factory_Base::getFactoryDataSet()
	 * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet
	 */
	protected function getFactoryDataSet() 
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/OLPBlackboxTest.xml'
		);
	}
}

?>
