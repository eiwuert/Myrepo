<?php

	define ('QUIET', $_SERVER['argv'][1] == '-q' ? 1 : 0);

	$ps = `ps -ef --cols=300 | grep smtp_purge_retry | grep -v grep | wc -l`;
	if (trim($ps) > 1)
	{
		exit;
	}


	require_once ('/virtualhosts/lib/mysql.3.php');
	require_once ('/virtualhosts/lib/soap_smtp_client.3.php');

	$mail = new SoapSmtpClient_3 ("soap.maildataserver.com");	
	
	$sql = new MySQL_3 ();
	Error_2::Error_Test (
		$sql->Connect (NULL, 'selsds001', 'smtp', 'sendingmail', Debug_1::Trace_Code (__FILE__, __LINE__)),
		TRUE
	);
	define ('SQL_BASE', 'smtp');

        /*
        ** Purge broken mailings
        */

        $query = "SELECT m.* FROM mailing m LEFT JOIN package p USING (mailing_id) WHERE p.mailing_id IS NULL AND m.created_date < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
        Error_2::Error_Test ($result, TRUE);

        while ($row = $sql->Fetch_Object_Row ($result))
        {
                Purge_Mailing ($row->mailing_id);
        }
 
	/*
	** Purge old mailings
	*/
	
	$query = "SELECT mailing_id FROM mailing WHERE DATE_ADD(created_date, INTERVAL ttl HOUR) < NOW()";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	while ($row = $sql->Fetch_Object_Row ($result))
	{
		Purge_Mailing ($row->mailing_id);
	}

	/*
	** Optimize
	*/

	if(date("H") == "04")
	{
		$query = "optimize table mailing, package, recipient, attachement";
		$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
	}
	/*
	** Retry unsucsessful mailings
	*/
	
	$query = "SELECT mailing_id FROM mailing WHERE success_count = 0 AND created_date < DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	while ($row = $sql->Fetch_Object_Row ($result))
	{
		Send_Mailing ($row->mailing_id);
	}
	
	
	/*
	** Functions
	*/

	function qPrint ($msg)
	{
		if (! QUIET)
			echo $msg;
	}
	
	function Send_Mailing ($mailing_id)
	{
		global $mail;
		
		qPrint ("Send_Mailing ($mailing_id)... ");
		
		$mail->SendMail($mailing_id);
		
		qPrint ("done.\n");
	}
	
	function Purge_Mailing ($mailing_id)
	{
		global $sql;
		
		qPrint ("Purge_Mailing ($mailing_id)... ");
		
		$query = "SELECT package_id FROM package WHERE mailing_id = ".$mailing_id;
		$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
		
		while ($row = $sql->Fetch_Object_Row ($result))
		{
			$packages .= $row->package_id.",";	
		}
		$packages = substr($packages, 0, -1);
		
		if (strlen ($packages))
		{
			$query = "DELETE FROM attachment WHERE package_id IN (".$packages.")";
			Error_2::Error_Test (
				$sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__)),
				TRUE
			);
			$query = "DELETE FROM recipient WHERE package_id IN (".$packages.")";
			Error_2::Error_Test (
				$sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__)),
				TRUE
			);
			$query = "DELETE FROM package WHERE package_id IN (".$packages.")";
			Error_2::Error_Test (
				$sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__)),
				TRUE
			);
		}
		$query = "DELETE FROM mailing WHERE mailing_id = ".$mailing_id." LIMIT 1";
		Error_2::Error_Test (
			$sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__)),
			TRUE
		);
	
		qPrint ("done.\n");
	}
	
?>
