<?php

require_once './autoload_setup.php';
require_once 'DB/Database.1.php';

/**
 * WritableModel_1 tests that require an actual database behind them
 * @group WritableModel
 */
class DB_Models_WritableModel1DatabaseTest extends PHPUnit_Extensions_Database_TestCase
{
	const DATABASE = './test.db';

	private $_db;
	private $_pdo;
	private $_model;

	protected function setUp()
	{
		$this->_db = $this->setupDatabase();
		$this->_pdo = new PDO('sqlite:'.self::DATABASE);

		$class = $this->generateModel();
		$this->_model = new $class($this->_db);

		parent::setUp();
	}

	protected function setupDatabase()
	{
		@unlink(self::DATABASE);

		$db = new DB_Database_1('sqlite:'.self::DATABASE);
		$db->exec('
			CREATE TABLE test (
				test_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				name TEXT
			)
		');

		return $db;
	}

	/**
	 * Generate a writable model class
	 * Couldn't use a mock object because we'd attempt to call mocked methods
	 * in the writablemodel constructor
	 * @return string
	 */
	protected function generateModel()
	{
		$class_name = 'Model_'.md5(microtime());

		eval("
			class {$class_name} extends DB_Models_WritableModel_1
			{
				public function getTablename() { return 'test'; }
				public function getPrimaryKey() { return array('test_id'); }
				public function getColumns() { return array('test_id', 'name'); }
				public function getAutoIncrement() { return 'test_id'; }
			}
		");

		return $class_name;
	}

	public function testInsertUpdatesAutoIncrementColumn()
	{
		$this->_model->name = 'test';
		$this->_model->insert();

		$this->assertEquals(2, $this->_model->test_id);
	}

	public function testInsert()
	{
		$this->_model->name = 'woot';
		$this->_model->insert();

		$this->assertDatabaseEquals($this->getExpectation('after_insert'));
	}

	public function testUpdate()
	{
		$this->_model->loadByKey(1);
		$this->_model->name = 'zing';
		$this->_model->save();

		$this->assertDatabaseEquals($this->getExpectation('after_update'));
	}

	/*public function testUpdatePrimaryKey()
	{
		$this->_model->loadByKey(1);
		$this->_model->test_id = 2;
		$this->_model->save();

		$this->assertDatabaseEquals($this->getExpectation('after_update_pk'));
	}*/

	public function testDelete()
	{
		$this->_model->loadByKey(1);
		$this->_model->delete();

		$this->assertDatabaseEquals($this->getExpectation('after_delete'));
	}

	public function testLoadBy()
	{
		$this->_model->loadBy(array('name' => 'blah'));
		$this->assertEquals(1, $this->_model->test_id);
	}

	public function testLoadByReturnsFalse()
	{
		$loaded = $this->_model->loadBy(array('test_id' => 0));
		$this->assertFalse($loaded);
	}

	public function testIsStoredReturnsTrueAfterLoadBy()
	{
		$this->_model->loadByKey(1);
		$this->assertTrue($this->_model->isStored());
	}

	public function testIsStoredReturnsTrueAfterInsert()
	{
		$this->_model->name = 'haha';
		$this->_model->insert();

		$this->assertTrue($this->_model->isStored());
	}

	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 */
	protected function getConnection()
	{
		return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($this->_pdo, '');
	}

	protected function getDataset()
	{
		return $this->getFixture('writable_model');
	}

	protected function getFixture($name)
	{
		$filename = dirname(__FILE__).'/_fixtures/'.$name.'.xml';
		return $this->createXMLDataSet($filename);
	}

	protected function getExpectation($name)
	{
		$filename = dirname(__FILE__).'/_expectations/'.$name.'.xml';
		return $this->createXMLDataSet($filename);
	}

	protected function assertDatabaseEquals(PHPUnit_Extensions_Database_DataSet_IDataSet $dataset)
	{
		$this->assertDataSetsEqual(
			$dataset,
			$this->getConnection()->createDataSet()
		);
	}
}

?>