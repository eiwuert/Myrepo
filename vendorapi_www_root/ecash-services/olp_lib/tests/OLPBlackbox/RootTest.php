<?php
/**
 * OLPBlackbox_Root test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_RootTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @var OLPBlackbox_Root
	 */
	protected $olpblackbox_root;
	
	/**
	 * @var OLPBlackbox_Data
	 */
	protected $bbx_data;
	
	/**
	 * @var OLPBlackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * @var OLPBlackbox_ITarget
	 */
	protected $root_collection;
	
	/**
	 * Prepares the environment before running a test.
	 * 
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();
		
		$this->bbx_data = new OLPBlackbox_Data();
		$this->state_data = new OLPBlackbox_StateData();
		$this->root_collection = $this->getMock(
			'OLPBlackbox_TargetCollection',
			array(),
			array('COLLECTION')
		);
		$this->olpblackbox_root = new OLPBlackbox_Root($this->state_data);
		$this->olpblackbox_root->setRootCollection($this->root_collection);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 * 
	 * @return void
	 */
	protected function tearDown()
	{
		unset($this->bbx_data);
		unset($this->state_data);
		unset($this->root_collection);
		unset($this->olpblackbox_root);
		
		parent::tearDown();
	}

	/**
	 * Tests OLPBlackbox_Root->__construct()
	 * 
	 * @return void
	 */
	public function testConstruct()
	{
		$this->assertAttributeEquals($this->state_data, 'state_data', $this->olpblackbox_root);
	}
	
	/**
	 * Tests OLPBlackbox_Root->getTargetLocation()
	 * 
	 * @return void
	 */
	public function testGetTargetLocation()
	{
		$property_short = 'TARGET';
		$expected_result = 'RETURN';
		$this->root_collection
			->expects($this->once())
			->method('getTargetLocation')
			->with($this->equalTo($property_short))
			->will($this->returnValue($expected_result));
		$result = $this->olpblackbox_root->getTargetLocation($property_short);
		$this->assertEquals($expected_result, $result);
	}
	
	/**
	 * Tests OLPBlackbox_Root->getTargetObject()
	 * 
	 * @return void
	 */
	public function testGetTargetObject()
	{
		$property_short = 'TARGET';
		$expected_result = 'RETURN';
		$this->root_collection
			->expects($this->once())
			->method('getTargetObject')
			->with($this->equalTo($property_short))
			->will($this->returnValue($expected_result));
		$result = $this->olpblackbox_root->getTargetObject($property_short);
		$this->assertEquals($expected_result, $result);
			
	}
	
	/**
	 * Tests OLPBlackbox_Root->pickWinner()
	 * 
	 * @return void
	 */
	public function testPickWinner()
	{
		$expected_result = $this->getMock('Blackbox_IWinner');
		$root = $this->getMock(
			'OLPBlackbox_Root',
			array('cleanUp'),
			array($this->state_data)
		);
		$root->setRootCollection($this->root_collection);
		$root->expects($this->once())
			->method('cleanUp');
		$this->root_collection
			->expects($this->once())
			->method('pickTarget')
			->with($this->equalTo($this->bbx_data))
			->will($this->returnValue($expected_result));
		$this->root_collection
			->expects($this->once())
			->method('isValid')
			->with($this->bbx_data, $this->state_data)
			->will($this->returnValue(TRUE));
		$result = $root->pickWinner($this->bbx_data);
		$this->assertEquals($expected_result, $result);
			
	}
	
	/**
	 * Tests OLPBlackbox_Root->prependTargetCollection()
	 * 
	 * @return void
	 */
	public function testPrependTargetCollection()
	{
		$target = $this->getMock('Blackbox_ITarget');
		$this->root_collection
			->expects($this->once())
			->method('prependTarget')
			->with($this->equalTo($target));
		$this->olpblackbox_root->prependTargetCollection($target);
	}
	
	/**
	 * Tests OLPBlackbox_Root->sleep()
	 * 
	 * @return void
	 */
	public function testSleep()
	{
		$root_sleep_value = array('I Slept with the root');
		$this->root_collection
			->expects($this->once())
			->method('sleep')
			->will($this->returnValue($root_sleep_value));
		$sleep_data = $this->olpblackbox_root->sleep();
		$this->assertArrayHasKey('root_collection', $sleep_data);
		$this->assertEquals($root_sleep_value, $sleep_data['root_collection']);
		$this->assertArrayHasKey('state_data', $sleep_data);	
		$this->assertEquals($this->state_data, $sleep_data['state_data']);
	}
	
	/**
	 * Tests OLPBlackbox_Root->unsetTarget()
	 * 
	 * @return void
	 */
	public function testUnsetTarget()
	{
		$property_short = 'TARGET';
		$collection_index = 1;
		$target_location = array('collection' => $this->root_collection, 'index' => $collection_index);
		$this->root_collection
			->expects($this->once())
			->method('getTargetLocation')
			->with($this->equalTo($property_short))
			->will($this->returnValue($target_location));
		$this->root_collection
			->expects($this->once())
			->method('unsetTargetIndex')
			->with($this->equalTo($collection_index))
			->will($this->returnValue(TRUE));
		$result = $this->olpblackbox_root->unsetTarget($property_short);
		$this->assertTrue($result);
	}
	
	/**
	 * Tests OLPBlackbox_Root->wakeup()
	 * 
	 * @return void
	 */
	public function testWakeup()
	{
		$state_data = new OLPBlackbox_StateData();
		$root_sleep_value = array('I Slept with the root');
		$sleep_data = array(
			'root_collection' => $root_sleep_value,
			'state_data' => $state_data
		);

		$this->root_collection
			->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($root_sleep_value));
		
		$this->olpblackbox_root->wakeup($sleep_data);
		$this->assertAttributeEquals($state_data, 'state_data', $this->olpblackbox_root);
	}

}

