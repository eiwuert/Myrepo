<?php
/**
 * PHPUnit class for testing the OLPBlackbox_DebugConf class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 * @author Adam Englander <adam.englander@sellingsource.com>
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

	/**
	 * Test data provider for testDebugSkipRule
	 *
	 * @return array
	 */
	public function dataDebugSkipRule()
	{
		return array(
			// no debug settings returns FALSE
			array(
				array(),
				NULL,
				NULL,
				FALSE
			),
			// no_checks returns TRUE
			array(
				array('NO_CHECKS' => TRUE),
				NULL,
				NULL,
				TRUE
			),
			// rules overrides no_checks and returns FALSE
			array(
				array('NO_CHECKS' => TRUE, 'RULES' => TRUE),
				NULL,
				NULL,
				FALSE
			),
			// no_checks returns TRUE
			array(
				array('NO_CHECKS' => TRUE),
				'RULES',
				NULL,
				TRUE
			),
			// rules overrides no_checks and returns FALSE
			array(
				array('NO_CHECKS' => TRUE, 'RULES' => TRUE),
				'RULES',
				NULL,
				FALSE
			),
			// RULES overrides NO_CHECKS
			array(
				array('NO_CHECKS' => TRUE, 'RULES' => FALSE, 'RULES_EXCLUDE' => array('rule_1','rule_2'), 'RULES_INCLUDE' => array('rule_2','rule_3')),
				'RULES',
				NULL,
				TRUE
			),
			// RULES_EXCLUDE overrides RULES
			array(
				array('NO_CHECKS' => TRUE, 'RULES' => TRUE, 'RULES_EXCLUDE' => array('rule_1','rule_2'), 'RULES_INCLUDE' => array('rule_2','rule_3')),
				'RULES',
				'rule_1',
				TRUE
			),
			// RULES_INCLUDE overrides RULES_EXCLUDE
			array(
				array('NO_CHECKS' => TRUE, 'RULES' => FALSE, 'RULES_EXCLUDE' => array('rule_1','rule_2'), 'RULES_INCLUDE' => array('rule_2','rule_3')),
				'RULES',
				'rule_2',
				FALSE
			),
			// RULES_INCLUDE overrides RULES
			array(
				array('NO_CHECKS' => TRUE, 'RULES' => FALSE, 'RULES_EXCLUDE' => array('rule_1','rule_2'), 'RULES_INCLUDE' => array('rule_2','rule_3')),
				'RULES',
				'rule_3',
				FALSE
			),
			// rule defaults to NO_CHECKS
			array(
				array('NO_CHECKS' => TRUE),
				'RULES',
				'rule_3',
				TRUE
			),
			// rule defaults to RULES
			array(
				array('RULES' => FALSE),
				'RULES',
				'rule_3',
				TRUE
			),
		);
	}

	/**
	 * Test debugSkipRule with various scenarios to make sure the following:
	 * 		Rule include overrides rule exclude
	 * 		Rule exclude overrides generic rule specification
	 * 		Generic rule specification overrides no_checks
	 *
	 * @param array $setup
	 * @param string $flag
	 * @param string $rule
	 * @param bool $result
	 * @return void
	 * @dataProvider dataDebugSkipRule
	 */
	public function testDebugSkipRule($setup, $flag, $rule, $result)
	{
		$debug = new OLPBlackbox_DebugConf($setup);
		$this->assertEquals($result, $debug->debugSkipRule($flag, $rule));
	}
}
?>
