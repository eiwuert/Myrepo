<?php
	// Version 2.1.1
	// A tool to handle sessions
	// He said tool

	/* UPDATES
		Features:
			1: 

		Bugs:
			1: 2003-02-17 - Paul Strange
				Added slashes to the data when writing to prevent blow ups when special chars are added.
	*/

	/* PROTOTYPES
		bool Session (string Database, string Table, string Read_Host, string Write_Host, string Login, string Password, [int Port, [string Trace_Code]])
		bool Open (string Save_Path, string Session_Name)
		bool Close (void)
		mixed Read (string Session_Id, [string Trace_Code])
		bool Write (string Session_Id, mixed Session_Info)
		bool Destroy (string Session_Id)
		bool Garbage_Collection (int Session_Life)
	*/
	
	/* REQUIRED TABLE STRUCTURE
		CREATE TABLE `session` (
		`session_id` varchar(33) NOT NULL default '',
		`modifed_date` timestamp(14) NOT NULL,
		`created_date` timestamp(14) NOT NULL,
		`session_info` longtext NOT NULL,
		PRIMARY KEY  (`session_id`)
		) TYPE=MyISAM; 
	*/

	class Session_2
	{
		var $database;
		var $table;

		function Session_2 ($sql_object, $session_database, $sesssion_table)
		{
			// Set the object properties
			$this->sql = $sql_object;
			$this->database = $session_database;
			$this->table = $sesssion_table;

			// All done
			return TRUE;
		}

		function Open ($save_path, $session_name) 
		{
			return true;
		}

		function Close ()
		{
			return true;
		}

		function Read ($session_id)
		{
			$trace_code = '';
			// Try to get the result set
			$query = "select session_info from ".$this->table." where session_id = '".$session_id."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");

			// Error checking
			Error_2::Error_Test ($result);

			// Determine if we found a row
			if ($this->sql->Row_Count ($result))
			{
				// Give the session information back
				return $this->sql->Fetch_Column ($result, "session_info");
			}
			// There were no rows
			else
			{
				// Start a new sesssion
				$query = "insert into ".$this->table." (session_id, created_date)values ('".$session_id."', NULL)";
				$result = $this->sql->Query ($this->database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");

				// Error checking
				Error_2::Error_Test ($result);
			}

			// Return nothing, because there was nothing
			return "";
		}

		function Write($session_id, $session_info)
		{
			$trace_code = '';
			// Update the db
			$query = "update ".$this->table." set session_info='".mysql_escape_string ($session_info)."' where session_id='".$session_id."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");

			// Error checking
			Error_2::Error_Test ($result);

			// All went well
			return TRUE;
		}

		function Destroy ($session_id, $trace_code=NULL)
		{
			$trace_code = '';
			// Blow it off the datase
			$query = "delete from ".$this->table." where session_id='".$session_id."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");

			// Error checking
			Error_2::Error_Test ($result);

			return TRUE;
		}

		function Garbage_Collection ($session_life)
		{
			// Not clear what to do here, so return true to make all happy
			return TRUE;
		}
	}
?>
