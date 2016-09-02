#!/usr/bin/php
<?php

if (! posix_getuid() === 0)
{
	die($argv[0].' must be run as root.');
}
echo "\n";

$dir = realpath(dirname(__FILE__));
$fp = '/etc/apache2/vhosts.d/99_srv_util.conf';

echo "install $fp\n";
if (! copy($dir.$fp, $fp))
{
	die('copy failed.');
}

if ($dir != '/virtualhosts/util')
{
	$out = $rc = NULL;
	echo "  - replace /virtualhosts/util with {$dir}\n";
	exec("sed -i -e 's@/virtualhosts/util@{$dir}@' {$fp}", $out, $rc);
	if ($rc)
	{
		die('replace failed with '.$rc);
	}
}
echo <<<MSG


Install complete.

Please reload your apache config. (/etc/init.d/apache2 reload)

MSG;

?>
