<?php
	set_time_limit(0);
	require_once ("/virtualhosts/lib/scrubber/db.scrubber.php");
	require_once ("/virtualhosts/lib/scrubber/lib.scrubber.php");

	$olesql = new MySQL_3();
	$conn = $olesql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($conn, TRUE);

	$scrubs_passed=array(SCRUB_NAME,SCRUB_EMAIL);

	$query = "SELECT tID FROM lists";
	$result = $olesql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);
	while($batches = $sql->Fetch_Array_Row($result))
	{
		$existing_batch[$batches["tID"]] = $batches["tID"];
	}

	$new_batch = batch_fetch_by_date ($sql, $scrubs_passed, strtotime("-10 days"));

	$batch = array_diff($new_batch, $existing_batch);
	// die(print_r($batch, 1));

	//$batch = array(308);
	$c = 0;
	foreach ($batch as $batch_id)
	{
		//if($batch_id != 65)
		{
			//echo "batch: ".$batch_id."\n";
			$batch_info = batch_fetch_by_id ($sql, $batch_id);
			$vendor_name = account_fetch_by_id($sql, $batch_info[$batch_id]["account_id"]);
			$batch_date = substr($batch_info[$batch_id]["batch_created"],4,2)."/".substr($batch_info[$batch_id]["batch_created"],6,2);
			if ($batch_info[$batch_id]["data_source_id"] == 33)
			{
				continue;
			}
			if ($batch_info[$batch_id]["data_source_id"])
			{
				$data_source = data_source_fetch_by_id ($sql, $batch_info[$batch_id]["data_source_id"]);
				$source_name = $data_source["description"];
				//echo $source_name."\n";
			}
			$list_desc = "CoReg - ".$vendor_name["vendor_name"]." - ".$batch_date." ".$source_name." ".$batch_id;
			$query = "INSERT INTO lists (name, description, tID) VALUES ('".$list_desc."', '".$list_desc."', ".$batch_id.")";
			$result = $olesql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test($result, TRUE);
			$list_id = $olesql->Insert_Id();
			$tablename = "list_".$list_id;
			$query = "
				CREATE TABLE ".$tablename." (
			  `ID` int(11) NOT NULL auto_increment,
			  `sID` int(11) default '0',
			  `piID` int(11) default '0',
			  `email` varchar(60) NOT NULL default '',
			  `name` varchar(50) NOT NULL default '',
			  `first` varchar(25) default '',
			  `last` varchar(25) default '',
			  `addr1` varchar(50) default '',
			  `addr2` varchar(50) default '',
			  `city` varchar(30) default '',
			  `state` varchar(20) default '',
			  `postcd` varchar(20) default '',
			  `country` varchar(20) default '',
			  `phone` varchar(20) default '',
			  `bdate` date default '0000-00-00',
			  `gender` char(1) default '',
			  `source` int(11) default '0',
			  `added` date default '0000-00-00',
			  `addedtime` time default '00:00:00',
			  `IPaddress` varchar(20) default '',
			  `domain` varchar(30) default NULL,
			  `statcd` tinyint(4) default '1',
			  `secret_code` varchar(32) default NULL,
			  `freq` tinyint(4) NOT NULL default '0',
			  `next_send_date` date default '0000-00-00',
			  `bounce_count` int(11) default '0',
			  PRIMARY KEY  (`ID`),
			  UNIQUE KEY `email` (`email`),
			  KEY `name` (`name`,`sID`,`piID`),
			  KEY `secret_code` (`secret_code`)
			) TYPE=MyISAM;
			";
			$result = $olesql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test($result, TRUE);

			unset($data);
			//$data = custdata_by_batch ($sql, $batch_id, $scrubs_passed);

			$query = "
			SELECT
				custdata_id,
				batch_id,
				name_title,
				name_first,
				name_middle,
				name_last,
				name_extra,
				email,
				phone,
				phone_work,
				phone_cell,
				phone_pager,
				phone_fax,
				addr1,
				addr2,
				city,
				state,
				zip,
				zip4,
				url_refer
			FROM
				custdata
			WHERE
				batch_id = ".$batch_id."
				AND valid_name = 'Y'
				AND valid_email = 'Y'
			ORDER BY
				custdata_id ASC";

			$scrub_result = $sql->Query ("scrubber", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test($scrub_result, TRUE);

			while($row = $sql->Fetch_Array_Row($scrub_result))
			{
				$query = "SELECT ID, lists FROM personindex where email = '".$row["email"]."'";
				$result = $olesql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test($result, TRUE);
				$ole = $sql->Row_Count($result);
				if ($ole)
				{
					$ole = $sql->Fetch_Array_Row($result);
					$pindex_id = $ole["ID"];

					$query = "UPDATE personindex SET lists = '".$ole["lists"].",".$list_id."' where ID = ".$pindex_id." LIMIT 1";
				}
				else
				{
					$query = "INSERT INTO personindex (email, name, first, last, lists) VALUES ('".$row["email"]."','".mysql_escape_string($row["first_name"]." ".$row["last_name"])."','".mysql_escape_string($row["first_name"])."','".mysql_escape_string($row["last_name"])."','".$list_id."')";

					$pindex_id = $sql->Insert_Id();
				}

				$result = $olesql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test($result, TRUE);

				$res = preg_match("/(.*)@(.*)/",$row["email"], $m);
				//echo $m[2]."\n";
				//exit;
				$query = "INSERT INTO ".$tablename." (sID, piID, email, name, first, last, source, domain, secret_code) VALUES (1015, ".$pindex_id.", '".$row["email"]."', '".mysql_escape_string($row["first_name"]." ".$row["last_name"])."', '".mysql_escape_string($row["first_name"])."', '".mysql_escape_string($row["last_name"])."', ".$list_id.",'".$m[2]."','".md5($row["email"])."')";
				$result = $olesql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test($result, FALSE);




				$c++;
				if($c%5000 == 0)
				{
					//echo $c."\n";
					//exit;
				}
			}
			//echo $c."\n";
			//echo $batch_id."\n";
		}
	}
?>
