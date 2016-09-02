<?php
/**
 * TargetCollectionTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the default TargetCollection class.
 *
 * @todo Add a test that checks that a collection inside of a collection still works.
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_TargetCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The Blackbox_TargetCollection object to use in these tests.
	 *
	 * @var Blackbox_TargetCollection
	 */
	protected $target_collection;

	/**
	 * State data object to use in tests.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;

	/**
	 * Instantiates a new Blackbox_TargetCollection for each test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->state_data = new Blackbox_StateData();
		$this->target_collection = new Blackbox_TargetCollection();
	}

	/**
	 * Unsets the target_collection class variable.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->target_collection);
	}

	/**
	 * Tests that the pickTarget function returns boolean FALSE by default.
	 *
	 * @return void
	 */
	public function testPickTargetDefault()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$winner = $this->target_collection->pickTarget($data);
		$this->assertFalse($winner);
	}

	/**
	 * Tests that if we have valid targets that the pickTarget function returns a winner.
	 *
	 * @return void
	 */
	public function testPickTargetValidTargets()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->exactly(1))
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(TRUE));

		$this->target_collection->addTarget($target);

		/**
		 * Normally we'd check the validity of the collection, but in this case we're actually
		 * trying to test the pickTarget function. Checking isValid is done in other tests. We
		 * however needs this to run in order to get valid targets.
		 */
		$this->target_collection->isValid($data, $this->state_data);

		$winner = $this->target_collection->pickTarget($data);
		$this->assertType('Blackbox_IWinner', $winner);
	}

	/**
	 * Tests that if we don't have any valid targets that the pickTarget function returns a FALSE.
	 *
	 * @return void
	 */
	public function testPickTargetNoValidTargets()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->exactly(1))
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(FALSE));

		$this->target_collection->addTarget($target);

		/**
		 * Normally we'd check the validity of the collection, but in this case we're actually
		 * trying to test the pickTarget function. Checking isValid is done in other tests. We
		 * however needs this to run in order to get valid targets.
		 */
		$this->target_collection->isValid($data, $this->state_data);

		$winner = $this->target_collection->pickTarget($data);
		$this->assertFalse($winner);
	}

	/**
	 * Tests that the default isValid function returns FALSE.
	 *
	 * @return void
	 */
	public function testIsValidDefault()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())
			->method('__set');
		$data->expects($this->never())
			->method('__unset');

		$valid = $this->target_collection->isValid($data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Tests that if the targets return TRUE, that the isValid function returns TRUE.
	 *
	 * @return void
	 */
	public function testIsValidPassOnTarget()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->once())
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(TRUE));

		$this->target_collection->addTarget($target);
		$valid = $this->target_collection->isValid($data, $this->state_data);

		$this->assertTrue($valid);
	}

	/**
	 * Tests that if the targets return FALSE, that the isValid function returns FALSE.
	 *
	 * @return void
	 */
	public function testIsValidFailOnTarget()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->once())
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(FALSE));

		$this->target_collection->addTarget($target);
		$valid = $this->target_collection->isValid($data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Runs a test on isValid expecting that the rule collection passed into the target collection
	 * returns as valid.
	 *
	 * @return void
	 */
	public function testIsValidPassOnRules()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->once())
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(TRUE));

		// Forces Blackbox_RuleCollection's isValid function to return TRUE
		$rules = $this->getMock('Blackbox_RuleCollection', array('isValid'));
		$rules->expects($this->once())
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(TRUE));

		$this->target_collection->addTarget($target);
		$this->target_collection->setRules($rules);

		$valid = $this->target_collection->isValid($data, $this->state_data);

		$this->assertTrue($valid);
	}

	/**
	 * Runs a test on isValid expecting that the rule collection passed into the target collection
	 * returns as invalid.
	 *
	 * @return void
	 */
	public function testIsValidFailOnRules()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->never())->method('isValid');

		// Forces Blackbox_RuleCollection's isValid function to return TRUE
		$rules = $this->getMock('Blackbox_RuleCollection', array('isValid'));
		$rules->expects($this->once())
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(FALSE));

		$this->target_collection->addTarget($target);
		$this->target_collection->setRules($rules);

		$valid = $this->target_collection->isValid($data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Add a collection to the target collection.
	 *
	 * @return void
	 */
	public function testAddTargetWithCollection()
	{
		$data = new Blackbox_Data();
		$target_col = new Blackbox_TargetCollection();

		// This in itself should pass and not generate an error
		$this->target_collection->addTarget($target_col);
	}

	/**
	 * Ensures that calling pickTarget() twice will return the second valid target
	 *
	 * @return void
	 */
	public function testPickTwice()
	{
		// Forces Blackbox_Target's isValid function to return TRUE
		$target1 = $this->getMock('Blackbox_Target', array('isValid'));
		$target1->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		// Forces Blackbox_Target's isValid function to return TRUE
		$target2 = $this->getMock('Blackbox_Target', array('isValid'));
		$target2->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$target_col = new Blackbox_TargetCollection();
		$target_col->addTarget($target1);
		$target_col->addTarget($target2);

		$data = new Blackbox_Data();

		$target_col->isValid($data, $this->state_data);
		$w1 = $target_col->pickTarget($data);
		$w2 = $target_col->pickTarget($data);

		$this->assertEquals($target2, $w2->getTarget());
	}

	/**
	 * Ensures that embedded collections are picked from twice
	 *
	 * @return void
	 */
	public function testRepickWithEmbeddedCollection()
	{
		// we're essentially mocking a TargetCollection here...
		// isValid() will return TRUE, and pickTarget will always return winner
		// we just want to ensure that pickTarget is called twice
		$target = $this->getMock('Blackbox_Target', array('isValid', 'pickTarget'));
		$target->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$target->expects($this->exactly(2))
			->method('pickTarget')
			->will($this->returnValue(new Blackbox_Winner($target)));

		$collection = new Blackbox_TargetCollection();
		$collection->addTarget($target);

		$data = new Blackbox_Data();

		$collection->isValid($data, $this->state_data);
		$collection->pickTarget($data);
		$winner = $collection->pickTarget($data);

		// make sure that it repicked from the same target
		$this->assertEquals($target, $winner->getTarget());
	}

	/**
	 * Tests that the rule validity caching on the collection works correctly.
	 *
	 * @return void
	 */
	public function testRuleValidityCaching()
	{
		$data = $this->getMock('Blackbox_Data', array('__set', '__unset'));
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->exactly(2))->method('isValid')
			->will($this->returnValue(TRUE));

		// Forces Blackbox_RuleCollection's isValid function to return TRUE
		$rules = $this->getMock('Blackbox_RuleCollection', array('isValid'));
		$rules->expects($this->once())->method('isValid')
			->will($this->returnValue(TRUE));

		$this->target_collection->addTarget($target);
		$this->target_collection->setRules($rules);

		$valid = $this->target_collection->isValid($data, $this->state_data);

		/**
		 * Call it a second time to make sure that the rule collection's isValid only
		 * got called once
		 */
		$valid = $this->target_collection->isValid($data, $this->state_data);

		// Still verify that valid was TRUE
		$this->assertTrue($valid);
	}

	/**
	 * Tests that when we pass state data along, that the state data from previous collections
	 * is saved and passed along to the targets below them.
	 *
	 * @return void
	 */
	public function testStateDataEncapsulation()
	{
		$name = 'test';
		$data = new Blackbox_Data();
		$target = new Blackbox_Target();
		$state_data = new Blackbox_StateData(array('name' => $name));

		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$target->setRules($rule);

		$this->target_collection->addTarget($target);
		$valid = $this->target_collection->isValid($data, $state_data);
		$winner = $this->target_collection->pickTarget($data);

		$this->assertTrue($valid);
		/**
		 * This assertion assumes that the only state data with a value will be the one we
		 * created at the beginning of the test. So when I pull the name from the target's state
		 * data, it will have to traverse the mulitple state data it has and pull the last name.
		 */
		$this->assertEquals($name, $winner->getTarget()->getStateData()->name);
	}
}
?>
