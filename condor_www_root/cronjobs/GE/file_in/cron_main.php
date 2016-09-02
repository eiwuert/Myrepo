<?php

require_once ('mysql.3.php');
require_once ('config.3.php');
require_once ('setstat.1.php');
require_once ('bugout.1.php');

$opts = getopt ('n:m:d:tp');

$GLOBALS ['bugout_level'] = (isset ($opts['d']) && is_numeric ($opts['d'])) ? $opts['d'] : 0;
bugout::msg ("Bugout level is {$bugout_level}");

$GLOBALS ['node'] = (isset ($opts['n'])) ? $opts['n'] : 'localhost';
bugout::msg ("Running against node {$node}");

$GLOBALS ['mode'] = (isset ($opts['m']) && strtoupper($opts['m']) == 'LIVE') ? 'LIVE' : 'RC';
bugout::msg ("Running in {$mode} mode");

$sql = new MySQL_3 ();
$result = $sql->Connect (NULL, $node, 'gecron', 'x10powerhouse');
Error_2::Error_Test ($result, TRUE);

/*
** Transfer files
*/

if (isset ($opts['t']))
{
	bugout::msg ("Transfering files");

	$ftp = ftp_connect ('ftp.gepmg.com');

	$res = ftp_login ($ftp, 'ssourpmg', 'iTI994a2');
	if ($res)
	{
		bugout::msg ("ftp_login {$ftp}");
	}
	else
	{
		bugout::msg ("unable to login to ftp server", BO_FATAL);
		exit (1);
	}

	ftp_pasv ($ftp, TRUE);

	$dir = 'outbound';
	$res = ftp_chdir ($ftp, $dir);
	if ($res)
	{
		bugout::msg ("ftp_chdir {$dir}");
	}
	else
	{
		bugout::msg ("unable to chdir to {$dir}", BO_FATAL);
		exit (1);
	}

	$nlist = ftp_nlist ($ftp, ".");

	if (is_array ($nlist) && count ($nlist))
	{
		foreach ($nlist as $file_name)
		{
			$res = ftp_get ($ftp, $file_name, $file_name, FTP_BINARY);
			if ($res)
			{
				bugout::msg ("ftp_get {$file_name}");
			}
			else
			{
				bugout::msg ("unable to get file {$file_name}", BO_FATAL);
				exit (1);
			}

			$clear_name = str_replace ('.pgp', '', trim($file_name));

			// we test for existance of the clear file after running gpg to determine success
			// so we have to make sure its not already there
			if (file_exists ($clear_name))
			{
				if (! unlink ($clear_name))
				{
					bugout::msg ("unable to unlink file {$clear_name}", BO_FATAL);
					exit (1);
				}
			}
			//exec ('bash -l -c "tty"', $o); echo implode ("\n", $o), "\n\n";
			exec ('bash -l -c "echo \'thisisourfirstprivatekey\' | gpg --homedir /home/release/.gnupg --no-secmem-warning --passphrase-fd 0 --yes -o '.$clear_name.' -d '.$file_name.'"');

			if (file_exists ($clear_name))
			{
				bugout::msg ("gpg decryption successfull");
				if (! unlink ($file_name))
				{
					bugout::msg ("unable to unlink file {$file_name}", BO_FATAL);
					exit (1);
				}
			}
			else
			{
				bugout::msg ("unable to decrypt file {$file_name}", BO_FATAL);
				exit (1);
			}

			$file_body = file_get_contents ($clear_name);
			switch (substr ($clear_name, -5, 1))
			{
				case '1':
					$file_type = 'DISPOSITION';
					break;
				case '2':
					$file_type = 'TRUEUP';
					break;
				default:
					bugout::msg ("unable to determine file_type from ".substr ($clear_name, -5, 1), BO_FATAL);
					exit (1);
			}

			$query = "insert into file_in (file_date, file_name, file_body, file_type) values (NOW(), '".mysql_escape_string($clear_name)."', '".mysql_escape_string($file_body)."', '".$file_type."')";
			$result = $sql->Query ('ge_batch', $query);
			Error_2::Error_Test ($result, TRUE);

			if (! unlink ($clear_name))
			{
				bugout::msg ("unable to unlink file $clear_name", BO_FATAL);
				exit (1);
			}

			$res = ftp_delete ($ftp, $file_name);
			if ($res)
			{
				bugout::msg ("ftp_delete {$file_name}");
			}
			else
			{
				bugout::msg ("unable to delete file {$file_name}", BO_FATAL);
				exit (1);
			}

		}
	}
	else
	{
		bugout::msg ("no files to transfer");
	}

	ftp_close ($ftp);
}

