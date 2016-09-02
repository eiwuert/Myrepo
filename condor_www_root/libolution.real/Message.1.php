<?php
/**
 * @package Message
 */

/**
 * Common static funcs and constants
 *
 * @author Rodric Glaser <rodric.glaser@sellingsource.com>
 */
class Message_1
{
	const TMP = '/var/tmp/msg/';

	public static $stat = array();

	/**
	 * Common error function
	 *
	 * @param string $message
	 * @return void
	 */
	public static function err($message)
	{
		fprintf(STDERR, "%s [%d] %s\n", date('Y-m-d H:i:s'), posix_getpid(), $message);
	}

	/**
	 * Common logging function
	 *
	 * @param string $message
	 * @param int $level
	 * @return void
	 */
	public static function log($message, $level = 0)
	{
		echo date('Y-m-d H:i:s ['), posix_getpid(), '] ', $message, "\n";
	}

	/**
	 * Calculate the retry delay
	 *
	 * @param int $try
	 * @return int
	 */
	public static function calculateDelay($try)
	{
		// use a sigmoid logistic function to scale the delay
		// http://en.wikipedia.org/wiki/Sigmoid_function
		return (int)((1.0 / (1.0 + exp(-(($try - 50) * 0.15)))) * 86400);
	}

	/**
	 * Enqueue a message for delivery
	 *
	 * @param Message_Container_1 $msg
	 * @param string $context
	 * @param int $delay
	 * @return void
	 */
	public static function enqueue(Message_Container_1 $msg, $context = NULL, $delay = 0)
	{
		$vmode = isset($_SERVER['VMODE']) ? $_SERVER['VMODE'] : 'live';
		$max = 11;
		$ctx = $context === NULL ? $_SERVER['SCRIPT_URI'] : $context;
		$key = $msg->getKey();

		if (! is_dir(self::TMP))
		{
			mkdir(self::TMP);
			chmod(self::TMP, 0777);
		}

		$pid = posix_getpid();
		$slice = mt_rand(0, 4);
		do
		{
			$file = self::TMP.$vmode.'_msg_pid'.$pid.'_slice'.$slice.'.db';
			$slice = ($slice + 1) % $max;
			if (! $slice) clearstatcache();
		}
		while (file_exists($file.'-lock'));

		$db = new DB_Database_1('sqlite:'.$file, NULL, NULL, array(PDO::ATTR_TIMEOUT => 1200));

		self::prepareJournal($db);

		$db->exec('begin exclusive');
		try
		{
			$qry = $db->prepare('insert or ignore into message (key, src, dst, msg) values (?, ?, ?, ?)');
			$qry->execute(array($key, $msg->getSrc(), $msg->getDst(), serialize($msg)));

			$qry = $db->prepare('insert into queue (context, message, deliver) values (?, (select oid from message where key = ?), ?)');
			$qry->execute(array((string)$ctx, $key, time()+$delay));

			$db->exec('commit');
		}
		catch(Exception $e)
		{
			$log = new Log_SysLog_1(__METHOD__, LOG_PID);
			$log->write('PDO errorCode: '.$qry->errorCode().'. Performing rollBack.', LOG_ERROR);
			$db->exec('rollback');

			throw $e;
		}

		$db->disconnect();
	}

