<?php
	// Version 4.0.0
	
	/* DESIGN TYPE
		object
	*/

	/* UPDATES
		Features:

		Bugs:
	*/

	/* PROTOTYPES
		bool Security_4 ()
		string Login_User ($login, $password, $trace_code = NULL, $no_expire = TRUE )
		Logout_User ($temp_pass, $trace_code = NULL)
		Admin_Set_Password ($login, $new_password, $trace_code = NULL)
		Set_Password ($login, $old_password, $new_password, $trace_code = NULL)
		Create_Customer_Account ($account_id, $active_application_id, $login, $trace_code = NULL)
		Open_Account ($login, $password = NULL, $active = TRUE, $access_level = 0, $access_type = "USER", $trace_code = NULL)
		Renew_Account ($login, $trace_code = NULL)
		Close_Account ($login, $trace_code = NULL)
		Change_Access_Level ($login, $access_level, $trace_code = NULL)
		Change_Access_Type ($login, $access_type, $trace_code = NULL)
		Set_Inactive ($login, $trace_code = NULL)
		Set_Active ($login, $trace_code = NULL)
		Get_Account_Life ($login, $trace_code = NULL)
		Get_Password_Life ($login, $trace_code = NULL)
		Get_Login_Life ($login, $trace_code = NULL)
		_Calculate_Age ($login, $type, $trace_code = NULL)
		_Mangle_Password ($password)
		_Un_Mangle_Password ($mangled_password)
	*/

	/* OPTIONAL CONSTANTS
		LOGIN_DURATION = The amount of time a person can be logged in as expressed in minutes.  If not set will default to forever
		ACCOUNT_DURATION = The length of time the account will be active expressed in days.  If not set will default to forever
		PASSWORD_DURATION = The length of time the password will be active before requireing a change expressed in days.  If not set will default to forever.
		PASSWORD_ENCRYPTION = HASH, ENCRYPT. Determine wether the passwords are one way hash (HASH) or encrypted (ENCRYPT).  If not set will default to HASH
	*/

	/* SAMPLE USAGE
	*/

	/* REQUIRED TABLE FIELDS
		`login` varchar(250) NOT NULL default '',
		`modified_date` timestamp(14) NOT NULL,
		`login_expire_date` timestamp(14) NOT NULL,
		`account_expire_date` timestamp(14) NOT NULL,
		`password_expire_date` timestamp(14) NOT NULL,
		`hash_pass` varchar(250) NOT NULL default '',
		`hash_temp` varchar(250) NOT NULL default '',
		`active` enum('TRUE','FALSE') NOT NULL default 'TRUE',
		`access_level` tinyint(3) unsigned NOT NULL default '0',
		`access_type` enum('CUSTOMER','VENDOR','TSS') default NULL,
		UNIQUE KEY  (`login`),
		UNIQUE KEY `hash_temp` (`hash_temp`),
		UNIQUE KEY `login_hash_pass` (`login`,`hash_pass`)
	*/

	// A class to handle ".$this->table." issues
	require_once "crypt.3.php";
	require_once "db2.1.php";

	class Security_4
	{
		var $sql;
		var $database;
		var $table;
		
		function Security_4 (&$db2_object, $table, $schema)
		{
			$this->sql = &$db2_object;
			$this->database = $database;
			$this->table = $schema.".".$table;
						
			return TRUE;
		}

		// added $no_expire for compatibilty with new db2 OLP scheme for customer service
		function Login_User ($login, $password, $trace_code = NULL, $no_expire = FALSE )
		{
			// Try to log a user into the system

			// Find the login
			$query = "select crypt_password from ".$this->table." where upper(login)='".$login."'";
			$result = $this->sql->Execute($query);
			Error_2::Error_Test ($result, TRUE);
			$db_data = $result->Fetch_Object();
			
			// Test for a hit
			if (!isset($db_data->CRYPT_PASSWORD))
			{
				// Login has failed, return an error
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Login does not exist";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}
			// is this login type allowed to expire
			if( !$no_expire ) 
			{
				// Validate the expiration dates
				if ($this->Get_Account_Life ($login, $trace_code) == 0)
				{
					// Account has expired, return an error
					$error = new Error_2 ();
					$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
					$error->message = "Account has expired.";
					$error->fatal = FALSE;
					$error->notify_admin = FALSE;
	
					return $error;
				}
	
				if ($this->Get_Password_Life ($login, $trace_code) == 0)
				{
					// Password has expired, return an error
					$error = new Error_2 ();
					$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
					$error->message = "Password has expired.";
					$error->fatal = FALSE;
					$error->notify_admin = FALSE;
	
					return $error;
				}
			}
				
			// Hash the passed in password
			$hash_pass = $this->_Mangle_Password ($password);

			if (trim ($hash_pass) != trim ($db_data->CRYPT_PASSWORD))
			{
				// Password has failed, return an error
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Passwords did not match";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

			// User is legitimate, create the temp password
			$temp_pass = crypt_3::Random_Char (16);
			$hash_temp = $this->_Mangle_Password ($temp_pass);

			// Update the database
			$query = "update ".$this->table." set crypt_temp = '".$hash_temp."' where login='".$login."' and crypt_password='".$hash_pass."'";
			$result = $this->sql->Execute($query);
			Error_2::Error_Test ($result, TRUE);
			
			// Return the hash_temp
			return $temp_pass;
		}

		function Admin_Set_Password ($login,  $new_password, $trace_code = NULL)
		{
			// Set the password for the user

			// Make sure new password is not null
			if (!strlen ($new_password))
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "New Password is null";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

			// Hash the passwords
			$hash_new_pass = $this->_Mangle_Password ($new_password);

			// Default is to allow password forever
			$password_expire_date = "'0001-01-01'";

			// Set the expire_date
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "current date + ".PASSWORD_DURATION." days";
				}
			}

			// Make the update
			$query = "update ".$this->table." set crypt_password='".$hash_new_pass."', date_expire_password=".$password_expire_date." where  login='".$login."'";
			$result = $this->sql->Execute ($query);			
			Error_2::Error_Test ($result, FALSE);
			
			return TRUE;
		}
		
		function Set_Password ($login, $old_password, $new_password, $trace_code = NULL)
		{
			// Set the password for the user

			// Make sure new password is not null
			if (!strlen ($new_password))
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "New Password is null";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

			// Hash the passwords
			$hash_old_pass = $this->_Mangle_Password ($old_password);
			$hash_new_pass = $this->_Mangle_Password ($new_password);

			// Default is to allow password forever
			$password_expire_date = "'0001-01-01'";

			// Set the expire_date
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "current date + ".PASSWORD_DURATION." days";
				}
			}

			// Make the update
			$query = "update ".$this->table." set crypt_password='".$hash_new_pass."' where crypt_password='".$hash_old_pass."' and login='".$login."'";
			$result = $this->sql->Execute ($query);

			Error_2::Error_Test ($result, FALSE);
			
			return TRUE;
		}

		//function Create_Customer_Account ($account_id, $active_application_id, $login, $trace_code = NULL)
		function Create_Customer_Account( $company_id, $customer_id, $login, $trace_code = NULL )
		{
			// Create/Open the user account

			// Check for the use of the login by another user
			$query = "select count(*) as found from ".$this->table." where login='".$login."'";
			$result = $this->sql->Execute ($query);
			Error_2::Error_Test ($result, TRUE);
			$db_data = $result->Fetch_Object();

			// Test for a hit
			if ($db_data->FOUND)
			{
				// This login is used
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->error_code = LOGIN_EXISTS;
				$error->message = "The login ".$login." is already in use.  Please choose another";
				$error->fatal = FALSE;
				$error->notify_admin = TRUE;

				return $error;
			}

			// Create a password if one is not passed
			$password = crypt_3::Random_Char ();

			// Hash the password
			$hash_pass = $this->_Mangle_Password ($password);
			$hash_temp = $this->_Mangle_Password (crypt_3::Random_Char (32));
			
			// Generate the durations

			// Default is to allow password/account forever
			$password_expire_date = "'0001-01-01'";
			$account_expire_date = "'0001-01-01'";

			// Set the expire_dates if not default values
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "current date + ".PASSWORD_DURATION." days";
				}
			}

			if (defined ("ACCOUNT_DURATION"))
			{
				if (ACCOUNT_DURATION)
				{
					$account_expire_date = "current date + ".ACCOUNT_DURATION." days";
				}
			}

			// Create the account
			$query = "INSERT INTO " . $this->table . "
						(date_modified, date_created, customer_id, company_id, login, crypt_password, crypt_temp, audit_id ) 
					  values
						(current timestamp, current timestamp, {$customer_id}, {$company_id}, '{$login}', '{$hash_pass}', '{$hash_temp}', 0 )";
			$result = $this->sql->Execute ($query);

			// Check for a result to make sure it all worked
			if (Error_2::Error_Test ($result))
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->error_code = UNKNOWN_ERROR;
				$error->message = "The update to the ".$this->table." table in ".$this->database." database failed.  I do not know why";
				$error->fatal = FALSE;
				$error->notify_admin = TRUE;

				return $error;
			}
			
			return $password;
		}
		
		function Open_Account ($access_type, $login, $password = NULL, $access_level = 0, $trace_code = NULL)
		{
			// Create/Open the user account

			// Check for the use of the login by another user
			$query = "select count(*) as found from ".$this->table." where login='".$login."'";
			$result = $this->sql->Execute ($query);
			Error_2::Error_Test ($result, TRUE);
			$db_data = $this->sql->Fetch_Object();

			// Test for a hit
			if ($db_data->FOUND)
			{
				// This login is used
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->error_code = LOGIN_EXISTS;
				$error->message = "The login ".$login." is already in use.  Please choose another";
				$error->fatal = FALSE;
				$error->notify_admin = TRUE;

				return $error;
			}

			// Create a password if one is not passed
			if (is_null ($password))
			{
				$password = crypt_3::Random_Char ();
			}

			// Hash the password
			$hash_pass = $this->_Mangle_Password ($password);
			$hash_temp = $this->_Mangle_Password (crypt_3::Random_Char (32));
			
			// Generate the durations

			// Default is to allow password/account forever
			$password_expire_date = "'0001-01-01'";
			$account_expire_date = "'0001-01-01'";

			// Set the expire_dates if not default values
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "current date + ".PASSWORD_DURATION." days";
				}
			}

			if (defined ("ACCOUNT_DURATION"))
			{
				if (ACCOUNT_DURATION)
				{
					$account_expire_date = "current date + ".ACCOUNT_DURATION." days";
				}
			}

			// Create the account
			$query = "insert into ".$this->table." (login, date_expire_password, date_expire_account, crypt_password, crypt_temp, access_level, access_type) values ('".$login."', ".$password_expire_date.", ".$account_expire_date.", '".$hash_pass."',  '".$hash_temp."','".$access_level."', '".$access_type."')";
			$result = $this->sql->Execute ($query);

			// Check for a result to make sure it all worked
			if (Error_2::Error_Test ($result))
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->error_code = UNKNOWN_ERROR;
				$error->message = "The update to the ".$this->table." table in ".$this->database." database failed.  I do not know why";
				$error->fatal = FALSE;
				$error->notify_admin = TRUE;

				return $error;
			}
			
			return $this->sql->Insert_Id ();
		}

		function Renew_Account ($login, $trace_code = NULL)
		{
			$account_expire_date = "'0001-01-01'";

			// Set the expire_dates if not default values
			if (defined ("ACCOUNT_DURATION"))
			{
				if (ACCOUNT_DURATION)
				{
					$account_expire_date = "date_expire_account + ".ACCOUNT_DURATION." days";
				}
			}

			$query = "update ".$this->table." set date_expire_account=".$account_expire_date." where login='".$login."'";
			$result = $this->sql->Execute ($query);
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Close_Account ($login, $trace_code = NULL)
		{
			// Close the user account
			$query = "update ".$this->table." set date_expire_account=current date where login='".$login."'";
			$result = $this->sql->Execute($query);
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}
		
		function Change_Access_Level ($login, $access_level, $trace_code = NULL)
		{
			// Change the user access level
			$query = "update ".$this->table." set access_level='".$access_level."' where login='".$login."'";
			$result = $this->sql->Execute ($query);
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Change_Access_Type ($login, $access_type, $trace_code = NULL)
		{
			// Change the user access level
			$query = "update ".$this->table." set access_type='".$access_type."' where login='".$login."'";
			$result = $this->sql->Execute ($query);
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Get_Account_Life ($login, $trace_code = NULL)
		{
			return $this->_Calculate_Age ($login, "date_expire_account", $trace_code);
		}

		function Get_Password_Life ($login, $trace_code = NULL)
		{
			return $this->_Calculate_Age ($login, "date_expire_password", $trace_code);
		}

		function _Calculate_Age ($login, $type, $trace_code = NULL)
		{
			//do this for DB2 b/c it likes uppers
			$type = strtoupper($type);
			
			// Return the number of days the column will remain active
			// the 2 in the timestampdiff parameter stands for seconds
			// for more info on this function, see IBM SQL Reference Vol. 1
			// JRF
			$query = "select (timestampdiff(2, char(timestamp(".$type.", '00.00.00') - current timestamp))) as seconds_left, ".$type." from ".$this->table." where login='".$login."'";
			$result = $this->sql->Execute ($query);
			Error_2::Error_Test ($result, TRUE);
			$db_data = $result->Fetch_Object();

			// Test for a hit
			if (!isset($db_data))
			{
				// This login is used
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "The login ".$login." does not exist.  Please choose another";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

			//print '(int)$db_data->$type'.(int)$db_data->$type;
			switch (TRUE)
			{
				case ((int)$db_data->$type == 0):
					// Return eternal
					$return_value = -1;
					break;

				case ($db_data->SECONDS_LEFT <= 0):
					// Return Expired
					$return_value = 0;
					break;

				case (preg_match ("/ACCOUNT/", $type)):
				case (preg_match ("/PASSWORD/", $type)):
					// Return Days
					$return_value = $db_data->SECONDS_LEFT / 86400;
					break;
			}

			return $return_value;
		}

		function Get_Info ($type, $value)
		{
			switch (strtoupper ($type))
			{
				case "TEMP":
					$ret_val = $this->_Get_Info_Temp ($value);
					break;
					
				case "LOGIN":
					$ret_val = $this->_Get_Info_Login ($value);
					break;
			}
			return $ret_val;
		}
				
		function _Get_Info_Login ($login)
		{
			// Find the login
			$query = "select * from ".$this->table." where login='".$login."'";
			$result = $this->sql->Execute ($query);
			Error_2::Error_Test ($result);

			return $result->Fetch_Object();
		}
		
		function _Mangle_Password ($password)
		{
			// Set the expire_date
			if (defined ("PASSWORD_ENCRYPTION"))
			{
				switch (PASSWORD_ENCRYPTION)
				{
					case "ENCRYPT":
						$mangled_password = crypt_3::Encrypt ($password);
						break;

					default:
						$mangled_password = crypt_3::Hash ($password);
						break;
				}
			}
			else
			{
				// Nothing set, hash it
				$mangled_password = crypt_3::Hash ($password);
			}

			return $mangled_password;
		}
		
		function _Un_Mangle_Password ($mangled_password)
		{
			// Default to an error of unable to unmangle a hash.
			$password = new Error_2 ();
			$password->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
			$password->message = "The string: ".$login." cannot be reveresed because it is an MD5 hash.";
			$password->fatal = FALSE;
			$password->notify_admin = FALSE;

			// This will return an error if PASSWORD_ENCRYPTION is HASH
			if (defined ("PASSWORD_ENCRYPTION"))
			{
				switch (PASSWORD_ENCRYPTION)
				{
					case "ENCRYPT":
						unset ($password);
						$password = crypt_3::Decrypt ($mangled_password);
						break;
				}
			}

			return $password;
		}
	}
?>
