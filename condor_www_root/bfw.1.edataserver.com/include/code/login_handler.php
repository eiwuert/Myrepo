<?php
/**
* Login Handler
* 
* Checks for the login in LDB
* @author Various People
*/
require_once 'crypt.3.php';
require_once 'security.8.php';

class Login_Handler
{
	private $security;

	private $sqli;
	private $database;

	public $application_id;
	private $applog;
	private $property_short;
	
	public function __construct( &$sqli, $property_short, $database, &$applog )
	{
		$this->security = new OLPSecurity($sqli, $property_short);

		$this->sqli = &$sqli;
		$this->database = $database;
		
		$this->applog = &$applog;
		$this->application_id = null;
		$this->property_short = $property_short;
	}
	
	/**
	 * Login User w/ App ID
	 * 
	 * Old login process. This function wants very much to be removed.
	 * @param int app id
	 * @param object OLP SQL
	 * @return object User Info 
	 */
	public function Login_User_App_ID($application_id, $sql)
	{
		if(empty($application_id))
        {
            $error = "Cannot Login User App ID - App ID is not set";
            $this->applog->Write($error);
            throw new Exception($error);
        }
        
        $count = 0;
        $return = FALSE;

        try
    	{
			$query = "
				SELECT application_id, login, password
				FROM application
				JOIN customer ON customer.customer_id = application.customer_id
				WHERE application.application_id = " . $application_id;
			
			$result = $this->sqli->Query($query);
			$row = $result->Fetch_Object_Row();
			$count = $result->Row_Count();
    	}
    	catch(Exception $e)
    	{
    		$count = 0;
    	}
        
		//Make sure we have a record; we don't want to overwrite the app id.
        if($count > 0)
        {
 			$this->application_id = $row->application_id;
			
			// get password for password change function if login exists
			if ($row->login)
			{
				$password = crypt_3::Decrypt( $row->password );
				$_SESSION['cs']['cust_password'] = $password;
				
				$return = $row->login;
			}
        }

		return $return;
	}
	
	/**
	* Logs a user in based on their username and password.
	*
	* @param string	The username to be checked.
	* @param string	The password to be checked.
	* @param bool Whether the account ever expires.
	*/ 
	public function Login_User( $username, $password, $no_expire = FALSE )
	{
		$return = FALSE;
		
		try
		{
			$return = $this->security->Login_User($username, $password, $no_expire);
		}
		catch(MySQL_Exception $e)
		{
			$this->applog->Write('Query exception in Login_Handler for LDB: ' . $e->getMessage());
			$return = FALSE;
		}
		
		return $return;
	}
	
	public function Set_Password($login, $password, $password_new)
	{
		$set_password_result = FALSE;
		
		try
		{
			//If LDB isn't connected, don't even try an update.
			if(!is_null($this->sqli->Get_Link()->sqlstate))
			{
				$this->security->Set_Password($login, $password, $password_new);
				$set_password_result = TRUE;
			}
		}
		catch(Exception $e)
		{
			//Just ignore LDB update errors.
		}
		
		return $set_password_result;
	}
	
	public function Find_Password($email)
	{
		$pass = null;
		try {
			$pass = $this->Find_Password_From_Email($email);
		}
		catch(MySQL_Exception $e)
		{
		}
		
		return $pass;
	}
	
	public function Find_Password_From_Username($username)
	{
		$pass = null;
		try {
			$pass = $this->Find_Password($username);
		}
		catch(MySQL_Exception $e)
		{
		}
		
		return $pass;
	}
	
	public function Find_Username($ssn)
	{
		$user = null;
		try {
			$user = $this->security->Find_Username($ssn);
		}
		catch(MySQL_Exception $e)
		{
		}
		
		return $user;
	}
	
	public function Find_User_Info($email)
	{
		try {
			$results = $this->security->Find_User_Info($email);
		}
		catch(MySQL_Exception $e)
		{
			return false;
		}
		
		return $results;
	}
	
	public function Find_User_Info_By_App_ID($app_id)
	{
		try {
			$results = $this->security->Find_User_Info_By_App_ID($app_id);
		}
		catch(MySQL_Exception $e)
		{
			return false;
		}
		
		return $results;
	}
	
	public function Decrypt_Password($crypt_pass)
	{
		$password = FALSE;
		
		try
		{
			$password = $this->security->Decrypt_Password($crypt_pass);
		}
		catch(Exception $e)
		{
			$password = FALSE;
		}
		
		return $password;
	}
	
	public function Find_App_ID($username)
	{
		if(!empty($username))
		{
			$app_id = $this->security->Find_App_ID($username);
					  
			if(!$app_id) return false;
			
			$this->application_id = $app_id;
		}
		
		return $this->application_id;
	}
}
?>