	/**
	 * Create delivery records
	 *
	 * @param DB_Database_1 $db
	 * @param array $subscription_set
	 * @return void
	 */
	public static function populateDelivery(DB_Database_1 $db, $subscription_set = NULL)
	{
		self::prepareJournal($db);

		$qsq = $db->prepare("select m.dst as dst, m.src as src, q.oid as oid from message m join queue q on m.oid = q.message where q.flag = 'NEW' and q.deliver < strftime('%s', 'now') and q.context = ? limit 50000");
		$qid = $db->prepare('insert into delivery (queue, subscriber, description, deliver) values (?, ?, \'\', ?)');
		$quq = $db->prepare("update queue set flag = 'FIN' where oid = ?");

		$db->exec('begin exclusive');
		try
		{
			// for messages with an explicit http destination skip the subscription set
			// this is a hack, the journal queue schema should be redone to allow native representation of http and rpc requests?
			$q = $db->query("select q.oid as queue, m.dst as dst from message m join queue q on m.oid = q.message where m.dst like 'http%' and q.flag = 'NEW' and q.deliver < strftime('%s', 'now')");
			$rs = $q->fetchAll();
			foreach ($rs as $r)
			{
				self::$stat['populate']['http']++;
				$qid->execute(array($r['queue'], $r['dst']));
				$quq->execute(array($r['queue']));
			}

			if ($subscription_set)
			{
				// apply the subscriptions to the rest of the messages
				// @todo allow filtering on msg->src and msg->head
				foreach ($subscription_set as $context => $sub_set)
				{
					$qsq->execute(array($context));
					$queue = $qsq->fetchAll(PDO::FETCH_OBJ|PDO::FETCH_GROUP);

					foreach ($queue as $dst => $dst_set)
					{
						foreach ($sub_set as $filter => $subscriber_set)
						{
							if (! strlen($filter) || preg_match('/'.$filter.'/', $dst))
							{
								foreach ($dst_set as $it)
								{
									foreach ($subscriber_set as $o)
									{
										$ts = strpos($o->subscriber, 'agg/context') ? time() + 142 : NULL;
										$qid->execute(array($it->oid, $o->subscriber, $ts));
									}
									self::$stat['populate']['subscribe'] += count($subscriber_set);
								}
							}
						}
						foreach ($dst_set as $it)
						{
							$quq->execute(array($it->oid));
						}
					}
				}
			}

			$db->exec('commit');
		}
		catch(Exception $e)
		{
			self::err(__METHOD__.' Exception: '.$e->getMessage().'. Performing rollback.');
			$db->exec('rollback');
		}

		$db->disconnect();
	}

	/**
	 * Execute message delivery records
	 *
	 * @param DB_Database_1 $db
	 * @return int Number of records processed
	 */
	public static function processDelivery(DB_Database_1 $db)
	{
		self::prepareJournal($db);

		$now = time();
		$sql = <<<SQL
select
case when subscriber like 'http%' then subscriber else q.context || subscriber end as url,
d.oid as oid, m.key as msg_key, d.attempt as attempt
from delivery d
join queue q on d.queue = q.oid
join message m on q.message = m.oid
where d.flag IN( 'NEW', 'ERR' )
and d.attempt < 100
and (d.deliver is null or $now > d.deliver)
order by length(url) asc
limit 25000
SQL;

		$db->exec('begin exclusive');
		$qry = $db->query($sql);
		$rs = $qry->fetchAll(PDO::FETCH_OBJ|PDO::FETCH_GROUP);

		//self::log($db->getDSN().' has '.count($rs).' result rows');

		$qry_fin = $db->prepare("update delivery set flag = 'FIN', description = '' where oid = ?");
		$qry_err = $db->prepare("update delivery set flag = 'ERR', attempt = ?, deliver = ?, description = ? where oid = ?");

		$num = array('batch' => 0, 'message' => 0, 'fail' => 0, 'row' => 0);
		$msg_set = array();
		$msg_len = array();
		foreach ($rs as $url => $tmp)
		{
			// ugly reindex by oid, PDO should be able to do this for me :(
			$sub = array();
			foreach ($tmp as $k => $v)
			{
				$sub[$v->oid] = $v;
			}

			$rpc = new Rpc_Client_1($url);
			if (!isset(self::$stat['url'][$url]))
			{
				self::$stat['url'][$url] = array('fail' => 0);
			}

			//self::log("Calling $url with ".count($sub)." msgs");

			self::$stat['process']['batch']++;
			self::$stat['process']['message'] += count($sub);
			$num['batch']++;
			$num['message'] += count($sub);
			$msg_size = 0;

			foreach ($sub as $it)
			{
				$k = $it->msg_key;
				if (! isset($msg_set[$k]))
				{
					$s = $db->querySingleValue('select msg from message where key = '.$db->quote($k));
					$msg_len[$k] = strlen($s);
					$msg_set[$k] = unserialize($s);
				}
				$msg = $msg_set[$k];
				$msg_size += $msg_len[$k];

				//self::log("Found msg {$msg->key }from {$msg->src} to {$msg->dst} for {$url}");

				$rows = (int) $msg->head['row_count'];
				$num['row'] += $rows;
				self::$stat['url'][$url]['rows'] += $rows;

				$rpc->call->addMethod($it->oid, 'consumeMessage', array($msg));

				if (count($msg_set) > 200)
				{
					$msg_set = array();
				}
			}

			try
			{
				$ts0 = microtime(1);
				$rpc_rs = $rpc->rpcBatchExec();
				$ts1 = microtime(1);
			}
			catch (Exception $e)
			{
				$ts1 = microtime(1);
				$now = time();
				$err = $e->getMessage();
				self::err("Batch INIT Exception in ".$db->getDSN()." for {$url}\nError: ".$err);
				foreach ($sub as $it)
				{
					$try = $it->attempt + 1;
					$deliver = $now + self::calculateDelay($try);
					$qry_err->execute(array($try, $deliver, $err, $it->oid));
				}
				$num['fail'] += count($sub);
				self::$stat['url'][$url]['fail'] += count($sub);
				continue;
			}
			$dt = $ts1 - $ts0;

			self::$stat['url'][$url]['iter']++;
			self::$stat['url'][$url]['msgs'] += count($sub);
			self::$stat['url'][$url]['size'] += $msg_size;
			self::$stat['url'][$url]['time'] += $dt;


			$now = time();
			foreach ($rpc_rs as $oid => $r)
			{
				if ($r[0] === Rpc_1::T_RETURN)
				{
					$qry_fin->execute(array($oid));
				}
				else
				{
					$try = $sub[$oid]->attempt + 1;
					$deliver = $now + self::calculateDelay($try);
					$err = $r[1]->getMessage();
					$qry_err->execute(array($try, $deliver, $err, $oid));
					//$m = $rpc->call->getMethod($oid);
					//self::err("Call (".print_r($m[1][0]->body, 1).") ERR (".$err.")");
					self::err("Exception on ".$db->getDSN()." delivery {$oid} for {$url}\nError: ".$err);
					$num['fail']++;
					self::$stat['url'][$url]['fail']++;
				}
			}
		}
		$db->exec('commit');
		$db->disconnect();

		return $num;
	}

