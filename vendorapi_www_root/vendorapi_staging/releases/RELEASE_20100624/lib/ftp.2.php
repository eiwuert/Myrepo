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
	var $current_local_dir;
	
	function FTP()
	{
		$this->conn_id = false;
		$this->login_result = false;
		$this->current_local_dir = getcwd ();
		
		return true;
	}
	
	function do_Connect ($ftp_obj)
	{
		// connection info
		$this->server = $ftp_obj->server;
		$this->user_name = $ftp_obj->user_name;
		$this->user_password = $ftp_obj->user_password;
		
		// check connection
		if ((!$this->conn_id) || (!$this->login_result))
		{
			$this->conn_id = ftp_connect ($this->server);

			// login with username and password
			$this->login_result = ftp_login ($this->conn_id, $this->user_name, $this->user_password);
			
			// Set to Pasv Mode - FTP Connects are initated by Client not Server
			ftp_pasv($this->conn_id, TRUE);
		}
		
		// check connection after attempting login
		if ((!$this->conn_id) || (!$this->login_result))
		{
			$this->error_array[] = "FTP connection has failed! Attempted to connect to " . $this->server . " for user " . $this->user_name . ".\n";
			return false;
		}
		else if ($show_success)
		{
			$this->error_array[] = "Connected to " . $this->server . ", for user " . $this->user_name . ".\n";
		}
		
		return true;
	}
	
	function do_Put ($ftp_obj, $show_success=false, $stay_connected=false)
	{
		// verify connection
		if (!$this->do_Connect($ftp_obj))
		{
			return false;
		}
		
		// upload the file(s)
		if (isset ($ftp_obj->regex_file))
		{	
			$file_prop_array =  $this->get_Local_List ();
			foreach ($file_prop_array as $file_prop)
			{
				if (preg_match ($ftp_obj->regex_file, $file_prop->name) && ($file_prop->type == "-"))
				{	
					$this->put_File ($file_prop->name);
					if ($ftp_obj->remove_source && !unlink ($file_prop->name))
					{
						$this->error_array[] = "Unlinking of  " . $file_prop->name  . " from source has failed!\n";
					}
				}
			}
		}
		elseif (isset ($ftp_obj->file))
		{
			if (gettype ($ftp_obj->file) == "string")
			{
				$this->put_File ($ftp_obj->file);
			}
			else if (gettype ($ftp_obj->file) == "array")
			{
				$file_array_count = count ($ftp_obj->file);
				for ($i = 0 ; $i < $file_array_count; $i++)
				{
					$this->put_File ($ftp_obj->file[$i]);
					if ($ftp_obj->remove_source && !unlink ($ftp_obj->file[$i]))
					{
						$this->error_array[] = "Unlinking of  " . $ftp_obj->file[$i]  . " from source has failed!\n";
					}
				}
			}
		}

		// close the FTP stream
		if (!$stay_connected)
		{
			ftp_close ($this->conn_id);
		}
		
		return true;
	}
	
	function do_Get ($ftp_obj, $show_success=false, $stay_connected=false)
	{
		// verify connection
		if (!$this->do_Connect($ftp_obj))
		{
			return false;
		}
		
		// download the file(s)
		if (isset ($ftp_obj->regex_file))
		{	
			$file_prop_array =  $this->get_List ();
			foreach ($file_prop_array as $file_prop)
			{
				if (preg_match ($ftp_obj->regex_file, $file_prop->name) && ($file_prop->type == "-"))
				{	
					$this->get_File ($file_prop->name);
					if ($ftp_obj->remove_source && !ftp_delete($this->conn_id, $file_prop->name))
					{
						$this->error_array[] = "Deletion of  " . $file_prop->name  . " from source has failed!\n";
					}
				}
			}
		}
		elseif (isset ($ftp_obj->file))
		{
			if (gettype ($ftp_obj->file) == "string")
			{
				$this->get_File ($ftp_obj->file);
			}
			else if (gettype ($ftp_obj->file) == "array")
			{
				$file_array_count = count ($ftp_obj->file);
				for ($i = 0 ; $i < $file_array_count; $i++)
				{
					$this->get_File ($ftp_obj->file[$i]);
					if ($ftp_obj->remove_source && !ftp_delete($this->conn_id, $ftp_obj->file[$i]))
					{
						$this->error_array[] = "Deletion of  " . $ftp_obj->file[$i]  . " from source has failed!\n";
					}
				}
			}
		}
		
		// close the FTP stream
		if (!$stay_connected)
		{
			ftp_close ($this->conn_id);
		}
		
		return true;
	}
	
	function do_Cd ($dir)
	{
		if (!ftp_chdir ($this->conn_id, $dir))
		{
			return false;	
		}
		
		return true;
	}
	
	function do_Local_Cd ($dir)
	{
		if (!chdir ($dir))
		{
			return false;	
		}
		else
		{
			$this->current_local_dir = getcwd ();
		}
		
		return true;
	}
	
	function get_List ()
	{
		$list_object[] = new stdClass();
		$list_array = ftp_nlist($this->conn_id, "");
		$raw_list_array = ftp_rawlist($this->conn_id, "");
		
		foreach ($list_array as $key => $value)
		{
			$list_object[$key]->id = $key;
			$list_object[$key]->name = $value;
			$list_object[$key]->type = substr ($raw_list_array[$key], 0, 1);	// first character of file properties, d =directory, l = link, - = file
			$list_object[$key]->properties = $raw_list_array[$key];
		}
		
		return $list_object;
	}
	
	function get_Local_List ()
	{
		$list_object[] = new stdClass();
		$dir_handle = opendir ($this->current_local_dir);
	 	
		$i = 0;
		while (false !== ($file = readdir ($dir_handle)))
		{
			$list_object[$i]->id = $i;
			$list_object[$i]->name = $file;
			if (is_dir ($file))
			{
				$list_object[$i]->type = "d";
			}
			elseif (is_link ($file))
			{
				$list_object[$i]->type = "l";
			}
			elseif (is_file ($file))
			{
				$list_object[$i]->type = "-";
			}
			$i++;
		}
		
		return $list_object;
	}
	
	// Internal Methods
	function put_File ($src_dest_pair_string, $mode=false)
	{
		$set_mode = FTP_ASCII;
		if ($mode)
		{
			$set_mode = FTP_BINARY;		
		}
		
		$src_dest_array = explode (",", $src_dest_pair_string);
		if (!isset ($src_dest_array[1]))
		{
			$src_dest_array[1] = $src_dest_array[0];	
		}
		// check upload status
		if (!ftp_put ($this->conn_id, $src_dest_array[1], $src_dest_array[0], $set_mode))
		{
			$this->error_array[] = "FTP upload of " . $src_dest_array[0]  . " has failed!\n";
		}
		else if ($show_success)
		{
			$this->error_array[] =  "Uploaded " . $src_dest_array[0]  . " to " . $this->server . " as " . $src_dest_array[1]  . "\n";
		}
	}
	
	function get_File ($src_dest_pair_string, $mode=false)
	{
		$set_mode = FTP_ASCII;
		if ($mode)
		{
			$set_mode = FTP_BINARY;		
		}
		
		$src_dest_array = explode (",", $src_dest_pair_string);
		if (!isset ($src_dest_array[1]))
		{
			$src_dest_array[1] = $src_dest_array[0];	
		}
		// check upload status
		if (!ftp_get ($this->conn_id, $src_dest_array[0], $src_dest_array[1], $set_mode))
		{
			$this->error_array[] = "FTP upload of " . $src_dest_array[0]  . " has failed!\n";
		}
		else if ($show_success)
		{
			$this->error_array[] =  "Uploaded " . $src_dest_array[0]  . " to " . $this->server . " as " . $src_dest_array[1]  . "\n";
		}
	}
}
?>
