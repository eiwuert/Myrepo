<?php
/**
 * DB_Models_Iterator_1 test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_Iterator1Test extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Tests DB_Models_Iterator_1->__construct() with valid instantiation
	 * @return void
	 */
	public function testConstructValid()
	{
		$i = new DB_Models_Iterator_1(
			array($this->getMock("DB_Models_IWritableModel_1"))
		);
	
	}
	
	/**
	 * Tests DB_Models_Iterator_1->__construct() with a bad item
	 * @return void
	 */
	public function testConstructBadItem()
	{
		$this->setExpectedException("InvalidArgumentException");
		$i = new DB_Models_Iterator_1(
			array(
				$this->getMock("DB_Models_IWritableModel_1"),
				$this->getMock("DB_Models_IWritableModel_1"),
				new StdClass()
			)
		);
	}
	
	/**
	 * Tests DB_Models_Iterator_1->count()
	 * @return void
	 */
	public function testCount()
	{
		$items = array(
			$this->getMock("DB_Models_IWritableModel_1"),
			$this->getMock("DB_Models_IWritableModel_1"),
		);
		
		$i = new DB_Models_Iterator_1($items);
		
		$this->assertEquals(count($items), $i->count());
	}

	/**
	 * Tests DB_Models_Iterator_1->currentRawData()
	 * @return void
	 */
	public function testCurrentRawData()
	{
		$data1 = "data1";
		$model1 = $this->getMock("DB_Models_IWritableModel_1");
		$model1->expects($this->once())
			->method("getColumnData")
			->will($this->returnValue($data1));

		$data2 = "data2";
		$model2 = $this->getMock("DB_Models_IWritableModel_1");
		$model2->expects($this->once())
			->method("getColumnData")
			->will($this->returnValue($data2));
		
		$i = new DB_Models_Iterator_1(
			array($model1, $model2)
		);
		
		$this->assertEquals($data1, $i->currentRawData());
		$i->next();
		$this->assertEquals($data2, $i->currentRawData());
	}
	
	/**
	 * Tests DB_Models_Iterator_1->getClassName()
	 * @return void
	 */
	public function testGetClassName()
	{
		$i = new DB_Models_Iterator_1(
			array($this->getMock("DB_Models_IWritableModel_1"))
		);
		$this->assertEquals("DB_Models_IWritableModel_1", $i->getClassName());
	
	}
	/**
	 * Tests DB_Models_Iterator_1->toArray()
	 * @return void
	 */
	public function testToArray()
	{
		$item_0 = $this->getMock("DB_Models_IWritableModel_1");
		$item_1 = $this->getMock("DB_Models_IWritableModel_1");
		$items = array($item_0, $item_1);
		
		$i = new DB_Models_Iterator_1($items);
		
		$this->assertEquals($items, $i->toArray());
	}
	
	/**
	 * Tests DB_Models_Iterator_1->toList()
	 * @return void
	 */
	public function testToList()
	{
		$this->setExpectedException("Exception");
		$i = new DB_Models_Iterator_1(
			array($this->getMock("DB_Models_IWritableModel_1"))
		);
		$i->toList();
	}

		
	/**
	 * Tests remaining methods
	 * @return void
	 */
	public function testTheRest()
	{
		$item_0 = $this->getMock("DB_Models_IWritableModel_1");
		$item_1 = $this->getMock("DB_Models_IWritableModel_1");
		$items = array($item_0, $item_1);
		
		$i = new DB_Models_Iterator_1($items);
		
		$this->assertEquals($item_0, $i->current());
		$this->assertEquals(0, $i->key());
		$this->assertTrue($i->valid());

		$i->next();
		$this->assertEquals($item_1, $i->current());
		$this->assertEquals(1, $i->key());
		$this->assertTrue($i->valid());
		
		$i->next();
		$this->assertEquals(2, $i->key());
		$this->assertFalse($i->valid());
		
		$i->rewind();
		$this->assertEquals($item_0, $i->current());
		$this->assertEquals(0, $i->key());
		$this->assertTrue($i->valid());		
	}
	
	
}

