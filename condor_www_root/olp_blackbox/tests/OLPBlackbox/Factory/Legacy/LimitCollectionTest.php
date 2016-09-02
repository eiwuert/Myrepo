<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Tests the LimitCollection factory
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_LimitCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Provide a list of all the CLK target names
	 *
	 * @return array
	 */
	public static function clkTargetProvider()
	{
		return array(
			array('ca'), array('ufc'), array('ucl'), array('pcl'), array('d1'),
		);
	}

	protected $event_limits = 'STAT_CHECK';

	/**
	 * @var OLPBlackbox_Config
	 */
	protected $config;

	/**
	 * @var MySQL_Wrapper
	 */
	protected $db;

	/**
	 * Set it up, yo.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		Blackbox_Utils::setToday('2008-01-01 10:15:00');

		if (!@include_once(BFW_DIR.'/include/modules/olp/stat_limits.php'))
		{
			$this->markTestSkipped('I am ashamed of you!');
		}

		$this->db = @Setup_DB::Get_Instance('blackbox', 'LOCAL');

		$this->config = OLPBlackbox_Config::getInstance();
		$this->config->olp_db = $this->db;
	}

	/**
	 * Test that a "simple" limit is created properly
	 *
	 * @return void
	 */
	public function testSimpleDailyLimit()
	{
		$row = array(
			'property_short' => 'acd',
			'limit' => 100,
			'hourly_limit' => '',
			'daily_limit' => '',
			'limit_mult' => 0,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_acd', 100, OLPBlackbox_Config::EVENT_DAILY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Test that an hourly limit is created properly and overrides the 'simple' limit
	 *
	 * @return void
	 */
	public function testHourlyLimits()
	{
		$row = array(
			'property_short' => 'acd',
			'limit' => 100,
			'hourly_limit' => 'a:13:{i:0;i:1;i:1;i:1;i:2;i:1;i:3;i:1;i:4;i:1;i:5;i:1;i:6;i:1;i:7;i:1;i:8;i:1;i:9;i:1;i:10;i:1;i:11;i:1;i:12;i:1;}',
			'daily_limit' => '',
			'limit_mult' => 0,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_acd', 11, OLPBlackbox_Config::EVENT_HOURLY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Test an hourly limit as percentage
	 *
	 * @return void
	 */
	public function testHourlyLimitsAsPercentageOfLimit()
	{
		$row = array(
			'property_short' => 'acd',
			'limit' => 100,
			'hourly_limit' => 'a:13:{i:0;d:0.5;i:1;i:1;i:2;i:1;i:3;i:1;i:4;i:1;i:5;i:1;i:6;i:1;i:7;i:1;i:8;i:1;i:9;i:1;i:10;i:1;i:11;i:1;i:12;i:1;}',
			'daily_limit' => '',
			'limit_mult' => 0,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_acd', 60, OLPBlackbox_Config::EVENT_HOURLY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Test an hourly limit as percentage
	 *
	 * @return void
	 */
	public function testMissingHourlyLimitUsesBaseLimit()
	{
		$row = array(
			'property_short' => 'acd',
			'limit' => 100,
			'hourly_limit' => 'a:12:{i:0;i:1;i:1;i:1;i:2;i:1;i:3;i:1;i:4;i:1;i:5;i:1;i:6;i:1;i:7;i:1;i:8;i:1;i:9;i:1;i:11;i:1;i:12;i:1;}',
			'daily_limit' => '',
			'limit_mult' => 0,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_acd', 100, OLPBlackbox_Config::EVENT_DAILY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Test that a DOW limit is created properly and overrides the 'simple' limit
	 *
	 * @return void
	 */
	public function testDOWLimits()
	{
		$row = array(
			'property_short' => 'acd',
			'limit' => 100,
			'hourly_limit' => '',
			'daily_limit' => 'a:9:{i:0;i:0;i:1;i:1;i:2;i:0;i:3;i:0;i:4;i:0;i:5;i:0;i:6;i:0;i:7;i:1;i:8;i:0;}',
			'limit_mult' => 0,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_acd', 1, OLPBlackbox_Config::EVENT_DAILY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Test that a DOW limit is created properly and overrides the 'simple' limit
	 *
	 * @return void
	 */
	public function testDOWGeneralLimit()
	{
		$row = array(
			'property_short' => 'acd',
			'limit' => 100,
			'hourly_limit' => '',
			'daily_limit' => 'a:9:{i:0;i:0;i:1;i:1;i:2;i:0;i:3;i:0;i:4;i:0;i:5;i:0;i:6;i:0;i:7;i:0;i:8;i:10;}',
			'limit_mult' => 0,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_acd', 10, OLPBlackbox_Config::EVENT_DAILY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Ensures that the limit multiplier works
	 *
	 * @return void
	 */
	public function testLimitMultiplier()
	{
		$row = array(
			'property_short' => 'acd',
			'limit' => 100,
			'hourly_limit' => '',
			'daily_limit' => '',
			'limit_mult' => .5,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_acd', 150, OLPBlackbox_Config::EVENT_DAILY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Ensures that the limit multiplier works
	 *
	 * @dataProvider clkTargetProvider
	 * @return void
	 */
	public function testCLKTargetsUseLookStats($target)
	{
		$row = array(
			'property_short' => $target,
			'limit' => 100,
			'hourly_limit' => '',
			'daily_limit' => '',
			'limit_mult' => 0,
		);

		$expected = $this->getRuleCollection(
			$this->event_limits,
			array(
				$this->getLimit('bb_'.$target.'_look', 100, OLPBlackbox_Config::EVENT_DAILY_LIMIT),
			)
		);

		$actual = OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($row);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Builds a rule collection
	 *
	 * @param array $rules
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getRuleCollection($event, array $rules)
	{
		$rc = new OLPBlackbox_RuleCollection();
		$rc->setEventName($event);
		foreach ($rules as $r) $rc->addRule($r);
		return $rc;
	}

	/**
	 * Builds a limit rule
	 *
	 * @param string $stat
	 * @param int $value
	 * @return OLPBlackbox_Rule_Limit
	 */
	protected function getLimit($stat, $value, $event)
	{
		$limit = new OLPBlackbox_Rule_Limit();
		$limit->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => $stat,
			Blackbox_StandardRule::PARAM_VALUE => $value,
		));
		$limit->setStatLimits(new Stat_Limits($this->db, $this->db->db_info['db']));
		$limit->setEventName($event);

		return $limit;
	}
}

?>