<?php
class TestDb
{
	protected static $instance;
	
	public static function getInstance()
	{
		if (!self::$instance)
		{
			$config = new DB_SQLiteConfig_1(':memory:');
			self::$instance = $config->getConnection();
			self::initialize();
		}
		
		return self::$instance;
	}
	
	protected function initialize()
	{
		self::$instance->exec("
			CREATE TABLE vehicle (
				vehicle_id integer primary key autoincrement,
				make_id integer,
				color_id integer,
				description text
			)
		");
		
		self::$instance->exec("
			CREATE TABLE make (
				make_id integer primary key autoincrement,
				make_name text
			)
		");
		
		self::$instance->exec("
			CREATE TABLE color (
				color_id integer primary key autoincrement,
				color_name text
			)
		");
	}
}

class Vehicle extends DB_Models_ObservableWritableModel_1
{
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return TestDb::getInstance();
	}
	
	public function getColumns()
	{
		static $columns = array(
			'vehicle_id',
			'make_id',
			'color_id',
			'description',
		);
		
		return $columns;
	}
	
	public function getPrimaryKey()
	{
		return array('vehicle_id');
	}
	
	public function getAutoIncrement()
	{
		return 'vehicle_id';
	}
	
	public function getTableName()
	{
		return 'vehicle';
	}
}

class Make extends DB_Models_ReferenceModel_1
{
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return TestDb::getInstance();
	}
	
	public function getColumns()
	{
		static $columns = array(
			'make_id',
			'make_name',
		);
		
		return $columns;
	}
	
	public function getPrimaryKey()
	{
		return array('make_id');
	}
	
	public function getAutoIncrement()
	{
		return 'make_id';
	}
	
	public function getTableName()
	{
		return 'make';
	}
	
	public function getColumnID()
	{
		return 'make_id';
	}

	public function getColumnName()
	{
		return 'make_name';
	}
}

class Color extends DB_Models_ReferenceModel_1
{
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return TestDb::getInstance();
	}
	
	public function getColumns()
	{
		static $columns = array(
			'color_id',
			'color_name',
		);
		
		return $columns;
	}
	
	public function getPrimaryKey()
	{
		return array('color_id');
	}
	
	public function getAutoIncrement()
	{
		return 'color_id';
	}
	
	public function getTableName()
	{
		return 'color';
	}
	
	public function getColumnID()
	{
		return 'color_id';
	}

	public function getColumnName()
	{
		return 'color_name';
	}
}

$make_table = new DB_Models_ReferenceTable_1(new Make(), TRUE);
$color_table = new DB_Models_ReferenceTable_1(new Color(), FALSE);

$vehicle = new DB_Models_Decorator_ReferencedWritableModel_1(new Vehicle());
$vehicle->addReferenceTable($make_table);
$vehicle->addReferenceTable($color_table);

$vehicle->make_name = 'honda';
$vehicle->color_name = 'black';
$vehicle->description = "John's car";
$vehicle->save();

var_dump(
	$vehicle->vehicle_id,
	$vehicle->make_id,
	$vehicle->make_name,
	$vehicle->getColumnData()
);

$vehicle->color_name = 'white';
$vehicle->save();

var_dump(
	$vehicle->color_id
);

$vehicle2 = new DB_Models_Decorator_ReferencedWritableModel_1(new Vehicle());
$vehicle2->addReferenceTable($make_table);
$vehicle2->addReferenceTable($color_table);

$vehicle2->make_id = $vehicle->make_id;
$vehicle2->color_name = 'black';
$vehicle2->description = "Jane's car";
$vehicle2->save();

var_dump(
	$vehicle2->vehicle_id,
	$vehicle2->color_id,
	$vehicle2->make_name
);

$vs = $vehicle->loadAllBy(array('color_name' => 'white'));
if ($vs)
{
	foreach ($vs AS $v)
	{
		var_dump($v->description);
	}
}

?>
