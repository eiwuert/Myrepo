<?php

/**
 * WritableModel_1 tests that don't require a database
 * @group WritableModel
 */
class DB_Models_WritableModel1Test extends PHPUnit_Framework_TestCase
{
	private $_db;
	private $_class;

	/**
	 * @var DB_Models_WritableModel_1
	 */
	private $_model;

	protected function setUp()
	{
		$this->_db = new DB_Database_1('sqlite::memory:');
		$this->_class = $this->generateModel();
		$this->_model = new $this->_class($this->_db);
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
				public function getPrimaryKey() { return array('test_id', 'foo'); }
				public function getColumns() { return array('test_id', 'foo', 'name'); }
				public function getAutoIncrement() { return 'test_id'; }
			}
		");

		return $class_name;
	}

	public function testSettingInvalidColumnThrowsException()
	{
		$this->setExpectedException('Exception', "'blah' is not a valid column for table 'test'.");
		$this->_model->blah = 'woot';
	}

	public function testGettingInvalidColumnThrowsException()
	{
		$this->setExpectedException('Exception', "'blah' is not a valid column for table 'test'.");
		$s = $this->_model->blah;
	}

	public function testIssetReturnsTrueForModifiedValue()
	{
		$this->_model->test_id = 1;
		$this->assertTrue(isset($this->_model->test_id));
	}

	public function testGetReturnsOriginalValueWhenUnmodified()
	{
		$this->_model->fromDbRow(array('test_id' => 1, 'name' => 'woot'));
		$this->assertEquals('woot', $this->_model->name);
	}

	public function testGetReturnsModifiedValueWhenModified()
	{
		$this->_model->name = 'test';
		$this->assertEquals('test', $this->_model->name);
	}

	public function testIsAlteredReturnsTrueWhenAltered()
	{
		$this->_model->name = 'name';
		$this->assertTrue($this->_model->isAltered());
	}

	public function testInsertThrowsExceptionWhenReadOnly()
	{
		$this->_model->setReadOnly(TRUE);

		$this->setExpectedException('DB_Models_ReadOnlyException');
		$this->_model->insert();
	}

	public function testUpdateThrowsExceptionWhenReadOnly()
	{
		$this->_model->setReadOnly(TRUE);

		$this->setExpectedException('DB_Models_ReadOnlyException');
		$this->_model->update();
	}

	public function testDeleteThrowsExceptionWhenReadOnly()
	{
		$this->_model->setReadOnly(TRUE);

		$this->setExpectedException('DB_Models_ReadOnlyException');
		$this->_model->delete();
	}

	public function testLoadByThrowsExceptionWhenReadOnly()
	{
		$this->_model->setReadOnly(TRUE);

		$this->setExpectedException('DB_Models_ReadOnlyException');
		$this->_model->loadBy(array('test_id' => 1));
	}

	public function testSetThrowsExceptionWhenReadOnly()
	{
		$this->_model->setReadOnly(TRUE);

		$this->setExpectedException('DB_Models_ReadOnlyException');
		$this->_model->name = 'woot';
	}

	public function testFromDbRowThrowsExceptionWhenReadOnly()
	{
		$this->_model->setReadOnly(TRUE);

		$this->setExpectedException('DB_Models_ReadOnlyException');
		$this->_model->fromDbRow(array());
	}

	public function testInsertThrowsExceptionWithoutPrimaryKey()
	{
		$this->setExpectedException('Exception', 'Insufficient data supplied for primary key on test.');
		$this->_model->insert();
	}

	public function testDeleteThrowsExceptionWithoutPrimaryKey()
	{
		$this->setExpectedException('Exception', 'Attempting to perform a delete on an object with no primary key.');
		$this->_model->delete();
	}

	public function testSaveCallsDeleteWhenDeleted()
	{
		$mock = $this->getMock(
			$this->_class,
			array('delete', 'insert', 'update')
		);

		$mock->expects($this->once())->method('delete');
		$mock->expects($this->never())->method('update');
		$mock->expects($this->never())->method('insert');

		$mock->test_id = 1;
		$mock->foo = 'a';
		$mock->setDeleted(TRUE);
		$mock->save();
	}

	public function testSaveCallsUpdateWhenStored()
	{
		$mock = $this->getMock(
			$this->_class,
			array('delete', 'insert', 'update')
		);

		$mock->expects($this->once())->method('update');
		$mock->expects($this->never())->method('delete');
		$mock->expects($this->never())->method('insert');

		$mock->fromDbRow(array('test_id' => 1, 'foo' => 'woot', 'name' => 'bah'));
		$mock->name = 'a';
		$mock->save();
	}

	public function testSaveCallsInsertWhenNotStored()
	{
		$mock = $this->getMock(
			$this->_class,
			array('delete', 'insert', 'update')
		);

		$mock->expects($this->once())->method('insert');
		$mock->expects($this->never())->method('delete');
		$mock->expects($this->never())->method('update');

		$mock->name = 'a';
		$mock->save();
	}
}

?>
