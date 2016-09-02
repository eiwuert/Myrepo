<?php

/****************************************************************************

Diag(nostic) Class

2.0
--
made this log everything..


a bunch of wrappers for printing diagnostic messages if Diag::Enable()
is run

****************************************************************************/

$_DIAG = array("ENABLED" => array(false), "LOGFILE" => "/tmp/diag.default.log");

class Diag
{
	function Enable()
	{
		global $_DIAG;
		$_DIAG["ENABLED"][0] = true;
	}

	function Disable()
	{
		global $_DIAG;
		$_DIAG["ENABLED"][0] = false;
	}

	# enable or disable diag in a local scope; before you leave the scope make sure to Pop()!
	# this allows code within code to have its own diag settings w/o caring about what other
	# parts of the app are doing
	function Push($bool)
	{
		assert(is_bool($bool));
		global $_DIAG;
		array_unshift($_DIAG["ENABLED"], $bool);
	}

	function Pop()
	{
		global $_DIAG;
		array_shift($_DIAG["ENABLED"]);
	}

	function SetLogFile($logfile)
	{
		global $_DIAG;
		$_DIAG["LOGFILE"] = $logfile;
	}

	function IsEnabled()
	{
		global $_DIAG;
		return (true === $_DIAG["ENABLED"][0]);
	}

	function Out($var)
	{
		if (!Diag::IsEnabled())
		{
			return;
		}
		global $_DIAG;
		
		if (false === ($fp = fopen($_DIAG["LOGFILE"], "a")))
		{
			# maybe complain to syslog?
			echo "could not open Diag[LOGFILE] '" . $_DIAG["LOGFILE"] . "'";
			return;
		}
		
		$bt = debug_backtrace();
		$ws = !empty($_SERVER["REMOTE_ADDR"]);
		echo $o = (
			($ws ? "<pre>" : "") .
			$bt[0]["file"] . ":" .
			$bt[0]["line"] . ":" .
			($ws ? htmlspecialchars($var) : $var) .
			($ws ? "</pre>" : "") . "\r\n");
		fwrite($fp,$o);
		fclose($fp);
	}

	function DumpToFile(&$var, $label="")
	{
		if (!Diag::IsEnabled())
		{
			return;
		}
		global $_DIAG;
		$bt = debug_backtrace();
		if (false === ($fp = fopen($_DIAG["LOGFILE"], "a")))
		{
			# maybe complain to syslog?
			echo "could not open Diag[LOGFILE] '" . $_DIAG["LOGFILE"] . "'";
			return;
		}
		$ws = !empty($_SERVER["REMOTE_ADDR"]);
		fwrite(
			$fp,
			date("Y-m-d H:i:s T") . ":" . $bt[0]["file"] . ":" . $bt[0]["line"] .
				":" . ($label ? $label . ":" : "") . print_r($var, 1) . "\r\n"
		);
		fclose($fp);
	}

	function GetLogFile()
	{
		global $_DIAG;
		return $_DIAG["LOGFILE"];
	}

	function Dump(&$var, $label="")
	{
		if (!Diag::IsEnabled())
		{
			return;
		}
		$bt = debug_backtrace();
		$ws = !empty($_SERVER["REMOTE_ADDR"]);
		echo
			($ws ? "<pre>" : "") .
			$bt[0]["file"] . ":" .
			$bt[0]["line"] . ":" .
			($label ? $label . ":" : "") .
			($ws ? htmlspecialchars(var_export($var, 1)) : var_export($var, 1)) .
			($ws ? "</pre>" : "") . "\r\n";
	}

	function Bail($var)
	{
		if (!Diag::IsEnabled())
		{
			return;
		}
		$bt = debug_backtrace();
		$ws = !empty($_SERVER["REMOTE_ADDR"]);
		die(
			($ws ? "<pre>" : "") .
			$bt[0]["file"] . ":" .
			$bt[0]["line"] . ":" .
			($ws ? htmlspecialchars($var) : $var) .
			($ws ? "</pre>" : "") . "\r\n"
		);
	}

}

?>
