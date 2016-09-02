<?php
/* FTP Handler Class
  Handles Various FTP functions
  
  Use:
  
  Arguments: 

  Examples:
*/

	// Version 1.0.0
	
	/* DESIGN TYPE
		static
	*/

	/* UPDATES
		Features:
			Provides FTP functionality.

		Bugs:
	*/

	/* PROTOTYPES
		bool FTP ()
		string do_Put ($ftp_obj, $show_success=false)
		array error_array

	*/
	
	/* OPTIONAL CONSTANTS
	*/

	/* SAMPLE USAGE
	do_Put ()
		Arguments: 

  		Examples:

	*/

class FTP
{
	var $server;
	var $user_name;
	var $user_password;
	var $file;
	var $error_array;
	var $conn_id;
	var $login_result;
	
	function FTP()
	{
		return true;
	}
	
	function do_Get ()
	{
		return true;
	}
	
	function do_Put ($ftp_obj, $show_success=false)
	{
		// connection info
		$this->server = $ftp_obj->server;
		$this->user_name = $ftp_obj->user_name;
		$this->user_password = $ftp_obj->user_password;
		$this->file = $ftp_obj->file;
		
		$this->conn_id = ftp_connect ($this->server);

		// login with username and password
		$this->login_result = ftp_login ($this->conn_id, $this->user_name, $this->user_password);

		// Set to Pasv Mode - FTP Connects are initated by Client not Server
		ftp_pasv($this->conn_id, TRUE);

		// check connection
		if ((!$this->conn_id) || (!$this->login_result))
		{
			$this->error_array[] = "FTP connection has failed! Attempted to connect to " . $this->server . " for user " . $this->user_name . ".\n";
		}
		else if ($show_success)
		{
			$this->error_array[] = "Connected to " . $this->server . ", for user " . $this->user_name . ".\n";
		}

		// upload the file
		if (gettype ($this->file) == "string")
		{
			$src_dest_array = explode (",", $this->file);
			if (!isset ($src_dest_array[1]))
			{
				$src_dest_array[1] = $src_dest_array[0];	
			}

			// check upload status
			if (!ftp_put ($this->conn_id, $src_dest_array[1], $src_dest_array[0], FTP_ASCII))
			{
				$this->error_array[] = "FTP upload of " . $src_dest_array[0] . " has failed!\n";
			}
			else if ($show_success)
			{
				$this->error_array[] = "Uploaded " . $src_dest_array[0] . " to " . $this->server . " as " . $src_dest_array[1] . "\n";
			}
		}
		else if (gettype ($this->file) == "array")
		{
			//$file_array = explode (",", $this->file);
			$file_array_count = count ($this->file);
			for ($i = 0 ; $i < $file_array_count; $i++)
			{
				$src_dest_array = explode (",", $this->file[$i]);
				if (!isset ($src_dest_array[1]))
				{
					$src_dest_array[1] = $src_dest_array[0];	
				}
				// check upload status
				if (!ftp_put ($this->conn_id, $src_dest_array[1], $src_dest_array[0], FTP_ASCII))
				{
					$this->error_array[] = "FTP upload of " . $src_dest_array[0]  . " has failed!\n";
				}
				else if ($show_success)
				{
					$this->error_array[] =  "Uploaded " . $src_dest_array[0]  . " to " . $this->server . " as " . $src_dest_array[1]  . "\n";
				}
			}
		}

		// close the FTP stream
		ftp_close ($this->conn_id);
		
		return true;
	}
}
?>