	/**
	 * Remove finished message delivery records
	 *
	 * @param DB_Database_1 $db
	 * @return void
	 */
	public static function removeFinished(DB_Database_1 $db)
	{
		self::prepareJournal($db);

		$c = $db->querySingleValue('select count(*) from message');
		if ($c)
		{
			$db->exec('begin exclusive');
			try
			{
				$sql = <<<SQL
delete from r_message
where oid in (
	select distinct m.oid
	from r_message m
	join r_queue q on q.message = m.oid
	where q.flag IN ('DONE', 'FIN')
	and not exists (
		select oid from r_delivery d
		where d.queue = q.oid
		and d.flag != 'FIN'
	)
)
SQL;
				$db->exec($sql);
				$db->exec('commit');
			}
			catch (Exception $e)
			{
				self::err(__METHOD__.' PDO errorCode: '.$e->getCode().'. Performing rollBack.');
				$db->exec('rollback');
			}

			$now = time();
			$dl0 = strtotime('+30 min', $now);
			$dl1 = strtotime('+90 min', $now);
			$dl2 = strtotime('+242 min', $now);
			$dl3 = strtotime('+1 day', $now);

			foreach (array('queue', 'delivery') as $t)
			{
				$sql = <<<SQL
select 
	count(*) as num, 
	case 
		when flag = 'NEW' and deliver > {$now} then  
		case
			when deliver < {$dl0} then 'DL0'
			when deliver < {$dl1} then 'DL1'
			when deliver < {$dl2} then 'DL2'
			when deliver < {$dl3} then 'DL3'
			else 'DL4'
		end
		else flag 
	end as state 
from {$t} 
where flag != 'FIN' 
group by state;

SQL;
				$q = $db->query($sql);
				foreach ($q as $r)
				{
					self::$stat[$t][$r['state']] += $r['num'];
				}
			}
		}

		$db->disconnect();

		if (!$c)
		{
			$file = str_replace('sqlite:', '', $db->getDSN());
			$dt = time() - filemtime($file);
			if ($dt >= 3600)
			{
				self::$stat['remove']['delete']++;
				self::log(__METHOD__.' deleting '.$file);
				unlink($file);
			}
			else
			{
				self::$stat['remove']['empty']++;
			}
		}
	}

