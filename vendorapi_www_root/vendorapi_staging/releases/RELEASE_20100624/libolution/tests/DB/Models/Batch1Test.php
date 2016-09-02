<?php

class DB_Models_Batch1Test extends PHPUnit_Framework_TestCase
{
	protected $_model1;
	protected $_model2;
	protected $_batch;

	public function setUp()
	{
		$model = $this->generateModel();

		$this->_model1 = $this->getMock(
			$model,
			array('insert', 'update', 'delete'),
			array(),
			'',
			FALSE
		);
		$this->_model2 = clone $this->_model1;

		$this->_batch = new DB_Models_Batch_1(null, false);
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

	public function testRequiresDatabaseConnectionIfUsingLists()
	{
		$this->setExpectedException('Exception');
		new DB_Models_Batch_1(null);
	}

	public function testDeleteGetsCalledOnScheduledDeletes()
	{
		$this->_batch->delete($this->_model1);

		$this->_model1->expects($this->atLeastOnce())
			->method('delete');
		$this->_batch->execute();
	}

	public function testInsertGetsCalledOnNewModels()
	{
		$this->_model1->test_id = 1;
		$this->_batch->save($this->_model1);

		$this->_model1->expects($this->once())
			->method('insert');
		$this->_batch->execute();
	}

	public function testModelsAreNotSavedTwice()
	{
		$this->_batch->delete($this->_model1);

		$this->_model1->expects($this->once())
			->method('delete');
		$this->_batch->execute();
		$this->_batch->execute();
	}
}

?>