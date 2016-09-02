<?php

class Collections_ImmutableList1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Collections_ImmutableList_1
	 */
	protected $list;

	public function setUp()
	{
		$this->list = new Collections_ImmutableList_1(array(1, 2));
	}

	public function testNonTraversableThrowsException()
	{
		$this->setExpectedException('Exception');
		$l = new Collections_ImmutableList_1('test');
	}

	public function testCurrentReturnsItem()
	{
		$actual = $this->list->current();
		$this->assertEquals(1, $actual);
	}

	public function testCurrentClonesObjects()
	{
		$expected = new stdClass();

		$l = new Collections_ImmutableList_1(array($expected));
		$actual = $l->current();

		$this->assertFalse($expected === $actual);
	}

	public function testNextReturnsItem()
	{
		$actual = $this->list->next();
		$this->assertEquals(2, $actual);
	}

	public function testNextClonesObjects()
	{
		$object = new stdClass();
		$l = new Collections_ImmutableList_1(array(1, $object));
		$actual = $l->next();

		$this->assertFalse($object === $actual);
	}

	public function testClearThrowsException()
	{
		$this->setExpectedException('Exception');
		$this->list->clear();
	}

	public function testUnsetThrowsException()
	{
		$this->setExpectedException('Exception');
		unset($this->list[0]);
	}

	public function testAddThrowsException()
	{
		$this->setExpectedException('Exception');
		$this->list->add('test');
	}

	public function testAppendThrowsException()
	{
		$this->setExpectedException('Exception');
		$this->list[] = 'test';
	}

	public function testSetThrowsException()
	{
		$this->setExpectedException('Exception');
		$this->list[3] = 'test';
	}
}

?>