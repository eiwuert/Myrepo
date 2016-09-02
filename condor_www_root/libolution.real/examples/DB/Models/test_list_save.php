<?php

	require_once('libolution/AutoLoad.1.php');

	class Test extends DB_Models_WritableModel_1
	{
		public function getTableName()
		{
			return 'test_list_save';
		}
		public function getPrimaryKey()
		{
			return array('blah_id');
		}
		public function getAutoIncrement()
		{
			return 'blah_id';
		}
		public function getColumns()
		{
			return array('blah_id', 'something');
		}
		public function getDatabaseInstance()
		{
			return Something::getInstance();
		}
	}

	class Something
	{
		protected static $inst;
		public static function getInstance()
		{
			if(self::$inst==NULL)
			{
				$conf = new DB_MySQLConfig_1('localhost', 'root');
				self::$inst = $conf->getConnection();
				self::$inst->BacktraceInfoEnabled = TRUE;
				self::$inst->selectDatabase('test');
			}
			return self::$inst;
		}
	}

	class TestList extends DB_Models_IterativeModel_1
	{
		public function getClassName()
		{
			return "Test";
		}
		public function getDatabaseInstance()
		{
			return Something::getInstance();
		}
		protected function createInstance(array $db_row)
		{
			$test = new Test();
			$test->fromDbRow($db_row);
			return $test;
		}
		public static function getMe()
		{
			$list = new TestList();

			$list->statement = Something::getInstance()->query("select * from blah");
			return $list;
		}
	}

Something::getInstance()->exec("drop table if exists test_list_save");
Something::getInstance()->exec("CREATE TABLE `test_list_save` (
  `blah_id` int(10) unsigned NOT NULL auto_increment,
  `something` varchar(50) default NULL,
  PRIMARY KEY  (`blah_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");

$a = new Test();
$a->something = 'My_Something';

$b = new Test();
$b->something = 'My_Other_Something';

$c = new Test();
$c->something = 'My_Friend';


$list = new DB_Models_ModelList_1('Test', Something::getInstance());
$list->add($a);
$list->add($b);
$list->add($c);
$list->save();



echo "\n";
echo "auto_increment on first item correct?  ";

if ($a->blah_id != 1)
{
	echo "[FAIL]\n";
}
else
{
	echo "[ OK ]\n";
}

echo "auto_increment on other items correct? ";

if ($a->blah_id != 1 || $b->blah_id != 2 || $c->blah_id != 3)
{
	echo "[FAIL]\n";
}
else
{
	echo "[ OK ]\n";
}

echo "\n";

foreach ($list as $test)
{

}
?>
