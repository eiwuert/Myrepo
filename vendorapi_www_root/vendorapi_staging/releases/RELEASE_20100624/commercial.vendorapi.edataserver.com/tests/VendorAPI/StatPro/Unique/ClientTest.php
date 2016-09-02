<?php
/**
 * Unit tests for VendorAPI_StatPro_Unique_Client
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */

class VendorAPI_StatPro_Unique_ClientTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_StatPro_Unique_Client
	 */
	private $client_mock;

	/**
	 * Set up for a test
	 */
	public function setUp()
	{
		$this->client_mock = $this->getMock(
			"VendorAPI_StatPro_Unique_Client",
			array("hitStat"),
			array(),
			"",
			FALSE
		);
	}

	/**
	 * Tear down after a test
	 */
	public function tearDown()
	{
		$this->client_mock = NULL;
	}

	/**
	 * Test that client will hot stats without adding history providers 
	 */
	public function testWorksWithoutHistory()
	{
		$stat = "stat";
		$track = "track";
		$app = 123456789;
		$space = "space";
		$this->client_mock
			->expects($this->once())
			->method("hitStat")
			->with($this->equalTo($stat),
				$this->equalTo($track),
				$this->equalTo($space));
		$this->client_mock->hitUniqueStat($stat, $app, $track, $space);
	}

	/**
	 * Data provider for testHistoryChechWithHitStat
	 */
	public function providerHistoryChechWithHitStat()
	{
		return array(
			array(TRUE, TRUE, TRUE, FALSE),
			array(TRUE, FALSE, FALSE, FALSE),
			array(FALSE, TRUE, FALSE, FALSE),
			array(FALSE, FALSE, TRUE, FALSE),
			array(FALSE, TRUE, TRUE, FALSE),
			array(TRUE, FALSE, TRUE, FALSE),
			array(TRUE, TRUE, FALSE, FALSE),
			array(FALSE, FALSE, FALSE, TRUE),
		);
	}

	/**
	 * Unit test with multiple history providers to ensure that stats are only
	 * hit when no provider has the stat and all providers are updated via
	 * addEvent when and only when stats are hit
	 *  
	 * @dataProvider providerHistoryChechWithHitStat
	 * @param bool $exists1 Does the stat exist in provider 1
	 * @param bool $exists2 Does the stat exist in provider 2
	 * @param bool $exists3 Does the stat exist in provider 3
	 * @param bool $stat_hit Is the stat expected to be hot based on the
	 * previous parameters
	 */
	public function testHistoryChechWithHitStat(
			$exists1, $exists2, $exists3, $stat_hit)
	{
		$stat = "stat";
		$track = "track";
		$app = 123456789;
		$space = "space";
		$history1 = $this->getHistoryMock();
		$history1
			->expects($this->any())
			->method("containsEvent")
			->will($this->returnValue($exists1));
		if ($stat_hit)
		{
			$history1
				->expects($this->once())
				->method("addEvent")
				->with($this->equalTo($stat, $app));
		}
		else
		{
			$history1
				->expects($this->never())
				->method("addEvent");
		}
		$this->client_mock->addHistory($history1);
		
		$history2 = $this->getHistoryMock();
		$history2
			->expects($this->any())
			->method("containsEvent")
			->will($this->returnValue($exists2));
		if ($stat_hit)
		{
			$history2
				->expects($this->once())
				->method("addEvent")
				->with($this->equalTo($stat, $app));
		}
		else
		{
			$history2
				->expects($this->never())
				->method("addEvent");
		}
		$this->client_mock->addHistory($history2);
			
		$history3 = $this->getHistoryMock();
		$history3
			->expects($this->any())
			->method("containsEvent")
			->will($this->returnValue($exists3));
		if ($stat_hit)
		{
			$history3
				->expects($this->once())
				->method("addEvent")
				->with($this->equalTo($stat, $app));
		}
		else
		{
			$history3
				->expects($this->never())
				->method("addEvent");
		}
		$this->client_mock->addHistory($history3);
			
		if ($stat_hit)
		{
			$this->client_mock
				->expects($this->once())
				->method("hitStat")
				->with($this->equalTo($stat),
					$this->equalTo($track),
					$this->equalTo($space));
		}
		else
		{
			$this->client_mock
			->expects($this->never())
			->method("hitStat");
		}
			$this->client_mock->hitUniqueStat($stat, $app, $track, $space);
	}
	
	private function getHistoryMock()
	{
		return $this->getMock("VendorAPI_StatPro_Unique_IHistory");
	}
}