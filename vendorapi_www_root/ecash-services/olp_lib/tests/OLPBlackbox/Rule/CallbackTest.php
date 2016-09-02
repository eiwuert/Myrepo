<?php

/**
 * Tests a the Callback class, a class which simply embodies a generic callback.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox 
 */
class OLPBlackbox_Rule_CallbackTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test a Callback which points to a global function.
	 * @return void
	 */
	public function testFunctionCall()
	{
		$callback = new OLPBlackbox_Rule_Callback('array_map', array('strtoupper', array('johnny')));
		$return = $callback->__invoke();
		$this->assertEquals(
			array('JOHNNY'),
			$return,
			"Output of call unexpected."
		);
	}
	
	/**
	 * Test a callback object pointing to a method on an object.
	 * @return void
	 */
	public function testMethodCall()
	{
		$mock = $this->getMock('stdClass', array('mockFunction'));
		$mock->expects($this->once())
			->method('mockFunction')
			->with($this->equalTo(2))
			->will($this->returnValue(33));
		
		$callback = new OLPBlackbox_Rule_Callback('mockFunction', array(2), $mock);
		
		$return = $callback->__invoke();
		
		$this->assertEquals(33, $return, "Return from method was incorrect.");
	}
	
	/**
	 * The Callback object should throw a runtime exception if it points to a 
	 * missing function.
	 * @return void
	 */
	public function testBrokenFunctionException()
	{
		$this->setExpectedException('RuntimeException');
		$callback = new OLPBlackbox_Rule_Callback('missing_function_1929847');
		$callback->__invoke();
	}
	
	/**
	 * The Callback object should throw a runtime exception if it points to a 
	 * method on an object which doesn't exist.
	 * @return void
	 */
	public function testBrokenMethodException()
	{
		$this->setExpectedException('RuntimeException');
		$callback = new OLPBlackbox_Rule_Callback('missing_function', array(), new stdClass());
		$callback->__invoke();
	}
	
	/**
	 * It should be possible to change the object a callback points to.
	 * @return void
	 */
	public function testSwitchObject()
	{
		$method_name = 'methodName';
		$a = $this->freshObjectExpectingMethodCall($method_name);
		$b= $this->freshObjectExpectingMethodCall($method_name);
		
		$callback = new OLPBlackbox_Rule_Callback($method_name, array(), $a);
		$callback->__invoke();
		
		// switch objects, the point of our test
		$callback->setObject($b);
		
		$this->assertTrue(
			$b === $callback->object,
			"Unable to change the object in the callback."
		);
		$callback->__invoke();
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Produce an object which expects a method call.
	 * @param string $method_name The name of the method this object expects to 
	 * be called.
	 * @param int $times The number of times this object expects this call.
	 * @return stdClass
	 */
	protected function freshObjectExpectingMethodCall($method_name, $times = 1)
	{
		$mock = $this->getMock('stdClass', array($method_name));
		$mock->expects($this->exactly($times))->method($method_name);
		
		return $mock;
	}
}

?>