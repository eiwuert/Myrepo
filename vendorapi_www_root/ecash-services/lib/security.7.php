<?php

require_once( 'crypt.3.php' );

class Security_7
{
	
	protected $sql;
	protected $property_short;
	
	public function __construct( &$sqli, $property_short )
	{
		$this->sql				= &$sqli;
		$this->property_short 	= $property_short;
		return;
	}
	
	public function __destruct()
	{
		return;
	}

	/**
	* Logs a user in based on their username and password.
	*
	* @param 	string		$username:			The username to be checked.
	* @param 	string		$password:			The password to be checked.
	* @param 	bool		$no_expire			(OPTIONAL) Whether the account ever expires.
	*/ 
	public function Login_User( $username, $password, $no_expire = FALSE )
	{
		
		// get the password from the database
		$real_password = $this->Find_Password($username);
		
		if (!$no_expire && !$this->Is_Active_Account($username))
		{
			return FALSE;
		}
		
		$enc_pass = $this->_Encrypt_Password($password);
		
		
		if (($real_password !== FALSE) && (trim($this->_Encrypt_Password($password)) == trim($real_password)))
		{
			$temp_password = crypt_3::Random_Char( 16 );
		}
		else
		{
			$temp_password = FALSE;
		}
		
		return $temp_password;
		
	}

	/**
	* Encrypts a password.
	*
	* @param 	string		$password:			The password to be encrypted.
	* @return 	string:		The encrypted password.
	*/
	private function _Encrypt_Password( $password )
	{
		if( defined( 'PASSWORD_ENCRYPTION' ) )
		{
			switch( PASSWORD_ENCRYPTION )
			{
				case 'ENCRYPT':
					$return = crypt_3::Encrypt( $password );
					break;
				default:
					$return = crypt_3::Hash( $password );
					break;
			}
		}
		else
		{
			$return = crypt_3::Hash( $password );
		}

		return $return;
	}
	
	/**
	* Decrypts a password.
	*
	* @param	string		$password:			The password to be decrypted.
	* @return	string:		The unencrypted password.
	*/
	public function _Decrypt_Password( $password )
	{
		if( defined( "PASSWORD_ENCRYPTION" ) )
		{
			switch( PASSWORD_ENCRYPTION )
			{
				case "ENCRYPT":
					$return = crypt_3::Decrypt( $password );
					break;
				default:
					throw new General_Exception( 'The password \"' . $password . '\" cannot be decrypted.' );
					break;
			}
		}
		else 
		{
			throw new General_Exception( 'The password \"' . $password . '\" cannot be decrypted.' );
		}
		
		return $return;
	}
	
	/**
		@publicsection
		@public
		@fn boolean Set_Password ( $login, $old_password, $new_password )
		@brief
			Set the password for the user
	
		@param $login string
			Users login name
		@param $old_password string
			Users current password
		@param $new_password string
			What we are going to change users password to
		@throws Exception When password is empty
		@throws MySQL_Exception When there is a problem with mysql
		@return boolean
			TRUE if all went well
	*/
	public function Set_Password ( $login, $old_password, $new_password )
	{
		
		// Make sure new password is not null
		if (strlen($new_password) < 1)
		{
			throw new Exception( 'Password is empty' );
		}
		
		// Hash the passwords
		$hash_old_pass = $this->_Encrypt_Password($old_password);
		$hash_new_pass = $this->_Encrypt_Password($new_password);

		// Default is to allow password forever
		$password_expire_date = "'0001-01-01'";
		
		// Set the expire_date
		if (defined("PASSWORD_DURATION") && PASSWORD_DURATION)
		{
			$password_expire_date = "current date + ".PASSWORD_DURATION." days";
		}
		
		// update the password in the database
		$this->Update_Password($login, $hash_old_pass, $hash_new_pass);
		
		return TRUE;
		
	}
	
	protected function Find_Password($username)
	{
		
		$query = "
			SELECT
				login.crypt_password
			FROM
				login
			JOIN
				company ON company.company_id = login.company_id
			WHERE
				login = '" . $username . "' AND
				company.name_short = '" . $this->property_short . "'
			LIMIT 1
		";

		$result = $this->sql->Query($query);
		
		$row = $result->Fetch_Object_Row();
		$password = (isset($row->crypt_password) ? $row->crypt_password : FALSE);
		
		return $password; 
		
	}
	
	protected function Update_Password($login, $old, $new)
	{
		// Make the update
		$query = "
			UPDATE
				login
			SET
				crypt_password = '{$new}'
			WHERE
				crypt_password = '{$old}' AND
				login = '{$login}'
			LIMIT 1
		";
		$this->sql->Query($query);
		
		if ($this->sql->Affected_Row_Count() < 1)
		{
			throw new Exception('No records were updated!');
		}
		
		return TRUE;
		
	}
	
	/**
	* Determines whether an account is in active status.
	* 
	* @param 	string		$username:			The username to be checked for active status.
	* @return 	bool:		True if active, otherwise false.
	*/
	protected function Is_Active_Account( $username )
	{
		
		$query = "
			SELECT
				login.active_status
			FROM
				login
			JOIN
				company ON company.company_id = login.company_id
			WHERE
				login = '{$username}' AND
				company.name_short = '{$this->property_short}'
		";
		
		$mysql_result = $this->sql->Query( $query );
		$result = $mysql_result->Fetch_Object_Row();

		if( strtolower($result->active_status) == 'active' )
		{
			$return = TRUE;
		}
		else 
		{
			$return = FALSE;
		}

		return $return;
		
	}
	
}

?>
