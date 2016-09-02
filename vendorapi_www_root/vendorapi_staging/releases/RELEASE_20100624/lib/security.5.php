<?php

class Security_5
{
	private $db2;
	private $table;
	private $schema;
	
	private $agent_id;
	private $name_first;
	private $name_last;
	private $timeout;
		
	public function __construct($db2, $table, $schema, $timeout = 8)
	{
		$this->db2 = $db2;
		$this->table = $table;
		$this->schema = $schema;
		$this->timeout = $timeout;
	}
	
	public function Login_User($login, $password)
	{
		$password = md5($password);
		
		$query = "SELECT
						date_expire_account, 
						date_expire_password, 
						agent_id, 
						login, 
						name_first, 
						name_last
					FROM 
						{$this->schema}.{$this->table}
					WHERE 
						login = '{$login}'
						AND crypt_password = '{$password}'";
		
		$login_query = $this->db2->Query($query);
		
		$login_query->Execute();
		
		if( $row = $login_query->Fetch_Object() )
		{
			$this->agent_id = $row->AGENT_ID;
			$this->name_first = $row->NAME_FIRST;
			$this->name_last = $row->NAME_LAST;
			
			$_SESSION['security_5']['login_time'] = strtotime("now");
								
			return TRUE;
		}
		else
		{
			return FALSE;
		}
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
	
	public function Check_Timeout()
	{
		if( strtotime("+{$this->timeout} hours", $_SESSION['security_5']['login_time']) < strtotime("now") )
		{
			return FALSE;
		}
		return TRUE;
	}
}

?>