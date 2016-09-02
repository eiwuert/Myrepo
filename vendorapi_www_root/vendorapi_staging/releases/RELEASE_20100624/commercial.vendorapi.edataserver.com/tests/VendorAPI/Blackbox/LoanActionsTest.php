<?php
/**
 * Tests the loan actions class
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Blackbox_LoanActionsTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Tests adding and retrieving a loan action
	 * @return void
	 */
	public function testLoanActions()
	{
		$loan_action_before = 'TEST ACTION';
		
		$loan_actions = new VendorAPI_Blackbox_LoanActions();
		$loan_actions->addLoanAction($loan_action_before);
		$loan_actions_array = $loan_actions->getLoanActions();
		$loan_action_after = $loan_actions_array[0];
		
		$this->assertEquals($loan_action_before, $loan_action_after);
		$this->assertEquals(1,count($loan_actions_array));
	}
	
	public function testCombine() 
	{
		$loan_action_before = 'TEST ACTION_1';
		
		$loan_actions_set = new VendorAPI_Blackbox_LoanActions();
		$loan_actions_set->addLoanAction($loan_action_before);
				
		$loan_actions = new VendorAPI_Blackbox_LoanActions();
		$loan_action_copy = $loan_actions->combine($loan_actions_set);
		$this->assertThat($loan_action_copy, $this->isinstanceOf('VendorAPI_Blackbox_LoanActions'));		
		
	}
	
	public function testToString()
	{
		$loan_action_before = 'TEST ACTION';
		
		$loan_actions = new VendorAPI_Blackbox_LoanActions();
		$loan_actions->addLoanAction($loan_action_before);

		$this->assertEquals($loan_actions->__toString(), implode("\n", $loan_actions->getLoanActions()));
	}
	
	public function testTriggerGetsHit()
	{
		$ac_name = "TEST_ACTION";
		$loan_actions = new VendorAPI_Blackbox_LoanActions();
		$triggers = new VendorAPI_Blackbox_Triggers();
		$loan_actions->setTriggers($triggers);
		$trigger = $this->getMock('VendorAPI_Blackbox_Trigger', array(), array($ac_name));
		$trigger->expects($this->any())->method('getAction')->will($this->returnValue($ac_name));
		$trigger->expects($this->once())->method('hit');
		$triggers->addTrigger($trigger);
		$loan_actions->addLoanAction($ac_name);
	}
	
	public function testTriggerDoesNotGetHit()
	{
		$ac_name = "TEST_ACTION";
		$loan_actions = new VendorAPI_Blackbox_LoanActions();
		$triggers = new VendorAPI_Blackbox_Triggers();
		$loan_actions->setTriggers($triggers);
		$trigger = $this->getMock('VendorAPI_Blackbox_Trigger', array(), array($ac_name));
		$trigger->expects($this->any())->method('getAction')->will($this->returnValue("ONO"));
		$trigger->expects($this->never())->method('hit');
		$triggers->addTrigger($trigger);
		$loan_actions->addLoanAction('TEST_ACTION');		
	}
	
}



?>