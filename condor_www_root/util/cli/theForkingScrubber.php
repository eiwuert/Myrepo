#!/usr/bin/php
<?php

require_once 'sourcepro/index.php';
require_once 'AutoLoad.1.php';

define('SYS_FQDN', Message_1::getFqdn());

/*
** PID
*/
$pid_file = "/tmp/theForkingScrubber.pid";

if(file_exists($pid_file))
{
	$pid = file_get_contents($pid_file);
	if($pid && posix_kill(trim($pid), 0))
	{
		$ps = trim(shell_exec("ps ax | grep {$pid} | grep \"{$argv[0]}\" | grep -v grep | wc -l"));
		if($ps)
		{
			echo "Process already running with PID: $pid\n";
			exit(0);
		}
		else
		{
			echo "Stale PID file: $pid_file\n";
		}
	}
}

file_put_contents($pid_file, posix_getpid(), LOCK_EX);


class std
{
	public static function out($msg)
	{
		$a = func_get_args();
		$msg = array_shift($a);
		vfprintf(STDOUT, $msg, $a);
	}

	public static function err($msg)
	{
		$a = func_get_args();
		$msg = array_shift($a);
		vfprintf(STDERR, $msg, $a);
	}
}


class AppDB extends DB_Database_1
{
	protected $ddl;

	public function __construct($dsn, $ddl)
	{
		$this->ddl = $ddl;
		parent::__construct($dsn, NULL, NULL, array(PDO::ATTR_TIMEOUT => 600));
		$uv = (int)$this->querySingleValue('select count(*) from sqlite_master');
		if (! $uv)
		{
			$this->exec($this->ddl);
		}
	}
}


class Statpro1Scrub extends Unix_WorkItem_1
{
	const MAX_ATTACH = 10;

	public $path;

	protected function log($line)
	{
		echo date("H:i:s") . "\\".posix_getpid().": $line\n";
	}

	public function __construct($path)
	{
		$this->path = is_array($path) ? $path : array($path);
	}

	public function execute()
	{
		foreach ($this->path as $p)
		{
			$this->log(__CLASS__." proc $p");

			$r = $p.'../repository/';
			if (! is_dir($r)) mkdir($r, 0770, 1);
			$r = realpath($r).'/';

			$db = new AppDB('sqlite2:'.$r.'space.db', "create table space (created_date int, space_key text unique, space_def blob, status_flag char)");
			$db = NULL;

			$day = date('Ymd');
			$event_path = $r.'event'.$day.'.db';
			$db = new AppDB('sqlite2:'.$event_path, "create table data (action_date, action, ap01, ap02, ap03, ap04, ap05, ap06, ap07, ap08, ap09, ap10, status_flag)");
			$db->exec("attach '{$p}/space.db' as spc; pragma count_changes=1;");


			$num = array('event' => 0, 'space' => 0);
			$glob = glob($p.'journal_*.db');

			for ($x = 0 ; count($glob) ; $x++)
			{

				$lh = array();
				foreach ($glob as $i => $f)
				{
					//echo "Locking $f.. ";
					if(! ((($lh[$i] = @fopen($f, 'a+')) !== FALSE) && flock($lh[$i], LOCK_EX)) )
					{
						unset($lh[$i]);
						//echo "failed.\n";
						continue;
					}
					touch($f.'-lock');
					//echo "success.\n";

					$sql = "attach '{$f}' as jnl{$i}";
					$db->exec($sql);

					if (count($lh) >= self::MAX_ATTACH - 1)
					{
						break;
					}
				}

				//echo "Processing {$glob[$i]}.\n";
				$db->exec('begin');

				foreach ($lh as $i => $h)
				{
					$sql = "insert into main.data select * from jnl{$i}.data where action != 2";
					$num['event'] += $db->querySingleValue($sql);

					$sql = "insert or ignore into spc.space select action_date, ap03, ap04, status_flag from jnl{$i}.data where action = 2";
					$num['space'] += $db->querySingleValue($sql);

					$sql = "delete from jnl{$i}.data";
					$db->exec($sql);
				}

				$db->exec('commit');

				foreach ($lh as $i => $h)
				{
					$db->exec("detach jnl{$i}");
					unlink($glob[$i].'-lock');
					fclose($lh[$i]);
					unset($glob[$i]);
				}
			}

			$db->exec("detach spc; pragma count_changes=0;");
			$db = NULL;

			$this->log("Moved {$num['space']} records and {$num['event']} records.");

			if ($num['space'])
				$this->work_queue_client->addWork(new ep2message($p.'space.db'));

			if ($num['event'])
				$this->work_queue_client->addWork(new sp2message($event_path));
		}
	}
}

