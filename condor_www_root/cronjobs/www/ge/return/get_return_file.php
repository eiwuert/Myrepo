<?php
include_once("/virtualhosts/lib/ftp.2.php");

class Get_Return_File extends FTP
{
	
	function Get_Return_File ()
	{
		parent::FTP();
		
		
	}
	
	function process_File ($ftp, $show_success=false)
	{
		
		// FTP process
		if ($ftp->action)
		{
			if ($show_success)
			{
				$this->error_array[] = "Attempting file retrieval . . .\n";
			}
			if ($this->do_Get ($ftp, $show_success))
			{
				if ($show_success)
				{
					$this->error_array[] = "Return file(s) have been retrieved.\n";
				}
			}
			else
			{
				$this->error_array[] = "Unable to FTP.\n";	
			}
		}
		else
		{
			$this->error_array[] = "FTP argument is not present. Will attempt to find files in local directory.\n";	
		}
				
		// make array of received files and files in the directory.
		$list_array = array();
		$file_dir = $this->current_local_dir;
		if (false !== ($curr_dir = opendir ($file_dir)))
		{
	 		$i = 0;
			while (false !== ($file = readdir ($curr_dir)))
			{
				if (is_file ($file))
				{
					$list_array[$i] = $file;
				}
				$i++;
			}
			if (count ($list_array) < 1)
			{
				$this->error_array[] = "No files to process.\n";
				return false;	
			}
		}
		else
		{
			$this->error_array[] = "Unable to find or open " . $file_dir . " directory.\n";	
		}
		
		// un-gpg files and set successful file list to an array
		$passwd = "thisisourfirstprivatekey";
		$gpg_path = "/usr/bin/gpg";
		$target_path = $ftp->file_path;
		$decrypted_file_array = array ();
		foreach ($list_array as $gpg_file)
		{
			// GPG decryption
			$unencrypted_file = $gpg_file . "_";
			$encrypted_file = $gpg_file;
			shell_exec("echo " . $passwd . " | " . $gpg_path . " --passphrase-fd 0 --yes -o " . $target_path . $unencrypted_file . " -d " . $target_path . $encrypted_file);
 			
			//verify that file has been decrypted and add to array
			if (file_exists ($target_path . $unencrypted_file))
			{
				unlink($target_path . $encrypted_file);
				$decrypted_file_array[] = $unencrypted_file;
			}
			else
			{
				$this->error_array[] = $encrypted_file . " cannot be decrypted and processed. Another attempt will be made during the next run.\n";
			}
		}
		// don't need anymore
		unset ($list_array);
		
		// parse received files and set data to object array
		$order_obj[] = new stdClass();
		foreach ($decrypted_file_array as $parse_file)
		{
			if($order_array = file($target_path . $parse_file))
			{
				foreach ($order_array as $key => $order)
				{
					//$order_obj[$key]->first_name = substr ($order, 0, 14);
					//$order_obj[$key]->middle_initial = substr ($order, 15, 1);
					//$order_obj[$key]->last_name = substr ($order, 16, 20);
					//$order_obj[$key]->address = substr ($order, 36, 25);
					//$order_obj[$key]->city = substr ($order, 61, 20);
					//$order_obj[$key]->state = substr ($order, 81, 2);
					//$order_obj[$key]->zip = substr ($order, 83, 5);
					//$order_obj[$key]->zip_4 = substr ($order, 88, 4);
					//$order_obj[$key]->phone_number = substr ($order, 92, 10);
					$order_obj[$key]->enroll_date = substr ($order, 102, 8);
					//$order_obj[$key]->cancel_date = substr ($order, 110, 8);
					// customer partner ID is split into the following
					//$order_obj[$key]->site_code = substr ($order, 118, 8);
					$order_obj[$key]->order_id = substr ($order, 126, 10);
					
					//$order_obj[$key]->pmg_member_id = substr ($order, 136, 11);
					//$order_obj[$key]->pmg_promo_code = substr ($order, 147, 9);
					//$order_obj[$key]->product_id = substr ($order, 156, 3);
					$order_obj[$key]->disposition_code = substr ($order, 159, 3); 
				}	
				
				// Set batch process status ???
			}
		}
		
		// display result data
		if ($show_success)
		{
			print_r ($order_obj);	
		}
		
		// set return file flag in batch table
		if ($db->db_insert)
		{
			// Hit Stats
			
			
			
		}
		
		
		// output any errors
		if (count ($this->error_array) > 0)
		{
			echo implode ("\n", $this->error_array);
		}
	
		return true;
	}
}
?>
