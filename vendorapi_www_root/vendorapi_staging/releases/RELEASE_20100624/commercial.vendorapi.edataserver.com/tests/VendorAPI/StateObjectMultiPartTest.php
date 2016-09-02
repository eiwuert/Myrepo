<?php

class StateObjectMultiPartTest extends PHPUnit_Framework_TestCase
{
	protected $part;
	protected $state;

	public function setUp()
	{
		$this->state = new VendorAPI_StateObject();
		$this->part = new VendorAPI_StateObjectMultiPart($this->state);
	}

	public function testCount()
	{
		$this->part[0]->name_first = "Pizza";
		$this->part[0]->name_middle = "The";
		$this->part[0]->name_last = "Hut";


		$this->part[1]->name_first = "Jodo";
		$this->part[1]->name_last = "Fett";
		$this->assertEquals(2, count($this->part));
	}

	public function testHighestIndex()
	{
		$this->part[0]->test_col = 'Value';
		$this->assertEquals(0, $this->part->highestIndex());
		$this->part[1]->test_col = 'Value';
		$this->assertEquals(1, $this->part->highestIndex());
	}

	public function testAppend()
	{
		$this->part[0]->test_col = 'Value';
		$this->assertEquals(0, $this->part->highestIndex());
		$this->part->append(array('test_col' => 'Val2'));
		$this->assertEquals(1, $this->part->highestIndex());
		$this->assertEquals('Val2', $this->part[1]->test_col);
	}

	public function testAppendingIncrementsVersion()
	{
		$this->part->append(array('test_col' => 'value'));
		$this->assertEquals(1, $this->state->getCurrentVersion());
	}

	public function testUnset()
	{
		$this->part[0]->name_first = "Pizza";
		$this->part[0]->name_middle = "The";
		$this->part[0]->name_last = "Hut";

		unset($this->part[0]);
		$this->assertEquals(0, count($this->part));
	}

	public function testGetVersionData()
	{
		$this->part[0]->name = "Pizza";
		$this->part[1]->name = "Tom";

		$expected = array(
			0 => array('name' => "Pizza"),
			1 => array('name' => "Tom"),
		);
		$this->assertEquals($expected, $this->part->GetTableDataSince());
		$part = unserialize(serialize($this->part));

		$part[2]->name = "Jerry";
		$expected = array("2" => array("name" => "Jerry"));
		$this->assertEquals($expected, $part->getTableDataSince(1));
	}

	public function testEmptyOffset()
	{
		$this->part[]->name = "Test";
		$this->assertEquals("Test", $this->part[$this->part->highestIndex()]->name);
	}

	public function testOffsetExists()
	{
		$this->assertFalse(isset($this->part[0]));
		$this->part[0]->name = "test";
		$this->assertTrue(isset($this->part[0]));
		$part = unserialize(serialize($this->part));
		$part[1]->name = "err";
		$this->assertTrue(isset($part[1]));
		$this->assertTrue(isset($part[0]));
		$this->assertFalse(isset($part[2]));
	}

	public function testNullOffsetSet()
	{
		$this->part[] = array("name" => "Test");
		$this->assertEquals("Test", $this->part[$this->part->highestIndex()]->name);
	}

	public function testOffsetSet()
	{
		$this->part[1] = array("name" => "Test");
		$this->assertEquals("Test", $this->part[1]->name);
	}

	public function testMultipleSameData()
	{
		$data = array('hello' => 'world');
		$this->part[] = $data;
		$this->part[] = $data;
		$this->assertEquals(1, count($this->part));
	}
}
