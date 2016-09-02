<?php

class VendorAPI_TemporaryPersistorTest extends PHPUnit_Framework_TestCase
{
	protected $_model;
	protected $_persistor;

	public function setUp()
	{
		$this->_model = new TemporaryPersistorTestModel1();
		$this->_persistor = new VendorAPI_TemporaryPersistor();
	}

	public function tearDown()
	{
		$this->_persistor = null;
	}

	public function testSaveDoesNotSetSynched()
	{
		$this->_model->name = 'test';

		$this->assertTrue($this->_model->isAltered());
		$this->_persistor->save($this->_model);
		$this->assertTrue($this->_model->isAltered());
	}

	public function testLoadReturnsSameInstance()
	{
		$this->_model->name = 'test';
		$this->_persistor->save($this->_model);

		$m = $this->_persistor->loadBy(
			new TemporaryPersistorTestModel1(),
			array('name' => 'test')
		);
		$this->assertSame($this->_model, $m);
	}

	public function testLoadByReturnsFalseWhenEmpty()
	{
		$m = $this->_persistor->loadBy(
			new TemporaryPersistorTestModel1(),
			array()
		);
		$this->assertFalse($m);
	}

	public function testLoadFailsWhenItDoesntMatch()
	{
		$this->_model->name = 'bob';
		$this->_persistor->save($this->_model);

		$m = $this->_persistor->loadBy(
			new TemporaryPersistorTestModel1(),
			array('name' => 'test')
		);
		$this->assertFalse($m);
	}
}

class TemporaryPersistorTestModel1 extends DB_Models_WritableModel_1
{
	public function getTableName()
	{
		return 'test';
	}

	public function getColumns()
	{
		return array(
			'test_id',
			'name',
			'phone',
		);
	}

	public function getPrimaryKey()
	{
		return array('test_id');
	}

	public function getAutoIncrement()
	{
		return 'test_id';
	}
}