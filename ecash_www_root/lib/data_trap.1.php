<?php

include_once ("/virtualhosts/lib/mysql.3.php");

class data_trap
{
	var $dt_sql;	// mysql connection
	var $db = "data_trap";
	var $tables;
	var $location;	// local, rc or live

	function data_trap ()
	{
		// test if local, rc or live
		if (strpos ($_SERVER ["HTTP_HOST"], ".tss") !== false)
		{
			$this->location = "local";
		}
		elseif (strpos ($_SERVER ["HTTP_HOST"], "rc.") !== false)
		{
			$this->location = "rc";
		}
		else
		{
			$this->location = "live";
		}

		// set connection variables accordingly
		$server = $this->location == "local" ? "ds001.ibm.tss" : "selsds001";

		// make the connection
		$this->dt_sql = new MySQL_3 ();
		$this->dt_sql->Connect ('', $server, 'sellingsource', 'password');

		$this->tables = $this->dt_sql->Get_Table_list ($this->db);
	}

	function insert_data ($table_name, $variable_name, $data)
	{
		$table_name = $this->location == "live" ? $table_name : $this->location."_".$table_name;
		if (is_array ($data) || is_object ($data))
		{
			$data = print_r ($data, true);
		}
		$table_flag = true;
		if (!isset ($this->tables->$table_name))
		{
			$table_flag = $this->create_table ($table_name);
		}
		if (!is_object ($table_flag))
		{
			$q = "INSERT INTO ".$table_name." (time_stamp, variable_name, data) VALUES (NOW(), '".addslashes ($variable_name)."', '".addslashes ($data)."')";
			$foobar = $this->dt_sql->Query ($this->db, $q);
		}
		else
		{
			echo "\n<!-- Data Trap Error: Could not create table '".$table_name."': \n".print_r ($table_flag, true)."\n-->\n\n";
		}
	}

	function create_table ($table_name)
	{
		$q = "CREATE TABLE `".$table_name."` (
  				`id` int(11) NOT NULL auto_increment,
  				`time_stamp` datetime NOT NULL default '0000-00-00 00:00:00',
				`variable_name` varchar(50) NOT NULL default ' ',
  				`data` text,
  				PRIMARY KEY  (`id`),
  				KEY `time_stamp` (`time_stamp`),
  				FULLTEXT KEY `data` (`data`)
			) TYPE=MyISAM AUTO_INCREMENT=1";
		$foobar = $this->dt_sql->Query ($this->db, $q);
	}
}

$data_trap = new data_trap ();
?>
