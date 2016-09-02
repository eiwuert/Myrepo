#!/usr/bin/php
<?php

error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(0);

function check_ext ($ext)
{
	$exts = is_array($ext) ? $ext : func_get_args();
	foreach ($exts as $e)
	{
		if (! (extension_loaded($e) || dl($e.'.so')))
		{
			die ($e.' not available');
		}
	}
}

check_ext('posix', 'pcntl', 'sysvmsg', 'sqlite');

require_once ('sourcepro/index.php');

if (file_exists('/virtuallib/include_path.php'))
{
	require_once ('/virtuallib/include_path.php');
}

declare (ticks = 1);

class P
{
	function makeDir ($path)
	{
		if (! file_exists($path))
		{
			$dirs = explode ('/', $path);
			$tmp = '/';
			foreach ($dirs as $dir)
			{
				if (! $dir) continue;
				$tmp .= $dir.'/';
				if (! file_exists($tmp))
				{
				    mkdir ($tmp, 0775);
				}
			}
		}
	}

	function bin2Str ($bin, $nbits = 6)
	{
		$alpha = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-";

		$p = $w = $have = 0;
		$q = strlen($bin);
		$mask = (1 << $nbits) - 1;
		$out = '';

		while (1)
		{
			if ($have < $nbits)
			{
				if ($p < $q)
				{
					$w |= ord($bin{$p++}) << $have;
					$have += 8;
				}
				else
				{
					if ($have == 0)
					    break;
					$have = $nbits;
				}
			}

			$out .= $alpha{($w & $mask)};

			$w >>= $nbits;
			$have -= $nbits;
		}

		return $out;
	}

	function hash ($msg, $key = NULL)
	{
		$hash = P::Bin2Str(pack("H*", sha1($msg.$key)));
		return $hash;
	}
}

class PSqlite
{
	const MAX_TRY = 42;
	
	function __construct ($file)
	{
		$this->file = $file;
		$this->db = NULL;
		$this->errmsg = '';

		$this->open();
	}

	function __destruct ()
	{
	}

	function msgOut ($msg, $lvl = 0)
	{
		echo date("Y-m-d H:i:s")." [".getmypid()."] $msg\n";
	}
	
	function open ()
	{
		$this->db = new SQLiteDatabase($this->file, 0666, $this->errmsg);
		if (! $this->db)
		{
			throw new Exception('sqlite_open failed on '.$this->file.': '.$this->errmsg);
		}
	}

	function busyTimeout($ms)
	{
		$this->db->busyTimeout($ms);
	}

	function query ($sql)
	{
		for ($t = 0, $r = FALSE ; $r === FALSE && $t < self::MAX_TRY && !usleep($t * 420000) ; $t++)
		{	
			$r = $this->db->unbufferedQuery ($sql);
			if ($r === FALSE)
			{
				$this->msgOut("WARNING: query failed with ".sqlite_error_string($this->db->lastError())."\nFILE: {$this->file}\nSQL:\n $sql\n");
			}
		}
		if (! $r)
		{
			throw new Exception (__FUNCTION__.': '.sqlite_error_string($this->db->lastError()), $this->db->lastError());
		}
		return $r;
	}

	function queryExec ($sql)
	{
		for ($t = 0, $r = FALSE ; $r === FALSE && $t < self::MAX_TRY && !usleep($t * 420000) ; $t++)
		{		
			$r = $this->db->queryExec($sql);
			if ($r === FALSE)
			{
				$this->msgOut("WARNING: query failed with ".sqlite_error_string($this->db->lastError())."\nFILE: {$this->file}\nSQL:\n $sql\n");
			}
		}
		if (! $r)
		{
			throw new Exception (__FUNCTION__.': '.sqlite_error_string($this->db->lastError()), $this->db->lastError());
		}
		return $r;
	}

	function querySingle ($sql)
	{
		$try = 0;
	   	$r = FALSE;
	   	do
		{
			$r = $this->db->singleQuery($sql);
			$try++;
			
			if ($r === FALSE)
			{
				$this->msgOut("WARNING: query failed with ".sqlite_error_string($this->db->lastError())."\nFILE: {$this->file}\nSQL:\n $sql\n");
			}
		}
		while($r === FALSE && $try < self::MAX_TRY && ! usleep(420000 * $try));

		if ($r === FALSE)
		{
			throw new Exception (__FUNCTION__.': '.sqlite_error_string($this->db->lastError()), $this->db->lastError());
		}
		return $r;
	}

