<?php

/*

File Format:

Promotion,
Client Identifcation Code,
Customer Phone #,
Customer First Name,
Customer Last Name,
Customer Billing Address 1,
Customer Billing Address 2,
Customer City,
Customer State,
Customer Zip,

Script Code,
Plan Code,
Rate Code,
Premium Code,
Sale Code,
Date of Sale,
Credit Card Number,
Credit Card Expiration Date,

Email Address,
Filler,
Primary Product Description,
CVV2


Examples:

"DOW","10003","3606651111","Chris","Cox","123 N St","","Ocean Park","AR","98640",
"1226","63","05","617","SZ","20030503","430572xxxxxxxxxx","0204",
"","","email@yahoo.com","511"

"DOW","3375","8702681111","Diana","Coles","123 Stevens","Apt 2","Brookland","WA","72417",
"1226","63","05","617","SZ","20030418","430572xxxxxxxxxx","0206",
"","","email@classicnet.net","374"


*/

	define ('CSV_APPLICANT_ID', 1);

	define ('SITE_ID', 8869);	// 5000freelongdistance.com
	define ('PAGE_ID', 8871);

	define ('SQL_HOST', 'write1.iwaynetworks.net');
	//define ('SQL_HOST', 'localhost');
	define ('SQL_USER', 'sellingsource');
	define ('SQL_PASS', 'password');

	define ('SQL_SITE_BASE', 'freelongdistance');
	define ('SQL_STAT_BASE', 'direct1_stat');

	require_once ('mysql.3.php');
	require_once ('setstat.1.php');

	ini_set ('display_errors', 1);
	ini_set ('html_errors', 0);
	ini_set ('magic_quotes_runtime', 0);
	ini_set ('auto_detect_line_endings', 1);

	set_time_limit (0);
	error_reporting (E_ALL & ~(E_NOTICE));

	$promo_status = new stdClass ();
	$promo_status->valid = 'valid';

	$sql = new MySQL_3 ();
	Error_2::Error_Test (
		$sql->Connect (NULL, SQL_HOST, SQL_USER, SQL_PASS, Debug_1::Trace_Code (__FILE__, __LINE__)), TRUE
	);

	function Batch_File_Parse ($file)
	{

		if (! ($fp = fopen ($path.$file, 'r')))
		{
			die ('fopen failed:'.$path.$file);
		}

		$valid = TRUE;
		$csv_data = array ();
		$app_ids = array ();

		for ($ln = 0 ; $line = fgets ($fp, 8192) ; $ln++)
		{
			// skip blank lines
			if (! ($line = trim ($line)))
			{
				$ln--;
				continue;
			}

			// i - char, f - field, q - in quotes
			$field = array ();
			for ($i = $f = $q = 0 ; $i < strlen ($line) ; $i++)
			{
				if (! isset ($field [$f]))
				{
					$field [$f] = '';
				}

				switch (TRUE)
				{
					case (! $q && $line{$i} === ','):
						$f++;
						break;

					case (! $q && $field [$f] == '' && $line{$i} == '"'):
						$q = TRUE;
						break;

					case ($line{$i} == '"'):
						if ($line{$i+1} == '"')
							$field [$f] .= $line{$i++};
						else
							$q = FALSE;
						break;

					default:
						$field [$f] .= $line{$i};
				}
			}

			$field_n = count ($field);
			if ($field_n != 22)
			{
				echo 'Invalid Line!', "\n", $line, "\n";
				exit (1);
			}

			$csv_data [] = $field;
			$app_ids [] = $field [CSV_APPLICANT_ID];
		}

		fclose ($fp);

		return array ($csv_data, $app_ids);
	}

	function Batch_File_Deny ($csv_data, $app_ids)
	{
		global $sql, $promo_status, $dry_run;

		if (! (is_array ($app_ids) && count ($app_ids)))
		{
			echo 'INFO: Processing 0 denied records.', "\n";
			return TRUE;
		}

		// query for tracking info
		$query = '
			SELECT
				ai.applicant_id, pending, promo_id, sub_code, denied, approved
			FROM
				applicant_information ai
				LEFT JOIN vendor_information USING (applicant_id)
				LEFT JOIN applicant_status USING (applicant_id)
			WHERE
				vendor_information.applicant_id in ('.implode (',', $app_ids).')
			';

		$result = $sql->Query (SQL_SITE_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);

		while ($row = $sql->Fetch_Object_Row($result))
		{
			$app_info [$row->applicant_id] = $row;
		}

		echo 'INFO: Processing ', count($app_ids), ' denied records.', "\n";

		foreach ($csv_data as $data)
		{

			if (! isset ($app_info [$data[CSV_APPLICANT_ID]]))
			{
				echo 'WARNING: No app_info for ', $data[CSV_APPLICANT_ID], " - skipped\n";
				continue;
			}
			$app = $app_info [$data[CSV_APPLICANT_ID]];

			$stat = Set_Stat_1::_Setup_Stats ($app->pending, SITE_ID, 0, PAGE_ID, $app->promo_id, $app->sub_code, $sql, SQL_STAT_BASE, $promo_status, 'week');

			if (! $dry_run && strtotime($app->pending) < strtotime('2003-05-07'))
			{
				Set_Stat_1::Set_Stat ($stat->block_id, $stat->tablename, $sql, SQL_STAT_BASE, 'pending', 1);
			}

			if ($app->denied)
			{
				echo 'INFO: Skipping deny for app ', $app->applicant_id, ' with existing deny date ('.$app->denied.')', "\n";
				continue;
			}

			if (! $dry_run)
			{
				// mark applicant as denied
				$query = 'UPDATE applicant_status SET returned = NOW(), denied = \''.$app->pending.'\' WHERE applicant_id = '.$data[CSV_APPLICANT_ID];
				//echo $query."\n";
				$sql->Query (SQL_SITE_BASE,  $query, Debug_1::Trace_Code (__FILE__, __LINE__));

				Set_Stat_1::Set_Stat ($stat->block_id, $stat->tablename, $sql, SQL_STAT_BASE, 'denied', 1);
			}

			echo 'INFO: Set denied for app ', $app->applicant_id, ' (', $app->pending, ")\n";

		}
	}

	function Batch_File_Approve ($csv_data, $app_ids)
	{
		global $sql, $promo_status, $dry_run;

		if (! (is_array ($app_ids) && count ($app_ids)))
		{
			echo 'INFO: Processing 0 approved records.', "\n";
			return TRUE;
		}

		// query for tracking info
		$query = '
			SELECT
				ai.applicant_id, pending, promo_id, sub_code, denied, approved
			FROM
				applicant_information ai
				LEFT JOIN vendor_information USING (applicant_id)
				LEFT JOIN applicant_status USING (applicant_id)
			WHERE
				vendor_information.applicant_id in ('.implode (',', $app_ids).')
			';

		$result = $sql->Query (SQL_SITE_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);

		while ($row = $sql->Fetch_Object_Row($result))
		{
			$app_info [$row->applicant_id] = $row;
		}

		echo "\n", 'INFO: Processing ', count($app_ids), ' approved records.', "\n";

		foreach ($csv_data as $data)
		{

			if (! isset ($app_info [$data[CSV_APPLICANT_ID]]))
			{
				echo 'WARNING: No app_info for ', $data[CSV_APPLICANT_ID], " - skipped\n";
				continue;
			}
			$app = $app_info [$data[CSV_APPLICANT_ID]];

			$stat = Set_Stat_1::_Setup_Stats ($app->pending, SITE_ID, 0, PAGE_ID, $app->promo_id, $app->sub_code, $sql, SQL_STAT_BASE, $promo_status, 'week');

			if (! $dry_run && strtotime($app->pending) < strtotime('2003-05-07'))
			{
				Set_Stat_1::Set_Stat ($stat->block_id, $stat->tablename, $sql, SQL_STAT_BASE, 'pending', 1);
			}

			if ($app->approved)
			{
				echo 'INFO: Skipping approve for app ', $app->applicant_id, ' with existing approve date ('.$app->approved.')', "\n";
				continue;
			}

			if (! $dry_run)
			{
				// mark applicant as approved
				$query = 'UPDATE applicant_status SET returned = NOW(), approved = \''.$app->pending.'\' WHERE applicant_id = '.$data[CSV_APPLICANT_ID];
				//echo $query."\n";
				$sql->Query (SQL_SITE_BASE,  $query, Debug_1::Trace_Code (__FILE__, __LINE__));

				// set tracking info
				Set_Stat_1::Set_Stat ($stat->block_id, $stat->tablename, $sql, SQL_STAT_BASE, 'approved', 1);
			}

			echo 'INFO: Set approved for app ', $app->applicant_id, ' (', $app->pending, ")\n";

		}
	}

	/**
	* Main
	*/

	$dry_run = TRUE;

	$batch = '20030525';
	//$path = '/virtualhosts/5000freelongdistance.com/data_mgt/';
	$path = '/home/rodricg/fix_direct1/';


	/**
	*
	*/

	if ($dry_run)
	{
		echo '--DRY RUN--', "\n\n";
	}

	$file = 'fail_'.$batch.'.txt';
	list ($csv_data, $app_ids) = Batch_File_Parse ($path.$file);
	Batch_File_Deny ($csv_data, $app_ids);

	$file = 'pass_'.$batch.'.txt';
	list ($csv_data, $app_ids) = Batch_File_Parse ($path.$file);
	Batch_File_Approve ($csv_data, $app_ids);

	echo "\n", '--DONE--', "\n";
?>
