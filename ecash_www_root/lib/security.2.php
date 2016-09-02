<?php
	// Version 2.1.0
	
	/* DESIGN TYPE
		object
	*/

	/* UPDATES
		Features:

		Bugs:
	*/

	/* PROTOTYPES
		bool Security_2 ()
		string Login_User ($login, $password, $trace_code = NULL)
		Logout_User ($temp_pass, $trace_code = NULL)
		Validate_User ($temp_pass, $trace_code = NULL)
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
		UNIUQE KEY  (`login`),
		UNIQUE KEY `hash_temp` (`hash_temp`),
		UNIQUE KEY `login_hash_pass` (`login`,`hash_pass`),
	*/

	// A class to handle ".$this->table." issues
	require_once "crypt.2.php";
	require_once "mysql.3.php";

	class Security_2
	{
		var $sql;
		var $database;
		var $table;
		
		function Security_2 (&$sql_object, $database, $table)
		{
			$this->sql = &$sql_object;
			$this->database = $database;
			$this->table = $table;
			
			return TRUE;
		}

		function Login_User ($login, $password, $trace_code = NULL)
		{
			// Try to log a user into the system

			// Find the login
			$query = "select hash_pass from ".$this->table." where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			// Test for a hit
			if (!$this->sql->Row_Count ($result))
			{
				// Login has failed, return an error
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Login does not exist";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

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

			// Get the data from the db
			$db_data = $this->sql->Fetch_Object_Row ($result);

			// Hash the passed in password
			$hash_pass = $this->_Mangle_Password ($password);
			
			if (trim ($hash_pass) != trim ($db_data->hash_pass))
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
			$temp_pass = Crypt_2::Random_Char (16);
			$hash_temp = $this->_Mangle_Password ($temp_pass);

			// Default is to allow login forever
			$login_expire_date = "00000000000000";

			// Set the expire_date
			if (defined ("LOGIN_DURATION"))
			{
				if (LOGIN_DURATION)
				{
					$login_expire_date = "date_add(now(), interval \"".LOGIN_DURATION."\" minute)";
				}
			}

			// Update the database
			$query = "update ".$this->table." set hash_temp = '".$hash_temp."', login_expire_date=".$login_expire_date." where login='".$login."' and hash_pass='".$hash_pass."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			// Return the hash_temp
			return $temp_pass;
		}

		function Logout_User ($temp_pass, $trace_code = NULL)
		{
			// hash the temp password
			$hash_temp = $this->_Mangle_Password ($temp_pass);
			
			// Replacement temp
			$new_temp = $this->_Mangle_Password (Crypt_2::Random_Char());

			// Log the user out
			$query = "update ".$this->table." set hash_temp='".$new_temp."' where hash_temp='".$hash_temp."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			if (Error_2::Error_Test ($result))
			{
				// Perhaps that temp pass is used.
				if (mysql_errno ($result->link_id->temp_link_id) == 1062)
				{
					// It was used, try again
					$this->Logout_User ($temp_pass, $trace_code);
				}
			}

			return TRUE;
		}

		function Validate_User ($temp_pass, $trace_code = NULL)
		{
			// hash the temp password
			$hash_temp = $this->_Mangle_Password ($temp_pass);

			// Check if the login is still good
			$query = "select * from ".$this->table." where hash_temp='".$hash_temp."' and (login_expire_date > now() or login_expire_date=00000000000000)";
			
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			// Test for a hit
			if (!$this->sql->Row_Count ($result) || !strlen ($temp_pass))
			{
				// No match was found, throw error
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "No account was found for the temp password ".$temp_pass." or the login has expired";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

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

			return TRUE;
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
			$password_expire_date = "00000000000000";

			// Set the expire_date
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "date_add(now(), interval \"".PASSWORD_DURATION."\" day)";
				}
			}

			// Make the update
			$query = "update ".$this->table." set hash_pass='".$hash_new_pass."', password_expire_date=".$password_expire_date." where  login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, FALSE);
			
			// Check for a result to make sure it all worked
			if (!$this->sql->Affected_Row_Count ())
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Login / Old password combination did not match";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

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
			$password_expire_date = "00000000000000";

			// Set the expire_date
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "date_add(now(), interval \"".PASSWORD_DURATION."\" day)";
				}
			}

			// Make the update
			$query = "update ".$this->table." set hash_pass='".$hash_new_pass."', password_expire_date=".$password_expire_date." where hash_pass='".$hash_old_pass."' and login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, FALSE);
			
			// Check for a result to make sure it all worked
			if (!$this->sql->Affected_Row_Count ())
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Login / Old password combination did not match";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

			return TRUE;
		}

		function Create_Customer_Account ($account_id, $active_application_id, $login, $trace_code = NULL)
		{
			// Create/Open the user account

			// Check for the use of the login by another user
			$query = "select count(*) as found from ".$this->table." where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);
			$db_data = $this->sql->Fetch_Object_Row ($result);

			// Test for a hit
			if ($db_data->found)
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
			$password = Crypt_2::Random_Char ();

			// Hash the password
			$hash_pass = $this->_Mangle_Password ($password);
			$hash_temp = $this->_Mangle_Password (Crypt_2::Random_Char (32));
			
			// Generate the durations

			// Default is to allow password/account forever
			$password_expire_date = "00000000000000";
			$account_expire_date = "00000000000000";

			// Set the expire_dates if not default values
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "date_add(now(), interval \"".PASSWORD_DURATION."\" day)";
				}
			}

			if (defined ("ACCOUNT_DURATION"))
			{
				if (ACCOUNT_DURATION)
				{
					$account_expire_date = "date_add(now(), interval \"".ACCOUNT_DURATION."\" day)";
				}
			}

			// Create the account
			$query = "
				insert into ".$this->table." 
					(account_id, active_application_id, login, password_expire_date, account_expire_date, hash_pass, hash_temp, active, access_level, access_type) 
				values 
					('".$account_id."', '".$active_application_id."', '".$login."', ".$password_expire_date.", ".$account_expire_date.", '".$hash_pass."',  '".$hash_temp."','TRUE', '0', 'CUSTOMER')";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));

			// Check for a result to make sure it all worked
			if (Error_2::Error_Test ($result))
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->error_code = UNKNOWN_ERROR;
				$error->message = "The update to the ".$this->table." table in ".$this->database." database failed.  I do not know why";
				$error->fatal = FALSE;
				$error->notify_admin = TRUE;

				// Check for unique value already in use.
				if (mysql_errno ($result->link_id->temp_link_id) == 1062)
				{
					// It was used, try again
					unset ($error);
					$error = new Error_2 ();
					$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
					$error->error_code = LOGIN_EXISTS;
					$error->message = "The login is already in use.  Please try again.";
					$error->fatal = FALSE;
					$error->notify_admin = TRUE;
				}

				return $error;
			}
			
			return $password;
		}
		
		function Open_Account ($access_type, $login, $password = NULL, $active = TRUE, $access_level = 0, $trace_code = NULL)
		{
			// Create/Open the user account

			// Check for the use of the login by another user
			$query = "select count(*) as found from ".$this->table." where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);
			$db_data = $this->sql->Fetch_Object_Row ($result);

			// Test for a hit
			if ($db_data->found)
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
				$password = Crypt_2::Random_Char ();
			}

			// Hash the password
			$hash_pass = $this->_Mangle_Password ($password);
			$hash_temp = $this->_Mangle_Password (Crypt_2::Random_Char (32));
			
			// Generate the durations

			// Default is to allow password/account forever
			$password_expire_date = "00000000000000";
			$account_expire_date = "00000000000000";

			// Set the expire_dates if not default values
			if (defined ("PASSWORD_DURATION"))
			{
				if (PASSWORD_DURATION)
				{
					$password_expire_date = "date_add(now(), interval \"".PASSWORD_DURATION."\" day)";
				}
			}

			if (defined ("ACCOUNT_DURATION"))
			{
				if (ACCOUNT_DURATION)
				{
					$account_expire_date = "date_add(now(), interval \"".ACCOUNT_DURATION."\" day)";
				}
			}

			// Create the account
			$query = "insert into ".$this->table." (login, password_expire_date, account_expire_date, hash_pass, hash_temp, active, access_level, access_type) values ('".$login."', ".$password_expire_date.", ".$account_expire_date.", '".$hash_pass."',  '".$hash_temp."','".$active."', '".$access_level."', '".$access_type."')";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));

			// Check for a result to make sure it all worked
			if (Error_2::Error_Test ($result))
			{
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->error_code = UNKNOWN_ERROR;
				$error->message = "The update to the ".$this->table." table in ".$this->database." database failed.  I do not know why";
				$error->fatal = FALSE;
				$error->notify_admin = TRUE;

				// Check for unique value already in use.
				if (mysql_errno ($result->link_id->temp_link_id) == 1062)
				{
					// It was used, try again
					unset ($error);
					$error = new Error_2 ();
					$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
					$error->error_code = LOGIN_EXISTS;
					$error->message = "The login is already in use.  Please try again.";
					$error->fatal = FALSE;
					$error->notify_admin = TRUE;
				}

				return $error;
			}
			
			return $this->sql->Insert_Id ();
		}

		function Renew_Account ($login, $trace_code = NULL)
		{
			$account_expire_date = "00000000000000";

			// Set the expire_dates if not default values
			if (defined ("ACCOUNT_DURATION"))
			{
				if (ACCOUNT_DURATION)
				{
					$account_expire_date = "date_add(account_expire_date, interval \"".ACCOUNT_DURATION."\" day)";
				}
			}

			$query = "update ".$this->table." set account_expire_date=".$account_expire_date." where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Close_Account ($login, $trace_code = NULL)
		{
			// Close the user account
			$query = "update ".$this->table." set active='FALSE' where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}
		
		function Change_Access_Level ($login, $access_level, $trace_code = NULL)
		{
			// Change the user access level
			$query = "update ".$this->table." set access_level='".$access_level."' where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Change_Access_Type ($login, $access_type, $trace_code = NULL)
		{
			// Change the user access level
			$query = "update ".$this->table." set access_type='".$access_type."' where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Set_Inactive ($login, $trace_code = NULL)
		{
			// Make the account inactive
			$query = "update ".$this->table." set active='FALSE' where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Set_Active ($login, $trace_code = NULL)
		{
			// Make the account active
			$query = "update ".$this->table." set active='TRUE' where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			return TRUE;
		}

		function Get_Account_Life ($login, $trace_code = NULL)
		{
			return $this->_Calculate_Age ($login, "account_expire_date", $trace_code);
		}

		function Get_Password_Life ($login, $trace_code = NULL)
		{
			return $this->_Calculate_Age ($login, "password_expire_date", $trace_code);
		}

		function Get_Login_Life ($login, $trace_code = NULL)
		{
			return $this->_Calculate_Age ($login, "login_expire_date", $trace_code);
		}

		function _Calculate_Age ($login, $type, $trace_code = NULL)
		{
			// Return the number of days the column will remain active
			$query = "select (unix_timestamp(".$type.") - unix_timestamp()) as seconds_left, ".$type." from ".$this->table." where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			// Test for a hit
			if (!$this->sql->Row_Count ($result))
			{
				// This login is used
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "The login ".$login." does not exist.  Please choose another";
				$error->fatal = FALSE;
				$error->notify_admin = FALSE;

				return $error;
			}

			// Get the data from the db
			$db_data = $this->sql->Fetch_Object_Row ($result);

			switch (TRUE)
			{
				case ((int)$db_data->$type == 0):
					// Return eternal
					$return_value = -1;
					break;

				case ($db_data->seconds <= 0):
					// Return Expired
					$return_value = 0;
					break;

				case (preg_match ("/account/", $type)):
				case (preg_match ("/password/", $type)):
					// Return Days
					$return_value = $db_data->seconds_left / 86400;
					break;

				case (preg_match ("/login/", $type)):
					// Return minutes
					$return_value = $db_data->seconds_left / 60;
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
		
		function _Get_Info_Temp ($hash_temp)
		{
			// Find the login
			$query = "select * from ".$this->table." where hash_temp='".$hash_temp."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result);

			return $this->sql->Fetch_Object_Row ($result);
		}
		
		function _Get_Info_Login ($login)
		{
			// Find the login
			$query = "select * from ".$this->table." where login='".$login."'";
			$result = $this->sql->Query ($this->database, $query, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result);

			return $this->sql->Fetch_Object_Row ($result);
		}
		
		function _Mangle_Password ($password)
		{
			// Set the expire_date
			if (defined ("PASSWORD_ENCRYPTION"))
			{
				switch (PASSWORD_ENCRYPTION)
				{
					case "ENCRYPT":
						$mangled_password = Crypt_2::Encrypt ($password);
						break;

					default:
						$mangled_password = Crypt_2::Hash ($password);
						break;
				}
			}
			else
			{
				// Nothing set, hash it
				$mangled_password = Crypt_2::Hash ($password);
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
						$password = Crypt_2::Decrypt ($mangled_password);
						break;
				}
			}

			return $password;
		}
	}
?>
