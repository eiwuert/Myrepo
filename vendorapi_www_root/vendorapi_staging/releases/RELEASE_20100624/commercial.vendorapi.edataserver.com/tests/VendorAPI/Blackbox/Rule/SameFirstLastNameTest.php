<?php 

class VendorAPI_Blackbox_Rule_SameFirstLastNameTest extends PHPUnit_Framework_TestCase
{
	private $_data;
	private $_loan_actions;
	private $_state;
	private $_rule;

	public function setUp()
	{
		$this->_data = new VendorAPI_Blackbox_Data();
		$this->_loan_actions = $this->getMock('VendorAPI_Blackbox_LoanActions', array('addLoanAction'));
		
		$this->_state = new VendorAPI_Blackbox_StateData();
		$this->_state->loan_actions = $this->_loan_actions;
		
		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$this->_rule = new VendorAPI_Blackbox_Rule_SameFirstLastName($log);
		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function tearDown()
	{
		$this->_data = null;
		$this->_loan_actions = null;
		$this->_state = null;
		$this->_rule = null;
	}

	public function testWithSameNames()
	{
		$this->_data->name_first = "Tom";
		$this->_data->name_last  = "Tom";
		
		$this->_loan_actions->expects($this->once())->method('addLoanAction')
			->with('VERIFY_SAME_FIRST_LAST');
		$this->_rule->isValid($this->_data, $this->_state);
	}
	
	public function testWithDifferentNames()
	{
		$this->_data->name_first = "Tom";
		$this->_data->name_last  = "Not";
		
		$this->_loan_actions->expects($this->never())->method('addLoanAction');
		$this->_rule->isValid($this->_data, $this->_state);		
	}
}
