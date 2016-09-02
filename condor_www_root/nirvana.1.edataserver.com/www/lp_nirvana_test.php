<?php

//this is here so that Kostya can test nirvana for the landing pages
//this file will move as soon as statpro is working on the new lp servers
//hope this will not inconvenience anyone in the meantime - mel leonard


require_once ('/virtualhosts/lib5/prpc/server.php');



class Nirvana_PRPC extends Prpc_Server
{

	
	public function Nirvana_PRPC()
	{
		parent:: __construct();
	}
	
	public function Get_Consumer_Data( $property, $track_id, $date, $cashbuzz = TRUE, $debug = FALSE )
	{
		
		$db_process = "rc_lp_process";
		$db_visitor = "rc_lp_visitor"; 

		//$sql = new MySQL_4($server["host"], $server["user"], $server["pass"]);
		//$sql->Connect();
		$link = mysql_connect('db100.clkonline.com', 'sellingsource', '%selling\$_db');
		$db = mysql_select_db($db_process, $link);
		
			$query = "
			SELECT 
				email as EMAIL_PRIMARY,
				name_first as NAME_FIRST,
				name_last as NAME_LAST,
				name_middle as NAME_MIDDLE,
				referring_url as REFERRING_URL,
				company_name as COMPANY_NAME,
				customer_svc_number as CUSTOMER_SVC_NUMBER
			FROM 
				$db_process.statpro s
			JOIN
				$db_process.process p on (p.process_id = s.process_id)
			JOIN
				$db_visitor.personal v on (v.visitor_id = p.visitor_id)
			WHERE
				s.track_key = '". $track_id ."'
			";

		//$result = $sql->Query($db_process, $query);
		//$row = $sql->Fetch_Array_Row($result);
		$result = mysql_query($query, $link);
		$row = mysql_fetch_assoc($result);
		return $row;
		//return $this->_Prpc_Pack($row);
	}
}

$nirvana_prpc = new Nirvana_PRPC();
$nirvana_prpc->_Prpc_Strict = TRUE;
$nirvana_prpc->Prpc_Process();

?>
