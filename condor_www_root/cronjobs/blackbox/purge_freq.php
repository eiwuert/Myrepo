<?php
/**
 * purge_freq.php
 *
 * GFORGE [#8252] Create cron to purge old freq. score records [TF]
 *
 * @desc cron that will purge records from the vendor_decline_freq
 * table that have been there for longer than two weeks.  Accepts command
 * line args as documented inline (php purge_freq.php -h)
 *
 */

// setting this in case there is a backlog-- prevents memory shortage
ini_set("memory_limit","124M");

if (!file_exists('/virtualhosts/bfw.1.edataserver.com/include/code/server.php'))
{
	echoAlt("\nserver.php not found in /virtualhosts/bfw.1.edataserver.com/include/code/");
	//throw new Exception("Purge Script for vendor_decline_freq FAILED");
	die();
}

require_once("/virtualhosts/bfw.1.edataserver.com/include/code/server.php");

if ($argc < 2 || in_array($argv[1], array('--help', '-help', '-h', '-?', 'help')))
{

	$thisscript = $argv[0];

	echo "  Purge Script for vendor_decline_freq:

  Usage:
  $thisscript [target] [args]
  
  Where [target] indicates the mode of operation:
  local		to target db local
  rc		to target db rc
  live		to target db live
  report	to target db report
  
  [args]
  -dry-run	to see the number of records that will be purged
  -h,--help to see this output
  -silent 	to suppress terminal output
  
  This script will delete records older than 2 weeks prior the current 
  timestamp from the table vendor_decline_freq on the indicated target 
  DB as defined in /virtualhosts/server.php.  It will attempt to save
  a compressed version of those deleted records locally, but if the
  local directory is not writeable it will proceed with the deletion
  anyway.
  
";

}
else
{
	if (in_array("-silent", $argv)) echoAlt("",TRUE);
	else echoAlt("",FALSE);

	if (in_array($argv[1], array('local','rc', 'live', 'report', 'tym')))
	{
		$currentmode = strtoupper($argv[1]);
	}

	else
	{
		$currentmode = "LOCAL";
	}

	echoAlt("Current mode set to $currentmode");

	if (in_array("-dry-run", $argv))
	{
		echoAlt("\ndry run selected:\n");
		echoAlt(getDry($currentmode));
	}
	else
	{
		echoAlt(doDelete($currentmode));
	}

	echoAlt("\nDONE\n");
}

/**
 * Dry run for purge_freq, doesn't do any DB writes
 *
 * @param string $mode rc, live, local, as defined in server.php
 * @return string a message relating the results
 */
function getDry($mode)
{
	$twoweeksago = strtotime("-2 weeks");
	$nowsql = date('Ymdhis',$twoweeksago);
	$message = "";

	$qstring = "select count(*) from vendor_decline_freq
				where 
				date_created < '$nowsql'";

	//echoAlt($qstring);
	$ans = doQuery($mode, $qstring);
	//echoAlt(print_r($ans,true));
	if (count($ans) == 0)
	{
		$message.= "No records older than 2 weeks found on $mode";
	}
	else
	{
		$tempct = $ans[0][0];
		$run_seconds = $tempct * .0001;
		$run_english = $run_seconds;
		$message.= "For db $mode there are $tempct records older than 2 weeks.";
		$message.= "\nQueried on date_created < $nowsql";
		$message.= "\nIt is estimated that the deletion and archive process will ";
		$message.= "\ntake $run_seconds seconds to complete.";
	}

	return $message;
}

/**
 * Attempt to delete records from vendor_decline_freq table older than 14 days.
 * If the subdirectory archive/ is writable, dump the deleted records there
 *
 * @param string $mode rc, live, local, as defined in server.php
 * @return string a message relating the results
 */
function doDelete($mode)
{
	$twoweeksago = strtotime("-2 weeks");
	$nowsql = date('Ymdhis',$twoweeksago);
	$message = "";

	$qstring = "select * from vendor_decline_freq
				where 
				date_created < '$nowsql'";

	$starttime = time();

	$ans = doQuery($mode, $qstring);

	$message.= getDry($mode);
	$message.= "\nRetrieving records took " . (time()-$starttime) . " seconds";
	if (count($ans) == 0)
	{
		$message.= "\nNo records were found.";
		return $message;
	}
	else
	{
		$readyforwrite = gzcompress(serialize($ans));
		$fname = "freq_$mode_pre" . date("Y_m_d_his",$twoweeksago) . ".ser.gz";
		$written = FALSE;
		$written = @file_put_contents("archive/$fname",$readyforwrite);

		if ($written)
		{
			$message.= "\nWrote $written bytes to file archive/$fname";
		}
		else
		{
			$message.= "\nUnable to write to file archive/$fname";
		}
		$message.= "\nTime elapsed so far: " . (time()-$starttime) . " seconds";
		$message.= "\nProceeding with delete process...";
		$qstring = "delete from vendor_decline_freq
				where 
				date_created < '$nowsql'";
		$ans2 = doQuery($mode, $qstring);
		$tempct = @$ans2[0][0];
		$message.= "\nKilled $tempct records from $mode DB.";
		$message.= "\nTotal time elapsed since first select process: " . (time()-$starttime) . " seconds";

	}

	return $message;
}

/**
 * Performs a query using native php5 functions
 *
 * @param string $mode live, local, rc, anything else defined in server.php
 * @param string $query the query
 * @return array either the seq-indexed query results or the number of affected rows as an array
 */
function doQuery($mode, $query)
{
	$answer = array();


	$tempsql = Server::Get_Server(strtoupper($mode),"BLACKBOX");

	$sqluser = $tempsql['user'];
	$sqlpass = $tempsql['password'];
	$sqldb = $tempsql['db'];
	$sqlhost = $tempsql['host'];

	$venus = array();

	$link = mysql_connect($sqlhost, $sqluser, $sqlpass,TRUE);
	if (!$link)
	{
		echoAlt("Purge Script for vendor_decline_freq FAILED " . mysql_error());
	}
	mysql_select_db($sqldb);
	$result = mysql_query($query, $link);


	if (!is_resource($result))
	{
		if ($result) return array(array(mysql_affected_rows($link)));
		else return array();
	}

	while ($fish = mysql_fetch_row($result))
	{
		$venus[] = $fish;
	}
	mysql_free_result($result);

	// leave the conn open if this is going to run a lot
	mysql_close($link);
	return $venus;
}

/**
 * A variation on echo to honor the -silent commandline option
 *
 * @param string $str the message to echo
 * @param mixed $silent TRUE to enable silent mode
 * @return nothing
 */
function echoAlt($str, $silent = NULL)
{
	static $issilent = FALSE;
	if (!is_null($silent))
	{
		$issilent = $silent;
	}
	if (!$issilent)
	{
		echo "\n" . $str;
	}

}

?>