<?php
	set_time_limit(0);
	include_once "/virtualhosts/lib/mysql.3.php";

	$sql = new MySQL_3 () ;
	//$result = $sql->Connect (NULL, 'write1.iwaynetworks.net', 'sellingsource', 'password', Debug_1::Trace_Code (__FILE__, __LINE__));
	$result = $sql->Connect (NULL, 'selsds001', 'sellingsource', 'password', Debug_1::Trace_Code (__FILE__, __LINE__));
	//$result = $sql->Connect (NULL, 'localhost', 'root', '', Debug_1::Trace_Code (__FILE__, __LINE__));

	$query = "SELECT * FROM master_remove_email where mre_updated > '".date("YmdHis",strtotime("-1 hour"))."'";
	//$query = "SELECT * FROM master_remove_email";
	$result = $sql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);
	$count = $sql->Row_Count($result);

	while ($app = $sql->Fetch_Array_Row($result))
	{
		$query = "SELECT * FROM personindex where email = '".$app["mre_email"]."'";
		$pi_result = $sql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
//		Error_2::Error_Test($pi_result, FALSE);
		if ($sql->Row_Count($pi_result)==0) continue; //if not found, go to the next
		$pi = $sql->Fetch_Array_Row($pi_result);

		$lists = explode(",",$pi["lists"]);
		foreach ($lists as $list)
		{
			if($list)
			{
				$query = "UPDATE  list_".$list." SET statcd = 0 WHERE email = '".$app["mre_email"]."' limit 1";
				//echo $query."\n";
				$list_result = $sql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			}
		}
		$count--;
		//echo $count."\n";
	}



?>
