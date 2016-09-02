<?php


	/***
		ufc_ffd_report.php

	***/

	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	require_once('csv.1.php');
	require_once('datax.diag.php');
	//require_once('dx.reportmail.php');


	class UFC_FFD_Report
	{
		function Insert_Data($srcfile, &$sql)
		{
			$fp = fopen($srcfile, "r");

			if ( $fp === FALSE )
				return FALSE;

			$sql->query("clreport", "TRUNCATE ufc_return_full");
			while ( ($s = fgets($fp)) )
			{
				$in = split(",", trim($s) );

				$query = "
						INSERT INTO
							ufc_return_full
						SET
							field1='{$in[0]}',
							field2='{$in[1]}',
							field3='{$in[2]}',
							field4='{$in[3]}',
							field5='{$in[4]}',
							field6='{$in[5]}',
							field7='{$in[6]}',
							field8='{$in[7]}',
							field9='{$in[8]}'";

				$rs = $sql->query("clreport", $query, Debug_1::Trace_Code(__FILE__,__LINE__));
				Error_2::Error_Test($rs, TRUE);
			}
		}
		function Generate_CSV($outfile, &$sql,$dt)
		{
			$fp = fopen($outfile, "w");

			$csv = new CSV(array(
						"forcequotes" => TRUE,
						"flush" => FALSE,
						"header" => array("custnum", "fn", "ln", "advance", "cycle"),
						"stream" => $fp,
						));

			$q="
			SELECT ufc_customer.custnum, fn, ln, advance, cycle
			FROM ufc_customer
			INNER JOIN ufc_loan ON (ufc_customer.custnum = ufc_loan.custnum)
			INNER JOIN ufc_raw_transact ON (ufc_raw_transact.loan_id=ufc_loan.loan_id and ufc_raw_transact.type='ACH RETURN' and ufc_raw_transact.transaction_date='".date("Y-m-d",$dt)."')
			WHERE ufc_loan.achret=1 and loannum=1 and ufc_loan.numcycles=2 and cycle like '%FXR%'";

			$rs = $sql->query("datax", $q, Debug_1::Trace_Code(__FILE__,__LINE__));
			Error_2::Error_Test($rs, TRUE);

			while ( $row = $sql->Fetch_Object_Row($rs) )
			{
				$csv->recordFromArray((array)$row);
			}



			$buf = $csv->_buf;
			$csv->flush();
			return $buf;
		}
	}

	Diag::SetLogFile("../logs/r.ffds.".date("Ymdhis").".log");
	Diag::Enable();



	$sql = new MySQL_3();
	$x=$sql->Connect(NULL,"serenity.x", "serenity","firefly", Debug_1::Trace_Code(__FILE__,__LINE__));
	Error_2::Error_Test($x, TRUE);

	$dateinfo = getdate();

	if ( $dateinfo["wday"]==0 || $dateinfo["wday"]==6 )
	{
		Diag::Out("not a weekday. bailing.");
		exit;
	}
	if ( $dateinfo["wday"]==1 )
	{
		$dt = strtotime("last friday");
	}
	else
	{
		$dt = strtotime("-1 day");
	}

	Diag::Out("generating reports for ".date("Y-m-d",$dt));

	$csv = UFC_FFD_Report::Generate_CSV("REPORT.UFC.FFD.".date("m-d-Y",$dt).".csv", $sql,$dt);
	/*
	$attach = array
	(
		(object)array(
			'name' => "REPORT.UFC.FFD.".date("m-d-Y",$dt).".csv",
			'content' => base64_encode($csv),
			'content_type' => "text/x-csv",
			'content_length' => strlen($csv),
			'encoded' => TRUE
		)
	);

	$recipients = array
	(
		(object)array(
			"name" => "Monitor",
			"address" => "john.hargrove@thesellingsource.com",
			"type" => "To"
		)
	);


	DX_Report_Mail::Send("UFC FFD REPORT ".date("m-d-Y",$dt),$recipients,"",$attach);*/

?>
