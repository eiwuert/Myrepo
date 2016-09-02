<?php
       
require 'libolution/AutoLoad.1.php';
    
define('TEST_DB_HOST', 'localhost');
define('TEST_DB_USER', 'root');
define('TEST_DB_PASS', '');
define('TEST_DB', 'ldb_schema_only');
define('TEST_DB_PORT', 3306);

set_include_path(get_include_path() .':'.dirname(__FILE__).'/../code');

class TestingDatabase extends DB_Database_1
{
	public function __construct($pdo)
	{
		$this->pdo = $pdo;
	}
}

class TestingDBConfig extends Object_1 implements DB_IDatabaseConfig_1
{
	private static $db;

	public function getConnection()
	{
		if(!self::$db)
		{
			throw new Exception('setConnection should be called on ' . __CLASS__ . ' before getting this connection (usually via config)');
		}
		return self::$db;
	}

	public static function setConnection(TestingDatabase $db)
	{
		self::$db = $db;
	}
}

?>