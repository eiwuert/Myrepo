<?php	

define('SMTP_DATABASE', 'smtp2');
define('DB_SERVER', 'olemaster.soapdataserver.com');
define('DB_USER', 'ole');
define('DB_PASSWD', 'hiueI93v');

class Ole_Smtp_Lib
{

	function Ole_Smtp_Lib ()
	{
	}

	function Ole_Send_Mail()
	{
		/**
		 * This function will be called as
		
		 * 		Ole_Send_Mail ($event, $property_id,  $data)
		 * OR	Ole_Send_Mail ($event_id, $data)
		 * 
		 * The ideal method would be
		 * 		ole_send_mail($data, $event{id}, $property_id = 17176);
		 */
		//Preset the variable for $queue_id to keep it in scope.
		$queue_id = 0;
		$args = func_get_args();
		// Based on # of params, params may be different
		switch (sizeof($args))
		{
			case 2 :
			$event_id = $args[0]; # $event_id;
			$property_id = null;
			$event_name = null;
			$data = $args[1]; # $recipient_data;
			break;
			case 3 :
			$event_id = null;
			$event_name = $args[0]; #$event_name;
			$property_id = $args[1]; #$property_id;
			$data = $args[2]; #$recipient_data;
			if (!$property_id)	$property_id = 17176;
			break;
			default:
			//die ('Invalid # of arguments');
			return -1;
		}
		if ($this->Validate_Data($event_id, $event_name, $property_id, $data))
		{
			$queue_id = $this->Store_Valid($event_id, $event_name, $property_id, $data);
		} 
		else 
		{
			$invalid = 'insert into failed queue id: ' . $queue_id;
			$queue_id = $this->Store_Invalid($event_id, $event_name, $property_id, $data);
		}

		if (!$queue_id)
		{
			return 'database insert failed: ' . $invalid;
		}
		else 
		{
			if (!isset($invalid))
			{
				return $queue_id;
			}
			else 
			{
				return $invalid;
			}
		}
	}

	function Store_Valid($event_id, $event_name, $property_id, $data)
	{
		$email_address = $data['email_primary'];
		$data =mysql_escape_string(serialize($data));
		$ipaddress = @$_SERVER['REMOTE_ADDR'];
		mysql_connect(DB_SERVER, DB_USER, DB_PASSWD);
		mysql_select_db(SMTP_DATABASE);
		$query = "INSERT INTO ole_mail_queue (`ipaddress`, `event_id`, `event_name`, `property_id`, `email_address`, `data`, `status`, `create_date`) "
		."VALUES ( '{$ipaddress}', '{$event_id}', '{$event_name}', '{$property_id}', '{$email_address}', '{$data}', 'QUEUED', NOW())";
		$result = mysql_query($query);
		return ($result)? mysql_insert_id() : false;
	}

	function Store_Invalid($event_id, $event_name, $property_id, $data)
	{
		$email_address = $data['email_primary'];
		$data =mysql_escape_string(serialize($data));
		$errors = mysql_escape_string(serialize($this->errors));
		$ipaddress = @$_SERVER['REMOTE_ADDR'];
		mysql_connect(DB_SERVER, DB_USER, DB_PASSWD);
		mysql_select_db(SMTP_DATABASE);
		$query = "INSERT INTO ole_mail_fail_queue (`ipaddress`, `event_id`, `event_name`, `property_id`, `email_address`, `data`, `errors`, `status`, `create_date`) "
		."VALUES ( '{$ipaddress}', '{$event_id}', '{$event_name}', '{$property_id}', '{$email_address}', '{$data}', '{$errors}', 'QUEUED', NOW())";
		$result = mysql_query($query);
		return ($result)? mysql_insert_id() : false;
	}

	function Error($msg)
	{
		$this->errors[] = 'Error: '.$msg;
		return false;
	}