	function queryAll ($sql, $result_type = SQLITE_ASSOC)
	{
		$q = $this->query($sql);
		$a = $q->fetchAll($result_type);
		if ($a === FALSE)
		{
			throw new Exception (__FUNCTION__.': '.sqlite_error_string($this->db->lastError()), $this->db->lastError());
		}
		return $a;
	}

    function queryRetry ($sql, $try = 1000, $nap = 2000)
    {
        do
        {
            $rc = $this->db->queryExec($sql);
        }
        while (! $rc && $try-- && usleep($nap));
        return $rc;
    }

    function queryBegin ()
    {
    	$this->queryRetry("BEGIN");
    }

    function queryCommit ()
    {
    	$this->queryRetry("COMMIT");
    }

    function hasTable ($name, $create = NULL)
    {
		$c = $this->querySingle("select count(*) from sqlite_master where type = 'table' and name = '{$name}'");
		if (! $c && $create)
		{
			$this->queryExec ($create);
		}
    }

	function integrityCheck ()
	{
		$c = $this->querySingle("PRAGMA integrity_check");
		return $c == 'ok' ? TRUE : FALSE;
	}
}

class PFork
{
	function __construct($dbg)
	{
		$this->dbg = $dbg;
		$this->forkSetup();
		pcntl_signal (SIGINT, array(&$this, 'sigHandler'));
		pcntl_signal (SIGTERM, array(&$this, 'sigHandler'));
		pcntl_signal (SIGHUP, array(&$this, 'sigHandler'));
		pcntl_signal (SIGCHLD, array(&$this, 'sigHandler'));
	}

	function forkSetup()
	{
		$this->pid = posix_getpid();
		$this->child = array();
		$this->sig = array();
	}

	function msgOut ($msg, $lvl = 0)
	{
		if ($lvl <= $this->dbg)
		{
			echo date("Y-m-d H:i:s")." [{$this->pid}] $msg\n";
		}
	}

	function sigHandler ($signo)
	{
		switch ($signo)
		{
			case SIGINT:
				$this->sig['term'] = TRUE;
				break;

			case SIGTERM:
				$this->sig['term'] = TRUE;
				break;

			case SIGHUP:
				$this->sig['hup'] = TRUE;
				break;

			case SIGCHLD:
				$this->sig['chld'] = TRUE;
				break;
		}
	}

	function childSig ($signo)
	{
		foreach ($this->child as $pid => $child)
		{
			if (! posix_kill($pid, $signo))
			{
				$this->msgOut ("WARN: {$this->pid} failed to signal {$pid} with {$signo}");
			}
		}
	}

	function childWait ($final = TRUE)
	{
		while (is_array ($this->child) && count ($this->child) && !$this->sig['die'])
		{
			$pid = pcntl_wait ($status, WUNTRACED);
			if ($pid == -1)
			{
				throw new Exception ("pcntl_wait failed");
			}
			if ($pid > 0)
			{
				$this->msgOut ("Reap child pid $pid", 2);
				unset($this->child[$pid]);
			}
			usleep(500);
		}
		if ($final)
		{
			exit ();
		}
	}

	function forkSpawn ($class, $arg, $nap = 0)
	{
		$pid = pcntl_fork ();
		if ($pid == -1)
		{
			throw new Exception ("unable to fork");
		}
		if ($pid)
		{
			$this->msgOut("Spawn child pid $pid", 2);
			$this->child[$pid] = 1;
		}
		else
		{
			$i = 0;
			$this->forkSetup ();
			$arg['fork'] = &$this;
			$o = new $class ($arg);
			while (1)
			{
				if ($this->sig['die'])
				{
					break;
				}
				if ($this->sig['term'])
				{
					$this->msgOut("Processing SIGTERM");
					$this->childSig (SIGTERM);
					$this->childWait ();
				}
				else
				{
					$t = $o->doTick($i++);
					if ($t == -1)
					{
						break;
					}
					if ($t > 0)
					{
						sleep($t);
					}
					elseif($nap > 0)
					{
						sleep($nap);
					}
				}
				usleep(1000);
			}
			$o->__destruct();
			exit();
		}
	}

