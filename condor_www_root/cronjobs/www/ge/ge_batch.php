<?php

	// Version 1.0.0
	// Extends FTP.1

	/* DESIGN TYPE
		static
	*/

	/* UPDATES
		Features:
			Takes data, compiles it into a report format, saves copy to DB and FTP's to GE.

		Bugs:
	*/

	/* PROTOTYPES
		bool GE_Batch ()
		string Make_Batch ($batch_obj, $promo_obj, $db_object, $ftp, $show_success=false)

	*/

	/* OPTIONAL CONSTANTS
	*/

	/* SAMPLE USAGE
	Make_Batch ()
		Arguments:
 		$batch_obj: array - array of client data pulled from a DB or other source (sample construction below)
  		$promo_obj: object - contains data specific to the promotion (sample construction below)
  		$db_object: object - contains database connection info (sample construction below)
  		$ftp: object - contains GE ftp info and response/reply emails (sample construction below)
  		$show_success: boolean - determines whether success information and batch data is displayed/sent to cron email (defaults to false)

		Construction of $batch_obj object (some object properties are optional depending on the database and site requirements):

			// create as an array of objects
			$batch_obj[] = new stdClass();
			$cust_count = 0;
			while ($row = $sql->Fetch_Array_Row($result))
			{
				// give each object properties while looping through the result
				// This may not be an up-to-date list of possible object properties
				$batch_obj[$cust_count]->order_id = $row['order_id'];
				$batch_obj[$cust_count]->promo_id = $row['promo_id'];
				$batch_obj[$cust_count]->first_name = $row['first_name'];
				$batch_obj[$cust_count]->last_name = $row['last_name'];
				$batch_obj[$cust_count]->middle_name = $row['middle_name'];
				$batch_obj[$cust_count]->email = $row['email'];				// optional
				$batch_obj[$cust_count]->dob = $row['dob'];				// optional
				$batch_obj[$cust_count]->phone = $row['phone'];
				$batch_obj[$cust_count]->address1 = $row['address_1'];
				$batch_obj[$cust_count]->address2 = $row['address_2'];
				$batch_obj[$cust_count]->city = $row['city'];
				$batch_obj[$cust_count]->state = $row['state'];
				$batch_obj[$cust_count]->zip = $row['zip'];
				$batch_obj[$cust_count]->date_of_sale = $row['date_of_sale'];
				$batch_obj[$cust_count]->card_num = $row['card_num'];
				$batch_obj[$cust_count]->card_type = $row['card_type'];
				$batch_obj[$cust_count]->card_exp = $row['card_exp'];

				$cust_count++;
			}


		Construction of $promo_obj object (all object properties are manditory to work):

			$promo = new stdClass ();
			// code provided from GE, monthly
			$promo->promo_code = "483948394";
			// made-up code to identify site, cannot change after set.
			$promo->site_code = "gepgmem";


		Construction of $db_object object (all object properties are manditory to work):

			// Best option is to use constants from global configuration, or just set to values in quotes.
			$db = new stdClass ();
			$db->sql_host = SQL_HOST;
			$db->sql_user = SQL_USER;
			$db->sql_pass = SQL_PASS;


		Construction of $ftp object (all object properties are manditory to work):

			// actual FTP data
			$ftp = new stdClass ();
			$ftp->server = "ftp.gefanet.com";
			$ftp->user_name = "ssourpmg";
			$ftp->user_password = "password";
			// email to send confirmation to
			$ftp->confirm_email = "cd.es@ge.com";
			// email that GE can reply to (confirmation sent using this email)
			$ftp->confirm_reply_email = "john.hawkins@thesellingsource.com";


  		Examples:
  		$new_batch = new GE_Batch;
  		$new_batch->Make_Batch ($batch_obj, $promo_obj, $db_object, $ftp);
	*/

include_once("/virtualhosts/lib/ftp.1.php");

class GE_Batch extends FTP
{
	var $current_date;
	// e records = customers
	var $count_e_records;
	// ea records =  records per customer
	var $count_ea_records;
	// reset counter every 200 records per GE's request
	var $batch_200_inc;
	// counts iterations of 200 records
	var $batch_200_count;
	// multi-dimensional array of customers and data
	var $cust_array;

	var $batch_temp;