/*
** Process files
*/

if (isset ($opts['p']))
{

	$query = "select * from file_in where proc_date = '0000-00-00 00:00:00'";
	$file_result = $sql->Query ('ge_batch', $query);
	Error_2::Error_Test ($file_result, TRUE);

	while ($file_row = $sql->Fetch_Object_Row ($file_result))
	{
		bugout::msg ("Processing {$file_row->file_name}");

		$unfound = 0;

		$lines = explode ("\n", $file_row->file_body);
		$orders = array ();

		foreach ($lines as $n => $data)
		{
			if ($data)
			{
				$orders[$n]->first_name = substr ($data, 0, 14);
				$orders[$n]->last_name = substr ($data, 16, 20);
				$orders[$n]->address = substr ($data, 36, 25);
				$orders[$n]->city = substr ($data, 61, 20);
				$orders[$n]->state = substr ($data, 81, 2);
				$orders[$n]->zip = substr ($data, 83, 9);
				$orders[$n]->phone_number = substr ($data, 92, 10);
				$orders[$n]->enroll_date = substr ($data, 102, 8);
				$orders[$n]->site_code = substr ($data, 118, 8);
				$orders[$n]->order_id = substr ($data, 126, 10);
				$orders[$n]->pmg_member_id = substr ($data, 136, 11);
				$orders[$n]->pmg_promo_code = substr ($data, 147, 9);
				$orders[$n]->product_id = substr ($data, 156, 3);
				$orders[$n]->disposition_code = substr ($data, 159, 3);
			}
		}


		foreach ($orders as $n => $order)
		{
			switch ($order->product_id)
			{
				case 'ENT':
					$base = 'criticschoicemembership_com';
					$live_key = '8e50baf642bd6685e593bf238aa27051349bf12ce84547b1ff329701fd1d045f';
					$rc_key = '8e50baf642bd6685e593bf238aa2705129bc9f8862cc190bc1a7278be839ec9c';
					break;

				case 'TRV':
					$base = 'perfectgetawaymembership_com';
					$live_key = '33b9c7c18ec3acc3747c41e70e9bb3d65dbcf88cdae9e353fcde593bd9bf0695';
					$rc_key = '33b9c7c18ec3acc3747c41e70e9bb3d6332a8da9d93f8c83034ff1547c767b0c';
					break;

				case 'IDT':
					$base = 'identitytracking_com';
					$live_key = '86b17892ce619e65a85cc9bdddf255c2';
					$rc_key = '8838296f9e1363a9ec4dc09d06dd9a95';
					break;

				default:
					bugout::msg ("unhandled product_id {$order->product_id}", BO_FATAL);
					exit (1);
			}

			$license_key = ($mode == 'LIVE') ? $live_key : $rc_key;
			$base = ($mode == 'LIVE') ? $base : 'rc_'.$base;

			$order->order_id = trim ($order->order_id);
			if ($order->order_id)
			{
				if (! preg_match ('/^0*(.*)$/', $order->order_id, $m))		// why are we using alphanumeric order ids?
				{
					bugout::msg ("unable to preg_match order_id {$order->order_id}", BO_FATAL);
					exit (1);
				}
				$order->order_id = $m[1];

				$query = "select DATE_FORMAT(orders.creation_stamp, '%Y-%m-%d') AS stat_date, orders.* from orders where order_id = '{$order->order_id}'";
				$result = $sql->Query ($base, $query);
				Error_2::Error_Test ($result, TRUE);

				if ($sql->Row_Count ($result) != 1)
				{
					bugout::msg ("unable to find order_id={$order->order_id} in database {$base}!", BO_FATAL);
					exit (1);
				}
				else
				{
					$row = $sql->Fetch_Object_Row ($result);
					bugout::msg ("found order_id={$order->order_id}", BO_GOBS);
				}
			}
			else
			{
				$query = "select DATE_FORMAT(MAX(orders.creation_stamp), '%Y-%m-%d') AS stat_date, orders.* from orders, person, address
				where orders.person_id = person.person_id and orders.bill_addr = address.address_id and
				REPLACE(address.firstname, '-', ' ') = '".trim($order->first_name)."' and REPLACE(address.lastname, '-', ' ') = '".trim($order->last_name)."' and
				address.zip = '".trim($order->zip)."' and address.phone = '".$order->phone_number."'
				group by person.email";
				$result = $sql->Query ($base, $query);
				Error_2::Error_Test ($result, TRUE);

				if ($sql->Row_Count ($result) != 1)
				{
					bugout::msg ("unable to find order by person/address search".var_export ($order, 1), BO_ERROR);
					$unfound++;
				}
				else
				{
					$row = $sql->Fetch_Object_Row ($result);
					bugout::msg ("found order_id={$row->order_id} by person/address search");
				}
			}

			/*
			** at this point $row is our database order record and $order is the data we got from GE
			*/

			$config = Config_3::Get_Site_Config ($license_key, $row->promo_id, $row->promo_sub_code);

			if (Error_2::Check($config) || ! strlen ($config->site_name))
			{
				bugout::msg ("Get_Site_Config failed", BO_FATAL);
				exit (1);
			}

			$stat_info = Set_Stat_1::_Setup_Stats ($row->stat_date, $config->site_id, $config->vendor_id, $config->page_id, $config->promo_id, $config->promo_sub_code, $sql, $config->stat_base, $config->promo_status, 'week');

			switch ($file_row->file_type)
			{
				case 'DISPOSITION':

					switch (TRUE)
					{
						case (! $row->disposition_status):
							$query = "update orders set disposition_status = '".mysql_escape_string($order->disposition_code)."' where order_id = '{$row->order_id}'";
							$result = $sql->Query ($base, $query);
							Error_2::Error_Test ($result, TRUE);

							bugout::msg ("disposition_status of order_id={$row->order_id} set to {$order->disposition_code}");

							// TODO: should hit stats for DUP and ERR
							switch ($order->disposition_code)
							{
								case 'ENR':
									Set_Stat_1::Set_Stat ($stat_info->block_id, $stat_info->tablename, $sql, $config->stat_base, 'enroll');
									break;
							}
							break;

						case ($row->disposition_status == $order->disposition_code):
							bugout::msg ("disposition_status for order_id={$row->order_id} is already {$row->disposition_status} skipping", BO_INFO);
							break;

						default:
							bugout::msg ("disposition_status for order_id={$row->order_id} is already {$row->disposition_status} but we got {$order->disposition_status} skipping", BO_ERROR);
							break;
					}
					break;

				case 'TRUEUP':

					switch (TRUE)
					{
						case (! $row->trueup_status):
							$query = "update orders set trueup_status = '".mysql_escape_string($order->disposition_code)."' where order_id = '{$row->order_id}'";
							$result = $sql->Query ($base, $query);
							Error_2::Error_Test ($result, TRUE);

							// TODO: should hit stats for DUP and ERR
							switch ($order->disposition_code)
							{
								case 'ENR':
									Set_Stat_1::Set_Stat ($stat_info->block_id, $stat_info->tablename, $sql, $config->stat_base, 'funded');
									break;
							}
							break;

						case ($row->trueup_status == $order->trueup_code):
							bugout::msg ("trueup_status for order_id={$row->order_id} is already {$row->trueup_status} skipping", BO_INFO);
							break;

						default:
							bugout::msg ("trueup_status for order_id={$row->order_id} is already {$row->trueup_status} but we got {$order->trueup_status} skipping", BO_ERROR);
							break;
					}
					break;
			}
		}

		if ($unfound)
		{
			bugout::msg ("file {$file_row->file_name} had {$unfound} order(s) that could not be found", BO_ERROR);
		}

		$query = "update file_in set proc_date = NOW() where file_id = {$file_row->file_id}";
		$result = $sql->Query ('ge_batch', $query);
		Error_2::Error_Test ($result, TRUE);

		bugout::msg ("finished processing {$file_row->file_name}");
	}
}
?>