	function forkLoop()
	{
		while (1)
		{
			if ($this->sig['die'])
			{
				break;
			}
			if ($this->sig['term'])
			{
				$this->msgOut("Processing SIGTERM");
				$this->childSig (SIGTERM);
				$this->childWait ();
			}
			elseif ($this->sig['chld'])
			{
				$status = 0;
				$this->msgOut("Got SIGCHLD but no SIGTERM");
				$pid = pcntl_wait($status, WNOHANG|WUNTRACED);
				$this->msgOut("pcntl_wait returned pid $pid");
				if ($pid)
				{
					if (($exited = pcntl_wifexited($status)))
					{
						$rc = pcntl_wexitstatus($status);
						$this->msgOut("Child pid $pid exited with status $rc");
					}
					elseif (($signaled = pcntl_wifsignaled($status)))
					{
						$sig = pcntl_wtermsig($status);
						$this->msgOut("Child pid $pid terminated with signal $sig");
					}
					elseif (($stopped = pcntl_wifstopped($status)))
					{
						$sig = pcntl_wstopsig($status);
						$this->msgOut("Child pid $pid stopped with signal $sig");
					}
					unset($this->child[$pid]);
				}
				$this->sig['term'] = TRUE;
				$this->sig['chld'] = FALSE;
			}

			usleep(50000);
		}
	}
}

class PMsgQueue
{

	function __construct($file)
	{
		if (! file_exists($file))
		{
			if (! touch($file))
			{
				throw new Exception("$file doesnt exist and cant be created");
			}
		}
		$this->mq = msg_get_queue(ftok($file, 'R'), 0666 | IPC_CREAT);
		if (! $this->mq)
		{
			throw new Exception("msg_get_queue failed");
		}
		$opt = array('msg_qbytes' => 2096912*4);
		if (! msg_set_queue($this->mq, $opt))
		{
				throw new Exception("msg_set_queue failed\n".print_r($opt,1));
		}
	}

	function __destruct()
	{
		//msg_remove_queue($this->mq);
	}

	function mqNum()
	{
		$ms = msg_stat_queue($this->mq);
		return $ms['msg_qnum'];
	}

	function mqSend($type, $msg)
	{
		$errcode = NULL;
		if (! msg_send($this->mq, $type, $msg, true, true, $errcode))
		{
			throw new Exception ("msg_send failed $errcode");
		}
	}

	function mqRecv($type, $flag = 0)
	{
		if (msg_receive($this->mq, $type, $msg_type, 16384, $msg, true, $flag, $msg_error))
		{
			return $msg;
		}
		else
		{
			throw new Exception("msg_recv failed $msg_error");
		}
	}

}

class StatProScrubProc extends PMsgQueue
{

	function __construct($arg)
	{
		parent::__construct($arg['qfile']);
		$this->fork = $arg['fork'];
		$this->mqType = 1;
		$this->mqRecvFlag = 0;
	}

	function __destruct()
	{
		parent::__destruct();
		//unset ($this->fork);
	}

	function msgOut($msg, $lvl = 0)
	{
		$this->fork->msgOut($msg, $lvl);
	}

	function doTick($i)
	{
		$loop = 0;
		do
		{
			/*
			$n = $this->mqNum();
			if (! $n)
			{
				return 2;
			}
			*/
			try
			{
				$m = $this->mqRecv($this->mqType, $this->mqRecvFlag);
			}
			catch (Exception $e)
			{
				return 2;
			}
			$this->procMesg($m);
		}
		while ($loop);

		return 1;
	}

	function procMesg($msg)
	{
		$this->fork->msgOut("Got msg $msg");
	}
}

class StatProScrubBase extends StatProScrubProc
{
	function __construct($arg)
	{
		parent::__construct($arg);
		$this->base = $arg['base'];
		$this->mqType = 2;
		$this->mqRecvFlag = MSG_IPC_NOWAIT;
		$this->status = array();
	}

	function __destruct()
	{
		parent::__destruct();
	}

	function doTick($i)
	{
		sleep(1);
		clearstatcache();
		$glob = glob($this->base.'*_*/journal/journal_*.db');
		foreach ($glob as $file)
		{
			if ($this->status[$file] || $this->checkLock($file.'-lock') || !filesize($file))
			{
				continue;
			}
			$this->status[$file] = 1;
			$this->mqSend(1, $file);
			$this->msgOut("Sent mqType=1 msg=$file", 3);
		}
		return parent::doTick($i);
	}

	function procMesg($msg)
	{
		$this->msgOut("Got mqType=$this->mqType msg=$msg", 3);
		unset($this->status[$msg]);
	}

	function checkLock ($lockFile)
	{
		if (! file_exists($lockFile))
		{
			return FALSE;
		}
		$fs = @stat($lockFile);
		$now = time();
		if ($now - $fs['mtime'] > 600)
		{
			$pid = @file_get_contents($lockFile);
			if ($pid && posix_kill(trim($pid),0))
			{
				return TRUE;
			}
			$this->msgOut("Removing stale lock $lockFile");
			@unlink($lockFile);
			return FALSE;
		}
		return TRUE;
	}
}