abstract class base2message extends Unix_WorkItem_1
{
	public $path;
	public $rpc = array();

	public function __construct($path)
	{
		$this->path = is_array($path) ? $path : array($path);
	}

	function send_msg($cust, $mode, $rs)
	{
		foreach($rs as $action => $r)
		{
			$this->_send_msg($cust, $mode, $action, $r);
		}
	}

	function _send_msg($cust, $mode, $action, $chunk)
	{
		$src = $_ENV['USER'].'@'.SYS_FQDN.':'.__FILE__;
		if(is_numeric($action) && $action < 5)
		{
			$dst = "action{$action}@action.{$cust}.{$mode}.sp2.epointps.net";
		}
		else
		{
			$dst = "{$action}@event.{$cust}.{$mode}.sp2.epointps.net";
		}

		$msg = new Message_Container_1($src, $dst);
		$msg->head['row_count'] = count($chunk);
		$msg->body = $chunk;

		$url = $mode === 'live' ? 'http://sp2.epointps.net/' : 'http://'.$mode.'.sp2.epointps.net/';

		$sp2 = $this->getClient($url);

		std::out(date('H:i:s').'\\'.posix_getpid().": Sending msg to $dst with ".count($chunk)." rows.. \n");

		$sp2->consumeMessage($msg);
	}


	function flushClient()
	{
		foreach($this->rpc as $url => $rpc)
		{
			if(!count($rpc->call))
				continue;

			$ts0 = microtime(1);
			for($i = 0 ; $i < 10 ; $i++)
			{
				try
				{
					$rs = $rpc->rpcBatchExec();
					break;
				}
				catch(Exception $e)
				{
					if($i > 8)
						throw $e;

					std::err("Exception ".$e->getMessage()."\n");
					sleep(3);
				}
			}
			std::out(date('H:i:s').'\\'.posix_getpid().": Flushing rpc client for $url with ".count($rpc->call)." calls. Done in ".number_format(microtime(1)-$ts0, 3)." seconds.\n");
			foreach($rs as $r)
			{
				// notify of individual exceptions, no retry atm
				if($r[0] === Rpc_1::T_THROW)
				{
					std::err("Exception: %s\n", $r[1]->getMessage());
				}
			}
			$rpc->rpcBatchBegin();
		}
	}

	function getClient($url)
	{
		if(!isset($this->rpc[$url]))
		{
			$this->rpc[$url] = new Rpc_Client_1($url);
			$this->rpc[$url]->rpcBatchBegin();
		}
		return $this->rpc[$url];
	}

	function move_file($fs)
	{
		std::out("Moving ".number_format(count($fs))." files... ");
		$t0 = microtime(1);
		foreach($fs as $f)
		{
			$t = str_replace('/statpro/', '/statpro-done/', dirname($f));
			if(! is_dir($t))
				mkdir($t, 0770, TRUE);
			$t .= '/journal_'.sha1(microtime(1).mt_rand().uniqid(mt_rand(),1)).'.db';
			rename($f, $t);
			@unlink($f.'-lock');
		}
		std::out("Done. ".number_format(microtime(1)-$t0, 2)." sec\n");
	}

	protected function log($line)
	{
		echo date("H:i:s") . "\\".posix_getpid().": $line\n";
	}
}

class ep2message extends base2message
{
	public function execute()
	{
		foreach ($this->path as $p)
		{
			touch($p.'-lock');
			$this->log(__CLASS__." proc $p");

			if (! preg_match('/\/(spc_[^\/]+)\//', $p, $m))
			{
				throw new Exception("cust preg failed!");
			}
			$part = explode('_', $m[1]);
			switch(count($part))
			{
				case 2:
					$custdir = 'pw';
					$mode = $part[1];
					break;
				case 3:
					$custdir = $part[1];
					$mode = $part[2];
					break;
				default:
					throw new Exception('bad number of cust parts');
					break;
			}
			$part = null;


			$db = new DB_Database_1('sqlite2:'.$p);
			$db->exec("begin");

			$sql = "select oid, created_date, 2, '{$custdir}', '', space_key, space_def, '', '', '', '', '', '', '', status_flag from space where status_flag != 'DONE' limit 50000";
			$qry = $db->query($sql);

			$oid = $rs = array();
			while($r = $qry->fetch(PDO::FETCH_NUM))
			{
				$rs[2][] = $r;
				$oid[] = $r[0];
			}

			$this->send_msg($custdir, $mode, $rs);

			$this->flushClient();

			$db->exec("update space set status_flag = 'DONE' where oid in (".implode(',', $oid).")");

			$db->exec("commit");

			unlink($p.'-lock');
		}
		$this->rpc = array();
		$this->result = TRUE;

	}
}

