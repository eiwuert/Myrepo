<?php
/**
 * Setup the memcache servers and memcache instance.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

if (defined('BFW_MODE') && strcasecmp(BFW_MODE, 'LIVE') == 0)
{
	$memcache_servers = array(
		array('host' => 'ps10.ept.tss', 'port' => 11211, 'weight' => 1),
		array('host' => 'ps11.ept.tss', 'port' => 11211, 'weight' => 2),
		array('host' => 'ps12.ept.tss', 'port' => 11211, 'weight' => 2),
		array('host' => 'ps30.ept.tss', 'port' => 11211, 'weight' => 2)
	);
}
else
{
	$memcache_servers = array(
		array('host' => 'localhost', 'port' => 11211, 'weight' => 1)
	);
}

foreach ($memcache_servers as $server)
{
	$server = new Cache_MemcacheServer($server['host'], $server['port'], TRUE, $server['weight']);
	Cache_Memcache::getInstance()->addServer($server);
}
?>
