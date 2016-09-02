<?php
/**
 * OLPBlackbox_StateData test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_StateDataTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @var OLPBlackbox_StateData
	 */
	private $state_data;
	
	/**
	 * Prepares the environment before running a test.
	 * 
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();
		
		$this->state_data = new OLPBlackbox_StateData();
	
	}
	
	/**
	 * Cleans up the environment after running a test.
	 * 
	 * @return void
	 */
	protected function tearDown()
	{
		$this->state_data = NULL;
		
		parent::tearDown();
	}
	
	/**
	 * Tests OLPBlackbox_StateData->__sleep()
	 * 
	 * @return void
	 */
	public function testSleep()
	{
		$datax_decision_1 = '1';
		$this->state_data->datax_decision = $datax_decision_1;
		// Add some other state data elements
		$sub_state_1 = new OLPBlackbox_StateData();
		$datax_decision_2 = '2';
		$sub_state_1->datax_decision = $datax_decision_2;
		$sub_state_2 = new OLPBlackbox_StateData();
		$datax_decision_3 = '3';
		$sub_state_2->datax_decision = $datax_decision_3;
		$this->state_data->addStateData($sub_state_1);
		$this->state_data->addStateData($sub_state_2);
		
		$new_state = unserialize(serialize($this->state_data));
		$this->assertEquals($this->state_data, $new_state);
	}
}

