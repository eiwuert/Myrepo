<?php

class VendorAPI_StateObjectTest extends PHPUnit_Framework_TestCase
{
	protected $_state;

	public function setUp()
	{
		$this->_state = new VendorAPI_StateObject();
	}

	public function tearDown()
	{
		$this->_state = NULL;
	}

	public function testGet()
	{
		$this->_state->createPart('test_part');
		$this->assertTrue($this->_state->test_part instanceof VendorAPI_StateObjectPart);
	}
	
	public function testValuesAreStored()
	{
		$this->_state->hi = 'test';
		$this->assertEquals('test', $this->_state->hi);
	}

	/**
	 * Trying to use up my hour...
	 */
	public function testNullKeyIsBlankString()
	{
		$this->_state->{NULL} = 'test';
		$this->assertEquals('test', $this->_state->{''});
	}
	
	public function testSerializeUpdatesVersion()
	{
		$state = unserialize(serialize($this->_state));
		$state->test = "test";
		$this->assertEquals(1, $state->getCurrentVersion());
	}
	
	public function testSetTableData()
	{
		$this->_state->createPart('application');
		$this->_state->application->test = "Super Test";
		
		$this->assertEquals("Super Test", $this->_state->application->test);
	}
	
	public function testVersionData()
	{
		$this->_state->createPart('application');
		$this->_state->application->test = "Hello World";
		$state = unserialize(serialize($this->_state));
		$state->application->test = "test2";
		
		$this->assertEquals("test2", $state->application->test);
	}
	
	public function testGetData()
	{
		$this->_state->createPart('application');
		$this->_state->application->test = "Hello World";
		$this->assertEquals(array("application" => array("test" => "Hello World")), $this->_state->getData());
	}
	
	public function testGetDataWithTable()
	{
		$this->_state->createPart('application');
		$this->_state->createMultipart('personal_reference');
		$this->_state->application->test = "Hello World";
		$this->_state->personal_reference[0]->name_first = ":[";
		$this->assertEquals(array("application" => array("test" => "Hello World")), $this->_state->getData('application'));
	}
	
	
	public function testMutliPartGetData()
	{
		
		$this->_state->createPart('application');
		$this->_state->createMultipart('personal_reference');
		$this->_state->application->test = "Hello World";
		$this->_state->personal_reference[0]->name_first = "Pizza";
		$this->_state->personal_reference[0]->name_middle = "The";
		$this->_state->personal_reference[0]->name_last = "Hut";
		
		$expected = array(
			'application' => array('test' => 'Hello World'), 
			'personal_reference' => array( 
				0 => array(
					'name_first' => 'Pizza',
					'name_middle' => 'The',
					'name_last'  => 'Hut'
			)
		));
	
		$this->assertEquals($expected, $this->_state->getData());
	}
	
	public function testMutliPartGetDataWithversions()
	{
		
		$this->_state->createPart('application');
		$this->_state->createMultipart('personal_reference');
		$this->_state->application->test = "Hello World";
		$this->_state->personal_reference[0]->name_first = "Pizza";
		$this->_state->personal_reference[0]->name_middle = "The";
		$this->_state->personal_reference[0]->name_last = "Hut";
		
		$state = unserialize(serialize($this->_state));
		$state->personal_reference[0]->name_last = "Test";
		
		$expected = array(
			'application' => array('test' => 'Hello World'), 
			'personal_reference' => array( 
				0 => array(
					'name_first' => 'Pizza',
					'name_middle' => 'The',
					'name_last'  => 'Test'
			)
		));
		$this->assertEquals($expected, $state->getData());
	}
	
	public function testAsIterator()
	{
		
		$this->_state->createPart('application');
		$this->_state->createMultipart('personal_reference');
		$this->_state->application->test = "Hello World";
		$this->_state->personal_reference[0]->name_first = "Pizza";
		$this->_state->personal_reference[0]->name_middle = "The";
		$this->_state->personal_reference[0]->name_last = "Hut";
		
		$state = unserialize(serialize($this->_state));
				
		$state->personal_reference[1]->name_first = "Jodo";
		$state->personal_reference[1]->name_last = "Fett";
		
		foreach ($state->personal_reference as $key => $val)
		{
			if ($key == 0)
			{
				$this->assertEquals(
					array('name_first' => "Pizza", 'name_middle' => "The", 'name_last' => "Hut"),
					$val
				);
			}
			elseif ($key == 1)
			{
				$this->assertEquals(
					array('name_first' => "Jodo", 'name_last'  => 'Fett'),
					$val
				);
			}
		}
	}
	
	public function testSetPartObject()
	{
		$part = new VendorAPI_StateObjectPart($this->_state);
		$part->name = "Hello";
		$this->_state->application = $part;
		$this->assertEquals("Hello", $this->_state->application->name);
	}
	
	public function testSetMultiPartObject()
	{
		$part = new VendorAPI_StateObjectMultiPart($this->_state);
		$part[0]->name = "Hello";
		$this->_state->personal_reference = $part;
		$this->assertEquals("Hello", $this->_state->personal_reference[0]->name);
	}
	
	public function testGetTableDataSince()
	{
		
		$this->_state->createPart('application');
		$this->_state->createMultipart('personal_reference');
		$this->_state->application->name = "Hello World";
		$this->assertEquals(array('application' => array('name' => "Hello World")), $this->_state->getTableDataSince());
		$state = unserialize(serialize($this->_state));
		$state->personal_reference[0]->name = "Hello World";
		$this->assertEquals(array('personal_reference' => array(0 => array('name' => "Hello World"))), $state->getTableDataSince(1));
	}
	
	public function testGetReferencePartsWithTables()
	{
		$this->createReferencePartFixture($this->getReferencePartData());
		$this->assertEquals(
			$this->getReferencePartData(), 
			$this->_state->getReferenceData());
	}
	
	public function testGetReferencePartsWithNoTables()
	{
		$this->assertEquals(array(), $this->_state->getReferenceData());
	}
	
	public function testGetReferencePartTableExists()
	{
		$this->createReferencePartFixture($this->getReferencePartData());
		$this->assertThat($this->_state->getReferencePart('test_table'),
			 $this->isInstanceOf('VendorAPI_StateObjectMultiPart'));
	}
	
	public function testGetReferencePartTableDoesNotExist()
	{
		$this->assertFalse($this->_state->getReferencePart('table'));
	}
	
	public function testgetReferencePartFromOldVersionDoesnt()
	{
		$this->createReferencePartFixture($this->getReferencePartData());
		$this->_state->updateVersion(TRUE);
		$this->assertEquals(array(), $this->_state->getReferenceData($this->_state->getCurrentVersion()));
	}
	
	public function testUnsetReferencePart()
	{
		$this->createReferencePartFixture($this->getReferencePartData());
		$this->_state->removeReferencePart('test_table');
		$this->assertFalse($this->_state->getReferencePart('test_table'));	
	}
	
	/**
	 * Utility method for setting up the fixture
	 */
	protected function createReferencePartFixture($data)
	{
		foreach ($data as $table => $values)
		{
			foreach ($values as $k => $v)
			{
				$this->_state->addReferencePart($table, $v);
			}
		}
	}
	
	protected function getReferencePartData()
	{
		
		return array(
			'test_table' => array(
				array(
					'test_col1' => 'test_val1',
					'test_col2' => 'test_val2',
				),
				array(
					'test_col1' => 'test_val3',
					'test_col2' => 'test_val4'
				)
			),
			'second_test_table' => array(
				array(
					'col1' => 'val1',
					'col2' => 'val2'
				)
			)
		);	
	}

}

?>