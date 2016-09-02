<?php

/**
 * Test the OLPBlackbox_Rule_CallbackContainerTest class.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_CallbackContainerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that callbacks are fired properly on a rule callback container.
	 * 
	 * @dataProvider callbacksFiredProvider
	 * @param bool $rule_should_pass Whether the rule put into the callback
	 * container should pass or fail.
	 * @param array $arguments The arguments that the callback should send.
	 * @return void
	 */
	public function testCallbacksFired($rule_should_pass, array $arguments = array())
	{
		// decide if the rule should pass or not.
		$rule = $this->getMockedRule();
		$rule->expects($this->any())
			->method('isValid')
			->will($this->returnValue($rule_should_pass));
		
		// sets up expectations on the "success" and "failure" methods on the callback_object
		$callback_object = $this->freshCallbackObjectWithExpects($rule_should_pass, $arguments);		
		
		$container = new OLPBlackbox_Rule_CallbackContainer($rule);
		$container->newOnValidCallback('success', $arguments, $callback_object);
		$container->newOnInvalidCallback('failure', $arguments, $callback_object);
		
		$container->isValid(new Blackbox_Data(), new OLPBlackbox_StateData());
	}
	
	/**
	 * @see testCallbacksFired()
	 * @return array
	 */
	public static function callbacksFiredProvider()
	{
		return array(
			// test callbacks firing with arguments
			array(TRUE, array(1, 2)),
			array(FALSE, array(1, 2)),
			// test callbacks firing without arguments
			array(TRUE, array()),
			array(FALSE, array()),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Return a new mocked rule.
	 * @return OLPBlackbox_Rule
	 */
	protected function getMockedRule()
	{
		return $this->getMock('OLPBlackbox_Rule', array('isValid', 'runRule'));
	}
	
	/**
	 * Set up a callback mock we can use to verify that the callback container is
	 * executing the expected callbacks.
	 * 
	 * @param bool $success_expected Whether or not the CallbackContainer rule
	 * will pass or fail, which affects which method will be called from it.
	 * @param array $arguments_for_callbacks List of arguments that should be 
	 * expected to be passed from the CallbackContainer to the mock callback 
	 * object.
	 * @return stdClass essentially a standard class with some methods, but used
	 * as a mock. (Mocks mess up __get/__set)
	 */
	protected function freshCallbackObjectWithExpects($success_expected, array $arguments_for_callbacks)
	{
		$callback_object = $this->getMock('stdClass', array('success', 'failure'));
		
		$success_method = $callback_object->expects($this->exactly($success_expected ? 1 : 0))
			->method('success');
		if ($success_expected)
		{
			// set up expected arguments, which we must "unpack"
			call_user_func_array(array($success_method, 'with'), $arguments_for_callbacks);
		}
		
		$failure_method = $callback_object->expects($this->exactly($success_expected ? 0 : 1))
			->method('failure');
		if (!$success_expected)
		{
			call_user_func_array(array($failure_method, 'with'), $arguments_for_callbacks);
		}
		
		return $callback_object;
	}
}
?>