	/**
	 * Check for updates and get the subscription set
	 *
	 * @return array
	 */
	public static function getSubscriptionSet()
	{
		$vmode = isset($_SERVER['VMODE']) ? $_SERVER['VMODE'] : 'live';
		$pre = '/dev/shm/'.$vmode.'_msg_sub.';
		$fp = fopen($pre.'lock', 'w');
		if (flock($fp, LOCK_EX))
		{
			$v = file_exists($pre.'vn') ? file_get_contents($pre.'vn') : 0;
			$url = 'http://'.$vmode.'.sp2.epointps.net/repo/';
			$s = new Rpc_Client_1($url);
			$r = $s->getUpdate('msg_sub', $v);

			if ($r)
			{
				self::log("Updating msg_sub repository");
				file_put_contents($pre.'db', $r['repo']);
				file_put_contents($pre.'vn', $r['version']);
			}

			flock($fp, LOCK_UN);
			fclose($fp);
		}
		else
		{
			throw new Exception('flock failed');
		}

		$msg_sub = new DB_Database_1('sqlite:'.$pre.'db');

		$subscription_set = array();
		$qs = $msg_sub->query("select * from subscription");
		$rs = $qs->fetchAll(PDO::FETCH_OBJ);
		foreach ($rs as $r)
		{
			$subscription_set[$r->context][$r->filter][] = $r;
		}

		$msg_sub->disconnect();

		return $subscription_set;
	}

