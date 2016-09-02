<?php
/**
 * Class containing methods to access the Condor database to gather information
 * for the statistics section of Condor Administration.
 *
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */
class Condor_Statistics_Query
{
	private $server;
	private $mysqli;
	
	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->mysqli = $server->MySQLi();
	}
	
	/**
	 * Returns an array of failed sends.
	 *
	 * @param string $date_start
	 * @param string $date_end
	 * @return array
	 */
	public function Get_Failed_Sends($date_start, $date_end)
	{
		$query_start_date = date("Y-m-d 00:00:00", strtotime($date_start));
		$query_end_date = date("Y-m-d 23:59:59", strtotime($date_end));
		
		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			SELECT
				dh.date_created,
				ds.name AS status,
				dd.document_id,
				dd.transport AS method
			FROM
				".CONDOR_DB_NAME.".dispatch_history AS dh
				JOIN ".CONDOR_DB_NAME.".dispatch_status AS ds ON dh.dispatch_status_id = ds.dispatch_status_id
				JOIN ".CONDOR_DB_NAME.".document_dispatch AS dd ON dh.document_dispatch_id = dd.document_dispatch_id
				JOIN agent AS a ON dd.user_id = a.agent_id
			WHERE
				dh.date_created BETWEEN '$query_start_date' AND '$query_end_date'
				AND ds.type = 'FAIL'
				AND a.company_id = {$this->server->company_id}";
		
		$result = $this->mysqli->Query($query);
		
		$failed_sends = array();
		while(($row = $result->Fetch_Object_Row()))
		{
			$failed_sends[] = $row;
		}
		
		return $failed_sends;
	}
}
?>