class sp2message extends base2message
{
	public function execute()
	{
		foreach ($this->path as $p)
		{
			touch($p.'-lock');
			$this->log(__CLASS__." proc $p");

			if (! preg_match('/\/(spc_[^\/]+)\//', $p, $m))
			{
				throw new Exception("cust preg failed!");
			}
			$part = explode('_', $m[1]);
			switch(count($part))
			{
				case 2:
					$custdir = 'pw';
					$mode = $part[1];
					break;
				case 3:
					$custdir = $part[1];
					$mode = $part[2];
					break;
				default:
					throw new Exception('bad number of cust parts');
					break;
			}
			$part = null;

			$db = new DB_Database_1('sqlite2:'.$p);
			$db->exec('begin');

			$sql = "select oid, * from data where status_flag != 'DONE' limit 50000";
			$qry = $db->query($sql);

			$oid = $rs = array();
			while($r = $qry->fetch(PDO::FETCH_NUM))
			{
				if(!$r[3])
					continue;

				if($r[3] == 'partnerweekly')
				{
					$r[3] = 'pw';
				}
				if($r[2] == 1)
				{
					$r[7] = strtolower($r[7]);
					$rs[$r[3]][$r[7]][] = $r;
				}
				else
				{
					$rs[$r[3]][$r[2]][] = $r;
				}
				$oid[] = $r[0];
			}
			$qry->closeCursor();

			foreach($rs as $cust => $sub)
			{
				$this->send_msg($cust, $mode, $sub);
			}

			$this->flushClient();

			$db->exec("update data set status_flag = 'DONE' where oid in (".implode(',', $oid).")");
			$db->exec('commit');

			unlink($p.'-lock');
		}
		$this->rpc = array();
		$this->setResult(TRUE);
	}
}

class Statpro1Space extends Unix_WorkItem_1
{
	public $path;

	public function __construct($path)
	{
		$this->path = is_array($path) ? $path : array($path);
	}

	public function execute()
	{
		foreach ($this->path as $p)
		{
			if (! preg_match('/\/(spc_[^\/]+)\//', $p, $m))
				throw new Exception('Invalid path '.$p);

			$this->log(__CLASS__." proc $p");

			$a = explode('_', $m[1]);
			$cust = count($a) == 2 ? 'pw' : $a[1];
			$mode = $a[2];

			$db = new DB_Database_1('sqlite2:'.$p);

			while ($db->querySingleValue("select count(*) from space where status_flag != 'DONE'") > 0)
			{
				$key = Util_Guid_1::newId();
				$db->exec("update space set status_flag = '{$key}' where oid in (select oid from space where status_flag = 'LOCAL' limit 2000)");
				$qry = $db->query("select created_date, 2, '{$cust}', '', space_key, space_def, '', '', '', '', '', '', status_flag from space where status_flag = '{$key}'");
				$data = $qry->fetchAll(PDO::FETCH_NUM);

				if (!(is_array($data) && count($data)))
				{
					$qry = $db->query("select created_date, 2, '{$cust}', '', space_key, space_def, '', '', '', '', '', '', status_flag from space where status_flag not in ('DONE')");
					$data = $qry->fetchAll(PDO::FETCH_NUM);

					if ((is_array($data) && count($data)))
					{
						$key = $data[0][13];
					}
					else
					{
						continue 2;
					}
				}

				$url = "http://{$mode}.2.statpro.epointps.net/event_batch_prpc.php";
				$this->log("Processing batch {$key} with ".count($data)." records from $p");

				try
				{
					$rpc = new SourcePro_Prpc_Client($url);
					//$rpc->Set_Timeout(count($data) < 500 ? 45 : 3000);
					$res = $rpc->sendPackage('spc_'.$cust.'_'.$mode, $data);
				}
				catch (Exception $e)
				{
					$this->log('Prpc Exception '.$e->__toString());
					continue 2;
				}

				$db->exec("update space set status_flag = 'DONE' where status_flag = '{$key}'");
			}
		}
	}