	/**
	 * Prepare a message journal
	 *
	 * @param DB_Database_1 $db
	 * @return void
	 */
	public static function prepareJournal(DB_Database_1 $db)
	{
		$sql = <<<SQL
pragma page_size=4096;
pragma legacy_file_format=off;
pragma default_cache_size = 42420;

begin exclusive;
SQL;
		$db->exec($sql);

		$vmode = isset($_SERVER['VMODE']) ? $_SERVER['VMODE'] : 'live';
		$vs = (int)$db->querySingleValue('PRAGMA user_version');

		$sql = NULL;
		if ($vs === 11)
		{
			$sql = <<<SQL

alter table r_queue rename to old_queue;
create table if not exists r_queue (
	id integer primary key,
	ts default CURRENT_TIMESTAMP,
	flag default 'NEW',
	context integer references t_context(id),
	message integer references r_message(id),
	deliver integer default 0
);
insert into r_queue select id, ts, flag, context, message, strftime('%s', ts, delay || ' seconds') from old_queue;
drop table old_queue;

drop view if exists queue;
create view queue as
select a.oid as oid, a.ts as ts, a.flag as flag, b.key as context, a.message as message, a.deliver as deliver
from r_queue a join t_context b on a.context = b.oid;

drop trigger if exists vi_queue;
create trigger vi_queue instead of insert on queue
for each row begin
	insert or ignore into t_context (key) values (new.context);
	insert into r_queue (context, message, deliver) values (
		(select oid from t_context where key = new.context),
		new.message, new.deliver);
end;
drop trigger if exists vu_queue;
create trigger vu_queue instead of update on queue
for each row begin
	insert or ignore into t_context (key) values (new.context);
	update r_queue set ts = new.ts, flag = new.flag, context = (select oid from t_context where key = new.context), message = new.message, deliver = new.deliver
	where oid = new.oid;
end;
drop trigger if exists rdc_queue;
create trigger rdc_queue before delete on r_queue
for each row begin
	delete from r_delivery where queue = old.oid;
end;

pragma user_version = 12;

commit;

SQL;
		}
		elseif ($vs < 10)
		{
			$sql = <<<SQL

create table if not exists t_src (id integer primary key, key not null default '' unique);
create table if not exists t_dst (id integer primary key, key not null default '' unique);

create table if not exists r_message (
	id integer primary key,
	key blob unique,
	src integer references t_src(id),
	dst integer references t_dst(id),
	msg blob
);
create trigger rdc_message before delete on r_message
for each row begin
	delete from r_queue where message = old.oid;
end;

create view message as
select a.oid as oid, a.key as key, b.key as src, c.key as dst, msg
from
	r_message a
	join t_src b on a.src = b.oid
	join t_dst c on a.dst = c.oid;

create trigger vi_message instead of insert on message
for each row begin
	insert or ignore into t_src (key) values (new.src);
	insert or ignore into t_dst (key) values (new.dst);
	insert into r_message (key, src, dst, msg) values (new.key,
		(select oid from t_src where key = new.src),
		(select oid from t_dst where key = new.dst),
		new.msg);
end;
create trigger vu_message instead of update on message
for each row begin
	insert or ignore into t_src (key) values (new.src);
	insert or ignore into t_dst (key) values (new.dst);
	update r_message set key = new.key,
		src = (select oid from t_src where key = new.src),
		dst = (select oid from t_dst where key = new.dst),
		msg = new.msg
	where oid = new.oid;
end;
create trigger vd_message instead of delete on message
for each row begin
	delete from r_message where oid = old.oid;
end;


create table if not exists t_context (id integer primary key, key not null default '' unique);
insert into t_context (key) values ('');

create table if not exists r_queue (
	id integer primary key,
	ts default CURRENT_TIMESTAMP,
	flag default 'NEW',
	context integer references t_context(id),
	message integer references r_message(id),
	deliver integer default 0
);
create trigger rdc_queue before delete on r_queue
for each row begin
	delete from r_delivery where queue = old.oid;
end;

create view queue as
select a.oid as oid, a.ts as ts, a.flag as flag, b.key as context, a.message as message, a.deliver as deliver
from r_queue a join t_context b on a.context = b.oid;

create trigger vi_queue instead of insert on queue
for each row begin
	insert or ignore into t_context (key) values (new.context);
	insert into r_queue (context, message, deliver) values (
		(select oid from t_context where key = new.context),
		new.message, new.deliver);
end;
create trigger vu_queue instead of update on queue
for each row begin
	insert or ignore into t_context (key) values (new.context);
	update r_queue set ts = new.ts, flag = new.flag, context = (select oid from t_context where key = new.context), message = new.message, deliver = new.deliver
	where oid = new.oid;
end;
create trigger vd_queue instead of delete on queue
for each row begin
	delete from r_queue where oid = old.oid;
end;


create table t_subscriber (id integer primary key, key not null default '' unique);
create table t_description (id integer primary key, key not null default '' unique);
insert into t_description (key) values ('');

create table r_delivery (
	id integer primary key,
	ts default CURRENT_TIMESTAMP,
	flag default 'NEW',
	queue integer references r_queue(id),
	subscriber integer references t_subscriber(id),
	description integer references t_description(id),
	attempt integer default 0,
	deliver integer default 0
);

create view delivery as
select a.oid as oid, a.ts as ts, a.flag as flag, a.queue as queue, b.key as subscriber, c.key as description, a.attempt as attempt, a.deliver as deliver
from r_delivery a
join t_subscriber b on a.subscriber = b.oid
join t_description c on a.description = c.oid;

create trigger vi_delivery instead of insert on delivery
for each row begin
	insert or ignore into t_subscriber (key) values (new.subscriber);
	insert or ignore into t_description (key) values (new.description);
	insert into r_delivery (queue, subscriber, description, deliver) values (new.queue,
	(select oid from t_subscriber where key = new.subscriber),
	(select oid from t_description where key = new.description), new.deliver);
end;
create trigger vu_delivery instead of update on delivery
for each row begin
	insert or ignore into t_subscriber (key) values (new.subscriber);
	insert or ignore into t_description (key) values (new.description);
	update r_delivery set ts = CURRENT_TIMESTAMP, flag = new.flag, queue = new.queue, attempt = new.attempt,
	subscriber = (select oid from t_subscriber where key = new.subscriber),
	description = (select oid from t_description where key = new.description),
	deliver = new.deliver
	where oid = new.oid;
end;
create trigger vd_delivery instead of delete on delivery
for each row begin
	delete from r_delivery where oid = old.oid;
end;

PRAGMA user_version = 12;

commit;

SQL;
		}
		else
		{
			$sql = <<<SQL
commit;
SQL;
		}

		if ($sql)
		{
			try
			{
				$db->exec($sql);
			}
			catch (Exception $e)
			{
				file_put_contents('/tmp/'.$vmode.'_prepareJournal.log', 'PDO errorCode: '.$db->errorCode().'. Performing rollBack.', FILE_APPEND);
				$db->exec('rollback');

				throw $e;
			}
			chmod(str_replace('sqlite:', '', $db->getDSN()), 0660);
		}
	}

	/**
	 * Get the fully qualified domain name for this machine.
	 *
	 * To avoid the overhead of shelling out export SYS_FQDN
	 * in /etc/conf.d/apache2 and /etc/profile.d/sys_fqdn.sh.
	 * Then add SYS_FQDN to /etc/conf.d/env_whitelist.
	 *
	 * @return string
	 */
	public static function getFqdn()
	{
		if (! isset($_ENV['SYS_FQDN'])) $_ENV['SYS_FQDN'] = trim(`hostname -f`);
		return $_ENV['SYS_FQDN'];
	}
}

?>
