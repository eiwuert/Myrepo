<?php
/**
 * OLPBlackbox_StateDataDecoratorWithheldTargets test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_StateDataDecoratorWithheldTargetsTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * State data being decorated
	 * 
	 * @var OLPBlackbox_StateData
	 */
	protected $state_data;

	/**
	 * State data decorator for tests
	 *
	 * @var OLPBlackbox_StateDataDecoratorWithheldTargets
	 */
	protected $state_data_decorator;
	
	/**
	 * Prepares the environment before running a test.
	 * 
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();
		
		$this->state_data = $this->getMockedStateData();
		
		$this->state_data_decorator = new OLPBlackbox_StateDataDecoratorWithheldTargets($this->state_data);
	
	}

	/**
	 * Get a mocked state data object
	 *
	 * @return OLPBlackbox_StateData
	 */
	protected function getMockedStateData()
	{
		return $this->getMock(
			'OLPBlackbox_StateData',
			array(
				'getCombinedKey',
				'initData',
				'__set',
				'__get',
				'__isset',
				'addStateData',
				'getMutableKeys',
				'fakeFunction', // Fake function for __call pass through test
			)
		);		
	}

	/**
	 * Cleans up the environment after running a test.
	 * 
	 * @return void;
	 */
	protected function tearDown()
	{
		$this->state_data = NULL;
		$this->state_data_decorator = NULL;
		
		parent::tearDown();
	}
	
	/**
	 * Tests __call()
	 * All methods not explicitly set should be passed directly to the target (state data)
	 * 
	 * @return void
	 */
	public function testCall()
	{
		$data = array();
		$this->state_data
			->expects($this->once())
			->method('fakeFunction')
			->with($this->equalTo($data));
		$this->state_data_decorator->fakeFunction($data);
	}
	
	/**
	 * Tests OLPBlackbox_StateDataDecoratorWithheldTargets->__construct()
	 * 
	 * @return void
	 */
	public function testConstruct()
	{
		$decorator = $this->getMock(
			'OLPBlackbox_StateDataDecoratorWithheldTargets',
			array(),
			array($this->state_data));
		
		$this->assertAttributeEquals($this->state_data, 'state_data', $decorator);
	}
	
	/**
	 * Tests OLPBlackbox_StateDataDecoratorWithheldTargets->__get()
	 * Gets for withheld target should return the withheld target value
	 * Gets for other values should be sent to the target
	 * 
	 * @return void
	 */
	public function test__get()
	{
		$test_value = 'test_value';
		
		// __get on the state data should get called once even though we are making two __get calls on
		// the decorator as  withheld_targets exists in the decorator's domain
		$this->state_data
			->expects($this->once())
			->method('__get')
			->with($this->equalTo('test_element'))
			->will($this->returnValue($test_value));
		$withheld_targets = $this->state_data_decorator->withheld_targets;
		
		// Get the value that should return from the state data and verify that the values are equal
		$return_test_value = $this->state_data_decorator->test_element;
		$this->assertEquals($test_value, $return_test_value);
	}
	
	/**
	 * Tests OLPBlackbox_StateDataDecoratorWithheldTargets->__isset()
	 * 
	 * @return void
	 */
	public function testIsset()
	{
		// __isset on the state data should get called once even though we are making two __isset calls on
		// the decorator as withheld_targets exists in the decorator's domain and will always return true
		$this->state_data
			->expects($this->once())
			->method('__isset')
			->with($this->equalTo('test_element'))
			->will($this->returnValue(TRUE));
		
		$this->assertTrue(isset($this->state_data_decorator->withheld_targets));
		$this->assertTrue(isset($this->state_data_decorator->test_element));
	}
	
	/**
	 * Tests OLPBlackbox_StateDataDecoratorWithheldTargets->__set()
	 * 
	 * @return void
	 */
	public function test__set()
	{
		$test_value = 'test_value';
		// __set on the state data should get called once even though we are making two __set calls on
		// the decorator as withheld_targets exists in the decorator's domain
		$this->state_data
			->expects($this->once())
			->method('__set')
			->with('test_element', $test_value);
		
		$this->state_data_decorator->withheld_targets = array();
		$this->state_data_decorator->test_element = $test_value;
					
	}
	
	/**
	 * Tests OLPBlackbox_StateDataDecoratorWithheldTargets->addStateData()
	 */
	public function testAddStateData()
	{
		$new_state_data_1 = $this->getMockedStateData();
		
		// addStateData() should pass through to the state data object that is wrapped
		// It is only defined due to Interface requirements
		$this->state_data
			->expects($this->once())
			->method('addStateData')
			->with($this->equalTo($new_state_data_1));

		$this->state_data_decorator->addStateData($new_state_data_1);
	}
}

