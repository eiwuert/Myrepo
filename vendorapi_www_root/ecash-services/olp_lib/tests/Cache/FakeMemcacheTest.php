<?php

/**
 * Test case for Cache_FakeMemcache.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Cache_FakeMemcacheTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we can set and get a simple value out of memcache.
	 * 
	 * @return void
	 */
	public function testSet()
	{
		$key = 'olp_lib_test_key';
		$value = 'test_data';
		
		$memcache = new Cache_FakeMemcache();
		
		$ret_val = $memcache->set($key, $value, 1);
		$this->assertTrue($ret_val);
		
		$ret_val = $memcache->get($key);
		$this->assertEquals($value, $ret_val);
	}
	
	/**
	 * Tests that we can add and get a simple value out of memcache.
	 * 
	 * @return void
	 */
	public function testAdd()
	{
		// This key has to be different since it's an add()
		$key = 'olp_lib_new_test_key';
		$value = 'test_data';
		
		$memcache = new Cache_FakeMemcache();
		
		$ret_val = $memcache->add($key, $value, 1);
		$this->assertTrue($ret_val);
		
		$ret_val = $memcache->get($key);
		$this->assertEquals($value, $ret_val);
	}
	
	/**
	 * Tests that we can delete a value from memcache.
	 *
	 * @return void
	 */
	public function testDelete()
	{
		$key = 'olp_lib_test_key';
		$value = 'test_data';
		
		$memcache = new Cache_FakeMemcache();
		
		$ret_val = $memcache->set($key, $value, 1);
		$this->assertTrue($ret_val);
		
		$ret_val = $memcache->get($key);
		$this->assertEquals($value, $ret_val);
		
		$ret_val = $memcache->delete($key);
		$this->assertTrue($ret_val);
	}
}
?>