class StatProScrubJournal extends StatProScrubProc
{
	protected $jdb;
	protected $in_trans = FALSE;
	
	function __construct($arg)
	{
		parent::__construct($arg);
	}

	function __destruct()
	{
		parent::__destruct();
	}

	function procMesg($msg)
	{
		$lockName = $msg.'-lock';
		if ( ($lh = @fopen($lockName, 'x')) !== FALSE && flock($lh, LOCK_EX) )
		{
			fputs($lh, $this->fork->pid);
			
			$this->msgOut("Recv msg for $msg", 4);
			$this->in_trans = FALSE;
			try
			{
				$this->procJournal($msg);
			}
			catch (Exception $e)
			{
				$this->msgOut("Caught Exception\n".$e->__toString());
				if ($this->in_trans)
				{
					$this->msgOut("In transaction. Performing Rollback.");
					$this->jdb->exec('rollback');
				}
			}
			fclose($lh);
			unlink($lockName);
		}
		$this->mqSend(2, $msg);
	}

	function openJournal ($journalName)
	{
		P::makeDir(dirname($journalName));
		$j = new PSqlite($journalName);
		$j->hasTable('data', "CREATE TABLE data (action_date, action, ap01, ap02, ap03, ap04, ap05, ap06, ap07, ap08, ap09, ap10, status_flag)");
		return $j;
	}

