<?php 
class Stats_LimitsTest extends PHPUnit_Extensions_Database_TestCase
{
	const SITE_ID = 1;
	const PROMO_ID = 324;
	
	/**
	 * Database connection.
	 *
	 * @var DB_Database_1
	 */
	protected $db;
	
	/**
	 * Test setup, must call parent setUp().
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->db = TEST_DB_CONNECTOR(TEST_OLP);
		parent::setUp();
	}
	
	/**
	 * Data provider to make sure we're calling count correctly
	 * @return array
	 */
	public static function overlimitDateProviders() 
	{
		return array(
			array('DAILY', NULL, NULL),
			array('WEEKLY', 'last sunday', 'today')
		);
	}
	
	/**
	 * 
	 * @dataProvider overlimitDateProviders
	 */
	public function testOverlimitCallsCountWithCorrectDates($type, $start_date, $end_date) 
	{
		$config = $this->getSiteConfig();
		$limits = new stdClass();
		$limits->stat_caps = array(
			'promo_id' => array(
				'stat_name' => array($type => 12)
			)
		);
		$config->limits = $limits;
				
		$stat_limits = $this->getMock("Stats_Limits", array("count"), array($this->db));
		$stat_limits->expects($this->once())
			->method('count')
			->with('stat_name', NULL, self::PROMO_ID, NULL, $start_date, $end_date)
			->will($this->returnValue(0));
		$stat_limits->overlimit('stat_name', $config);
	}
	
	/**
	 * This uses the old stat cap format in the sitecofnig to validate that
	 * they still function properly.
	 */
	public function testOverlimitDailyWithOldFormat()
	{
		$config = $this->getConfigForOldDailyFormat(100);
		$stat_limits = $this->getMock('Stats_Limits', array("getNow"), array($this->db));
		$stat_limits->expects($this->any())->method('getNow')
			->will($this->returnValue(strtotime("2009-09-06")));
		$this->assertTrue($stat_limits->overlimit('daily', $config));
	}

	/**
	 * Validate that we don't return overlimit when we're really 
	 * under the limit
	 */
	public function testUnderlimitDailyWithOldFormat()
	{
		$config = $this->getConfigForOldDailyFormat(500000);
		$stat_limits = $this->getMock('Stats_Limits', array("getNow"), array($this->db));
		$stat_limits->expects($this->any())->method('getNow')
			->will($this->returnValue(strtotime("2009-09-06")));
		$this->assertFalse($stat_limits->overlimit('daily', $config));
		
	}
	
	/**
	 * Validate that overlimit daily works with the newly formatted
	 * stat caps in the site conig
	 */
	public function testOverlimitDailyWithNewFormat()
	{
		$config = $this->getConfigForNewDailyFormat(100);
		$stat_limits = $this->getMock('Stats_Limits', array("getNow"), array($this->db));
		$stat_limits->expects($this->any())->method('getNow')
			->will($this->returnValue(strtotime("2009-09-06")));
		$this->assertTrue($stat_limits->overlimit('daily', $config));
	}
	
	/**
	 * Validate that if we're under the limit in the new format
	 * we don't fail  
	 */
	public function testUnderlimitDailyWithNewFormat()
	{
		$config = $this->getConfigForNewDailyFormat(500000);
		$stat_limits = $this->getMock('Stats_Limits', array("getNow"), array($this->db));
		$stat_limits->expects($this->any())->method('getNow')
			->will($this->returnValue(strtotime("2009-09-06")));
		$this->assertFalse($stat_limits->overlimit('daily', $config));
	}
	
	/**
	 * Test that overlimit for weekly works
	 */
	public function testOverlimitWeekly()
	{
		$config = $this->getConfigForWeekly(100);
		$stat_limits = $this->getMock('Stats_Limits', array("getStartDate", "getEndDate", "getNow"), array($this->db));
		
		$stat_limits->expects($this->any())->method('getNow')
			->will($this->returnValue(strtotime("2009-09-11")));
		$stat_limits->expects($this->once())->method('getStartDate')
			->with("last sunday")
			->will($this->returnValue(strtotime("2009-09-06")));
		$stat_limits->expects($this->any())->method('getEndDate')
			->will($this->returnValue(strtotime("2009-09-11")));
			
		$this->assertTrue($stat_limits->overlimit('weekly', $config));
	}
	
	/**
	 * Test that underlimit weekly works
	 */
	public function testUnderlimitWeekly()
	{
		$config = $this->getConfigForWeekly(1000000);
		$stat_limits = $this->getMock('Stats_Limits', array("getStartDate", "getEndDate", "getNow"), array($this->db));
		
		$stat_limits->expects($this->any())->method('getNow')
			->will($this->returnValue(strtotime("2009-09-11")));
		$stat_limits->expects($this->once())->method('getStartDate')
			->with("last sunday")
			->will($this->returnValue(strtotime("2009-09-06")));
		$stat_limits->expects($this->any())->method('getEndDate')
			->will($this->returnValue(strtotime("2009-09-11")));
			
		$this->assertFalse($stat_limits->overlimit('weekly', $config));
	}
	
	
	/**
	 * Return a site config configured with weekly stat caps
	 * @param $cap
	 * @return SiteConfig
	 */
	protected function getConfigForWeekly($cap)
	{
		$config = $this->getSiteConfig();
		$limits = new stdClass();
		$limits->stat_caps = array(
			'promo_id' => array(
				'weekly' => array(
					'WEEKLY' => $cap
				)
			)
		);
		$config->limits = $limits;
		return $config;
	}
	
	/**
	 * Return a site config with the new limits format for
	 * daily stat caps
	 * @param $cap
	 * @return SiteConfig
	 */
	protected function getConfigForNewDailyFormat($cap)
	{
		$config = $this->getSiteConfig();
		$limits = new stdClass();
		$limits->stat_caps = array(
			'promo_id' => array(
				'daily' => array(
					'DAILY' => $cap
				)
			)
		);
		$config->limits = $limits;
		return $config;
	}
	
	/**
	 * Sets up a site config for using the old format of
	 * daily limits with $cap as the limit cap
	 * @param $cap
	 * @return SiteConfig
	 */
	protected function getConfigForOldDailyFormat($cap)
	{
		$config = $this->getSiteConfig();
		$limits = new stdClass();
		$limits->stat_caps = array(
			'promo_id' => array('daily' => $cap)
		);
		$config->limits = $limits;
		return $config;
	}
	
	
	/**
	 * Gets a site config instance from the singleton
	 * and unsets all the things we probably would have used
	 * and adds the most common back
	 * @return SiteConfig
	 */
	protected function getSiteConfig()
	{
		$config = SiteConfig::getInstance();
		unset($config->site_id);
		unset($config->promo_id);
		unset($config->limits);
		$config->site_id = self::SITE_ID;
		$config->promo_id = self::PROMO_ID;
		return $config;
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection()
	{
		$connection = $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_OLP), 
			TEST_GET_DB_INFO(TEST_OLP)->name
		);
		return $connection;
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		$dataset = $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/Limits/StatCapSet.xml');
		return $dataset;
	}
}