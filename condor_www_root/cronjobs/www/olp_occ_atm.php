<?php

switch ($argv[1])
{
	case 'LIVE':
		$olp_host = "olp.2.soapdataserver.com";
		$dir_atm = "/home/atm/";
		break;

	case 'RC':
		$olp_host = "rc.olp.2.soapdataserver.com";
		$dir_atm = "/home/rc_atm/";
		break;

	default:
		echo "Usage: php ".$argv[0]." <LIVE|RC>\n";
		exit (0);
}

require_once ("prpc/client.php");
$occ_atm = new Prpc_Client ("prpc://".$olp_host."/occ/occ_atm.php");


$files = glob ($dir_atm.'LoanApp-*');

foreach ($files as $file)
{
	$s = stat ($file);
	if (time() - $s['ctime'] > 60)
	{
		$result = $occ_atm->Process_App (basename ($file), file_get_contents ($file), $s['ctime']);

		if ($result)
		{
			unlink ($file);
		}
	}
}

?>
