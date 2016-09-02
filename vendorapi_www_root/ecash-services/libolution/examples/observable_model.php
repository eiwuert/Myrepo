<?php

	require 'libolution/AutoLoad.1.php';

	class NameModel extends DB_Models_ObservableWritableModel_1
	{
		public function getColumns()
		{
			return array('name_id', 'name');
		}
		public function getTableName()
		{
			return 'name';
		}
		public function getPrimaryKey()
		{
			return array('name_id');
		}
		public function getAutoIncrement()
		{
			return 'name_id';
		}
		public function getDatabaseInstance($db_inst = NULL)
		{
			return TestDb::getInstance();
		}
	}

	class PhoneModel extends DB_Models_ObservableWritableModel_1
	{
		public function getColumns()
		{
			return array('phone_id', 'name_id', 'phone');
		}
		public function getTableName()
		{
			return 'phone';
		}
		public function getPrimaryKey()
		{
			return array('phone_id');
		}
		public function getAutoIncrement()
		{
			return 'phone_id';
		}
		public function getDatabaseInstance($db_inst = NULL)
		{
			return TestDb::getInstance();
		}
	}

	class TestDb
	{
		protected static $instance;

		public static function getInstance()
		{
			if (!self::$instance)
			{
				$config = new DB_SQLiteConfig_1(':memory:');

				self::$instance = $config->getConnection();
				self::initalize(self::$instance);
			}
			return self::$instance;
		}

		public static function initalize(DB_Database_1 $db)
		{
			$db->exec("
				CREATE TABLE name
				(
					name_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
					name VARCHAR(255) NOT NULL
				)
			");

			$db->exec("
				CREATE TABLE phone
				(
					phone_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
					name_id INTEGER NOT NULL,
					phone VARCHAR(255) NOT NULL
				)
			");
		}
	}


	$name = new NameModel();
	$name->name = 'Andrew';

	$phone = new PhoneModel();
	$phone->phone = '7021234567';

	// create an observer and let it update the phone
	// model when the name_id field gets changed
	$o = new DB_Models_ColumnObserver_1($name, 'name_id');
	$o->addTarget($phone);

	$name->save();

	var_dump($phone->name_id);
	$phone->save();


?>
