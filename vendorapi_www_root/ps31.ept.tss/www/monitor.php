<?php
/**
 * Returns the contents of the store file for the scrubber servers.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

$config = parse_ini_file('../config/config.ini');

header('Content-type: text/xml');

if (isset($config['store_file']) && file_exists($config['store_file']))
{
	// We'll assume it's valid XML
	echo file_get_contents($config['store_file']);
}
else
{
	echo '<?xml version="1.0" encoding="UTF-8"?>
<monitor>
	<error>No scrubbing monitor installed</error>
</monitor>';
}
