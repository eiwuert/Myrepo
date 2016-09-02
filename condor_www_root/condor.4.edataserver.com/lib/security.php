<?php

require_once("mysqli.1.php");
require_once('mysql_pool.php');

/**
 * Security class used for Condor 2.0. Based off of Security_6, this modification adds
 * a company ID to the agent table and associates each agent with a company. Security_6 is
 * still compatable with this version if it needs to be used.
 *
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */
class Security
{
	
	private $mode;
	private $mysqli;
	
	private $system_id;

	private $agent_id;
	private $name_first;
	private $name_last;
	private $company_id;
	private $timeout;
	protected $company_data;
		
	const CRYPT_TYPE = MCRYPT_TWOFISH;
	const CRYPT_MODE = MCRYPT_MODE_ECB;
	
	public function __construct($mode, $timeout = 10)
	{
		// get a database connection
		
		$this->mode = $mode;
		
		//Now uses the default CONDOR database defines
		$this->mysqli = MySQL_Pool::Connect('condor_' . $mode);
		if(!$this->mysqli instanceof MySQLi_1)
		{
			throw new Exception("Could not connect to db $mode\n");
		}
		return;
		
	}

	/**
	 * Attempts to authenticate the user using either the new
	 * login method OR the old one.
	 *
	 * @param string $system_name_short
	 * @param string $login
	 * @param string $password
	 * @param string $session_storage_location
	 * @param boolean $new_login
	 * @return bool
	 */
	public function Login_User($system_name_short, $login, $password, &$session_storage_location = NULL, $new_login = true)
	{
		
		$ret_val = FALSE;
		$orig_pw = $password;
		if($new_login === true && $system_name_short == 'condorapi')
		{
			$password = $this->mysqli->Escape_String(self::Encrypt($password));
		}
		else
		{
			
			$password = $this->mysqli->Escape_String($this->Mangle_Password($password));
		}
		$slogin = $this->mysqli->Escape_String($login);
		$query = "
			SELECT
				agent.agent_id,
				agent.login,
				agent.name_first,
				agent.name_last,
				agent.company_id,
				system.system_id,
				company.name as company_name,
				company.name_short as company_name_short
			FROM
				condor_admin.agent
				JOIN condor_admin.system ON system.system_id = agent.system_id
				JOIN condor_admin.company ON agent.company_id = company.company_id
			WHERE
				agent.login = '$slogin'
				AND agent.crypt_password = '$password'
				AND agent.active_status = 'active'
				AND system.name_short = '$system_name_short'";
		try 
		{
			$result = $this->mysqli->Query($query);
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		if ($row = $result->Fetch_Object_Row())
		{
			$agent_data = new stdClass();
			$agent_data->agent_id = $row->agent_id;
			$agent_data->name_first = $row->name_first;
			$agent_data->name_last = $row->name_last;
			$agent_data->system_id = $row->system_id;
			$agent_data->login = $row->login;
						
			$this->company_data = new stdClass();
			$this->company_data->name = $row->company_name;
			$this->company_data->name_short = $row->company_name_short;
			$this->company_data->company_id = $row->company_id;
			$this->company_data->agent_data = $agent_data;
			
			$session_storage_location = strtotime("now");
			
			$ret_val = TRUE;
			
		}
		elseif($new_login === true && $system_name_short == 'condorapi')
		{
			$val = $this->Login_User($system_name_short, $login, $orig_pw, $session_storage_location, false);
			//The login is correct, so we better update the login to the new way
			if($val === true)
			{
				$this->Update_Old_Login($this->Get_Agent_ID(),$login, $orig_pw, $sys_id);
				$ret_val = TRUE;
			}
			else
			{
				$ret_val = FALSE;
			}
		}

		return $ret_val;
		
	}
	
	/**
	 * Update a login still setup for the old method to the 
	 * new one.
	 *
	 * @param int $agent_id
	 * @param string $login
	 * @param string $password
	 */
	private function Update_Old_Login($agent_id, $login, $password)
	{
		$s_pw = $this->mysqli->Escape_String(self::Encrypt($password));
		$query = "
			UPDATE 
				condor_admin.agent 
			SET 	
				crypt_password='$s_pw' 
			WHERE 
				agent_id=$agent_id 
			AND 
				login='$login'
			LIMIT 1
		";
		$this->mysqli->Query($query);
	}
	
	/**
	 *	Get all Agent Ids for a company based on an api_auth string
	 *
	 * @param string $auth_str
	 * @return array
	 */
    public function Get_All_Company_Agents($auth_str)
    {
    	list($user,$pass) = split(':',$auth_str);
    	$pass = self::Encrypt($pass);
    	$query = "
    		SELECT 
    			agent_id 
    		FROM 
    			condor_admin.agent 
            WHERE 
            	company_id=( 
                	SELECT 
                		company_id 
                	FROM 
                		condor_admin.agent 
                    WHERE 
                    	login='$user' 
                    AND 
                    	crypt_password='$pass'
                    LIMIT 1
                  );";
        $res = $this->mysqli->Query($query);
        $ret = Array();
        while ($row = $res->Fetch_Object_Row())
        {
        	$ret[] = $row->agent_id;
        }
	
        return $ret;
    }
	
    public function Get_Company_Name()
    {
    	if(is_object($this->company_data) && is_string($this->company_data->name))
    	{
    		return $this->company_data->name;
    	}
    	else
    	{
    		return NULL;
    	}
    
    }
    
    /**
     * Return the company_data object
     *
     * @return unknown
     */
    public function getCompanyData()
    {
    	return $this->company_data;
    }
    
	/**
	 * Returns the system ID.
	 *
	 * @return int
	 */
	public function Get_System_ID()
	{
		if(is_object($this->company_data) && 
			is_object($this->company_data->agent_data) && 
			is_numeric($this->company_data->agent_data->system_id))
		{
			return $this->company_data->agent_data->system_id;
		}
		else 
		{
			return NULL;
		}
	}
	
	/**
	 * Returns the user's first name.
	 *
	 * @return string
	 */
	public function Get_Name_First()
	{
		if(is_object($this->company_data) && 
			is_object($this->company_data->agent_data) && 
			is_string($this->company_data->agent_data->name_first))
		{
			return $this->company_data->agent_data->name_first;
		}
		else 
		{
			return NULL;
		}
	}

	/**
	 * Returns the user's last name.
	 *
	 * @return string
	 */
	public function Get_Name_Last()
	{
		if(is_object($this->company_data) && 
			is_object($this->company_data->agent_data) && 
			is_string($this->company_data->agent_data->name_last))
		{
			return $this->company_data->agent_data->name_last;
		}
		else 
		{
			return NULL;
		}
	}
	
	public function Get_Agent_Login()
	{
		if(is_object($this->company_data) && 
			is_object($this->company_data->agent_data) && 
			is_string($this->company_data->agent_data->login))
		{
			return $this->company_data->agent_data->login;		
		}
		else 
		{
			return NULL;
		}
	}

	/**
	 * Returns the user's agent ID.
	 *
	 * @return int
	 */
	public function Get_Agent_ID()
	{
		if(is_object($this->company_data) && 
			is_object($this->company_data->agent_data) && 
			is_numeric($this->company_data->agent_data->agent_id))
		{
			return $this->company_data->agent_data->agent_id;
		}
		else 
		{
			return NULL;
		}
	}
	
	/**
	 * Returns the company ID the user is associated with.
	 *
	 * @return int
	 */
	public function Get_Company_ID()
	{
		if(is_object($this->company_data) && 
			is_numeric($this->company_data->company_id))
		{
			return $this->company_data->company_id;
		}
		else 
		{
			return NULL;
		}
	}
	
	public function Check_Timeout($session_storage_location)
	{
		
		$expired = (strtotime("+{$this->timeout} hours", $session_storage_location) < strtotime("now"));
		
		return $expired;
	}
	
	/**
	 * Creates a hash of the password.
	 *
	 * @param string $clear_password
	 * @return string
	 */
	public static function Mangle_Password($clear_password)
	{
		return md5($clear_password);
	}
	/**
	* Encryption Used to store API_AUTH stuff
	* @param string $str 
	* @param string $key
	* @return string
	*/
    public static function Encrypt($str,$key='IMALTPSASTMOAPMO')
    {

        $module = mcrypt_module_open(self::CRYPT_TYPE,"",self::CRYPT_MODE,"");
        $key = substr($key,0,mcrypt_enc_get_key_size($module));
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($module),MCRYPT_RAND);
        mcrypt_generic_init($module,$key,$iv);
        $encrypted_str = mcrypt_generic($module,$str);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        return base64_encode($encrypted_str);
    }
	/** Decryption Used to store API_AUTH stuff
	* @param string $str
	* @param string $key
	* @return string
	*/
    public static function Decrypt($str,$key='IMALTPSASTMOAPMO')
    {
        $mcrypt = mcrypt_module_open(self::CRYPT_TYPE,"",self::CRYPT_MODE,"");
        $key = substr($key,0,mcrypt_enc_get_key_size($mcrypt));
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($mcrypt),MCRYPT_RAND);
        mcrypt_generic_init($mcrypt,$key,$iv);
        $decrypted_str = trim(mdecrypt_generic($mcrypt,base64_decode($str)));
        mcrypt_generic_deinit($mcrypt);
        mcrypt_module_close($mcrypt);
        return $decrypted_str;
    }

}
?>
