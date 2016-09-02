<?

	switch($runmode)
	{
	case 'test':
		$prpc_server = "prpc://rc.bfw.1.edataserver.com/smscom.php";
		$ldb_server = array(
			'host' => 'db101.clkonline.com:3308',
			'user' => 'ldb_writer',
			'pass' => '1canwr1t3',
			'db' => 'ldb'
		);
		$olp_server = array(
			'host' => 'db101.clkonline.com',
			'user' => 'sellingsource',
			'pass' => 'password',
			'db' => 'react_db',
		);
		$ole_server = array(
			'host' => 'db101.clkonline.com:3322',
			'user' => 'olp',
			'pass' => 'test0LE1',
			'db' => 'oledirect2',
		);
		define('SEND_LIMIT', 0); // 0 = no limit
	break;
	case 'live':
		$prpc_server = "prpc://bfw.1.edataserver.com/smscom.php";
		$ldb_server = array(
			'host' => 'writer.ecashclk.ept.tss',
			'user' => 'olp',
			'pass' => 'dicr9dJA',
			'db' => 'ldb',
			'port' => '3308'
		);
		$olp_server = array(
			'host' => 'writer.olp.ept.tss',
			'user' => 'sellingsource',
			'pass' => 'password',
			'db' => 'react_db',
		);
		$ole_server = array(
			'host' => 'olemaster.soapdataserver.com',
			'user' => 'olp',
			'pass' => 'KhN6JksR',
			'db' => 'oledirect2',
		);
		define('SEND_LIMIT', 0); // 0 = no limit
	break;
	default:
		die ('runmode is not set');
	break;
		
	}


?>
