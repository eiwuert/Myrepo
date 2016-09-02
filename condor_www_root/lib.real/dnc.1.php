<?php
	// Version 1.0.0
	// The Do Not Call class

//this assumes that this library is being used on IBM
//or in a location where the DNC databases are located
require_once("/virtualhosts/site_config/server.cfg.php");
require_once("error.2.php");

class DNC_1
{
	var $dnc_db = 'donotcall';
	var $sql;
	
	function DNC_1()
	{
		global $sql;
		$this->sql = $sql;
	}

	function Do_Not_Call($number, $table = NULL)
	{
		$number = preg_replace ("/\D+/", "", $number);
		
		if(strlen($number) != 10)
		{
			return new Error_2("Number specified not 10 digits");
		}

		if($table == NULL)
		{
			require_once("scrubber/db.scrubber.php");
			require_once("scrubber/lib.scrubber.php");
			return is_phone_on_natl_donotcall($sql, $number);
		}
		else
		{
			$database = $this->dnc_db;
		}
		
		$select = "select count(*) as count from
				   {$table} where dnc_phone = '{$number}'";

		//echo $database . "\n";
		//echo $select;
		
		$result = $this->sql->Query ($database, $select);

		if(Error_2::Error_Test($result, FALSE))
		{
			return $result;
		}
		
		$row = $this->sql->Fetch_Object_Row ($result);
			
		return $row->count;
	}

	function Add_Do_Not_Call($number, $table)
	{
		$result = $this->Do_Not_Call($number, $table);
		if(Error_2::Error_Test ($result, FALSE))
		{
			return $result;
		}
		
		if($result == 0)
		{
			$insert = "insert into {$table}
					   (dnc_phone)
					   values
					   ('{$number}')";
			
			$result = $this->sql->Query ($this->dnc_db, $insert);

			if(Error_2::Error_Test ($result, FALSE))
			{
				return $result;
			}
		}
		else
		{
			return new Error_2("This number already exists in the {$table} DNC database");
		}
	}
}

?>