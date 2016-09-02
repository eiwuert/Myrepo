<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_TargetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The OLPBlackbox_Target object used in tests.
	 *
	 * @var OLPBlackbox_Target
	 */
	protected $target;

	/**
	 * State data to pass around to tests.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;

	/**
	 * Sets up the tests OLPBlackbox_Target object.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->state_data = new Blackbox_StateData();
		$this->target = new OLPBlackbox_Target('test', 0);
	}

	/**
	 * Destroys the target at the end of each test.
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
		$data = $this->getMock('OLPBlackbox_Data', array());
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
		$data = $this->getMock('OLPBlackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));

		$this->target->setRules($rules);
		$valid = $this->target->isValid($data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Tests to see if the Blackbox_Exception is thrown if we don't setup the rules.
	 *
	 * @expectedException Blackbox_Exception
	 * @return void
	 */
	public function testIsValidException()
	{
		$data = $this->getMock('OLPBlackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$this->target->isValid($data, $this->state_data);
	}

	/**
	 * Tests that the setInvalid() function works properly.
	 *
	 * @return void
	 */
	public function testSetInvalid()
	{
		$data = new OLPBlackbox_Data();

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		// Test that we actually were TRUE the first run
		$this->target->setRules($rules);
		$valid = $this->target->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		// Set this target to invalid and test that we get FALSE back now
		$this->target->setInvalid();
		$valid = $this->target->isValid($data, $this->state_data);
		$this->assertFalse($valid);
	}

	/**
	 * Data provider for testPickTargetRules.
	 *
	 * @return array
	 */
	public static function pickTargetRulesDataProvider()
	{
		return array(
			array(TRUE, TRUE),
			array(FALSE, FALSE)
		);
	}

	/**
	 * Tests that the pickTargetRules affects the outcome.
	 *
	 * @param bool $rule_is_valid validity of the rule
	 * @param bool $expected what we expect from pickTarget
	 * @dataProvider pickTargetRulesDataProvider
	 * @return void
	 */
	public function testPickTargetRules($rule_is_valid, $expected)
	{
		$data = new Blackbox_Data();

		// Mock rule that we'll add to the target collection's pickTargetRules
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->any())->method('isValid')->will($this->returnValue($rule_is_valid));

		$target = new OLPBlackbox_Target('test', 0);
		$target->setPickTargetRules($rules);

		$winner = $target->pickTarget($data);

		if ($expected)
		{
			$this->assertType('Blackbox_IWinner', $winner);
		}
		else
		{
			$this->assertFalse($winner);
		}
	}
}
?>
