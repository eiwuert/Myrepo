<?

/*****************

Create two nightly batches of PreQual and Full App leads per IO.

Two separate batches:

PreQual leads with FN,LN, home phone, email, city, st, zip, time/date stamp and ip address. Deduped by email for each day.

Full App Leads with all fields above plus SSN. Deduped on email and SSN for each day.

For each batches all fields must be populated.
For each batch, send all available leads nightly until 100,000K sent.
Send batches to following FTP destination:
ftp.flexmg.com
user: partnerweekly@flexmg.com
pass: p4rtn3r

These are non going to be used for Cash Advance. So, no need to scrub against funded. 

http://webadmin2.tss/index.php?m=tasks&a=view&task_id=4462&tab=0

*******************/


	$ftp_server = 'ftp.flexmg.com';
	$ftp_user_name = 'partnerweekly@flexmg.com';
	$ftp_user_pass = 'p4rtn3r';

	require_once('mysql.4.php');

	$yesterday = strtotime(date("Y-m-d",strtotime('yesterday'))); // start at 7/5
	$today = strtotime(date("Y-m-d",strtotime('today'))); // start at 7/5

	$olp = new MySQL_4('selsds001', 'sellingsource', 'password');
	//$olp = new MySQL_4('ds001.tss', 'sellingsource', 'password');
	$olp->Connect();
	$csv = '';
	$file = 'grab_olpbbp_'.date("YmdHis").'.csv';
	$putfile = "partial_".date("YmdHis").'.csv';
	$localpath = '/tmp/'.$file;
	$stat_file = '/tmp/grab_olpbbp_stat.log';
	if (!is_file($stat_file)) {
		file_put_contents($stat_file, '');
		chmod($stat_file, 0777);
	}
	$stats = file_get_contents($stat_file);
	$stats = !empty($stats)?unserialize(trim($stats,"\n")):array();

	$total = 0;
	foreach ($stats as $date => $daily_total) {
		$total += $daily_total;
	}

	if ($total>=100000) {
		$msg = 'limit reached for grab_partial.php.  Remove from cron';
		mail('norbinn.rodrigo@sellingsource.com', 'Limit Reached!!!', $msg);
		die;
	}

	$limit = ((100000-$total)<10000)?(100000-$total):10000;

	$olpq = "select ".
					"distinct email, ".
					"campaign_info.modified_date as modified_date, ".
					"first_name, ".
					"last_name, ".
					"home_phone, ".
					"city, ".
					"state, ".
					"zip, ".
					"ip_address ".
				"from ".
					"campaign_info, ".
					"personal, ".
					"residence ".
				"where ".
					"campaign_info.application_id=personal.application_id and ".
					"campaign_info.application_id=residence.application_id  and ".
					"campaign_info.modified_date>='".date("YmdHis", $yesterday)."' and ".
					"campaign_info.modified_date<'".date("YmdHis", $today)."' and ".
					"personal.first_name!='' and ".
					"personal.last_name!='' and ".
					"email!='' and ".
					"home_phone!='' and ".
					"city!='' and ".
					"state!='' and ".
					"zip!='' and ".
					"ip_address!='' ".
				"LIMIT ".$limit;
	
	$olpr = $olp->Query('olp_bb_partial', $olpq);
	$count = $olp->Row_Count($olpr);
	if ($count<5000) {
		$msg = "Grab Partial Data below minimum count (".$count.")";
		mail('norbinn.rodrigo@sellingsource.com', 'Below Minimum Limit!!!', $msg);
		// send and keep going
	}
	
	$stats[date("Y-m-d H:i:s")] = $count;

	if ($olp->Row_Count($olpr)>0) {
		while ($olpres = $olp->Fetch_Array_Row($olpr)) {
			if (empty($csv)) {
				$header_args = array();
				foreach (array_keys($olpres) as $key) {
					$header_args[] = '"'.$key.'"';
				}
				$csv .= implode(',',$header_args)."\n";
			}
			$args = array();
			foreach ($olpres as $key => $value) {
				if ($key=='modified_date') {
					$value = substr($value,0,4).'-'.substr($value,4,2).'-'.substr($value,6,2).' '.substr($value,8,2).':'.substr($value,10,2).':'.substr($value,12,2);
				}
				$args[] = '"'.$value.'"';
			}
			$csv .= implode(',',$args)."\n";
		}
	}

	$fh = fopen($localpath, 'w');
	fwrite($fh, $csv);
	fclose($fh);

	// set up basic connection
	$conn_id = ftp_connect($ftp_server);

	// login with username and password
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

	if (ftp_put($conn_id, $putfile, $localpath, FTP_ASCII)) {
		$msg =  "successfully uploaded $localpath\n";
		mail('norbinn.rodrigo@sellingsource.com', 'FTP Success', $msg);
	}

	file_put_contents($stat_file, serialize($stats));

?>
