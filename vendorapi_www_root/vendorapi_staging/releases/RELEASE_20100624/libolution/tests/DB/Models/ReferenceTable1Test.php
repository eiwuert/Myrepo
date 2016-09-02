<?php

class DB_Models_ReferenceTable1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var DB_Models_WritableModel_1
	 */
	private $empty;
	private $table;

	public function setUp()
	{
		$class = $this->generateModel();
		$this->empty = new $class();

		$this->table = new DB_Models_ReferenceTable_1($this->empty, FALSE);
	}

	public function tearDown()
	{
		$this->table = null;
		$this->empty = null;
	}

	public function testCannotSetOffset()
	{
		$new = clone $this->empty;
		$new->test_id = 1;
		$new->setDataSynched();

		$this->setExpectedException('Exception');
		$this->table[1] = $new;
	}

	public function testCannotAppendWrongType()
	{
		$class = $this->generateModel();

		$new = new $class();
		$new->test_id = 1;
		$new->setDataSynched();

		$this->setExpectedException('Exception');
		$this->table[] = $new;
	}

	public function testCannotAppendUnsavedModel()
	{
		$new = clone $this->empty;

		$this->setExpectedException('Exception');
		$this->table[] = $new;
	}

	public function testCannotAppendAlteredModel()
	{
		$new = clone $this->empty;
		$new->test_id = 1;
		$new->setDataSynched();
		$new->name = 'hi';

		$this->setExpectedException('Exception');
		$this->table[] = $new;
	}

	public function testAppendedModelCanByFoundByName()
	{
		$new = clone $this->empty;
		$new->test_id = 1;
		$new->name = 'hi';
		$new->setDataSynched();

		$this->table[] = $new;
		$this->assertEquals('hi', $this->table->toName(1));
	}

	public function testAppendedModelCanByFoundByID()
	{
		$new = clone $this->empty;
		$new->test_id = 1;
		$new->name = 'hi';
		$new->setDataSynched();

		$this->table[] = $new;
		$this->assertEquals(1, $this->table->toID('hi'));
	}

	public function testCannotAppendNameThatAlreadyExists()
	{
		$existing = clone $this->empty;
		$existing->test_id = 1;
		$existing->name = 'hi';
		$existing->setDataSynched();
		$this->table[] = $existing;

		$new = clone $this->empty;
		$new->test_id = 2;
		$new->name = 'hi';
		$new->setDataSynched();

		$this->setExpectedException('Exception');
		$this->table[] = $new;
	}

	public function testCannotAppendIDThatAlreadyExists()
	{
		$existing = clone $this->empty;
		$existing->test_id = 1;
		$existing->name = 'hi';
		$existing->setDataSynched();
		$this->table[] = $existing;

		$new = clone $this->empty;
		$new->test_id = 1;
		$new->name = 'woot';
		$new->setDataSynched();

		$this->setExpectedException('Exception');
		$this->table[] = $new;
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
			class {$class_name} extends DB_Models_WritableModel_1 implements DB_Models_IReferenceModel_1
			{
				public function getTablename() { return 'test'; }
				public function getPrimaryKey() { return array('test_id', 'foo'); }
				public function getColumns() { return array('test_id', 'foo', 'name'); }
				public function getAutoIncrement() { return 'test_id'; }
				public function getColumnName() { return 'name'; }
				public function getColumnID() { return 'test_id'; }
			}
		");

		return $class_name;
	}
}

?>