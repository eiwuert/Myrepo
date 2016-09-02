<?php
	include_once ("/virtualhosts/lib/mysql.3.php");

	$db_host = "selsds001";
	$db = "pw_visitor";
	$date_str = date ("YmdHis", strtotime ("-1 month"));

	$sql = new MySQL_3 ();
	$sql->Connect ('', $db_host, 'sellingsource', 'password');

	$query = "DELETE FROM session_site WHERE modifed_date < '".$date_str."' LIMIT 250";

	$records_to_purge = true;
	while ($records_to_purge)
	{
		$result = $sql->Query ($db, $query);
		$records_to_purge = $sql->Affected_Row_Count () > 0 ? true : false;
	}
	$sql->Free_Result ($result);
?>