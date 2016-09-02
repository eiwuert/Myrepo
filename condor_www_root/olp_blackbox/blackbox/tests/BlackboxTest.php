<?php
/**
 * BlackboxTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test case for the Blackbox class.
 *
 * A couple of these tests kind of test the TargetCollection code more so than the Blackbox code,
 * but we're ensuring that the pickWinner function is working properly by including them.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class BlackboxTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Blackbox object for testing.
	 *
	 * @var Blackbox
	 */
	protected $blackbox;

	/**
	 * Setups the Blackbox object for these tests.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->blackbox = new Blackbox();
	}

	/**
	 * Destroys the Blackbox object for these tests.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->blackbox);
	}

	/**
	 * Checks that pickWinner throws an exception when a target collection was not set
	 *
	 * @return void
	 */
	public function testExceptionNoCollection()
	{
		$data = new Blackbox_Data();

		$this->setExpectedException('Blackbox_Exception');
		$this->blackbox->pickWinner($data);
	}

	/**
	 * Checks that the pickWinner function returns FALSE when the collection object's pickTarget returns false.
	 *
	 * @return void
	 */
	public function testPickWinnerFailOnCollection()
	{
		/**
		 * We don't ever expect the Blackbox_Data object to have a value set or unset inside of
		 * Blackbox.
		 */
		$data = $this->getMock('Blackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		/**
		 * Mocking collection so we always return FALSE on isValid.
		 */
		$target_collection = $this->getMock('Blackbox_TargetCollection', array('isValid', 'pickTarget'));
		$target_collection->expects($this->once())->method('isValid')
			->will($this->returnValue(TRUE));
		$target_collection->expects($this->once())->method('pickTarget')
			->will($this->returnValue(FALSE));

		$this->blackbox->setRootCollection($target_collection);

		$winner = $this->blackbox->pickWinner($data);
		$this->assertFalse($winner);
	}

	/**
	 * Checks that the pickWinner function returns FALSE when the collection is invalid.
	 *
	 * @return void
	 */
	public function testPickWinnerFailOnTargetCollection()
	{
		/**
		 * We don't ever expect the Blackbox_Data object to have a value set or unset inside of
		 * Blackbox.
		 */
		$data = $this->getMock('Blackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		// Forces Blackbox_Target's isValid function to return TRUE
		$target_collection = $this->getMock('Blackbox_TargetCollection', array('isValid'));
		$target_collection->expects($this->once())
			->method('isValid')
			->with($this->equalTo($data))
			->will($this->returnValue(FALSE));

		$this->blackbox->setRootCollection($target_collection);

		$winner = $this->blackbox->pickWinner($data);
		$this->assertFalse($winner);
	}

	/**
	 * Checks that the pickWinner function returns a valid Blackbox_Winner when there is a valid
	 * target in the collection.
	 *
	 * @return void
	 */
	public function testPickWinnerPassOnTarget()
	{
		$data = new Blackbox_Data();

		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$target_collection = new Blackbox_TargetCollection();
		$target_collection->addTarget($target);

		$this->blackbox->setRootCollection($target_collection);

		$winner = $this->blackbox->pickWinner($data);
		$this->assertType('Blackbox_Winner', $winner);
	}
	
	/**
	 * Tests setting and getting state data from Blackbox.
	 * 
	 * This tests both setting state data and making sure we get the same instance back as well as
	 * making sure we create an instance if we don't pass it in.
	 *
	 * @return void
	 */
	public function testSetAndGetStateData()
	{
		$state_data = new Blackbox_StateData();
		
		// See that the one we passed in is the same that we get out
		$blackbox = new Blackbox($state_data);
		$this->assertSame($state_data, $blackbox->getStateData());
		
		// See that it's a new object.
		$blackbox = new Blackbox();
		$this->assertNotSame($state_data, $blackbox->getStateData());
		$this->assertType('Blackbox_IStateData', $blackbox->getStateData());
	}
}
?>