	function GE_Batch ()
	{
		parent::FTP();
		$this->current_date = date ("mdy");
		$this->count_e_records = 0;
		$this->count_ea_records = 0;
		$this->batch_200_inc = 0;
		// batch_200_count starts at 1 because it has to be at least one, then increments up 1 for every 200 records
		$this->batch_200_count = 1;
		$this->cust_array = array ();
	}

	function Make_Batch ($batch_obj, $promo_obj, $db_object, $ftp, $show_success=false)
	{
		include_once ("/virtualhosts/lib/mysql.3.php");
		$sql = new MySQL_3 ();
		$result = $sql->Connect (NULL, $db_object->sql_host, $db_object->sql_user, $db_object->sql_pass, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);

		// Get last batch id #
		$query = "SELECT file_id FROM batch_file ORDER BY file_id DESC ";
		$result = $sql->Query (SQL_DB_BATCH, $query, $trace_code = NULL);
		Error_2::Error_Test ($result, TRUE);
		$row = $sql->Fetch_Array_Row($result);
		$last_batch_id = $row['file_id'];
		$current_batch_id = $last_batch_id + 1;

		// split ftp->file string into source/destination filenames
		$file_array = explode (",", $ftp->file);
		$this->batch_temp = $file_array [0];

		// HEADER
		$record_header = "0" . " " . $this->Zero_Fill ($current_batch_id, 4) . $this->current_date . "Q;" . $this->Space_Fill ("", 66) . "\n";

		foreach ($batch_obj as $customer)
		{
			// reset $this->batch_200_count
			if($this->batch_200_inc == 200)
			{
				$this->batch_200_inc = 0;
				$this->batch_200_count++;
			}

			//record A
			$record_a = "EA\$TPD" . $this->Space_Fill ($customer->card_type, 3) . $this->Space_Fill ($promo_obj->promo_code, 8) . "12" . $this->Space_Fill ($customer->card_num, 20) . $this->Space_Fill ("", 38) . "TSS" . "\n";
			$this->count_e_records++;

			// record B
			$exp_date = $customer->card_exp;
			$month = substr($exp_date, 0, 2);
			$year = substr($exp_date, -4);
			$timestamp = mktime(0,0,0,$month,1,$year);
			$record_b = "EB" . date ("mty", $timestamp) . $this->Space_Fill ("", 11) . $this->Zero_Fill ($this->batch_200_count , 5) . $this->Space_Fill ("", 2) . strtoupper($this->Space_Fill ($promo_obj->site_code, 8)) . $this->Zero_Fill ($customer->order_id, 10) . $this->Space_Fill ("", 2) . $this->Space_Fill ($customer->promo_id, 9) . $this->Zero_Fill ($customer->phone, 10) . $this->Space_Fill ($customer->first_name, 15) . "\n";
			$this->count_e_records++;

			// record C
			$record_c = "EC" . $this->Space_Fill (substr ($customer->middle_name, 0, 1), 1) . $this->Space_Fill ($customer->last_name, 20) . $this->Space_Fill (substr ($customer->address1, 0, 25), 25) . $this->Space_Fill ($customer->address2, 25) . $this->Space_Fill ("", 7) . "\n";
			$this->count_e_records++;

			// record D
			if (isset($customer->dob) && ($customer->dob != ""))
			{
				$customer_dob = substr (preg_replace ("/-/", "", $customer->dob), 2);
			}
			else
			{
				$customer_dob = "";
			}
			$record_d = "ED" . $this->Space_Fill($customer->city, 18) . $this->Space_Fill ($customer->state, 2) . $this->Space_Fill ($customer->zip, 9) . $this->Space_Fill ("", 3) . $this->Space_Fill ($customer_dob,6) . $this->Space_Fill ("", 20) . $this->Space_Fill (substr ($customer->date_of_sale, 4, 2) . substr ($customer->date_of_sale, 6, 2) . substr ($customer->date_of_sale, 2, 2),6) . $this->Space_Fill ("", 14) . "\n";
			$this->count_e_records++;


			// Compile all mandatory records into one array element
			 $customer_data = $record_a . $record_b . $record_c . $record_d;


			// record I (email only): report only for products that require email
			if (isset($customer->email) && ($customer->email != ""))
			{
				$record_i = "EUI:" . $this->Space_Fill ($customer->email, 48)  . $this->Space_Fill ("", 28) . "\n";
				$this->count_e_records++;
				$customer_data .= $record_i;
			}

			// Set final customer array element
			$this->cust_array[] = $customer_data;

			$this->count_ea_records++;
			$this->batch_200_inc++;
		}

		//TRAILER
		$record_trailer = "9" . $this->Zero_Fill ($this->count_e_records, 7) . $this->Zero_Fill ($this->count_ea_records, 7) . str_repeat (0, 65) . "\n";


		$full_record = strtoupper ($record_header . implode ("", $this->cust_array) . $record_trailer);

		// DB Insertion
		$encoded_batch = base64_encode ($full_record);

		if ($db_object->db_insert)
		{
			// insert base64 encoded batch into database
			$query = "INSERT INTO batch_file (batch_id, file, batch_date,site_code) VALUES('fillerid', '" . $encoded_batch . "', NOW(), '" . $promo_obj->site_code . "')";
			$result = $sql->Query (SQL_DB_BATCH, $query, $trace_code = NULL);
			Error_2::Error_Test ($result, TRUE);
		}
		else
		{
			$this->error_array[] = "Database argument is not present. Batch is not being saved in the database.\n";
		}

		if($show_success)
		{
			echo "<pre>";
			echo $full_record;
			echo "</pre>";
		}

		// write temp file to location
		$fp = fopen ($this->batch_temp, 'w');
		fwrite ($fp, $full_record);
		fclose ($fp);

		// echo name of local file
		if($show_success)
		{
			echo "\n" . $this->batch_temp . "\n";
		}

		$gpg_filename = $this->batch_temp . ".gpg";

		if ($ftp->action)
		{
			// GPG encryption: below gpg options are to make file compatible with GE's version of PGP
			exec('gpg -e --homedir /home/release/.gnupg --compress-algo 1 --cipher-algo cast5 --no-secmem-warning -r Electronic '.$this->batch_temp, $this->error_array, $rc);
			$this->error_array[] = "gpg return code = $rc\n";

			if(file_exists ($gpg_filename))
			{
				// delete local temp file if gpg was successful
				unlink ($this->batch_temp);

				$local_file_array = explode (",",$ftp->file);

				$ftp->file = implode (".gpg,", $local_file_array);

				// FTP File to location
				if ($this->do_Put ($ftp, $show_success))
				{
					// if ftp is successfull, delete local gpg'd file
					unlink ($gpg_filename);
				}
				else
				{
					$this->error_array[] = "Unable to FTP.\n";
				}
			}
			else
			{
				$this->error_array[] = "Batch cannot be encrypted.\n";
			}
		}
		else
		{
			$this->error_array[] = "FTP argument is not present.\n";
		}

		// Send confirmation email.
		if ($ftp->action && $ftp->confirm && $ftp->confirm_email && (trim ($ftp->confirm_email) != ""))
		{
			if (count ($this->error_array) > 0)
			{
				$confirm_message = "The batching process did not complete. All unbatched records will be in the next batch.\n"
					.implode("\n",$this->error_array)."\n";
			}
			else
			{
				$confirm_file_name_array = explode (",", $ftp->file);
				$confirm_file_name = $confirm_file_name_array[1];
				$confirm_message = "The following file has been uploaded to the FTP site:"
					. $confirm_file_name . " contains " . $this->count_ea_records . " sales.";
			}
			mail ($ftp->confirm_email, "TSS".str_pad($current_batch_id, 4, '0', STR_PAD_LEFT)."Club_enrolls.TXT.PGP ".$this->count_ea_records." enrollments", $confirm_message, "From: " . $ftp->confirm_reply_email);
			if ($ftp->notify_admin)
			{
				mail ($ftp->confirm_reply_email, "TSS".str_pad($current_batch_id, 4, '0', STR_PAD_LEFT)."Club_enrolls.TXT.PGP ".$this->count_ea_records." enrollments", $confirm_message, "From: " . $ftp->confirm_reply_email);
			}
		}

		// output any errors
		if (count ($this->error_array) > 0)
		{
			echo implode ("\n", $this->error_array);
		}

		return true;
	}
	function Zero_Fill ($str, $length)
	{
		$str =  str_pad ($str, $length, "0", STR_PAD_LEFT);
		return $str;
	}

	function Space_Fill($str, $length)
	{
		$str =  str_pad ($str, $length);
		return $str;
	}

	function Custom_Fill($str, $length, $pad, $justify="left")
	{
		if ($justify == "left")
		{
			$justify = STR_PAD_LEFT;
		}
		else
		{
			$justify = STR_PAD_RIGHT;
		}
		$str =  str_pad ($str, $length, $pad, $justify);
		return $str;
	}
}
?>
