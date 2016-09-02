<?php

/**
 * Unit tests for  VendorAPI_Blackbox_Rule_Suppression_Verify
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_Suppression_VerifyTest extends PHPUnit_Framework_TestCase
{
	protected $log;
	protected $list;
	protected $blackbox_data;
	protected $state;
	protected $ssn;
	protected $tss_suppression_list;
	protected $expected_event;

	protected function setUp()
	{
		
 		$this->log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		$this->ssn = '123456789';
		$this->blackbox_data = new VendorAPI_Blackbox_Data();
        $this->blackbox_data->loadFrom(array('ssn' => $this->ssn));
        $this->list_id = 9999;
        $this->tss_suppression_list = $this->getMock('TSS_SuppressionList_1', array(), array(), '', FALSE); 

		 $list_collections = new VendorAPI_SuppressionList_Wrapper($this->tss_suppression_list, $this->list_name, '', 'ssn', $this->list_id);

		$list_name = "list name";
		$this->expected_event = 'LIST_VERIFY_LIST NAME';
		$list = $this->getMock('VendorAPI_SuppressionList_DBLoader',	array(), array(), '', FALSE);
        $list->expects($this->any())
        	->method('getByName')
        	->with($this->equalTo($list_name))
        	->will($this->returnValue($list_collections));
 		
        $this->state = new VendorAPI_Blackbox_StateData();
		
        $this->rule = new VendorAPI_Blackbox_Rule_Suppression_Verify(
			$this->log,
			$list,
			$list_name
		);
        
		
	}	
	
	protected function tearDown()
	{
		$this->blackbox_data = NULL;
		$this->state = NULL;
		$this->list = NULL;
		$this->rule = NULL;
		$this->list_id = NULL;
		$this->log = NULL;
		$this->expected_event = NULL;
	}

	public function testVerifyNoMatch()
	{

		$this->tss_suppression_list
			->expects($this->any())
			->method('match')
			->with($this->equalTo($this->ssn))
			->will($this->returnValue(FALSE));
		$this->log
			->expects($this->once())
			->method('logEvent')
			->with($this->equalTo($this->expected_event), $this->anything(), $this->anything());

		$response = $this->rule->isValid($this->blackbox_data, $this->state);
		$this->assertTrue($response, 'Verify should always pass isValid');
		$this->assertNull($this->state->getCombined('loan_actions'),
				'There should be no loan actions when no macth is found');
	}
		
	public function testVerifyMatch()
	{
		$this->tss_suppression_list
			->expects($this->any())
			->method('match')
			->with($this->equalTo($this->ssn))
			->will($this->returnValue(TRUE));
		$this->log
			->expects($this->once())
			->method('logEvent')
			->with($this->equalTo($this->expected_event), $this->anything(), $this->anything());
		
		$response = $this->rule->isValid($this->blackbox_data, $this->state);
		$loan_actions = $this->state->getCombined('loan_actions');
		$this->assertTrue($response, 'Verify should always pass isValid');
		$this->assertNotNull($loan_actions,
				'There should be a loan action when a match is found');
		$this->assertEquals(1, count($loan_actions->getLoanActions()),
				'Unexpected number of loan actions');
		$loan_action = $loan_actions->getLoanActions();
		$this->assertEquals("LIST_VERIFY_SSN_{$this->list_id}", $loan_action[0],
				'Unexpected loan action name');
	}
	
}
?>