	function procJournal ($journalName, $repositoryPath = NULL)
	{
		if (! preg_match('/(.*\/)([^\/]+)_(\w+)\/journal\/journal_(\d+)\.db$/', $journalName, $m))
		{
			throw new Exception ("invalid journalName ($journalName)");
		}
		$basePath = $m[1];
		$mode = $m[3];
		$custKey = $m[2].'_'.$m[3];
		$journalNum = $m[4];
		$journalShort = $custKey.'/journal_'.$journalNum;
		
		if (! file_exists($journalName))
		{
			$this->msgOut("Warning $journalName dosen't exist");
			return FALSE;
		}

		//sleep(2);

		$this->msgOut("Processing $journalShort", 3);

		$this->jdb = $this->openJournal($journalName);

		$ic = $this->jdb->querySingle("PRAGMA integrity_check");
		if ($ic != 'ok')
		{
			$this->msgOut("integrity_check failed on $journalName");
			mail('rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net', 'integrity_check failed', "$journalName\n\n$ic");
			rename($journalName, $journalName.'-bad');
			return FALSE;
		}

		$c = $this->jdb->querySingle("select count(*) from data");
		if (! $c)
		{
			$this->msgOut("Delete empty journal $journalShort", 2);
			unlink($journalName);
			return FALSE;
		}
		elseif ($c < 5)
		{
			$t = $this->jdb->querySingle("select MIN(action_date) from data");
			if (time() - $t < 10)
			{
				$this->msgOut("Skipping small/recent journal $journalShort");
				return FALSE;
			}
		}

		// action 5 is deprecated in favor of 6. remove this in the future
		$this->jdb->queryBegin();
		$this->in_trans = TRUE;
		$data = $this->jdb->queryAll("select OID, * from data where action == 5", SQLITE_NUM);
		if (is_array ($data) && count ($data))
		{
			foreach ($data as $row)
			{
				$event = $row[6];

				$url = "http://";
				if ($mode != 'live')
				{
					$url .= $mode.".";
				}
				$url .= "{$event}.linkstattrack.com/?pwadvid=".$row[5];

				if (file_get_contents($url) !== FALSE)
				{
					$this->jdb->queryExec("delete from data where OID = ".$row[0]);
					$this->msgOut("success calling $url");
				}
				else
				{
					$this->msgOut("failure calling $url");
				}
			}
		}
		$this->jdb->queryCommit();
		$this->in_trans = FALSE;
		

		// action 6 is a generic http request
		$this->jdb->queryBegin();
		$this->in_trans = TRUE;
		$data = $this->jdb->queryAll("select OID, * from data where action == 6", SQLITE_NUM);
		if (is_array ($data) && count ($data))
		{
			foreach ($data as $row)
			{
				if ($mode != 'live')
				{
					$url = parse_url($row[5]);

					if ($url !== FALSE)
					{
						$s = $url['scheme']."://".$mode.".".$url['host'].$url['path'];
						if (isset($url['query']))
						{
							$s .= '?'.$url['query'];
						}
						if (isset($url['fragment']))
						{
							$s .= '#'.$url['fragment'];
						}
						$url = $s;
					}
				}
				else
				{
					$url = $row[5];
				}

				if ($url && file_get_contents($url) !== FALSE)
				{
					$this->jdb->queryExec("delete from data where OID = ".$row[0]);
					$this->msgOut("success calling $url");
				}
				else
				{
					$this->msgOut("failure calling $url");
				}
			}
		}
		$this->jdb->queryCommit();
		$this->in_trans = FALSE;
		

		for ($pass = 0 ; $pass < 42 ; $pass++)
		{
			$batch_key = P::hash($this->fork->pid.microtime(1).mt_rand().uniqid(mt_rand(), 1));
			$this->jdb->queryExec("update data set status_flag = '{$batch_key}' where OID in (select OID from data where action in (1,2,3,4) and status_flag = 'LOCAL' limit 2000)");

			$data = $this->jdb->queryAll("select * from data where status_flag = '{$batch_key}'", SQLITE_NUM);
			if (! (is_array ($data) && count ($data)))
			{
				$batch_key = $this->jdb->querySingle("select status_flag from data where action in (1,2,3,4) and status_flag != 'LOCAL' LIMIT 1");
				$data = $this->jdb->queryAll("select * from data where action in (1,2,3,4) and status_flag = '{$batch_key}'", SQLITE_NUM);

				if (! (is_array ($data) && count ($data)))
				{
					return FALSE;
				}
			}

			$url = "http://{$mode}.2.statpro.epointps.net/event_batch_prpc.php";
			$this->msgOut("Processing batch $batch_key with ".count($data)." records from $journalShort", 2);

			try
			{
				$remotePush = new SourcePro_Prpc_Client($url);
				$remotePush->Set_Timeout(count($data) < 1000 ? 45 : 3000);
				$response = $remotePush->sendPackage ($custKey, $data);
			}
			catch (Exception $e)
			{
				$this->msgOut("Prpc Exception ".$e->__toString());
				return FALSE;
			}

			if ($response === TRUE)
			{
				if (is_null ($repositoryPath))
				{
					$repositoryPath = $basePath.$custKey."/repository/";
				}
				$rdb = $this->openJournal($repositoryPath."repository.db");
				$rdb->busyTimeout(10*60*1000);

				$free = disk_free_space($repositoryPath) / 1024 / 1024;

				if ($free < 500)
				{
					$min = $rdb->querySingle("SELECT MIN(action_date) FROM data");
					if ($min)
					{
						$cut = strtotime("+1 day", strtotime(date('Y-m-d', $min)));
						$this->msgOut("Low disk space! deleting repository records before ".date('Y-m-d', $cut));
						$rdb->queryExec("DELETE FROM data WHERE action_date < $cut");
						$rdb->queryExec("VACUUM");
					}
				}

				$rdb->queryBegin();
				foreach ($data as $row)
				{
					$rdb->queryExec ("INSERT INTO data VALUES ('".implode("','", array_map ('sqlite_escape_string', $row))."')");
				}
				$rdb->queryCommit();

				$this->jdb->queryRetry("delete from data where status_flag = '{$batch_key}'");
				//$this->jdb->queryRetry("VACUUM");
			}

			/*
			$c = $this->jdb->querySingle("select count(*) from data");
			if (! $c)
			{
				$this->msgOut("Delete empty journal $journalShort", 2);
				$this->jdb = NULL;
				unlink($journalName);
				break;
			}
			*/
		}
		$this->jdb = NULL;
	}
}

class StatProScrub extends PFork
{

	function __construct ($dbg = 0)
	{
		parent::__construct($dbg);
	}
}


$opt = getopt('d:p:');

if(! is_array($opt)) $opt = array();
if(! isset($opt['p'])) $opt['p'] = 5; // parallel level
if(! isset($opt['d'])) $opt['d'] = 1; // debug level


$qfile = '/tmp/mq_statpro';

// HACK: to clear the q
if(! file_exists($qfile)) touch($qfile);
$q = msg_get_queue(ftok($qfile, 'R'), 0666 | IPC_CREAT);
msg_remove_queue($q);

$s = new StatProScrub ($opt['d']);

$s->forkSpawn('StatProScrubBase', array('qfile' => $qfile, 'base' => '/opt/statpro/var/'));

for ($i = 0 ; $i < $opt['p'] ; $i++)
{
	$s->forkSpawn('StatProScrubJournal', array('qfile' => $qfile));
}

$s->forkLoop();

?>
