<?php

require 'AutoLoad.1.php';

$db = new DB_Database_1('sqlite:/dev/shm/last.db', NULL, NULL, array(PDO::ATTR_TIMEOUT => 600));

$sql = <<<SQL
select 
	case 
		when name IN ('msg-cron-done', 'sp2msg-done') then name || ':' || (strftime('%s','now')-value)
		when ts > strftime('%s','now','-75 seconds') then name || ':' || value 
		else name || ':' || 'nan'
	end as nv 
from 
	last_value

SQL;

$rs = $db->querySingleColumn($sql);
echo implode(' ', $rs);

?>
