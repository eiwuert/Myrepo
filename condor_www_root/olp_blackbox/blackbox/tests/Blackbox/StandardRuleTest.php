<?php
/**
 * RuleTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the default Rule class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_StandardRuleTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that the isValid function returns FALSE when canRun returns FALSE.
	 *
	 * @return void
	 */
	public function testIsValid()
	{
		/**
		 * We don't ever expect the Blackbox_Data object to have a value set or unset inside of
		 * Blackbox.
		 */
		$data = $this->getMock('Blackbox_Data');
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// state_data, however, is mutable
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('runRule', 'canRun'));
		$rule->expects($this->once())->method('canRun')->will($this->returnValue(FALSE));

		$valid = $rule->isValid($data, $state_data);
		$this->assertFalse($valid);
	}

	/**
	 * Make sure onSkip is called when the rule is skipped
	 *
	 * @return void
	 */
	public function testOnSkip()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('onSkip', 'runRule'));

		$rule->expects($this->exactly(1))
			->method('onSkip');

		$rule->isValid($data, $state_data);
	}

	/**
	 * Make sure onValid is triggered when the rule passes
	 *
	 * @return void
	 */
	public function testOnValid()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('canRun', 'onValid', 'runRule'));

		// force to ignore the fact that the data is missing
		$rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));

		// force a pass
		$rule->expects($this->any())
			->method('runRule')
			->will($this->returnValue(TRUE));

		$rule->expects($this->exactly(1))
			->method('onValid');

		$rule->isValid($data, $state_data);
	}

	/**
	 * Make sure onInvalid is triggered when the rule fails
	 *
	 * @return void
	 */
	public function testOnInvalid()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('canRun', 'onInvalid', 'runRule'));

		// force to ignore the fact that the data is missing
		$rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));

		// force a failure
		$rule->expects($this->any())
			->method('runRule')
			->will($this->returnValue(FALSE));

		$rule->expects($this->exactly(1))
			->method('onInvalid');

		$rule->isValid($data, $state_data);
	}

	/**
	 * Test that onError is triggered when the rule fails
	 *
	 * @return void
	 */
	public function testOnError()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('canRun', 'onERror', 'runRule'));

		// force to ignore the fact that the data is missing
		$rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));

		$exception = new Blackbox_Exception();

		// force an exception
		$rule->expects($this->any())
			->method('runRule')
			->will($this->throwException($exception));

		// ensure onError gets called
		$rule->expects($this->exactly(1))
			->method('onError')
			->with($exception, $data, $state_data);

		$rule->isValid($data, $state_data);
	}

	/**
	 * Test that onError is the only event triggered on an exception
	 *
	 * @return void
	 */
	public function testExceptionOnlyCallsOnError()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('canRun',
			'onValid', 'onInvalid', 'runRule'));

		// force to ignore the fact that the data is missing
		$rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));

		// force an exception
		$rule->expects($this->any())
			->method('runRule')
			->will($this->throwException(new Blackbox_Exception()));

		// these should never be called
		$rule->expects($this->exactly(0))
			->method('onInvalid');
		$rule->expects($this->exactly(0))
			->method('onValid');

		$rule->isValid($data, $state_data);
	}
}
?>