	/**
	 * Logs a message
	 *
	 * @param string $line
	 */
	protected function log($line)
	{
		echo date("H:i:s") . "\\".posix_getpid().": $line\n";
	}

}

class Statpro1Event extends Unix_WorkItem_1
{
	const MAX_PASS = 242;
	const MAX_BATCH = 2000;

	public $path;

	public function __construct($path)
	{
		$this->path = is_array($path) ? $path : array($path);
	}

	public function execute()
	{
		foreach ($this->path as $p)
		{
			if (! preg_match('/\/(spc_[^\/]+)\//', $p, $m))
				throw new Exception('Invalid path '.$p);

			$this->log(__CLASS__." proc $p");

			$a = explode('_', $m[1]);
			$cust = count($a) == 2 ? 'pw' : $a[1];
			$mode = $a[2];

			$db = new DB_Database_1('sqlite2:'.$p);

			for ($x = 0 ; $db->querySingleValue("select count(*) from data where status_flag != 'DONE'") && $x < self::MAX_PASS ; $x++)
			{
				$key = Util_Guid_1::newId();
				$db->exec("update data set status_flag = '{$key}' where oid in (select oid from data where status_flag = 'LOCAL' limit ".self::MAX_BATCH.")");
				$qry = $db->query("select * from data where status_flag = '{$key}'");
				$data = $qry->fetchAll(PDO::FETCH_NUM);

				if (!(is_array($data) && count($data)))
				{
					$qry = $db->query("select * from data where status_flag not in ('DONE')");
					$data = $qry->fetchAll(PDO::FETCH_NUM);

					if ((is_array($data) && count($data)))
					{
						$key = $data[0][13];
					}
					else
					{
						continue;
					}
				}

				$url = "http://{$mode}.2.statpro.epointps.net/event_batch_prpc.php";
				$this->log("Processing batch {$key} with ".count($data)." records from $p");

				try
				{
					$rpc = new SourcePro_Prpc_Client($url);
					//$rpc->Set_Timeout(count($data) < 500 ? 45 : 3000);
					$res = $rpc->sendPackage('spc_'.$cust.'_'.$mode, $data);
				}
				catch (Exception $e)
				{
					$this->log('Prpc Exception '.$e->__toString());
					continue;
				}

				$db->exec("update data set status_flag = 'DONE' where status_flag = '{$key}'");
			}
		}
	}

	/**
	 * Logs a message
	 *
	 * @param string $line
	 */
	protected function log($line)
	{
		echo date("H:i:s") . "\\".posix_getpid().": $line\n";
	}

}


class ForkingScrubber extends Unix_WorkQueueMasterProcess_1
{
	protected $shutdown = FALSE;

	public function addWork()
	{
		foreach (glob('/opt/{stat,enterprise}pro/var/spc_*/journal/', GLOB_BRACE) as $i => $d)
		{
			$this->work_queue->addWork(new Statpro1Scrub($d));
		}
	}

	public function onStartup()
	{
		parent::onStartup();

		/*
		$child = new EventManager($this->ipc_key, 5000000);
		$pid = $child->fork(FALSE);
		$this->children[$pid] = $pid;
		//*/

		$this->addWork();
	}

	public function tick()
	{
		parent::tick();

		if (!$this->work_queue->HasIncompleteItems)
		{
			if ($this->shutdown)
			{
				$this->continue_execution = FALSE;
			}
			else
			{
				$this->quit();
				//sleep(10);
				//$this->addWork();
			}
		}
	}

	public function quit()
	{
		$this->shutdown = TRUE;
	}

	public function workerFactory(Unix_IPCKey_1 $key)
	{
		return new MyWorker($key);
	}

	public function onWorkFinish(Unix_WorkItem_1 $item)
	{
		$this->log(__METHOD__.' '.implode(', ', $item->path));
	}
}


class MyWorker extends Unix_WorkQueueClientProcess_1
{
}

class EventManager extends Unix_ForkedWorkerProcess_1
{
	public function tick()
	{
		$this->log(__METHOD__);
		//sleep(5);
	}
}

$opt = getopt('d');

$ipckey = new Unix_IPCKey_1("/tmp/mq_location");

$parent = new ForkingScrubber($ipckey);
$event = $parent->getWorkQueue()->OnWorkFinished;
$event->addDelegate(Delegate_1::fromMethod($parent, 'onWorkFinish'));

if (isset($opt['d']))
{
	$parent->fork();
}
else
{
	$parent->main();
}
exit(0);

?>
