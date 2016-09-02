<?php

class TestList extends Collections_List_1
{
	public function __construct(array $items = array())
	{
		$this->items = $items;
	}

	public function getArray()
	{
		return $this->items;
	}
}

class Collections_List1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Collections_List_1
	 */
	protected $list;

	public function setUp()
	{
		$this->list = new TestList(array(
			'a',
			5 => 'test',
		));
	}

	public function testGetReturnsItemAtOffset()
	{
		$this->assertEquals('test', $this->list[5]);
	}

	public function testSetWithoutOffsetAppends()
	{
		$this->list[] = 'hi';

		$expected = array(0 => 'a', 5 => 'test', 6 => 'hi');
		$this->assertEquals($expected, $this->list->getArray());
	}

	public function testUnsetRemovesItemAtOffset()
	{
		unset($this->list[0]);

		$expected = array(5 => 'test',);
		$this->assertEquals($expected, $this->list->getArray());
	}

	public function testExistsReturnsTrueForItemsThatExist()
	{
		$this->assertTrue(isset($this->list[0]));
	}

	public function testExistsReturnsFalseForMissingItems()
	{
		$this->assertFalse(isset($this->list[1]));
	}

	public function testCountReturnsNumberOfItems()
	{
		$this->assertEquals(2, $this->list->count());
	}

	public function testRewindMovesToBeginning()
	{
		// move forward
		$this->list->next();

		// now rewind
		$this->assertEquals('test', $this->list->current());
		$this->list->rewind();
		$this->assertEquals('a', $this->list->current());
	}

	public function testNextMovesToNextItem()
	{
		$this->assertEquals('a', $this->list->current());
		$this->list->next();
		$this->assertEquals('test', $this->list->current());
	}

	public function testAddCallsSetWithNullOffset()
	{
		$l = $this->getMock('Collections_List_1', array('offsetSet'));
		$l->expects($this->once())
			->method('offsetSet')
			->with(NULL, 'hi');

		$l->add('hi');
	}

	public function testClearRemovesAllItems()
	{
		$this->list->clear();
		$this->assertEquals(array(), $this->list->getArray());
	}

	public function testValidReturnsFalseWhenEmpty()
	{
		$list = new Collections_List_1();
		$this->assertFalse($list->valid());
	}

	public function testValidReturnsFalseAtEndOfList()
	{
		$this->list->next()
			&& $this->list->next();
		$this->assertFalse($this->list->valid());
	}

	public function testKeyReturnsKeys()
	{
		$this->assertEquals(0, $this->list->key());
	}
}

?>