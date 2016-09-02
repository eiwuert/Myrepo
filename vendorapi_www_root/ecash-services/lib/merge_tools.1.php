<?php

class Merge_Tools
{
	var $sql;
	
	function Merge_Tools($sql)
	{
		$this->sql = $sql;
	}	
	
	function Fetch_Range_Tables($database, $start_ts, $end_ts)
	{
		$tables = $this->sql->get_table_list($database);
						
		$range_tables = array();
		
		// Find tables in our range
		foreach($tables as $table => $one)
		{
			if (preg_match ("/^cache_(\d+)_(\d+)/", $table, $match))
			{
				$table_ts = strtotime ($match [1]."-".$match [2]."-01");
				
				if ($table_ts >= $start_ts && $table_ts <= $end_ts)
				{
					$range_tables[] = $table;
				}
			}
		}
					
		return $range_tables;
	}
	
	function Fetch_Table_Create($database, $table)
	{
		$errors = 0;
		
		$query = 'SHOW CREATE TABLE '.$table;
		$result = $this->sql->Query($database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
		Error_2::Error_Test($result, TRUE);

		$tbl_create = array_pop($this->sql->Fetch_Array_Row($result));

		if (! preg_match('/create table \S+ \((.+)\) type/is', $tbl_create, $match))
		{
			$errors = "Show create table failed";
		}
		
		return ( is_numeric($errors) && !$errors ) ? $match[1] : new Error_2($errors);
	}
	
	function Merge_Stat_Tables($database, $start_date, $end_date, $filter = 0)
	{
		$start_date_ts = strtotime($start_date);
		$end_date_ts = strtotime($end_date);
		
		$start_ts = mktime (0, 0, 0, date ("m", $start_date_ts), 1, date ("Y", $start_date_ts));
		$end_ts = mktime (0, 0, 0, date ("m", $end_date_ts) + 1, 1, date ("Y", $end_date_ts));	
		
		$tables_to_merge = $this->Fetch_Range_Tables($database, $start_ts, $end_ts);
		
		$merge_code = $this->Fetch_Table_Create($database, $tables_to_merge[0]);
			
		$query = 'DROP TABLE IF EXISTS merge_temp';
		$result = $this->sql->Query($database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
							
		$query = 'CREATE TEMPORARY TABLE merge_temp ('.$merge_code.') TYPE=MERGE UNION=('.implode(',', $tables_to_merge).') INSERT_METHOD=LAST';
		$result = $this->sql->Query($database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
		
		//echo $query . "<br><br>";
		
		return TRUE;
	}
}

?>