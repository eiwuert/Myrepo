<?php

include_once "mysql.3.php";
$sql = new MySQL_3 () ;
	
$result = $sql->Connect (NULL, 'selsds001', 'sellingsource', 'password', Debug_1::Trace_Code (__FILE__, __LINE__));
	
$db = $sql->Get_Database_List();

foreach($db as $name => $val)
{
	if(stristr($name, '_tracking') || stristr($name, '_stat'))
	{
		$table = $sql->Get_Table_List($name);
		
		foreach($table as $tname => $tval)
		{
			$query = "CHECK TABLE ".$tname."";
			
			$result = $sql->Query ($name, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test($result, TRUE);
			$res = $sql->Fetch_Array_Row($result);
			
			if($res["Msg_text"] != "OK")
			{
				$query = "REPAIR TABLE ".$tname." QUICK";
				
				$result = $sql->Query ($name, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test($result, TRUE);
				print_r($sql->Fetch_Array_Row($result));
			}
		}

	}
}
?>
