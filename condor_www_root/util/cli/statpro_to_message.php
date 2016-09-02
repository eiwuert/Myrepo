#!/usr/bin/php
<?php

if (posix_getuid() !== 0) die($argv[0]." must be run as root.");

require_once 'AutoLoad.1.php';

set_include_path(realpath(dirname(__FILE__).'/../lib/').':'.get_include_path());

define('SYS_FQDN', Message_1::getFqdn());


/*
** PID
*/
$pid_file = "/tmp/statpro_to_message.pid";

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

$opt = getopt('rd:m:s:');

define('NAP_TIME', isset($opt['d']) ? $opt['d'] : 0);
define('MAX_MEM', isset($opt['m']) ? $opt['m'] : 142);
define('MAX_SIZE', isset($opt['s']) ? $opt['s'] : 10000);


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



class sp2message
{
	public $rpc = array();
	public $stat = array('rpc' => 0, 'row' => 0, 'call' => 0, 'journal' => 0);
	public $opt;

	function main($opt)
	{
		$this->opt = $opt;
		$glob = glob('/opt/statpro/var/*/journal/');
		foreach($glob as $dir)
		{
			echo $dir,"\n";

			if (! preg_match('/\/(spc_[^\/]+)\//', $dir, $m))
			{
				throw new Exception("cust preg failed!");
			}
			$p = explode('_', $m[1]);
			switch(count($p))
			{
				case 2:
					$custdir = 'pw';
					$mode = $p[1];
					break;
				case 3:
					$custdir = $p[1];
					$mode = $p[2];
					break;
				default:
					throw new Exception('bad number of cust parts');
					break;
			}
			$p = null;


			$fs = $rs = $ls = array();

			if ($handle = opendir($dir))
			{
				$sum = 0;
				while (false !== ($file = readdir($handle)))
				{
					if (preg_match('/\.db$/',$file))
					{
						$file = $dir.$file;

						$jlf = $file.'-lock';

						$lh = @fopen($jlf, 'x');
						if (! $lh) continue;

						$wb = NULL;
						if (!flock($lh, LOCK_EX|LOCK_NB, $wb))
						{
							fclose($lh);
							$lh = NULL;
							continue;
						}


						$fs[] = $file;
						$ls[$file] = $lh;

						$db = new DB_Database_1('sqlite2:'.$file);

						$max = 0;
						$a = $db->querySingleValue("select count(*) from data");
						$loopmax = ceil($a / MAX_SIZE);

						for($i = 0 ; $i < $loopmax ; $i++)
						{
							$sql = "select oid, * from data where oid > {$max} order by oid asc limit ".MAX_SIZE;
							$qry = $db->query($sql);

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
								if($r[0] > $max) $max = $r[0];
								$sum++;
							}
							$qry->closeCursor();

							if ($sum >= MAX_SIZE || $loopmax > 1)
							{
								foreach($rs as $cust => $sub)
								{
									$this->send_msg($cust, $mode, $sub);
								}
								$rs = array();
								$sum = 0;
							}

							if(memory_get_usage(1) >= (MAX_MEM * 1000000) || NAP_TIME)
							{
								$this->flushClient();
							}
						}

						$this->stat['journal']++;
		        	}
		    	}

				closedir($handle);

				if($sum > 0)
				{
					foreach($rs as $cust => $sub)
					{
						$this->send_msg($cust, $mode, $sub);
					}
					$rs = array();
					$sum = 0;
				}
				$this->flushClient();

				$this->move_file($fs, $ls);
				unset($fs, $ls);
			}
		}

		if (isset($this->opt['r']))
		{
			$last = new LastValue();

			$last->update('sp2msg-done', time());
			foreach ($this->stat as $k => $v) $last->update('sp2msg-'.$k, $v);
		}
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
		if(is_numeric($action) && $action < 8)
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

		//std::out(date('Y-m-d H:i:s')." Sending msg to $dst with ".count($chunk)." rows.. \n");

		$sp2->consumeMessage($msg);

		$this->stat['call']++;
		$this->stat['row'] += count($chunk);
	}

	function flushClient()
	{
		foreach($this->rpc as $url => $rpc)
		{
			if(!count($rpc->call))
				continue;

			std::out(date('Y-m-d H:i:s')." Flushing rpc client for $url with ".count($rpc->call)." calls. ");
			$ts0 = microtime(1);
			for($i = 0 ; $i < 10 ; $i++)
			{
				try
				{
					$rs = $rpc->rpcBatchExec();
					$this->stat['rpc']++;
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
			std::out("Done in ".number_format(microtime(1)-$ts0, 3)." seconds.\n");
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
		if(NAP_TIME) sleep(NAP_TIME);
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

	function move_file($fs, $ls)
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
			fclose($ls[$f]);
			@unlink($f.'-lock');
		}
		std::out("Done. ".number_format(microtime(1)-$t0, 2)." sec\n");
	}
}

$a = new sp2message();
$a->main($opt);


class ep2message
{
	public $rpc = array();
	
	public function main()
	{
		$glob = glob('/opt/enterprisepro/var/*/journal/');
		foreach($glob as $dir)
		{
			echo $dir,"\n";

			if (! preg_match('/\/(spc_[^\/]+)\//', $dir, $m))
			{
				throw new Exception("cust preg failed!");
			}
			$p = explode('_', $m[1]);
			switch(count($p))
			{
				case 2:
					$custdir = 'pw';
					$mode = $p[1];
					break;
				case 3:
					$custdir = $p[1];
					$mode = $p[2];
					break;
				default:
					throw new Exception('bad number of cust parts');
					break;
			}
			$p = null;

			$repo = "/opt/enterprisepro/var/spc_{$custdir}_{$mode}/repository/space.db";

			if (! is_dir(dirname($repo)))
			{
				mkdir(dirname($repo), 0777, 1);
			}
			
			$db = new DB_Database_1('sqlite2:'.$repo);
			if (! file_exists($repo))
			{
				$db->exec("begin");
				if (! $db->querySingleValue("select count(*) from sqlite_master where type = 'table' and name = 'space'"))
					$db->exec("CREATE TABLE space (created_date, space_key, space_def, status_flag); CREATE UNIQUE INDEX ix_space_key ON space (space_key);");
				$db->exec("commit");
			}

			
			$journals = glob($dir.'*.db');
			foreach ($journals as $file) touch($file.'-lock');
			usleep(100000);
			
			foreach ($journals as $file)
			{				
				$sql = <<<SQL
attach "{$file}" as j;
begin;
insert or ignore into main.space select action_date, ap03, ap04, status_flag from j.data where action = 2;
delete from j.data where action = 2;
commit;
detach j;

SQL;
				$db->exec($sql);
	    	}
			foreach ($journals as $file) unlink($file.'-lock');
			
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
		}
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

		//std::out(date('Y-m-d H:i:s')." Sending msg to $dst with ".count($chunk)." rows.. \n");

		$sp2->consumeMessage($msg);
	}
	

	function flushClient()
	{
		foreach($this->rpc as $url => $rpc)
		{
			if(!count($rpc->call))
				continue;

			std::out(date('Y-m-d H:i:s')." Flushing rpc client for $url with ".count($rpc->call)." calls. ");
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
			std::out("Done in ".number_format(microtime(1)-$ts0, 3)." seconds.\n");
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
	
}

$b = new ep2message();
$b->main();

unlink($pid_file);

?>
