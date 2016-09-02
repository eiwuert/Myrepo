<?php

class LastValue
{
	protected $db;
	protected $qu;
	protected $qs;

	public function __construct()
	{
	}

	public function update($name, $value)
	{
		$this->init();

		if ($this->qu === NULL)
		{
			$sql = <<<SQL
update last_value set ts = ?, value = ? where name = ?
SQL;
			$this->qu = $this->db->prepare($sql);
		}

		$this->qu->execute(array(time(), $value, $name));
	}

	public function get()
	{
		$this->init();

		if ($this->qs === NULL)
		{
			$sql = <<<SQL
select 
	name,
	case 
		when name in ('msg-cron-done', 'sp2msg-done') then strftime('%s','now')-value
		when ts < strftime('%s','now','-5 minutes') then 0
		else value 
	end as value
from 
	last_value
order by
	pos

SQL;
			$this->qs = $this->db->prepare($sql);
		}

		$this->qs->execute();

		return $this->qs->fetchAll();
	}

	protected function init()
	{
		if ($this->db === NULL)
		{
			$db = new DB_Database_1('sqlite:/dev/shm/last.db', NULL, NULL, array(PDO::ATTR_TIMEOUT => 600));
			$db->exec('begin exclusive');
			if (! $db->querySingleValue('select count(*) from sqlite_master'))
			{
				$sql = '';
				$pos = array('msg-cron-done', 'msg-delivery-DL0', 'msg-delivery-DL1', 'msg-delivery-DL2', 'msg-delivery-DL3', 'msg-delivery-DL4', 'msg-delivery-ERR', 'msg-delivery-FIN', 'msg-delivery-NEW', 'msg-populate-http', 'msg-populate-subscribe', 'msg-process-batch', 'msg-process-journal', 'msg-process-message', 'msg-queue-DL0', 'msg-queue-DL1', 'msg-queue-DL2', 'msg-queue-DL3', 'msg-queue-DL4', 'msg-queue-ERR', 'msg-queue-FIN', 'msg-queue-NEW', 'msg-remove-delete', 'msg-remove-empty', 'sp2msg-call', 'sp2msg-done', 'sp2msg-journal', 'sp2msg-row', 'sp2msg-rpc', 'sp2-action-rps', 'sp2-log-rps', 'sp2-aggspace-rps', 'sp2-aggcontext-rps');
				foreach ($pos as $k => $v)
				{
					$sql .= "insert into last_value (pos, name) values ({$k}, '{$v}');\n";
				}
				$sql = <<<SQL
create table last_value (pos int primary key, name char unique not null, value, ts int);
{$sql}
SQL;
				$db->exec($sql);
			}
			$db->exec('commit');

			$this->db = $db;				
		}
	}
}

?>
