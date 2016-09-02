<?php

/** A class to add in BBxAdmin's memcache servers.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class Cache_Servers_BBxAdmin
{
	static protected $servers_added = FALSE; /**< @var bool */
	
	/** Retrieve the list of servers to add.
	 *
	 * @param string $mode
	 * @return array
	 */
	protected function getServers($mode)
	{
		switch (strtoupper($mode))
		{
			case 'LIVE':
				$memcache_servers = array(
					array('host' => 'ps1.tss', 'port' => 11211, 'weight' => 1),
					array('host' => 'ps2.tss', 'port' => 11211, 'weight' => 1),
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
	
	/** Setup memcache servers.
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
	}
}

?>
