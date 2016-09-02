<?php
	// Version 3.0.0
	// A tool to handle sessions
	
	/* PROTOTYPES
		bool Session (string Database, string Table, string Read_Host, string Write_Host, string Login, string Password, [int Port, [string Trace_Code]])
		bool Session_Config (object & sql, string property_name, string site_name, string page_name, int promo_id, string promo_sub_code)
		bool Hit_Stat (object & sql, string name, mixed value = 1, bool unique = TRUE)
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

	require_once ("config.2.php");
	require_once ("setstat.1.php");

	class Session_3
	{
		var $database;
		var $table;

		function Session_3 ($sql_object, $session_database, $session_table)
		{
			// Set the object properties
			$this->sql = $sql_object;
			$this->database = $session_database;
			$this->table = $session_table;

			// All done
			return TRUE;
		}

		function Session_Config (&$sql, $property_name, $site_name, $page_name, $promo_id, $promo_sub_code, $batch_id = NULL)
		{
			// Identity Block (Session settings always override hand code)
			if (! is_object ($_SESSION ["config"]))
			{
				// Not in the session create the data
				$_SESSION["config"] = Config_2::Get_Site_Config ($property_name, $site_name, $page_name, $promo_id);
				
				if (preg_match("/^rc\./", $_SERVER ["SERVER_NAME"]))
				{
					$_SESSION ["config"]->stat_base = "rc_".$_SESSION ["config"]->stat_base;
				}
				
				$_SESSION ["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION ["config"]->site_id, $_SESSION ["config"]->vendor_id, $_SESSION ["config"]->page_id, $_SESSION ["config"]->promo_id, $promo_sub_code, $sql, $_SESSION ["config"]->stat_base, $_SESSION ["config"]->promo_status, $batch_id);
				
				$_SESSION ["promo_id"] = $_SESSION ["config"]->promo_id;
				$_SESSION ["promo_sub_code"] = $promo_sub_code;
				
				$_SESSION ["unique_stat"] = new stdClass ();
				
				return TRUE;
			}
			
			return FALSE;
		}

		function Hit_Stat (&$sql, $name, $value = 1, $unique = TRUE)
		{
			if (! ($unique && isset ($_SESSION ["unique_stat"]->$name)))
			{
				Set_Stat_1::Set_Stat ($_SESSION ["stat_info"]->block_id, $_SESSION ["stat_info"]->tablename, $sql, $_SESSION ["config"]->stat_base, $name, $value);
				$_SESSION ["unique_stat"]->$name = TRUE;
				
				return TRUE;
			}
			
			return FALSE;
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
				$query = "insert into ".$this->table." (session_id, created_date) values ('".$session_id."', NULL)";
				$result = $this->sql->Query ($this->database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");

				// Error checking
				Error_2::Error_Test ($result);
			}

			// Return nothing, because there was nothing
			return "";
		}

		function Write($session_id, $session_info)
		{
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
