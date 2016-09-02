<?php

require_once("mysqli.1.php");

class Security
{
	private $mysqli;
	
	private $system_id;

	private $agent_id;
	private $name_first;
	private $name_last;
	private $timeout;
	private $company_id;
	private $api_auth;
	private $company_short;
	const CRYPT_TYPE = MCRYPT_TWOFISH;
	const CRYPT_MODE = MCRYPT_MODE_ECB;
	
	public function __construct($mysqli, $timeout = 10)
	{
		$this->mysqli = $mysqli;
		$this->timeout = $timeout;
	}

	public function Login_User($system_name_short, $login, $password, &$session_storage_location = NULL)
	{		
		$password = $this->Mangle_Password($password);
		
		$query = "SELECT
						agent.agent_id, 
						agent.login, 
						agent.name_first, 
						agent.name_last,
						agent.company_id,
						system.system_id,
						company.name_short
					FROM
						agent,
						system,
						company
					WHERE 
						login = '{$login}'
						AND agent.crypt_password = '{$password}'
						AND agent.active_status = 'Active'
						AND system.name_short = '{$system_name_short}'
						AND system.system_id = agent.system_id
						AND company.company_id = agent.company_id";
		
		$result = $this->mysqli->Query($query);

		if( $row = $result->Fetch_Object_Row() )
		{
			$this->agent_id = $row->agent_id;
			$this->name_first = $row->name_first;
			$this->name_last = $row->name_last;
			$this->system_id = $row->system_id;
			$this->company_short = $row->name_short;
			$this->company_id = $row->company_id;
			$session_storage_location = strtotime("now");

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public function Get_System_ID()
	{
		return $this->system_id;
	}
	
	public function Get_Company_ID()
	{
		return $this->company_id;
	}
		
	public function Get_Name_First()
	{
		return $this->name_first;
	}

	public function Get_Name_Last()
	{
		return $this->name_last;
	}

	public function Get_Agent_ID()
	{
		return $this->agent_id;
	}
	
	//Grab the API Auth String if it's not set.
	public function Get_API_Auth()
	{
		if(!isset($this->api_auth))
		{
			$query = 'SELECT 
					login,crypt_password
				FROM
					agent
				JOIN system USING (system_id)
				WHERE
					agent.company_id = '.$this->company_id.'
				AND
					system.name_short=\'condorapi\'
			';
			
			$res = $this->mysqli->Query($query);
			if($row = $res->Fetch_Object_Row())
			{
				$user = $row->login;
				$pass = $this->Decrypt($row->crypt_password);
			}
			$this->api_auth = "$user:$pass";			
		}
		return $this->api_auth;
	}
	public function Get_Company_Short()
	{
		return $this->company_short;
	}
	public function Check_Timeout($session_storage_location)
	{
		if( strtotime("+{$this->timeout} hours", $session_storage_location) < strtotime("now") )
		{
			return FALSE;
		}
		return TRUE;
	}

	public static function Mangle_Password($clear_password)
	{
		return md5($clear_password);
	}
	//This is taken straight out of condor.4.edataserver.com/lib/security.php
	//It uses the same class name so I couldn't just include it so this is
	//the best I can do.
	private function Decrypt($str,$key='IMALTPSASTMOAPMO')
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