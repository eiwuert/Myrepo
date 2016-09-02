<?php

//	$err_log = new Err_Log($this->application_id, $this->config->site_type, $this->config->page ,$this->sql, $db['db']);
class Err_Log {
	protected $error_limit = 5;
	protected $load_called = FALSE;

	protected $sql;
	protected $database;

	protected $application_id = 0;
	protected $page;
	protected $site_type;
	protected $mode;
	protected $promo_id;

    protected $error = array();
    
    public function __construct($application_id, $promo_id, $site_type, $page, $mode, &$sql,$db = NULL) 
    {
    	$error_limit = array( 'LIVE' => 5, 'LOCAL' => 999, 'RC' => 999 );
    	    
    	$this->application_id = $application_id;
    	$this->promo_id = $promo_id;
    	$this->site_type = $site_type;
    	$this->page = $page;	

    	$this->mode = $mode;
		$this->error_limit = $error_limit[$mode];

		if (is_object($sql) && (!is_null($db)))
		{
			// use an existing connection
			$this->sql = &$sql;
			$this->database = $db;
		}
		elseif (is_array($sql))
		{
			$this->sql = &$this->Connect($sql);
			$this->database = $sql['db'];
		}
    }
	
	
	protected function Connect($server)
	{
		$sql = FALSE;
		
		if (is_array($server))
		{
			// connect to the server
			$sql = new MySQL_4($server['host'], $server['user'], $server['password']);
			$sql->Connect();
		}
		
		return($sql);
	}
	
	
	private function Load_Application_Errors()
	{
		if (!$this->application_id)
		{
			return;
		}
		
		$query = "SELECT error_log_id,error_code,num_errors FROM error_log " .
				"WHERE application_id = {$this->application_id} AND site_type = '{$this->site_type}' AND page = '{$this->page}'";
		
		$result = $this->sql->Query($this->database, $query);
	
		while(($row = $this->sql->Fetch_Object_Row($result)))
		{
			$this->error[$row->error_code] = $row;
		}
		
		$this->load_called = TRUE;
		return;
	}
	
	
	public function Set_Error($error = null)
	{
	
		if (! $this->load_called)
		{
			$this->Load_Application_Errors();
		}

		if (!empty($error))
		{
			foreach ($error as $k => $v)
			{
				if (Is_Numeric($k))
				{				
					if (preg_match('/^Your Electronic Signature/', $v))
					{
						$v = 'esignature_failed';
					}	
					
					if (preg_match('/^Your login\/password/', $v))
					{
						$v = 'login_username';
					}	
					
					if (preg_match('/^Bank Account Warning/', $v))
					{
						$v = 'bad_bank_account';
					}	
					
					if (preg_match('/^The login you entered/', $v))
					{
						$v = 'mail_password';
					}	
				
					if (preg_match('/^No login for this/', $v))
					{
						$v = 'login_application_id';
					}	
				
					if (preg_match('/^Password too short/', $v))
					{
						$v = 'password_length';
					}	
				
					if (preg_match('/^Passwords didn/', $v))
					{
						$v = 'password_mismatch';
					}	
				
					if (preg_match('/^Please enter you current/', $v))
					{
						$v = 'password_request';
					}	
				
					if (preg_match('/^Password incorrect/', $v))
					{
						$v = 'password_incorrect';
					}	
				
					if (preg_match('/^Your info could not be found/', $v))
					{
						$v = 'react_no_record_in_ldb';
					}	
				
					if (preg_match('/^No records were updated/', $v))
					{
						$v = 'password_update_failed';
					}	
				
					$this->error[$v]->num_errors++;
					$this->error[$v]->error_code = $v;
					$this->error[$v]->dirty = TRUE;
				}
				else
				{
					$this->error[$k]->num_errors++;
					$this->error[$k]->error_code = $k;
					$this->error[$k]->dirty = TRUE;					
				}
			}
		}
	}
	
	
	public function Write_Errors()
	{
		// Do we actually have an error?
		if (count($this->error))
		{	
	        $error_count = 0;
            
			foreach ($this->error as $error)
			{
				if ($error->dirty)
				{
					$error_count++;
				}
			}

			if ($error_count <= $this->error_limit)
			{	
				foreach ($this->error as $error)
				{
					if ($error->dirty)
					{		
						if ($error->error_log_id)
						{
							$query = "UPDATE error_log " .
									"SET num_errors = {$error->num_errors} " .
									"WHERE error_log_id = {$error->error_log_id}";
						}
						else
						{
							$error_code = mysql_escape_string(substr($error->error_code,0,60));
						
							$query = "INSERT INTO error_log " .
									"(application_id, error_code, num_errors, site_type, page, promo_id) " .
									"VALUES ('{$this->application_id}','{$error_code}','{$error->num_errors}','{$this->site_type}','{$this->page}','{$this->promo_id}')";
						}
						 $result = $this->sql->Query($this->database, $query);
					}
				}
			}
		}
	}


} // End of Err_Log


/* Sql Definition
create table error_log (
    `error_log_id` int(10) unsigned not null auto_increment,
    `date_modified` timestamp(14) NOT NULL,
    `application_id` int(10) unsigned NOT NULL,
    `error_code` varchar(64) NOT NULL,
	`num_errors` tinyint(3) unsigned NOT NULL,
	`site_type` varchar(50) NOT NULL,
	`page` varchar(50) NOT NULL,
	`promo_id` int(10) unsigned default 0,
	primary key (error_log_id),
	key application_id (application_id),
	key error_code (error_code),
	key err (application_id,error_code),
	key err2 (application_id,site_type, page)
);
 */
?>