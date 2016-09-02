<?
	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	include_once "/virtualhosts/lib/mysql.3.php";
	$sql = new MySQL_3 () ;
	$result = $sql->Connect (NULL, 'write1.iwaynetworks.net', 'sellingsource', 'password', Debug_1::Trace_Code (__FILE__, __LINE__));
	//$result = $sql->Connect (NULL, 'localhost', 'root', '', Debug_1::Trace_Code (__FILE__, __LINE__));

	// generate a data file for nms on the fly by inputting specific data
	if (!$HTTP_POST_VARS)
	{
		// no data posted.  So show a form
		Form();
	}
	else
	{
		
		if ( !$HTTP_POST_VARS['start_date'] || !$HTTP_POST_VARS['end_date'] )
		{
			$error = 'date / dates are incomplete';
		}
		else
		{
			echo $HTTP_POST_VARS['database'].'<br>';
			// Get GLOBAL SQL file for this server

			// start finding the record based on the dates.
			switch ($HTTP_POST_VARS['result'])
			{
				case 'prequal':
					$query = "SELECT applications.unique_id, applications.application_id as id, base.email as email_address, base.full_name full_name, applications.created_date as date, applications.type as type, promo_sub_code, url FROM applications, site_info, base WHERE applications.created_date BETWEEN '".date('Y-m-d', strtotime($HTTP_POST_VARS['start_date']))."' AND '".date('Y-m-d', strtotime($HTTP_POST_VARS['end_date']))."' AND applications.application_id=site_info.application_id AND applications.unique_id=base.unique_id AND applications.type IN('VISITOR', 'QUALIFIED')";
						if (!$_GET['all_results']) $query .= " AND url IN ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com')";
					$db = "ucl_visitor";
					$query .=" ORDER BY date, url";
				break;

				case 'complete':
					$query = "select application.application_id as id, DATE_FORMAT(campaign_info.created_date, '%m/%d/%Y') as date, url, first_name as first, last_name as last, email as email_address, promo_sub_code from application join campaign_info on application.application_id = campaign_info.application_id join personal on application.application_id = personal.application_id where url in ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com') and campaign_info.created_date between '".date('Y-m-d', strtotime($HTTP_POST_VARS['start_date']))."' AND '".date('Y-m-d', strtotime($HTTP_POST_VARS['end_date']))."' and type in ('PROSPECT', 'APPLICANT', 'CUSTOMER', 'DOA') ";
					if (!$_GET['all_results']) $query .= " AND url IN ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com') ";
					$db = "olp_ucl_visitor";
					$query .=" GROUP BY application.application_id ORDER BY date, url";
				break;
			}
			
			$result = $sql->Query ($db, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test($result, TRUE);
			
			//strips out any dups returned by the query
			while ($data = $sql->Fetch_Object_Row($result))
			{
				if($db == "ucl_visitor")
				{
					$email_body_ob->{"ap".$data->id} = date('m/d/Y', strtotime($data->date)).', '.$data->url.', '.strtolower($data->email_address).', '.ucwords(strtolower(str_replace(",", ".", $data->full_name))).", ".$data->promo_sub_code."\r\n";
				}
				else
				{
					$email_body_ob->{"ap".$data->id} = $data->date.', '.$data->url.', '.strtolower($data->email_address).', '.ucwords(strtolower(str_replace(",", ".", $data->first)))." ".ucwords(strtolower(str_replace(",", ".", $data->last))).", ".$data->promo_sub_code."\r\n";
				}
			} // end while
			
			foreach($email_body_ob as $email_thing)
			{
				$email_body .= $email_thing;
			}
			
			$outer_boundry = md5 ("Outer Boundry");
			$inner_boundry = md5 ("Inner Boundry");
		//exit;
			$batch_headers =
				"MIME-Version: 1.0\r\n".
				"Content-Type: Multipart/Mixed;\\boundary=\"".$outer_boundry."\"\r\n\r\n\r\n".
				"--".$outer_boundry."\r\n".
				"Content-Type: text/plain;\r\n".
				" charset=\"us-ascii\"\r\n".
				"Content-Transfer-Encoding: 7bit\r\n".
				"Content-Disposition: inline\r\n\r\n".
				"Data File\r\n".
				"--".$outer_boundry."\r\n".
				"Content-Type: text/plain;\r\n".
				" charset=\"us-ascii\";\r\n".
				" name=\"maxoutloan\"\r\n".
				"Content-Transfer-Encoding: 7bit\r\n".
				"Content-Disposition: attachment; filename=\"Maxout_loan_".date('mdy', strtotime($HTTP_POST_VARS['start_date'])).'_'.date('mdy', strtotime($HTTP_POST_VARS['end_date'])).'_'.$HTTP_POST_VARS['result'].".txt\"\r\n\r\n".
				$email_body."\r\n".
				"--".$outer_boundry."--\r\n\r\n";

				// Send the file to ed for processing
				$to_email_address = $_GET['to_email_address'] ? $_GET['to_email_address'] : "davidb@sellingsource.com";
				mail ($to_email_address,  "Maxout log ".$HTTP_POST_VARS['result']." from ".$HTTP_POST_VARS['start_date']." - ".$HTTP_POST_VARS['end_date'], NULL, $batch_headers);

		// write the file
			$file_path = 'maxout_'.date('mdy', strtotime($HTTP_POST_VARS['start_date'])).'_'.date('mdy', strtotime($HTTP_POST_VARS['end_date'])).'_'.$HTTP_POST_VARS['result'].'.txt';
			echo $file_path;
			// Create a NEW file
			$fd = @fopen($file_path, "w") or die("cannot open file $filename");
			if ($fd)
			{
				$fout = fwrite($fd, $email_body);
				$fout ? print '<!-- File named <a href="'.$file_path.'">'.$file_path.'</a> has been generated -->' : '<!-- Could NOT generate that file -->';
				fclose($fd);
			}
			else
			{
				print $file;
				print '<br><br><br><br>';
			}

		}
		print '<div align="center">'.$error.'</div>';
		Form();
	}

	function Form()
	{
		print '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
<table width="400" border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
	<td>
		<form action="" method="post">
		<table width="300" border="0" align="center" cellpadding="0" cellspacing="0">
		  <tr>
		    <td>Starting Date:</td>
		    <td><input name="start_date" type="text" id="start_date" value="';
		     print $GLOBALS['HTTP_POST_VARS']['start_date'] ? $GLOBALS['HTTP_POST_VARS']['start_date'] : date('n').'/1/'.date(y);
		     print '"></td>
		  </tr>
		  <tr>
		    <td>Ending Date:</td>
		    <td><input name="end_date" type="text" id="end_date" value="';
		    print $GLOBALS['HTTP_POST_VARS']['end_date'] ? $GLOBALS['HTTP_POST_VARS']['end_date'] : date('n').'/'.date(t).'/'.date(y);
		    print '"><i>m/d/yy</i></td>
		  </tr>
		  <tr>
		    <td>Results:</td>
		    <td><select name="result" id="result">
		        <option value="complete" SELECTED>Complete Aps</option>
		        <option value="prequal">Pre-Qual</option>
		      </select></td>
		  </tr>
		  <tr>
		    <td>&nbsp;</td>
		    <td><input type="submit" name="Submit" value="Submit"></td>
		  </tr>
		</table>
</td>
		  </tr>
		</table>
</body>
</html>
';
	}
?>
