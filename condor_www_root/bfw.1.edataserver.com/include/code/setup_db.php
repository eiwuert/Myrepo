<?php
/**
 * Setup_db 
 * 
 * Sets up mysql db connection and returns an instance
 * of mysql4 or mysqli
 */
 
	include_once(BFW_CODE_DIR.'server.php');
	
	/**
	 *
	 * MySQL "singleton"
	 *
	 */
	class Setup_DB
	{
			
		private static $instance = array();
		
		private static $auto_close = TRUE;
		private static $dual_write_db_types = array(
			'blackbox',
			'event_log',
			'blackbox_stats',
			'management',
			'site_types',
			'react',
			'stats'
		);
		public static function Connect($info, $type = false, $mode = BFW_MODE)
		{
			
			switch (strtolower($info['db_type']))
			{
				
				case 'mysqli':
					if(!isset($info['port'])) $info['port'] = NULL;
					
					include_once('mysqli.1.php');
					
					$sql = new MySQLi_1($info['host'], $info['user'], $info['password'], $info['db'], $info['port']);
					break;
					
				case 'mysql':
					//If we're dual writing AND it's a dual writeable database type
					//use the dual write class instead of the regular one.
					if(defined('OLP_DUAL_WRITE') && 
						OLP_DUAL_WRITE == true && 
						in_array(strtolower($type),self::$dual_write_db_types))
					{
						include_once(BFW_CODE_DIR.'OLP_DualWrite_DB.php');
						if (isset($info['port']) && strpos($info['host'],':') === false) $info['host'] .=  ':'.$info['port'];
						$sql = new OLP_DualWrite_DB(
							$info['host'], 
							$info['user'], 
							$info['password'], 
							DEBUG,
							$type,
							$mode);
					}
					else 
					{
						include_once('mysql.4.php');
						// add the port, if needed
						if (isset($info['port']) && strpos($info['host'],':') === false) $info['host'] .=  ':'.$info['port'];
						$sql = new MySQL_4($info['host'], $info['user'], $info['password'], DEBUG);
						
					}
					$sql->Connect(TRUE);
					break;
					
			}
						
			return $sql;
			
		}
		
		/**
		 * Return a PDO looking object. 
		 * Uses $type/$mode/$property_short just like Server::Get_Server
		 * returns DB_Database_1
		 *
		 * @param string $type
		 * @param string $mode
		 * @param string $property_short
		 * @return mixed
		 */
		public function Get_PDO_Instance($type, $mode, $property_short = NULL)
		{
			$sql = false;
			$info = Server::Get_Server($mode, $type, $property_short);
			$hash = md5($info['host'].@$info['port'].$info['user'].$info['password']);
			$pdo_hash = $hash.'PDO';
			if(!isset(self::$instance[$pdo_hash]) || !self::$instance[$pdo_hash] instanceof DB_Database_1)
			{
				if(strpos($info['host'],':'))
				{
					list($host,$port) = explode(':',$info['host']);
				}
				else 
				{
					$host = $info['host'];
					$port = (is_numeric($info['port'])) ? $info['port'] : 3306;
				}
				$config = new DB_MySQLConfig_1(
					$host,
					$info['user'],
					$info['password'],
					$info['db'],
					$port
				);
				$sql = $config->getConnection();
				self::$instance[$pdo_hash] = $sql;
			}
			return self::$instance[$pdo_hash];
		}
		
		/**
		 *
		 * Returns a MySQL_4 or MySQLi_1 object (depending on the connection type) for
		 * a given connection definition.
		 *
		 */
		public static function Get_Instance($type, $mode, $property_short = NULL)
		{
			// get database information
			$info = Server::Get_Server($mode, $type, $property_short);

			// allow connection re-use across databases
			$hash = md5($info['host'].@$info['port'].$info['user'].$info['password']);

			if (!isset(self::$instance[$hash]))
			{
				try
				{
					self::$instance[$hash] = self::Connect($info, $type, $mode);
				}
				catch (Exception $e)
				{
					//Failover for ecash databases GForge #6018 [MJ]
					if (strcasecmp($type, 'MYSQL') === 0)
					{
						switch (strtoupper($mode))
						{
							case 'LIVE_READONLY': //LIVE_READONLY is out, try SLAVE
								return self::Get_Instance($type, 'SLAVE', $property_short);
								break;
							case 'SLAVE': //SLAVE is out, try LIVE
								return self::Get_Instance($type, 'LIVE', $property_short);
								break;
							case 'LIVE': //LIVE is out, no more options left, throw error as usual.
							default:
								throw $e;
								break;
						}
					}
					else
					{
						throw $e;
					}
				}
			}
			
			$sql = new MySQL_Wrapper(self::$instance[$hash]);
			$sql->db_info = $info;
			$sql->db_type = 'mysql'; //$info['db_type'];
			if(strcasecmp($type, 'mysql') == 0 && 
				strcasecmp('live',$mode) == 0 && 
				!empty($_SESSION['application_id']) && 
				//Seems stupid, but we don't want to log for import ldb
				!class_exists('Import_LDB')) 
			{
				$event_log = Event_Log_Singleton::Get_Instance($mode, $_SESSION['application_id']);
				$event_log->Log_Event( 'LDB_MASTER_CONNECT', 'PASS', $property_short);
			}
			
			return $sql;
			
		}
		
	}
	
	/**
	 *
	 * A wrapper around MySQL_4 and MySQLi_1, which only serves to provide
	 * a local copy of the db_info array and ease the transition to shared
	 * connections. It's a hack, really.
	 *
	 */
	class MySQL_Wrapper
	{
		
		protected $sql;
		
		public $db_info;
		public $db_type;
		
		public function __construct($sql)
		{
			$this->sql = $sql;
			return;
		}
		
		public function __destruct()
		{
		}
		
		public function __get($name)
		{
			$value = $this->sql->$name;
			return $value;
		}
		
		public function __set($name, $value)
		{
			$this->sql->$name = $value;
			return;
		}
		
		public function __isset($name)
		{
			$set = isset($this->sql->$name);
			return $set;
		}
		
		public function __unset($name)
		{
			unset($this->sql->$name);
			return;
		}
		
		public function __wakeup()
		{
			return;
		}
		
		public function __sleep()
		{
			return array();
		}
		
		public function __call($name, $args)
		{
			$return = call_user_func_array(array(&$this->sql, $name), $args);
			return $return;
		}
		
		/**
		 * Returns the underlying connection, unwrapped
		 *
		 * @return MySQLi_1|MySQL_4
		 */
		public function getConnection()
		{
			return $this->sql;
	}
	}
	
?>
