<?php
	// A wrapper class for MySQL
	
	/* PROTOTYPES
		bool MySQL (string Read_Host, string Write_Host, string Login, string Password, [int Port, [string Trace_Code]])
		resource Read_Connect ([string Trace_Code])
		resource Write_Connect ([string Trace_Code])
		resource Query (string Database, string Query, [string Trace_Code])
		bool Free_Result (resource Result_Id)
		int Row_Count (resource Result_Id)
		int Insert_Id ()
		object Fetch_Object_Row (resource Result_Id)
		array Fetch_Array_Row (resource Result_Id)
		mixed Fetch_Column (resource Result_Id, mixed Column)
		object Get_Table_Info (string Database, string Table, [string Trace_Code])
		object Get_Table_List (string Database, [string Trace_Code])
		object Get_Database_List ([string Trace_Code])
		object Get_Version (void)
	*/

	require_once ("error.1.php");

	class MySQL_2
	{
		// connection
		var $read_host ;
		var $write_host ;
		var $login;
		var $password;
		var $port;
		
		// resources
		var $result_id;
		var $read_link_id;
		var $write_link_id;

		function MySQL_2 ($read_host, $write_host, $login, $password, $port = 3306)
		{
			// Assign the connection parameters
			$this->read_host = $read_host;
			$this->write_host = $write_host;
			$this->login = $login;
			$this->password = $password;
			$this->port = $port;

			// Provide a return value
			return TRUE;
		}

		function Read_Connect ($trace_code = NULL)
		{
			// Get the link
			if (FALSE === ($this->read_link_id = @mysql_connect ($this->read_host.":".$this->port, $this->login, $this->password)))
			{
				// Could not establish a link to the server
				// Create the error class
				$error = new Error_1 ();
				$error->trace_code = $trace_code."\t".__FILE__."->".__LINE__."\n";
				$error->link_id = $this->read_link_id;

				return $error;
			}

			// Return the resource
			return $this->read_link_id;
		}

		function Write_Connect ($trace_code = NULL)
		{
			// Get the link
			if (FALSE === ($this->write_link_id = @mysql_connect ($this->write_host.":".$this->port, $this->login, $this->password)))
			{
				// Create the error class
				$error = new Error_1 ();
				$error->trace_code = $trace_code."\t".__FILE__."->".__LINE__."\n";
				$error->link_id = $this->write_link_id;
				
				return $error;
			}

			// Return the resource
			return $this->write_link_id;
		}

		function Query ($database, $query, $trace_code = NULL)
		{
			// Determine the query type
			if (preg_match ("/^select/i", $query))
			{
				// A read connection
				$temp_link_id = $this->read_link_id;
			}
			else
			{
				// A write connection
				$temp_link_id = $this->write_link_id;
			}

			// Set up the database
			if (!@mysql_select_db ($database, $temp_link_id))
			{
				// Create the error class
				$error = new Error_1 ();
				$error->trace_code = $trace_code."\t".__FILE__."->".__LINE__."\n";
				$error->link_id = $temp_link_id;
				
				return $error;
			}

			// Query the database
			if (FALSE === ($this->result_id = @mysql_query ($query, $temp_link_id)))
			{
				// Create the error class
				$error = new Error_1 ();
				$error->trace_code = $trace_code."\t".__FILE__."->".__LINE__."\n";
				$error->link_id = $temp_link_id;
				$error->query = $query;
				
				return $error;
			}

			// Return the resource
			return $this->result_id;
		}

		function Free_Result ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_free_result ($result_id) : FALSE);
		}
		
		function Row_Count ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_num_rows ($this->result_id) : 0);
		}

		function Insert_Id ()
		{
			return @mysql_insert_id ($this->write_link_id);
		}

		function Fetch_Object_Row ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_fetch_object ($result_id) : FALSE);
		}
		
		function Fetch_Array_Row ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_fetch_assoc ($result_id) : FALSE);
		}
		
		function Fetch_Column ($result_id, $column)
		{
			return (is_resource ($result_id) ? @mysql_result ($result_id, $column) : FALSE);
		}

		function Get_Table_Info ($database, $table, $trace_code = NULL)
		{
			// Get the table information
			$query = "describe ".$table;
			$resource_id = $this->Query ($database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");
			
			// Create the object
			$field_list = new stdClass ();

			// Walk the results and build the return object
			while (FALSE !== ($row_data = $this->Fetch_Object_Row ($resource_id)))
			{
				$field_list->{$row_data->Field} = $row_data;
			}
			
			// Return the fields as an object
			return $field_list;
		}
		
		function Get_Table_List ($database, $trace_code = NULL)
		{
			// Get the list of tables
			$query = "show tables";
			$this->Query ($database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");
			
			// Create the name of default element
			$element = "Tables_in_".$database;
			
			// Create the object to return
			$table_list = new stdClass ();
			
			// Walk the result set and push into the return object
			while (FALSE !== ($row_data = $this->Fetch_Object_Row ()))
			{
				$table_list->{$row_data->$element} = TRUE;
			}
			
			// Return the tables as an object
			return $table_list;
		}
		
		function Get_Database_List ($trace_code = NULL)
		{
			// Get the list
			$query = "show databases";
			$this->Query ($database, $query, $trace_code."\t".__FILE__." -> ".__LINE__."\n");
			
			$db_list = new stdClass ();
			
			while ($temp = $this->Fetch_Row ())
			{
				$db_list->{$row_data->Database} = TRUE;
			}
			
			// Return the list of Databases as an object
			return $db_list;
		}
		
		function Get_Version ()
		{
			$version = new stdClass ();
			
			$version->api = 2;
			$version->feature = 0;
			$version->bug = 0;
			$version->version = $version->api.".".$version->feature.".".$version->bug;
			
			return $version;
		}
	}
?>
