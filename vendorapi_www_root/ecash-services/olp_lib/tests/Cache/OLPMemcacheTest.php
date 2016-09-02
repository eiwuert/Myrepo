<?php
/**
 * Tests the Cache_OLPMemcache class.
 *
 * @group Cache
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Cache_OLPMemcacheTest extends PHPUnit_Framework_TestCase
{
	/**
	 * This is the object we're testing.
	 *
	 * @var Cache_OLPMemcache
	 */
	protected $cache = NULL;
	
	/**
	 * This is the object we use to verify that {@see $cache} is working.
	 *
	 * @var Cache_Memcache
	 */
	protected $rawcache = NULL;
	
	/**
	 * Server we'll use for both cache interfaces.
	 *
	 * @var Cache_MemcacheServer
	 */
	protected $server = NULL;
	
	/**
	 * Determine if we can run and set up memcache objects/servers.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!class_exists('Cache_OLPMemcache'))
		{
			$this->markTestIncomplete(
				'Could not test, missing class Cache_OLPMemcache'
			);
		}
		
		if (!extension_loaded('memcache'))
		{
			$this->markTestIncomplete(
				'Could not test Cache_OLPMemcache, memcache not loaded'
			);
		}
		
		$this->server = new Cache_MemcacheServer('localhost');
		$this->cache = Cache_OLPMemcache::getInstance();
		$this->cache->addServer($this->server);
		$this->rawcache = Cache_Memcache::getInstance();
		$this->rawcache->addServer($this->server);
	}
	
	/**
	 * Test setting a key/value and check that it's prefixed correctly in cache.
	 *
	 * @return void
	 */
	public function testCacheSet()
	{
		$key = 'my/new/key';
		$value = 'string';
		$expected_key = sprintf('%s/%s', $this->cache->prefix, $key);
		
		$this->assertTrue($this->cache->set($key, $value));
		$this->assertEquals($value, $this->rawcache->get($expected_key));
	}
	
	/**
	 * Test setting a key and then checking that it is listed properly.
	 *
	 * @return void
	 */
	public function testListKeys()
	{
		$key = 'list/me';
		$value = 'something';
		$expected_key = sprintf('%s/%s', $this->cache->prefix, $key);
		
		$this->cache->set($key, $value);
		$this->assertTrue(in_array($expected_key, $this->cache->getKeys()));
	}
	
	/**
	 * Test deleting a key using the * notation.
	 *
	 * @return void
	 */
	public function testDelete()
	{
		$key = 'delete/me';
		$value = 123;
		$stored_key_name = sprintf('%s/%s', $this->cache->prefix, $key);
		
		$this->assertTrue($this->cache->set($key, $value));
		$this->assertTrue($this->cache->delete('delete/*'));
		$this->assertFalse($this->rawcache->get($stored_key_name));
	}
	
	/**
	 * Test setting a key/value and getting it correctly from cache.
	 *
	 * @return void
	 */
	public function testCacheGet()
	{
		$key = 'my/new/key';
		$value = 'string';
		$expected_key = sprintf('%s/%s', $this->cache->prefix, $key);
		
		$this->assertTrue($this->cache->set($key, $value));
		$this->assertEquals($value, $this->cache->get($key));
		$this->assertEquals($this->rawcache->get($expected_key),$this->cache->get($key));
	}
	
}

?>
