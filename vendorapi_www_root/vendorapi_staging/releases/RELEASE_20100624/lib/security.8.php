<?php
/**
 * Security 8
 * 
 * Use ECash's latest login scheme - the customer table
 * @author Jason Gabriele (well sorta)
 */
require_once 'crypt.3.php';

class Security_8
{
	protected $sql;
	protected $property_short;
	
	/**
	 * Decrypts a password.
	 *
	 * @param string The password to be decrypted.
	 * @return string The unencrypted password.
	 */
	public static function Decrypt_Password( $password )
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
	 * Encrypts a password.
	 *
	 * @param string The password to be encrypted.
	 * @return string The encrypted password.
	 */
	public static function Encrypt_Password( $password )
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
			}
		}
		else
		{
			$return = crypt_3::Hash( $password );
		}

		return $return;
	}

	public function __construct( &$sqli, $property_short )
	{
		$this->sql				= &$sqli;
		$this->property_short 	= $property_short;
	}

	/**
	* Logs a user in based on their username and password.
	*
	* @param string The username to be checked.
	* @param string The password to be checked.
	* @param bool Whether the account ever expires.
	* @return string Encrypted password
	*/ 
	public function Login_User( $username, $password, $no_expire = FALSE )
	{
		// get the password from the database
		$real_password = trim($this->Find_Password($username));

		$enc_pass = trim(self::Encrypt_Password(trim($password)));

		if (($real_password !== FALSE) && $enc_pass == $real_password)
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
	 * Set Password
	 * 
	 * Updates the user's password
	 * @param string User Name
	 * @param string Old Password (unencrypted)
	 * @param string New Password (unencrypted)
	 * @return boolean
	 */
	public function Set_Password ( $login, $old_password, $new_password )
	{
		// Make sure new password is not null
		if (strlen($new_password) < 1)
		{
			throw new Exception( 'Password is empty' );
		}
		
		// Hash the passwords
		$hash_old_pass = self::Encrypt_Password($old_password);
		$hash_new_pass = self::Encrypt_Password($new_password);
		
		// update the password in the database
		$this->Update_Password($login, $hash_old_pass, $hash_new_pass);
		
		return TRUE;
	}
	

	
	/**
	 * Find App ID
	 * 
	 * Finds the latest app id for a username
	 * @param string User Name
	 * @return int App ID or false if user has two apps with balance > 0
	 */
	public function Find_App_ID($username)
	{
		$query = "
			SELECT
				a.application_id
			FROM
				application a
				INNER JOIN customer c
					ON a.customer_id = c.customer_id
				INNER JOIN company co
					ON a.company_id = co.company_id
				INNER JOIN application_status_flat asf
					ON a.application_status_id = asf.application_status_id
			WHERE
				c.login = '" . $this->sql->Escape_String($username) . "'
				AND co.name_short = '" . strtolower($this->sql->Escape_String($this->property_short)) . "'
				AND NOT (
					asf.level1 = 'prospect'
					AND asf.level0 IN ('agree','disagree','confirmed','confirm_declined','pending')
				)
			ORDER BY a.date_created ASC";
			  
		$result = $this->sql->Query($query);
		$count = $result->Row_Count();
		
		$last_balance_app = false;
		
		if($count > 1)
		{
			//Check for balance greater than 0
			$last_app = false;
			$prior_balance = false;
			
			while($row = $result->Fetch_Array_Row())
			{
				//Grab balance info
				require_once 'ecash_common/ecash_api/ecash_api.2.php';
				$ec2 = new eCash_API_2($this->sql, $row['application_id']);
				$balance = (float)$ec2->Get_Payoff_Amount();
				$last_app = $row['application_id'];

				if($balance > 0.0 && !$prior_balance)
				{
					$last_balance_app = $row['application_id'];
					$prior_balance = true;
				} 
				elseif($balance <= 0.0)
				{
					continue;
				}
				else
				{
					//According to the spec must not return the app id if they
					//have multiple loans with balance > 0
					return false;
				}
			}
			
			//Return last app id
			return $last_balance_app ? $last_balance_app : $last_app;
		}
		elseif($count == 1)
		{
			$row = $result->Fetch_Array_Row();
			return $row['application_id'];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Find Password
	 * 
	 * Finds the password for a username
	 * @param string User Name
	 * @return string Encrypted password
	 */
	public function Find_Password($username)
	{
		$query = "
			SELECT c.password
			FROM customer as c, company as co
			WHERE c.company_id = co.company_id
			  AND c.login = '" . $this->sql->Escape_String($username) . "'
			  AND co.name_short = '" . $this->sql->Escape_String(strtolower($this->property_short)) . "'
			LIMIT 1";

		$result = $this->sql->Query($query);

		if($result->Row_Count() == 0) return false;
		
		$row = $result->Fetch_Array_Row();
		
		return $row['password'];
	}
	
	/**
	 * Find password from email
	 * 
	 * @param string Email
	 * @return string Encrypted password
	 */
	public function Find_Password_From_Email($email)
	{
		$query = "
				SELECT c.password
				FROM customer as c, application as a, company as co
				WHERE c.company_id = co.company_id
				  AND a.application_id = c.application_id
				  AND application.email = '" . $this->sql->Escape_String($email) . "'
				  AND co.name_short = '" . $this->sql->Escape_String($this->property_short) . "'
				ORDER BY c.date_created DESC LIMIT 1";

		$result = $this->sql->Query($query);
		
		if($result->Row_Count() == 0) return false;
		
		$row = $result->Fetch_Array_Row();
		
		return $row['password'];; 
	}
	
	/**
	 * Find Username
	 * 
	 * Finds a username using a SSN and Property Short
	 * @param string SSN
	 * @param Property Short
	 * @return string Username or false on fail
	 */
	public function Find_Username($ssn)
	{
		$query = "
				SELECT c.login
				FROM customer as c, company as co
				WHERE c.company_id = co.company_id
				  AND c.ssn = '" . $this->sql->Escape_String($ssn) . "'
				  AND co.name_short = '" . $this->sql->Escape_String($this->property_short) . "'
				LIMIT 1";

		$result = $this->sql->Query($query);
		
		if($result->Row_Count() == 0) return false;
		
		$row = $result->Fetch_Array_Row();
		
		return $row['login'];
	}
	
	/**
	 * Find User Info
	 * 
	 * Find user information By Email
	 * @param string Email Address
	 * @return Array User info
	 */
	public function Find_User_Info($email)
	{
		$query = "
				SELECT c.login,c.password,a.name_first,a.name_last
				FROM customer as c, company as co, application as a
				WHERE c.company_id = co.company_id
				  AND a.customer_id = c.customer_id
				  AND a.email = '" . $this->sql->Escape_String($email) . "'
				  AND co.name_short = '" . $this->sql->Escape_String($this->property_short) . "'
				ORDER BY a.date_created DESC";

		$result = $this->sql->Query($query);
		
		if($result->Row_Count() == 0) return false;
		
		return $result->Fetch_Array_Row();
	}
	
	/**
	 * Find User Info By App ID
	 * 
	 * Find user information By App ID
	 * @param string App ID
	 * @return Array User info
	 */
	public function Find_User_Info_By_App_ID($app_id)
	{
		$query = "
				SELECT c.login,c.password,a.name_first,a.name_last
				FROM customer as c, company as co, application as a
				WHERE c.company_id = co.company_id
				  AND a.customer_id = c.customer_id
				  AND a.application_id = " . $app_id . "
				  AND co.name_short = '" . $this->sql->Escape_String($this->property_short) . "'
				ORDER BY a.date_created DESC";

		$result = $this->sql->Query($query);
		
		if($result->Row_Count() == 0) return false;
		
		return $result->Fetch_Array_Row();
	}
	
	/**
	 * Update Password
	 * 
	 * Updates the password in the database
	 * @param string User Name
	 * @param string Old password (Encrypted)
	 * @param string New password (Encrypted)
	 * @return boolean True on success
	 */
	protected function Update_Password($login, $old, $new)
	{
		// Make the update
		$query = "
			UPDATE customer
			SET password = '" . $this->sql->Escape_String($new) . "'
			WHERE password = '" . $this->sql->Escape_String($old) . "'
			  AND login = '" . $this->sql->Escape_String($login) . "'
			LIMIT 1";
		
		$this->sql->Query($query);
		
		if ($this->sql->Affected_Row_Count() < 1)
		{
			throw new Exception('No records were updated!');
		}
		
		return TRUE;
	}
}
?>
