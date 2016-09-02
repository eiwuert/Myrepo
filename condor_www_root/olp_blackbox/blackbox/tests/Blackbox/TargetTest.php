<?php
/**
 * TargetTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the default Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_TargetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The Blackbox_Target object for tests.
	 *
	 * @var Blackbox_Target
	 */
	protected $target;

	/**
	 * State data object to pass around in tests.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;

	/**
	 * Sets up the Blackbox_Target object for every test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->state_data = new Blackbox_StateData();
		$this->target = new Blackbox_Target();
	}

	/**
	 * Tears down the Blackbox_Target object after every test.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->target);
	}

	/**
	 * Tests that the isValid function returns TRUE if the rules' isValid returns TRUE.
	 *
	 * @return void
	 */
	public function testIsValidPassOnRules()
	{
		/**
		 * We don't ever expect the Blackbox_Data object to have a value set or unset inside of
		 * Blackbox.
		 */
		$data = $this->getMock('Blackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$this->target->setRules($rules);
		$valid = $this->target->isValid($data, $this->state_data);

		$this->assertTrue($valid);
	}

	/**
	 * Tests that the isValid function returns FALSE if the rules' isValid returns FALSE.
	 *
	 * @return void
	 */
	public function testIsValidFailOnRules()
	{
		/**
		 * We don't ever expect the Blackbox_Data object to have a value set or unset inside of
		 * Blackbox.
		 */
		$data = $this->getMock('Blackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));

		$this->target->setRules($rules);
		$valid = $this->target->isValid($data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Tests that pickTarget() returns an instance of Blackbox_IWinner
	 *
	 * @return void
	 */
	public function testPickReturnsWinner()
	{
		$target = new Blackbox_Target();
		$data = new Blackbox_Data();

		$winner = $target->pickTarget($data);

		$this->assertType('Blackbox_IWinner', $winner);
	}

	/**
	 * Tests that pickTarget() returns FALSE on subsequent invocations
	 *
	 * @return void
	 */
	public function testPickTwiceReturnsFalse()
	{
		$target = new Blackbox_Target();
		$data = new Blackbox_Data();

		$target->pickTarget($data);

		$winner = $target->pickTarget($data);
		$this->assertFalse($winner);
	}

	/**
	 * Tests that an exception is thrown if we don't setup the rules first.
	 *
	 * @expectedException Blackbox_Exception
	 * @return void
	 */
	public function testIsValidException()
	{
		$data = $this->getMock('Blackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$this->target->isValid($data, $this->state_data);
	}
}
?>
