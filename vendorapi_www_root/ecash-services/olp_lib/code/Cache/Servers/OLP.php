<?php
/**
 * A class to add in OLP's memcache servers.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Cache_Servers_OLP
{
	/**
	 * Whether servers have been added.
	 *
	 * @var bool
	 */
	static protected $servers_added = FALSE;
	
	/**
	 * Retrieve the list of servers to add.
	 *
	 * @param string $mode
	 * @return array
	 */
	protected function getServers($mode)
	{
		$mode = OLP_Environment::getOverrideEnvironment($mode);

		switch (strtoupper($mode))
		{
			case 'LIVE':
				$memcache_servers = array(
					array('host' => 'ps11.ept.tss', 'port' => 11211, 'weight' => 1),
					array('host' => 'ps12.ept.tss', 'port' => 11211, 'weight' => 1),
					array('host' => 'ps30.ept.tss', 'port' => 11211, 'weight' => 1),
					array('host' => 'ps34.ept.tss', 'port' => 11211, 'weight' => 1)
				);
				break;

			case 'STAGING':
				$memcache_servers = array(
					array('host' => 'ps74.ept.tss', 'port' => 11211, 'weight' => 1),
				);
				break;
			
			case 'UNITTEST':
				$memcache_servers = array(
					array('host' => 'dev1.ept.tss', 'port' => 11211, 'weight' => 1),
				);
				break;
			
			case 'RC':
			case 'LOCAL':
			default:
				$memcache_servers = array(
					array('host' => 'localhost', 'port' => 11211, 'weight' => 1)
				);
				break;
		}

		return $memcache_servers;
	}
	
	/**
	 * Setup memcache servers.
	 *
	 * @param string $mode
	 * @return void
	 */
	static public function setupMemcacheServers($mode)
	{
		if (!self::$servers_added)
		{
			self::$servers_added = TRUE;
			
			$memcache_servers = self::getServers($mode);
			$cache_memcache = Cache_Memcache::getInstance();
			$cache_olpmemcache = Cache_OLPMemcache::getInstance();
			
			foreach ($memcache_servers AS $server)
			{
				$server = new Cache_MemcacheServer($server['host'], $server['port'], TRUE, $server['weight']);
				$cache_memcache->addServer($server);
				$cache_olpmemcache->addServer($server);
			}
		}
		
		$cache_memcache->setCompressThreshold(Cache_Memcache::MEMCACHE_COMPRESS_THRESHOLD);
	}
}
