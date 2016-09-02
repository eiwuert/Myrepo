<?php

require_once("mysqli.1.php");

class Security_6
{
	private $mysqli;
	
	private $system_id;

	private $agent_id;
	private $name_first;
	private $name_last;
	private $timeout;
	
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
						system.system_id
					FROM
						agent,
						system
					WHERE 
						login = '{$login}'
						AND agent.crypt_password = '{$password}'
						AND agent.active_status = 'Active'
						AND system.name_short = '{$system_name_short}'
						AND system.system_id = agent.system_id";		
		
		$result = $this->mysqli->Query($query);

		if( $row = $result->Fetch_Object_Row() )
		{
			$this->agent_id = $row->agent_id;
			$this->name_first = $row->name_first;
			$this->name_last = $row->name_last;
			$this->system_id = $row->system_id;
			
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
}

?>