	function Field_Test($field_name, $val, $type)
	{
		if (strlen($val) == 0) return $this->Error("Required $field_name is empty");
		if ($type=='string') return (is_string($val))? true : $this->Error("String Field required for $field_name");
		if ($type=='number') return (is_numeric($val))? true : $this->Error("Numeric Field required for $field_name");
		return $this->Error("Bad data test for $field_name = $val");
	}

	function Data_Test($data)
	{
		foreach ($data as $key=>$value)
		{
			//if (strlen($key) == 0) $this->Error("Data with NULL key field associated with value '$value'");
			//if (strlen($value) == 0) $this->Error("Data with NULL value field associated with key '$key'");
			//	if (strlen($value) > 255) $this->Error("Data with value GREATER then 255 for key '$key'");
		}
		if (!isset($data['email_primary'])) $this->Error("Required field 'email_primary' was not set");
		if (!isset($data['email_primary_name'])) $this->Error("Required field 'email_primary_name' was not set");
		if (!isset($data['site_name'])) $this->Error("Required field 'site_name' was not set");
		if (!$this->Validate_Email($data['email_primary'])) $this->Error("Invalid E-mail address provided ".$data['email_primary']);
	}

	function Validate_Email($email)
	{
		$match = null;
		//if (preg_match("/[a-zA-Z0-9._%-]+@[a-zA-Z0-9_%-]+\.[a-zA-Z]{2,4}/", $email, $match))
		if (preg_match("/[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+/", $email, $match))
		{
			if (strcmp($match[0],$email) == 0) return true;
		}
		return false;
	}

	function Validate_Data($event_id, $event_name, $property_id, $data)
	{
		$this->errors = array();
		if (is_null($event_id))
		{
			$this->Field_Test('event_name', $event_name, 'string');
			$this->Field_Test('property_id', $property_id, 'number');
		} else {
			$this->Field_Test('event_id', $event_id, 'number');
		}
		$this->Data_Test($data);
		//print_r($this->errors);
		return (count($this->errors)>0)? false : true;
	}


	/**
	 * Add attachments to the outgoing email
	 * $method can be either ATTACH or EMBED
	 */
	function Add_Attachment($file_data, $mimetype, $filename, $method="ATTACH")
	{
		ob_start();
		$file_data_size = strlen($file_data);
		$file_data = mysql_escape_string(gzcompress($file_data));
		mysql_connect(DB_SERVER, DB_USER, DB_PASSWD);
		mysql_select_db(SMTP_DATABASE);
		$query = "
			INSERT INTO ole_mail_queue_attachment (`method`, `mime_type`, `filename`, `file_data_size`, `file_data`) 
			VALUES ('$method', '$mimetype', '$filename', '$file_data_size', '$file_data')";
		$result = mysql_query($query);
		$debug = ob_get_contents();
		ob_end_flush();
		return ($result)? mysql_insert_id() : (($this->_prpc_use_debug)? $result : false);
	}



}
	
//Example code
/*
	$data = array();
	//********Must have data fields ************************
	$data['email_primary'] = 'jasons@sellingsource.com'; 	
	$data['email_primary_name'] = 'Jason Shiverdecker'; 
	$data['site_name'] = 'UnitedCashLoans.com'; 
	//******************************************************
	//Add what ever you want for the email replace function
	//**************user defined data************************
	$data['name_view'] = $data['site_name'];
	$data['name'] = "Jason Shiverdecker";
	$data['application_id'] = 1122334455;
	$data['confirm'] = "http://oneclickcash.com/?page=ent_cs_login&application_id=513791&property_short=pcl";
	$data['site_name'] = 'oneclickcash.com';
	$data['username'] = "jason_shiverdecker";
	$data['password'] = "password";
	$data['date'] = "01/04/2005";
	
	$email = new Ole_Smtp_Lib(true);
	print_r($email->Ole_Send_Mail("THANK_YOU", 1579, $data));

*/

?>
