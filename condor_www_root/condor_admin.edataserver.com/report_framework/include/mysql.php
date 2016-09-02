<?php
	
	class MySQL_Connection
	{
		
		private $link = NULL;
		private $database = NULL;
		
		public function __construct()
		{
		}
		
		public function __destruct()
		{
			if ($this->link) $this->Disconnect();
		}
		
		public function Connect($host, $user, $pass, $port = NULL, $database = NULL, $force_new = FALSE)
		{
			
			$result = FALSE;
			
			if (!$this->link)
			{
				
				if (is_numeric($port) && (strpos($host, ':') === FALSE)) $host .= ':'.$port;
				$result = @mysql_connect($host, $user, $pass, $force_new);
				
				if ($result !== FALSE)
				{
					
					$this->link = $result;
					
					if ($database !== NULL)
					{
						$this->Select($database);
					}
					
				}
				else
				{
					throw new Exception(mysql_error());
				}
				
			}
			
			return($result);
			
		}
		
		public function Disconnect()
		{
			
			if ($this->link)
			{
				@mysql_close($this->link);
				$this->link = NULL;
			}
			
			return(TRUE);
			
		}
		
		public function Select($database)
		{
			
			$result = FALSE;
			
			if ($this->link)
			{
				
				if ($database != $this->database)
				{
					
					$result = @mysql_select_db($database, $this->link);
					
					if ($result !== FALSE)
					{
						$this->database = $database;
					}
					else
					{
						throw new Exception(mysql_error($this->link));
					}
					
				}
				else
				{
					$result = TRUE;
				}
				
			}
			
			return($result);
			
		}
		
		public function Query($query, $buffer = TRUE)
		{
			
			$result = FALSE;
			
			if ($this->link)
			{
				
				if ($buffer)
				{
					$result = @mysql_query($query, $this->link);
				}
				else
				{
					$result = @mysql_unbuffered_query($query, $this->link);
				}
				
				if (is_resource($result))
				{
					$result = new MySQL_Result($result, $buffer);
				}
				elseif ($result === FALSE)
				{
					throw new Exception(mysql_error($this->link));
				}
				
			}
			
			return($result);
			
		}
		
		public function Escape($string)
		{
			
			if ($this->link && function_exists('mysql_real_escape_string'))
			{
				$string = @mysql_real_escape_string($string, $this->link);
			}
			else
			{
				$string = @mysql_escape_string($string);
			}
			
			return($string);
			
		}
		
		public function Affected_Rows()
		{
			
			$result = FALSE;
			
			if ($this->link)
			{
				$result = @mysql_affected_rows($this->link);
			}
			
			return($result);
			
		}
		
		public function Insert_ID()
		{
			
			$result = FALSE;
			
			if ($this->link)
			{
				$result = @mysql_insert_id($this->link);
			}
			
			return($result);
			
		}
		
	}
	
	class MySQL_Result
	{
		
		private $result = NULL;
		private $buffered = TRUE;
		
		public function __construct($result, $buffered = TRUE)
		{
			
			if (is_resource($result))
			{
				$this->result = $result;
				$this->buffered = $buffered;
			}
			
		}
		
		public function __destruct()
		{
			if ($this->result) $this->Free();
		}
		
		public function Free()
		{
			@mysql_free_result($this->result);
			$this->result = NULL;
		}
		
		public function Next()
		{
			
			$row = FALSE;
			
			if ($this->result)
			{
				$row = @mysql_fetch_assoc($this->result);
			}
			
			return($row);
			
		}
		
		public function Count()
		{
			
			$count = FALSE;
			
			if ($this->result && $this->buffered)
			{
				$count = @mysql_num_rows($this->result);
			}
			
			return($count);
			
		}
		
		public function Seek($row)
		{
			
			$result = FALSE;
			
			if ($this->result && $this->buffered)
			{
				$result = @mysql_data_seek($this->result, $row);
			}
			
			return($result);
			
		}
		
		public function Field_Info($offset)
		{
			
			$field = FALSE;
			
			if ($this->result)
			{
				$field = @mysql_fetch_field($this->result, $offset);
			}
			
			return $field;
			
		}
		
	}
	
	class MySQL_Pool
	{
		
		static protected $pool = array();
		
		static public function &Connect($host, $user, $password, $db, $port = NULL, $force_new = FALSE)
		{ 
			
			$hash = md5($host.$user.$password);
			
			if (($force_new !== TRUE) && isset(self::$pool[$hash]))
			{
				$sql = &self::$pool[$hash];
				$sql->Select($db);
			}
			else
			{
				
				// create a new connection
				$sql = new MySQL_Connection();
				$sql->Connect($host, $user, $password, $port, $db, TRUE);
				
				// save it for others
				self::$pool[$hash] = &$sql;
				
			}
			
			return $sql;
			
		}
		
	}
	
?>