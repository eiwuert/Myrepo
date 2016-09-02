#!/usr/lib/php5/bin/php
<?php

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

if (file_exists('/virtuallib/include_path.php'))
{
	require_once ('/virtuallib/include_path.php');
}


require_once ('sourcepro/index.php');

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

	function open ()
	{
		$this->db = new SQLiteDatabase($this->file, 0666, $this->errmsg);
		if (! $this->db)
		{
			throw new Exception('sqlite_open failed on '.$this->file.': '.$this->errmsg);
		}
	}

	function close ()
	{
		unset($this->db);
	}

	function busyTimeout($ms)
	{
		$this->db->busyTimeout($ms);
	}

	function query ($sql)
	{
		$q = $this->db->unbufferedQuery ($sql);
		if (! $q)
		{
			throw new Exception (__FUNCTION__.': '.sqlite_error_string($this->db->lastError()), $this->db->lastError());
		}
		return $q;
	}

	function queryExec ($sql)
	{
		$r = $this->db->queryExec($sql);
		if (! $r)
		{
			throw new Exception (__FUNCTION__.': '.sqlite_error_string($this->db->lastError()), $this->db->lastError());
		}
		return $r;
	}

	function querySingle ($sql)
	{
		$r = $this->db->singleQuery($sql);
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
			usleep(5000);
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
				throw new Exception("$file doesnt exist or cant be created");
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

class EnterpriseProScrubProc extends PMsgQueue
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
				return 10;
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

class EnterpriseProScrubBase extends EnterpriseProScrubProc
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
		sleep(30);
		$glob = glob($this->base.'*_*/journal/journal_*.db');
		foreach ($glob as $file)
		{
			if ($this->status[$file] || $this->checkLock($file.'-lock'))
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
		if (time() - $fs['mtime'] > 600)
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

class EnterpriseProScrubJournal extends EnterpriseProScrubProc
{

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
		$this->msgOut("Recv msg for $msg", 3);
		$this->procJournal($msg);
		$this->mqSend(2, $msg);
	}

	function openJournal ($journalName)
	{
		P::makeDir(dirname($journalName));
		$j = new PSqlite($journalName);
		$j->hasTable('data', "CREATE TABLE data (action_date, action, ap01, ap02, ap03, ap04, ap05, ap06, ap07, ap08, ap09, ap10, status_flag)");
		return $j;
	}

	function openSpaceLog ($fileName)
	{
		P::makeDir(dirname($fileName));
		$j = new PSqlite($fileName);
		$j->hasTable('space', "CREATE TABLE space (created_date, space_key, space_def, status_flag); CREATE UNIQUE INDEX ix_space_key ON space (space_key) ON CONFLICT IGNORE;");
		return $j;
	}

	function procJournal ($journalName)
	{
		if (! preg_match('/(.*\/)([^\/]+)_(\w+)\/journal\/journal_(\d+)\.db$/', $journalName, $m))
		{
			throw new Exception ("invalid journalName ($journalName)");
		}
		$basePath = $m[1];
		$mode = $m[3];
		$custKey = $m[2].'_'.$m[3];
		$journalNum = $m[4];

		if (! file_exists($journalName))
		{
			$this->msgOut("Warning $journalName dosen't exist");
			return FALSE;
		}

		$lockName = $journalName.'-lock';
		file_put_contents($lockName, $this->fork->pid);
		sleep(5);

		$this->msgOut("Processing $custKey journal $journalNum", 3);

		$jdb = $this->openJournal($journalName);

		$c = $jdb->querySingle("select count(*) from data");
		/*
		if ($c < 10)
		{
			$t = $jdb->querySingle("select MIN(action_date) from data");
			if (time() - $t < 10)
			{
				$this->msgOut("Skipping small/recent journal $journalName");

				unlink($lockName);
				return FALSE;
			}
		}
		*/

		$batch_key = P::hash($this->fork->pid.microtime(1).mt_rand().uniqid(mt_rand(), 1));
		$jdb->queryExec("update data set status_flag = '{$batch_key}' where oid in (select oid from data where status_flag = 'LOCAL' limit 2000)");

		$data = $jdb->queryAll("select * from data where status_flag = '{$batch_key}'", SQLITE_NUM);
		if (! (is_array ($data) && count ($data)))
		{
			$c = $jdb->querySingle("select count(*) from data");
			if (! $c)
			{
				$this->msgOut("No data in $journalName");
				unlink($journalName);
			}
			else
			{
				$batch_key = $jdb->querySingle("select status_flag from data where status_flag != 'LOCAL' LIMIT 1");
				$data = $jdb->queryAll("select * from data where status_flag = '{$batch_key}'", SQLITE_NUM);
				$this->msgOut("Processing leftover batch $batch_key in $journalName");
			}
			if (! (is_array ($data) && count ($data)))
			{
				unlink($lockName);
				return FALSE;
			}
		}

		$sdb = $this->openSpaceLog($basePath.$custKey."/repository/space.db");
		$sdb->busyTimeout(10*60*1000);
		$sdb->queryBegin();
		foreach ($data as $row)
		{
			$sdb->queryExec ("INSERT OR IGNORE INTO space VALUES ('".implode("','", array_map ('sqlite_escape_string', array($row[0],$row[4],$row[5],'LOCAL')))."')");
		}
		$sdb->queryCommit();

		$jdb->queryRetry("delete from data where status_flag = '{$batch_key}'");
		$jdb->queryRetry("VACUUM");

		$c = $sdb->querySingle("select count(*) from space where status_flag != 'DONE'");	
		if ($c > 0)
		{
			/*
			if ($c < 100)
			{
				$t = $sdb->querySingle("SELECT MIN(created_date) FROM space WHERE status_flag != 'DONE'");
				if (time() - $t < 300)
				{
					unlink($lockName);
					return FALSE;
				}
			}
			*/

			$sdb->queryExec("update space set status_flag = '{$batch_key}' where status_flag != 'DONE'");
			$data = $sdb->queryAll("select space_def, space_key from space where status_flag = '{$batch_key}'", SQLITE_NUM);

			$url = "http://{$mode}.1.enterprisepro.epointps.net/index.php";

			$this->msgOut("Processing batch $batch_key with ".count($data)." records", 2);

			$response = FALSE;
			try
			{
				$remotePush = new SourcePro_Prpc_Client($url);
				$remotePush->Set_Timeout(1200);
				$response = $remotePush->Batch_Space_Key ($data);
			}
			catch (Exception $e)
			{
				$this->msgOut("Prpc Exception ".$e->__toString());
			}

			if ($response === TRUE)
			{
				$sdb->queryExec("update space set status_flag = 'DONE' where status_flag = '{$batch_key}'");
			}
		}


		$free = disk_free_space($basePath.$custKey."/repository/") / 1024 / 1024;

		if ($free < 300)
		{
			$min = $sdb->querySingle("SELECT MIN(action_date) FROM space WHERE status_flag = 'DONE'");
			if ($min)
			{
				$cut = strtotime("+1 day", strtotime(date('Y-m-d', $min)));
				$this->msgOut("Low disk space! deleting space records before ".date('Y-m-d', $cut));
				$sdb->queryExec("DELETE FROM space WHERE action_date < $cut AND status_flag = 'DONE'");
				$sdb->queryExec("VACUUM");
			}
		}

		unlink($lockName);
	}
}

class EnterpriseProScrub extends PFork
{

	function __construct ($dbg = 0)
	{
		parent::__construct($dbg);
	}
}


$qfile = '/tmp/mq_enterprisepro';

// HACK: to clear the q
if(! file_exists($qfile)) touch($qfile);
$q = msg_get_queue(ftok($qfile, 'R'), 0666 | IPC_CREAT);
msg_remove_queue($q);

$s = new EnterpriseProScrub (1);

$s->forkSpawn('EnterpriseProScrubBase', array('qfile' => $qfile, 'base' => '/opt/enterprisepro/var/'));

for ($i = 0 ; $i < 4 ; $i++)
{
	$s->forkSpawn('EnterpriseProScrubJournal', array('qfile' => $qfile));
}

$s->forkLoop();

?>
