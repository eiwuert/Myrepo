<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Test case for the limit rule
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Rule_LimitTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Blackbox_Data
	 */
	private $bb_data;

	/**
	 * @var OLPBlackbox_CampaignStateData
	 */
	private $bb_state;

	/**
	 * Skip if we can't include stat_limits.php
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!@include_once(BFW_DIR.'include/modules/olp/stat_limits.php'))
		{
			$this->markTestSkipped();
		}

		$this->bb_data = new Blackbox_Data();
		$this->bb_state = new OLPBlackbox_CampaignStateData();
	}

	/**
	 * Ensures that the stat name used is the one passed in the params array
	 *
	 * @return void
	 */
	public function testUsesStatNameInParams()
	{
		/* @var $sl Stat_Limits */
		$sl = $this->getMock(
			'Stat_Limits',
			array('Fetch'),
			array(),
			'',
			FALSE
		);

		$sl->expects($this->once())
			->method('Fetch')
			->with('bb_acd', null, null, null, $this->anything());

		$limit = new OLPBlackbox_Rule_Limit();
		$limit->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'bb_acd',
			Blackbox_StandardRule::PARAM_VALUE => 0,
		));
		$limit->setStatLimits($sl);

		$limit->isValid($this->bb_data, $this->bb_state);
	}

	/**
	 * Ensures that the rule fails when the current value is at the limit
	 *
	 * @return void
	 */
	public function testFailsWhenCurrentEqualsLimit()
	{
		$sl = $this->getMockLimit(100);

		$limit = $this->getLimit($sl, 'bb_acd', 100);

		$this->assertFalse($limit->isValid($this->bb_data, $this->bb_state));
	}

	/**
	 * Ensures that the rule fails when the current value is at the limit
	 *
	 * @return void
	 */
	public function testPassesWhenCurrentBelowLimit()
	{
		$sl = $this->getMockLimit(10);

		$limit = $this->getLimit($sl, 'bb_acd', 100);

		$this->assertTrue($limit->isValid($this->bb_data, $this->bb_state));
	}

	/**
	 * Ensures that the rule fails when the current value is at the limit
	 *
	 * @return void
	 */
	public function testFailsWhenCurrentAboveLimit()
	{
		$sl = $this->getMockLimit(1000);

		$limit = $this->getLimit($sl, 'bb_acd', 100);

		$this->assertFalse($limit->isValid($this->bb_data, $this->bb_state));
	}

	/**
	 * Ensures that the rule sets the current value in the state data
	 *
	 * @return void
	 */
	public function testRuleSetsCurrentValueInStateData()
	{
		$sl = $this->getMockLimit(1000);

		$limit = $this->getLimit($sl, 'bb_acd', 100);
		$limit->isValid($this->bb_data, $this->bb_state);

		$this->assertEquals(1000, $this->bb_state->current_leads);
	}

	/**
	 * Gets a mock object that returns a static limit
	 *
	 * @param int $limit
	 * @return object
	 */
	protected function getMockLimit($limit)
	{
		/* @var $sl Stat_Limits */
		$sl = $this->getMock(
			'Stat_Limits',
			array('Fetch'),
			array(),
			'',
			FALSE
		);

		$sl->expects($this->once())
			->method('Fetch')
			->with($this->anything())
			->will($this->returnValue($limit));
		return $sl;
	}

	/**
	 * Builds a limit rule
	 *
	 * @param string $stat
	 * @param int $value
	 * @return OLPBlackbox_Rule_Limit
	 */
	protected function getLimit($stat_limits, $stat, $value)
	{
		$limit = new OLPBlackbox_Rule_Limit();
		$limit->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => $stat,
			Blackbox_StandardRule::PARAM_VALUE => $value,
		));
		$limit->setStatLimits($stat_limits);

		return $limit;
	}
}

?>