<?php
require('olp_lib_setup.php');
/**
 * Test case for Cache_Memcache.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Cache_MemcacheTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The test server to use.
	 *
	 * @var Cache_MemcacheServer
	 */
	protected $server;
	
	/**
	 * Sets up the server before each test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->server = new Cache_MemcacheServer('localhost');
	}
	
	/**
	 * Tests that the getInstance function returns an instance of Cache_Memcache.
	 *
	 * @return void
	 */
	public function testGetInstance()
	{
		$this->assertType('Cache_Memcache', Cache_Memcache::getInstance());
	}
	
	/**
	 * Tests that we can set and get a simple value out of memcache.
	 * 
	 * @return void
	 */
	public function testSet()
	{
		$key = 'olp_lib_test_key';
		$value = 'test_data';
		
		Cache_Memcache::getInstance()->addServer($this->server);
		
		$ret_val = Cache_Memcache::getInstance()->set($key, $value, 1);
		$this->assertTrue($ret_val);
		
		$ret_val = Cache_Memcache::getInstance()->get($key);
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
		
		Cache_Memcache::getInstance()->addServer($this->server);
		
		$ret_val = Cache_Memcache::getInstance()->add($key, $value, 1);
		$this->assertTrue($ret_val);
		
		$ret_val = Cache_Memcache::getInstance()->get($key);
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
		
		Cache_Memcache::getInstance()->addServer($this->server);
		
		$ret_val = Cache_Memcache::getInstance()->set($key, $value, 1);
		$this->assertTrue($ret_val);
		
		$ret_val = Cache_Memcache::getInstance()->get($key);
		$this->assertEquals($value, $ret_val);
		
		$ret_val = Cache_Memcache::getInstance()->delete($key);
		$this->assertTrue($ret_val);
	}
}
?>
