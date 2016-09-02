<?php 

class VendorAPI_StateObjectPartTest extends PHPUnit_Framework_TestCase
{
	protected $state;
	protected $part;

	public function setUp()
	{
		$this->state = new VendorAPI_StateObject();
		$this->part = new VendorAPI_StateObjectPart($this->state);
	}
	
	public function testGetTableData1Version()
	{
		$this->part->test_col = 1;
		$this->part->col2     = 2;
		
		$data = $this->part->getTableDataSince();
		foreach ($data as $col => $val)
		{
			if ($col == 'test_col')
			{
				$this->assertEquals(1, $val);
			}
			elseif($col == 'col2')
			{
				$this->assertEquals(2, $val);
			}
		}
	}
	
	public function testGetTableDataVersions()
	{
		$this->part->test_col = 1;
		$this->part->col2     = 2;
		
		$part = unserialize(serialize($this->part));
		$part->col2 = 3;
		
		
		$part = unserialize(serialize($part));
		$part->col3 = 5;
		
		$data = $part->getTableDataSince();
		foreach ($data as $col => $val)
		{
			if ($col == 'test_col')
			{
				$this->assertEquals(1, $val);
			}
			elseif ($col == 'col2')
			{
				$this->assertEquals(3, $val);
			}
			elseif ($col == 'col3')
			{
				$this->assertEquals(5, $val);
			}
		}
	}
	
	public function testGetSetGet()
	{
		// Test for a bug that occured where
		// it was only looking in the current version
		// which means if the version had been updated, and you
		// tried to get a key, it'd use the wrong one.
		$this->part->test = "Hello";
		$this->part->test2  = "World";
		
		$part = unserialize(serialize($this->part));
		$part->test = "Hello World";
		$this->assertEquals("World", $part->test2);
		// Make sure that the newly set variable is actually
		// getting the new version
		$this->assertEquals("Hello World", $part->test);
	}

}