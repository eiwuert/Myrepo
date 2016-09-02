<?php
	// A wrapper class for MySQL
	
	class MySQL
	{
		// connection
		var $read_host ;
		var $write_host ;
		var $login;
		var $password;
		var $database;
		var $port;
		
		// error handling
		var $failed_line;
		var $failed_file;
		
		// resources
		var $result_id;
		var $read_link_id;
		var $write_link_id;
		
		// general
		var $table_list;

/*
	Name: MySQL
	Return Value: boolean (always true)
	Purpose: Class constructor
	Passed Values:
		host->string The name of the database server to connect to.
		login->string The username for the database connection
		password->string The password needed to connect
		port->integer The port to use for the connection
*/
		function MySQL ($read_host, $write_host, $login, $password, $database, $port = 3306, $trace_code = NULL)
		{
			// Assign the connection parameters
			$this->read_host = $read_host;
			$this->write_host = $write_host;
			$this->login = $login;
			$this->password = $password;
			$this->database = $database;
			$this->port = $port;

			// Establish the connection
			$this->Read_Connect ($trace_code."\t".__FILE__."->".__LINE__."\n");
			$this->Write_Connect ($trace_code."\t".__FILE__."->".__LINE__."\n");
			
			// Select the database for use
			mysql_select_db ($this->database, $this->write_link_id);
 			mysql_select_db ($this->database, $this->read_link_id);
			
			// Grab the valid tables
			$this->Get_Tables ();

			// Free the memory
			$this->Free_Result ();

			return TRUE;
		}

/*
	Name: Wrapper
	Return Value: object
	Purpose: Query the database and return the data as an object
	Passed Values:
		query->string The query to run against the database
		index_column->string (optional) The column name to index the object by
		line->int (optional) The line number of the file that generated the query for error handling purposes
		file->string (optional) The name of the file that generated the query for error handling purposes
	Comments:
		This will return a structure of the following structure:
		Result_Set [Index_Column]->Column_Name = Value
*/
		function Wrapper ($query, $index_column = "", $trace_code = NULL)
		{
			// Create the return value
			$result_set = array ();

			// Establish the error handling
			$this->Set_Error_Location ($trace_code."\t".__FILE__."->".__LINE__."\n");

			// Run the query
			$this->Query ($query, $trace_code."\t".__FILE__."->".__LINE__."\n");

			if ($this->Row_Count ())
			{
				while ($temp = $this->Fetch_Row ())
				{
					// Place this row into the sub object
					if (strlen ($index_column))
					{
						$result_set [$temp->$index_column] = $temp;
					}                                         
					else
					{
						$result_set [] = $temp;
					}
				}
			}

			// Free the memory
			$this->Free_Result ();

			// No errors, purge error handling
			$this->Clear_Error_Location ();

			// Return the data from the database
			return $result_set;
		}
		
		function First_Row ($query, $trace_code = NULL)
		{
			$result_set = $this->Wrapper ($query, "", $trace_code."\t".__FILE__."->".__LINE__."\n");

			return $result_set [0];
		}

		function Is_Table ($table_name, $trace_code = NULL)
		{
			// Test for the table and return
			return strlen ($this->table_list->$table_name);
		}
		
		function Is_Column ($table_name, $column_name)
		{
			$fields = $this->Get_Field_Names ($table_name);
			
			foreach ($fields as $field_name)
			{
				if ($field_name == $column_name)
				{
					return TRUE;
				}
			}
			
			return FALSE;
		}

/*
	Name: Connect
	Return Value: resource
	Purpose: Establish a connection to the database
	Passed Values:
		line->int (optional) The line number of the file that generated the query for error handling purposes
		file->string (optional) The name of the file that generated the query for error handling purposes
*/
		function Read_Connect ( $trace_code = NULL)
		{
			// Establish the error handling
			$this->Set_Error_Location ($trace_code."\t".__FILE__."->".__LINE__."\n");

			// Get the link
			if (FALSE === ($this->read_link_id = @mysql_pconnect ($this->read_host.":".$this->port, $this->login, $this->password)))
			{
				$this->Report_Error ("Unable to establish a connection to: ".$this->read_host.", ".$_SERVER ["SERVER_NAME"]." -- ".$this->database);
			}

			// No errors, purge error handling
			$this->Clear_Error_Location ();

			// Return the resource
			return $this->read_link_id;
		}

		function Write_Connect ( $trace_code = NULL)
		{
			// Establish the error handling
			$this->Set_Error_Location ($trace_code."\t".__FILE__."->".__LINE__."\n");

			// Get the link
			if (FALSE === ($this->write_link_id = @mysql_pconnect ($this->write_host.":".$this->port, $this->login, $this->password)))
			{
				$this->Report_Error ("Unable to establish a connection to: ".$this->write_host.", ".$_SERVER ["SERVER_NAME"]." -- ".$this->database);
			}

			// No errors, purge error handling
			$this->Clear_Error_Location ();

			// Return the resource
			return $this->write_link_id;
		}

/*
	Name: Query
	Return Value: resource
	Purpose: Query the database and return a resource to the result
	Passed Values:
		query->string The query to run against the database
		line->int (optional) The line number of the file that generated the query for error handling purposes
		file->string (optional) The name of the file that generated the query for error handling purposes
*/
		function Query ($query, $trace_code = NULL)
		{
			// Establish the error handling
			$this->Set_Error_Location ($trace_code."\t".__FILE__."->".__LINE__."\n");

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

			// Query the database
			if (FALSE === ($this->result_id = @mysql_query ($query, $temp_link_id)))
			{
				$this->Report_Error (mysql_errno ($temp_link_id).': '.mysql_error ($temp_link_id), $query);
			}

			// No errors, purge error handling
			$this->Clear_Error_Location ();

			// Return the resource
			return $this->result_id;
		}

/*
	Name: Free_Result
	Return Value: boolean
	Purpose: Free the memory used by the query
*/
		function Free_Result ()
		{
			// Free the memory required by a query
			@mysql_free_result ($this->result_id);
			
			// Free the var
			unset ($this->result_id);

			// Return Success
			return TRUE;
		}

/*
	Name: Row_Count
	Return Value: integer
	Purpose: Determine the number of rows the query generated
*/
		function Row_Count ()
		{
			if (is_resource ($this->result_id))
			{
				$this->num_rows = @mysql_num_rows ($this->result_id);
			}

			// Return the value
			return $this->num_rows;
		}

/*
	Name: Insert_Id
	Return Value: integer
	Purpose: Get the auto_increment value from the database for the last insert
*/
		function Insert_Id ()
		{
			// Return the value
			return @mysql_insert_id ($this->write_link_id);
		}

/*
	Name: Fetch_Row
	Return Value: object
	Purpose: Get the next row in the result set
*/
		function Fetch_Row ($result_id = NULL)
		{
			if (is_null ($result_id))
			{
				$result_id = $this->result_id;
			}
			// Return the value
			return @mysql_fetch_object ($result_id);
		}

		function Get_Field_Names ($table)
		{
			// Create the object
			$field_list = new stdClass ();

			// Query for the fields
			$this->result_id = mysql_list_fields($this->database, $table, $this->read_link_id);

			// Determine how many fields we have
			$num_fields = mysql_num_fields($this->result_id);

			// Walk the list and push into the object
			for ($counter = 0; $counter < $num_fields; $counter++)
			{
				$field_name = mysql_field_name ($this->result_id, $counter);
				$field_list->$field_name  = $field_name;
			}

			// Free the memory
			$this->Free_Result ();

			return $field_list;
		}
		
		function Get_Tables ()
		{
			$query = "show tables";
			$this->Query ($query, "", "\t".__FILE__." -> ".__LINE__."\n");
			
			while ($temp = $this->Fetch_Row ())
			{
				foreach ($temp as $table_name)
				{
					$this->table_list->$table_name = $table_name;
				}
			}
			
			return TRUE;
		}

/*
	Name: Report_Error
	Return Value: void
	Purpose: Generate an error report when a failure in the db occurs
	Passed Values:
		Name->Type comment
		message->string The message that should be displayed in the report
		query->string (Optional) The query we were trying to use
*/
		function Report_Error ($message, $query = "")
		{
			$err_from = "dberror@sellingsource.com";
			$err_to = "dbadmin@sellingsource.com";
			$err_subject = "A database error has occured";
			$err_send = FALSE;
			$err_show = TRUE;

			// Generate an error report and then act according to configuration
			$err_text = "An error has been generated by the server.  Following is the debug information:\n".
				"  * Trace: \n".$this->failed_trace."\n\n".
				"  * Error: ".$message."\n";

			// If a query was used, show the query
			$err_text .= strlen ($query) ? "  * Query: ".$query."\n" : "";

			// Send the error as email
			if ($err_send)
			{
				mail($err_to, $err_subject, $err_text, "From: ".$err_from."\n");
			}

			// Show the error in the browser
			if ($err_show)
			{
				echo "<pre>".$err_text."</pre>";
			}

			// Punt and let the other team deal with the ball
			exit;
		}
		
/*
	Name: Set_Error_Location
	Return Value: boolean
	Purpose: Set the location that called the object for error reporting
	Passed Values:
		line->int (optional) The line number of the file that generated the query for error handling purposes
		file->string (optional) The name of the file that generated the query for error handling purposes
*/
		function Set_Error_Location ($trace_code)
		{
			// Check for value
			$this->failed_trace = strlen ($trace_code) ? $trace_code : "Trace code was not passed!";

			// Give something back
			return TRUE;
		}
		
/*
	Name: Clear_Error_Location
	Return Value: boolean
	Purpose: Clear the location values for error reporting
*/
		function Clear_Error_Location ()
		{
			// Clear the values for the failures
			unset ($this->failed_trace);

			// Give something back
			return TRUE;
		}
	}
?>
