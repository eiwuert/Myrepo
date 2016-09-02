<?php

class Status_Base
{
	
	// location of the load average stuff
	var $PROC_LOADAVG = '/proc/loadavg';
	
	function MySQL_Test($host_port, $user, $pass, $query_string = NULL)
	{
		//connect
		$link = mysql_connect($host_port, $user, $pass);
		if($link === FALSE) return FALSE;

		$result_array = NULL;
		// performing query if specified, it will fail if it returns 0 rows
		if($query_string != NULL)
		{
			$result = mysql_query($query_string);
			if($result === FALSE) return FALSE;

			$result_array = array();
			
			while($row = mysql_fetch_assoc($result))
			{
				$result_array[] = $row;
			}
			
			// free resultset
			if(!mysql_free_result($result)) return FALSE;
		}

		// close connection
		if(!mysql_close($link)) return FALSE;

		//provided query and got results, return them
		if($query_string != NULL && $result_array != NULL)
		{
			return $result_array;
		}

		//no query, just connect/disconnect
		if($query_string == NULL)
		{
			return TRUE;
		}

		//FAIL
		return FALSE;
	}

	function HD_Test($write_string = NULL)
	{
		$temp = tmpfile();
		if(!$temp) return FALSE;

		//make sure your string isn't "0" ;)
		$read_string = NULL;
		if($write_string != NULL)
		{
			if(!fwrite($temp, $write_string)) return FALSE;
			if(fseek($temp, 0) == -1) return FALSE;
			$read_string = fread($temp, 1024);
			if($read_string === FALSE) return FALSE;
		}
		if(!fclose($temp)) return FALSE;

		//provided string, read it back, return it
		if($write_string != NULL && $read_string != NULL)
		{
			return $read_string;
		}

		//no string to write, just open/close
		if($write_string == NULL)
		{
			return TRUE;
		}
		
		//FAIL
		return FALSE;
	}
	
	function Load_Test($threshold = NULL)
	{
		
		// assume it's nothing?
		$load = 0.0;
		
		if (is_readable($this->PROC_LOADAVG))
		{
			
			// get the current load
			$info = explode(' ', file_get_contents($this->PROC_LOADAVG));
			$load = (float)$info[0];
			
			// turn to boolean if we have a threshold
			if (is_numeric($threshold)) $load = ($load < $threshold);
			
		}
		
		return $load;
		
	}
	
}

?>
