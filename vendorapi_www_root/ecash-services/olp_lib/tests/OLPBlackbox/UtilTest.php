<?php

/**
 * Test case for the OLPBlackbox_Util class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPBlackbox_UtilTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testHasCampaignHitCap().
	 *
	 * @return array
	 */
	public function dataProviderHasCampaignHitCap()
	{
		return array(
			array(
				'test_campaign',
				10,
				20,
				FALSE,
			),
			
			array(
				'test_campaign',
				20,
				20,
				TRUE,
			),
			
			array(
				'test_campaign',
				30,
				20,
				TRUE,
			),
		);
	}
	
	/**
	 * Tests hasCampaignHitCap().
	 *
	 * @dataProvider dataProviderHasCampaignHitCap
	 *
	 * @param string $campaign_name
	 * @param int $stat_limit
	 * @param int $daily_limit
	 * @param bool $expected_result
	 * @return void
	 */
	public function testHasCampaignHitCap($campaign_name, $stat_limit, $daily_limit, $expected_result)
	{
		$stats_limit = $this->getMock(
			'Stats_Limits',
			array('count'),
			array(),
			'',
			FALSE
		);
		$stats_limit->expects($this->once())
			->method('count')
			->with("bb_{$campaign_name}")
			->will($this->returnValue($stat_limit));
		
		$db = $this->getMock('DB_IConnection_1');
		
		$memcache = $this->getMock(
			'Cache_Memcache',
			array(
				'get',
				'set',
			)
		);
		$memcache->expects($this->once())
			->method('get')
			->will($this->returnValue(FALSE));
		
		$olpblackbox_util = $this->getMock(
			'OLPBlackbox_Util',
			array('getDailyLimit'),
			array($db, $memcache)
		);
		$olpblackbox_util->expects($this->once())
			->method('getDailyLimit')
			->with($campaign_name)
			->will($this->returnValue($daily_limit));
		
		$result = $olpblackbox_util->hasCampaignHitCap($campaign_name, $stats_limit);
		
		$this->assertEquals($expected_result, $result);
	}
	
	/**
	 * Data provider for testHasCollectionHitCap().
	 *
	 * @return array
	 */
	public function dataProviderHasCollectionHitCap()
	{
		return array(
			array(
				'test_collection',
				array(
					'test_child_pass',
				),
				FALSE,
			),
			
			array(
				'test_collection',
				array(
					'test_child_fail',
				),
				TRUE,
			),
			
			array(
				'test_collection',
				array(
					'test_child1_pass',
					'test_child2_pass',
				),
				FALSE,
			),
			
			array(
				'test_collection',
				array(
					'test_child1_pass',
					'test_child2_pass',
					'test_child3_fail',
				),
				TRUE,
			),
		);
	}
	
	/**
	 * Tests hasCollectionHitCap().
	 *
	 * @dataProvider dataProviderHasCollectionHitCap
	 *
	 * @param string $campaign_name
	 * @param int $stat_limit
	 * @param int $daily_limit
	 * @param bool $expected_result
	 * @return void
	 */
	public function testHasCollectionHitCap($collection_name, array $children, $expected_result)
	{
		$children_objects = array();
		foreach ($children AS $child)
		{
			$object = new stdClass();
			$object->property_short = $child;
			$children_objects[] = $object;
		}
		
		$stats_limit = $this->getMock(
			'Stats_Limits',
			array(),
			array(),
			'',
			FALSE
		);
		
		$db = $this->getMock('DB_IConnection_1');
		
		$memcache = $this->getMock(
			'Cache_Memcache',
			array(
				'get',
				'set',
			)
		);
		$memcache->expects($this->once())
			->method('get')
			->will($this->returnValue(FALSE));
		$memcache->expects($this->once())
			->method('set');
		
		$olpblackbox_util = $this->getMock(
			'OLPBlackbox_Util',
			array(
				'hasCampaignHitCap',
				'getChildren',
			),
			array($db, $memcache)
		);
		$olpblackbox_util->expects($this->any())
			->method('hasCampaignHitCap')
			->will($this->returnCallBack(array($this, 'hasCollectionHitCap_HasCampaignHitCap')));
		$olpblackbox_util->expects($this->once())
			->method('getChildren')
			->will($this->returnValue($children_objects));
		
		$result = $olpblackbox_util->hasCollectionHitCap($collection_name, $stats_limit);
		
		$this->assertEquals($expected_result, $result);
	}
	
	/**
	 * Hack to return what we want.
	 *
	 * @param string $campaign_name
	 * @param Stats_Limits $stats_limits
	 * @return bool
	 */
	public function hasCollectionHitCap_HasCampaignHitCap($campaign_name, Stats_Limits $stats_limits)
	{
		$fail = strpos($campaign_name, "fail") !== FALSE;
		
		return $fail;
	}
}

?>
