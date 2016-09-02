<?php
	// Version 3.2.0
	// A wrapper class for MySQL

	/* UPDATES
		Features:
			2: 2003-04-02 00:25 - Rodric Glaser
				- Modified for master/slave queries
				- Includes minimal query profiling code
				
			1: 2003-01-03 ??:?? - Paul Strange
				- Added the Affected_Row_Count function to return the number of affected rows by a query
			
		Bugs:
			1.1: ??? - ???
				Unknown fix
	*/

	/* PROTOTYPES
		bool MySQL ()
		resource Connect (string Connection_Type, string Host, string Login, string Password, [int Port, [string Trace_Code]])
		resource Query (string Database, string Query, [string Trace_Code])
		bool Free_Result (resource Result_Id)
		int Row_Count (resource Result_Id)
		int Affected_Row_Count ()
		int Insert_Id ()
		object Fetch_Object_Row (resource Result_Id)
		array Fetch_Array_Row (resource Result_Id)
		mixed Fetch_Column (resource Result_Id, mixed Column)
		object Get_Table_Info (string Database, string Table, [string Trace_Code])
		object Get_Table_List (string Database, [string Trace_Code])
		object Get_Database_List ([string Trace_Code])
		object Get_Version (void)
	*/

	require_once ("error.2.php");
	require_once ("debug.1.php");

	define ('MYSQL_3_QUERY_MASTER', 1);
	define ('MYSQL_3_QUERY_SLAVE', 2);

	class MySQL_3
	{
		// resources
		var $read_link_id; //!< The link to the read server
		var $write_link_id; //!< The link to the write server
		var $host;

		function MySQL_3 ()
		{
			$this->connect_time = 0;
			$this->query_time = 0;
			$this->query_count = 0;

			// Provide a return value
			return TRUE;
		}

		//! Establish a connection to the database server
		/*!
			If you need to pass the port, use the following syntax: $host = host:port
		*/
		function Connect ($type, $host, $login, $password, $trace_code = NULL)
		{
			// Start the timer.
			list ($start_sec, $start_msec) = explode (" ", microtime ());

			$this->host = $host;

			// Get the link
			if (FALSE === ($link_id = mysql_connect ($host, $login, $password)))
			{
				// Could not establish a link to the server
				// Create the error class
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Mysql connect failed to ".$host." as ".$login.".";
				$error->fatal = FALSE;

				return $error;
			}

			switch (strtolower ($type))
			{
				case "read":
					$this->read_link_id = $link_id;
					break;

				case "write":
					$this->write_link_id = $link_id;
					break;

				default:
					$this->read_link_id = $link_id;
					$this->write_link_id = $link_id;
					break;
			}

			// End the timer.
			list ($end_sec, $end_msec) = explode (" ", microtime ());

			$sec = (((float)$end_sec + (float)$end_msec) - ((float)$start_sec + (float)$start_msec));
			
			$this->connect_time += $sec;

			// Return the resource
			return $link_id;
		}

		function Is_Connected()
		{
			return (isset($this->read_link_id) || isset($this->write_link_id));
		}

		// sorry, i added this function originally, which does not conform to the class's naming convention,
		// so i've left it in here deprecated... once we're sure nothing that relies on this function it can go away
		//DO NOT USE THIS FUNCTION, USE Is_Connected!!!
		function IsConnected()
		{
			return $this->Is_Connected();
		}


		function Query ($database, $query, $trace_code = NULL, $type = MYSQL_3_QUERY_MASTER)
		{
			// Start the timer.
			list ($start_sec, $start_msec) = explode (" ", microtime ());

			// Determine the query type

			switch ($type)
			{
				case MYSQL_3_QUERY_SLAVE:
					$temp_link_id = $this->read_link_id;
					break;

				case MYSQL_3_QUERY_MASTER:
				default:
					$temp_link_id = $this->write_link_id;
					break;
			}

			// Set up the database
			if (!@mysql_select_db ($database, $temp_link_id))
			{
				// Create the error class
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Select DB (".$database.") failed: ".mysql_errno ($temp_link_id)." -> ".mysql_error ($temp_link_id);
				$error->database = $database;
				$error->link_id = $temp_link_id;
				$error->fatal = FALSE;

				return $error;
			}

			// Query the database
			if (FALSE === ($result_id = @mysql_query ($query, $temp_link_id)))
			{
				// Create the error class
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "SQL Error: ".mysql_errno ($temp_link_id)." -> ".mysql_error ($temp_link_id);
				$error->database = $database;
				$error->sql_errno = mysql_errno ($temp_link_id);
				$error->link_id = $temp_link_id;
				$error->query = $query;

				return $error;
			}

			// End the timer.
			list ($end_sec, $end_msec) = explode (" ", microtime ());

			$sec = (((float)$end_sec + (float)$end_msec) - ((float)$start_sec + (float)$start_msec));

			$this->query_count++;
			$this->query_time += $sec;

			// Return the resource
			return $result_id;
		}

		function Free_Result ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_free_result ($result_id) : FALSE);
		}

		function Row_Count ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_num_rows ($result_id) : 0);
		}

		function Affected_Row_Count ()
		{
			return @mysql_affected_rows ($this->write_link_id);
		}

		function Insert_Id ()
		{
			return @mysql_insert_id ($this->write_link_id);
		}

		function Seek_Row ($result_id,$row_id)
		{
			if ( is_resource ($result_id) ) {
				$n = @mysql_num_rows ($result_id);
				$row_id = ($row_id < 0) ? 0 : ($row_id > $n) ? $n : $row_id;
				return @mysql_data_seek ($result_id,$row_id);
			} else {
				return FALSE;
			}
		}

		function Fetch_Object_Row ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_fetch_object ($result_id) : FALSE);
		}

		function Fetch_Array_Row ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_fetch_assoc ($result_id) : FALSE);
		}

		function Fetch_Row ($result_id)
		{
			return (is_resource ($result_id) ? @mysql_fetch_row ($result_id) : FALSE);
		}

		function Fetch_Column ($result_id, $column)
		{
			return (is_resource ($result_id) ? @mysql_result ($result_id, $column) : FALSE);
		}

		function Get_Table_Info ($database, $table, $trace_code = NULL, $type = MYSQL_3_QUERY_SLAVE)
		{
			// Get the table information
			$query = "describe ".$table;
			$resource_id = $this->Query ($database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__), $type);

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

		function Get_Table_List ($database, $trace_code = NULL, $type = MYSQL_3_QUERY_SLAVE)
		{
			// Get the list of tables
			$query = "show tables";
			$result = $this->Query ($database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__), $type);
			Error_2::Error_Test ($result);

			// Create the name of default element
			$element = "Tables_in_".$database;
			
			// Create the object to return
			$table_list = new stdClass ();

			// Walk the result set and push into the return object
			while (FALSE !== ($row_data = $this->Fetch_Object_Row ($result)))
			{
				$table_list->{$row_data->$element} = TRUE;
			}

			/*
			$result = mysql_list_tables($database, $this->read_link_id);

			while (FALSE !== ($row_data = $this->Fetch_Array_Row($result)))
			{
				$table_list->{$row[0]} = TRUE;
			}
			*/

			// Return the tables as an object
			return $table_list;
		}
		
		function Get_Database_List ($trace_code = NULL, $type = MYSQL_3_QUERY_SLAVE)
		{
			// Get the list
			$query = "show databases";
			$result = $this->Query ("mysql", $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			if (Error_2::Error_Test ($result))
			{
				return $result;
			}
			
			$db_list = new stdClass ();
			
			while ($row = $this->Fetch_Object_Row ($result))
			{
				$db_list->{$row->Database} = TRUE;
			}
			
			// Return the list of Databases as an object
			return $db_list;
		}

		function Get_Total_Time ()
		{
			return $this->connect_time + $this->query_time;
		}

		function Get_Error ()
		{
			return @mysql_error ($this->write_link_id);
		}

		function Get_Errno ()
		{
			return @mysql_errno ($this->write_link_id);
		}
	}
?>
