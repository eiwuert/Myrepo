<?php
/**
 * 
 * @todo someone please come up with a better name
 */
class VendorAPI_ReferenceColumn_LocatorTest extends PHPUnit_Framework_TestCase
{
	
	public function testGetModel()
	{
		$model = new VendorAPITestModel(); 
		$this->assertEquals($model, $this->createLocator($model)->getModel());
	}
	
	public function testAddLoadByMethodReturnsLocator()
	{
		$locator = $this->createLocator(new VendorAPITestModel());
		$this->assertEquals($locator, $locator->addLoadByMethod('loadBy'));
	}
	
	public function testCallsLoadByMethodWithParameters()
	{
		$loadBy = array('col1' => "Hello World");
		
		$model = $this->getMock('VendorAPITestModel', array('loadBy', 'save'));
		$model->expects($this->once())->method('loadBy')
			->with($loadBy)
			->will($this->returnValue(TRUE));
			
		$locator = $this->createLocator($model);
		$locator->addLoadByMethod('loadBy', $loadBy);
		$this->assertTrue($locator->locateModel());
	}
	
	/**
	 * 
	 * @expectedException InvalidArgumentException
	 */
	public function testLoadByMethodExceptionOnInvalidCallback()
	{
		$loadBy = array('col1' => "Hello World");
		
		$model = $this->getMock('VendorAPITestModel', array('loadBy', 'save'));
		$locator = $this->createLocator($model);
		$locator->addLoadByMethod('somereallynonexistantmethod', $loadBy);
		
	}
	
	public function testResolveReferenceReturnsAutoIncrement()
	{
		$loadBy = array('col1' => "Hello World");
		
		$model = $this->getMock('VendorAPITestModel', array('loadBy', 'save'));
		$model->test_id = 99;
		$model->expects($this->once())->method('loadBy')
			->with($loadBy)
			->will($this->returnValue(TRUE));
			
		$locator = $this->createLocator($model);
		$locator->addLoadByMethod('loadBy', $loadBy);
		$this->assertEquals($model->test_id, $locator->resolveReference());
	}
	
	
	public function testResolveReferenceReturnsProvidedColumn()
	{
		$loadBy = array('col1' => "Hello World");
		
		$model = $this->getMock('VendorAPITestModel', array('loadBy', 'save'));
		$model->test_id = 99;
		$model->col1 = 15;
		$model->expects($this->once())->method('loadBy')
			->with($loadBy)
			->will($this->returnValue(TRUE));
			
		$locator = $this->createLocator($model);
		$locator->addLoadByMethod('loadBy', $loadBy);
		$locator->setReferencedColumn('col1');
		$this->assertEquals($model->col1, $locator->resolveReference());
	}
	
	public function testSetReferencedColumnReturnsThis()
	{
		$model = $this->getMock('VendorAPITestModel', array('loadBy', 'save'));
		$locator = $this->createLocator($model);
		$this->assertEquals($locator, $locator->setReferencedColumn('col1'));
	}
	
	/**
	 * 
	 * @expectedException RuntimeException
	 */
	public function testSetReferencedColumnExceptionOnBadColumn()
	{
		$model = $this->getMock('VendorAPITestModel', array('loadBy', 'save', 'getColumns'));
		$model->expects($this->any())->method('getColumns')->will($this->returnValue(array()));
		$locator = $this->createLocator($model);
		$locator->setReferencedColumn('some_super_non_existant_column');	
	}
	
	
	
	
	
	public function createLocator($model)
	{
		return new VendorAPI_ReferenceColumn_Locator($model);
	}
	
	

}