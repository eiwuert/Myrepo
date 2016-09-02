<?php
	
	class MySQL_Pool
	{
		protected static $defs = array();
		protected static $pool = array();
		
		/**
		 * Defines a connection in the defintion list.
		 *
		 * @param string $name name to reference this connection by
		 * @param string $host hostname
		 * @param string $user user to login with
		 * @param string $pass password to login with
		 * @param string $db database to use
		 * @param int $port port to connect with (Default 3306)
		 * @return bool 
		 */
		public static function Define($name, $host, $user, $pass, $db, $port = NULL)
		{
			$defined = FALSE;
			
			if (!isset(self::$defs[$name]))
			{
				// connection definition
				$def = array(
					'host' => $host,
					'username' => $user,
					'password' => $pass,
					'port' => $port,
					'database' => $db,
				);
				
				// store the definition
				self::$defs[$name] = $def;
				$defined = TRUE;
			}
			
			return $defined;
			
		}
	
		/**
		 * Acquires a MySQLi_1 object for the given definition
		 * 
		 * @param string $name name of the definition to use
		 * @return MySQLi_1 
		*/
		public static function &Connect($name)
		{
			$sql = FALSE;
			
			if (isset(self::$pool[$name]))
			{
				// return the existing connection
				$sql = &self::$pool[$name];
			}
			elseif (isset(self::$defs[$name]))
			{
				// get a new connection
				$def = self::$defs[$name];
				$sql = new MySQLi_1($def['host'], $def['username'], $def['password'], $def['database'], $def['port']);
			}
			
			return $sql;
		}
		
		/**
		 * Read definition detail
		 *
		 * @param string $name definition name to obtain information on
		 * @return mixed null if invalid, or array containing schema info
		*/
		public static function Get_Definition($name)
		{
			if (isset(self::$defs[$name]))
			{
				return self::$defs[$name];
			}
			return null;
		}
	}
	
?>