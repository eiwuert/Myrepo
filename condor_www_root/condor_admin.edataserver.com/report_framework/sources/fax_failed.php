<?php

	global $company_id;
	
	if (!isset($now)) $now = time();
	
	$where = array(
		"ds.type = 'FAIL'",
		"dd.transport = 'FAX'",
		"a.company_id = $company_id"
	);
	
	$table = "
		document d
		JOIN document_dispatch dd ON d.document_id = dd.document_id
		JOIN condor_admin.agent a ON d.user_id = a.agent_id
		LEFT JOIN dispatch_history dh USING (dispatch_history_id)
		LEFT JOIN dispatch_status ds
			ON dh.dispatch_status_id = ds.dispatch_status_id";
	
	$source = new MySQL_Interval_Source($now);
	$source->Table($table);
	$source->Field_Date('dh.date_created');
	$source->Field_Value('COUNT(*)');
	$source->Where($where);
	
	require_once('automode.1.php');
	
	$mode = new Auto_Mode();
	$mode = $mode->Fetch_Mode($_SERVER['HTTP_HOST']);
	
	switch($mode)
	{
		case 'LOCAL':
			$source->Host('monster.tss');
			$source->Username('condor');
			$source->Password('password');
			$source->Database('condor');
			$source->Port(3311);
			break;
		case 'RC':
			$source->Host('db101.ept.tss');
			$source->Username('condor');
			$source->Password('password');
			$source->Database('condor');
			$source->Port(3313);
			break;
		case 'LIVE':
			$source->Host('writer.condor2.ept.tss');
			$source->Username('condor');
			$source->Password('password');
			$source->Database('condor');
			$source->Port(3308);
			break;
	}
	
	// set up an intermediate cache for this source
//	$cache = new Interval_Source_Cache();
//	$cache->Cache_Dir('/tmp/');
//	$cache->Cache_Name(strtoupper(basename(__FILE__, '.php')));
//	$cache->TTL('5 days');
//	$cache->Overlap('10 minutes');
//	$cache->Source($source);
	
	return $source;
	
?>
