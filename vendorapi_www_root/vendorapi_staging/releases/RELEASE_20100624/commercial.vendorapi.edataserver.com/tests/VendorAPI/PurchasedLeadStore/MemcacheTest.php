<?php

/**
 * Test for ECash_VendorAPI_PurchasedLeadsCache
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PurchasedLeadStore_MemcacheTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECash_VendorAPI_PurchasedLeadStore_Memcache
	 */
	protected $store;

	/**
	 * @var Memcache
	 */
	protected $memcache;

	/**
	 * Sets up the cache object
	 * @return NULL
	 */
	public function setUp()
	{
		$this->memcache = new Memcache();
		$this->memcache->addServer('127.0.0.1', 11211);
		if (!$this->memcache->flush())
		{
			$this->markTestSkipped("Must have memcache running locally to execute tests.");
		}
		$this->store = new VendorAPI_PurchasedLeadStore_Memcache("prefix", $this->memcache);
	}

	/**
	 * Tears down the memcache connection and data.
	 * @return NULL
	 */
	public function tearDown()
	{
		$this->memcache->flush();
		$this->memcache->close();
		$this->memcache = NULL;
	}

	/**
	 * Tests that ssn locking will succeed
	 * @return NULL
	 */
	public function testLockSsn()
	{
		$this->assertTrue($this->store->lockSsn('123456789'));
	}

	/**
	 * Tests that lockSsn() will return false when a lock cannot be acheived.
	 * @return NULL
	 */
	public function testLockSsnFailsOnLock()
	{
		$this->store->lockSsn('123456789');
		$this->assertFalse($this->store->lockSsn('123456789'));
	}

	/**
	 * Tests the lockSsn() will block for specified seconds.
	 * @return NULL
	 */
	public function testLockSsnBlocking()
	{
		$time = microtime(TRUE);
		$this->store->lockSsn('123456789');
		$this->assertFalse($this->store->lockSsn('123456789', 3000));
		$this->assertGreaterThanOrEqual(0.003, microtime(TRUE) - $time);

		$time = microtime(TRUE);
		$this->assertFalse($this->store->lockSsn('123456789'));
		$this->assertLessThanOrEqual(0.003, microtime(TRUE) - $time);
	}

	/**
	 * Tests that unlocking really unlocks
	 * @return NULL
	 */
	public function testUnlockSsn()
	{
		$this->store->lockSsn('123456789');
		$this->store->unlockSsn('123456789');
		$this->assertTrue($this->store->lockSsn('123456789'));
	}

	/**
	 * Tests that adding applications really adds applications.
	 * @return NULL
	 */
	public function testAddApplication()
	{
		$this->store->addApplication('123456789', 'company', '1', strtotime('2009-01-01 00:00:00'));

		$this->assertEquals(
			array(
				array(
					'application_id' => '1', 
					'ssn' => '123456789', 
					'company' => 'company', 
					'date' => strtotime('2009-01-01 00:00:00')
				)
			), 
			array_values($this->store->getApplications('123456789'))
		);
	}

	/**
	 * Tests that multiple calls to add the same application will only add it once for the same company.
	 * @return NULL
	 */
	public function testAddApplicationMultipleTimes()
	{
		$this->store->addApplication('123456789', 'company', '1', strtotime('2009-01-01 00:00:00'));
		$this->store->addApplication('123456789', 'company', '1', strtotime('2009-01-01 00:00:00'));

		$this->assertEquals(
			array(
				array(
					'application_id' => '1', 
					'ssn' => '123456789', 
					'company' => 'company', 
					'date' => strtotime('2009-01-01 00:00:00')
				)
			), 
			array_values($this->store->getApplications('123456789'))
		);
	}
}

?>
