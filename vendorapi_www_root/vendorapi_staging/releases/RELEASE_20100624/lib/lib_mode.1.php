<?php

# vim: set ts=4:
# we have hundreds of instances of hard-coded regex matches to try to figure out
# whether the current code is running on local, rc or live. i know that we have the
# config_map stuff in site_config and theoretically everything would use that, but
# this is an attempt to at least centralize the code that detects where the code
# is running

if (!defined("MODE_UNKNOWN")) define("MODE_UNKNOWN", 0);
if (!defined("MODE_LOCAL")) define("MODE_LOCAL", 1);
if (!defined("MODE_RC")) define("MODE_RC", 2);
if (!defined("MODE_LIVE")) define("MODE_LIVE", 3);

class Lib_Mode
{
	function Set_Mode($mode)
	{
		assert(is_integer($mode));
		assert(MODE_LOCAL == $mode || MODE_RC == $mode || MODE_LIVE == $mode);
		define("MODE", $mode);
	}

	# try to figure out where we this code is running
	# returns TRUE on success, FALSE on failure
	function Get_Mode()
	{

		############### try to determine based on path

		if (isset($_SERVER["PWD"]))
		{
			$path = $_SERVER["PWD"];
		}
		else if (isset($_SERVER["PATH_TRANSLATED"]))
		{
			$path = $_SERVER["PATH_TRANSLATED"];
		}
		else
		{
			$path = __FILE__;
		}

		define("MODE_PATH", $path);

		# being run on the cmdline or website in an rc directory
		if (preg_match('#^/(?:var/www/html/|virtualhosts|vh)/(?:[^/]+/)+rc\b#', $path))
		{
			define("MODE", MODE_RC);
			define("MODE_HOST", NULL);
			return MODE;
		}

		############## try to determine based on host

		# try to figure out based on URL
		if (isset($_SERVER["HTTP_HOST"]))
		{
			$host = $_SERVER["HTTP_HOST"];
		}
		# try to figure out based on hostname for cmdline
		else if ("" == ($host = trim(`hostname`)))
		{
			return FALSE;
		}

		# at this point we've got our hostname in $host or bailed
		$host = strtolower($host);

		if (preg_match('/((?:ds\d{2}|test|alpha|nightwing)(?:\.tss)?)$/', $host, $match))
		{
			define("MODE", MODE_LOCAL);
			define("MODE_HOST", $match[1]);
		}
		else if (preg_match('/^rc\.(.*)$/', $host))
		{
			define("MODE", MODE_RC);
			define("MODE_HOST", $match[1]);
		}
		else
		{
			define("MODE", MODE_LIVE);
			define("MODE_HOST", $host);
		}

		return MODE;
	}
}

?>
