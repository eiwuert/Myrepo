<?php
/**
 * Unit tests for VendorAPI_Blackbox_Rule_ReactVerificationTest
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_ReactVerificationTest extends PHPUnit_Framework_TestCase
{
	protected $_api;
	protected $_app;
	protected $_inner_rule;
	protected $_rule;

	protected $_data;
	protected $_state;

	public function setUp()
	{
		$this->_api = $this->getMock('ECash_API_2', array('Get_Status_Date'));
		$this->_app = $this->getMock('ECash_Models_Application', array('loadByKey'));
		$this->_inner_rule = $this->getMock('Blackbox_IRule', array('isValid'));

		$this->_data = new VendorAPI_Blackbox_Data();
		$this->_data->react_application_id = 100;
		$this->_data->bank_aba = '123123123';
		$this->_data->bank_account = '123456789';

		$this->_state = new VendorAPI_Blackbox_StateData();
		
		$this->_state->customer_history = 
			$this->getMock('ECash_CustomerHistory', array('getIsReact', 'getReactID'));


		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$this->_rule = new VendorAPI_Blackbox_Rule_ReactVerification(
			$log,
			'-45 days',
			$this->_api,
			$this->_app,
			$this->_inner_rule
		);
		
	}

	public function tearDown()
	{
		$this->_api = null;
		$this->_app = null;
		$this->_inner_rule = null;
		$this->_rule = null;
		$this->_data = null;
		$this->_state = null;
	}
	
	protected function setIsReact($is_react, $react_app_id = 100)
	{
		$this->_state
			->customer_history
			->expects($this->any())
			->method('getIsReact')
			->will($this->returnValue($is_react));
	
		$this->_state
			->customer_history
			->expects($this->any())
			->method('getReactID')
			->will($this->returnValue($is_react ? $react_app_id : NULL));
	}

	protected function setAppLoadByKeyReturn($return)
	{
		$this->_app
			->expects($this->any())
			->method('loadByKey')
			->will($this->returnValue($return));
		
	}

	public function testRuleSkipsWhenNotAReact()
	{
		$this->_app->expects($this->never())
			->method('loadByKey');

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testSkipPassesWhenNotAReactAndMissingData()
	{
		$this->_data->react_application_id = null;
		$valid = $this->_rule->isValid($this->_data, $this->_state);

		$this->assertTrue($valid);
	}

	public function testFailsIfReactAndMissingReactID()
	{
		$this->setIsReact(TRUE, NULL);
		$this->_data->react_application_id = null;
		$valid = $this->_rule->isValid($this->_data, $this->_state);

		$this->assertFalse($valid);
	}

	public function testFailsIfReactAndMissingBankAba()
	{
		$this->setIsReact(TRUE);
		$this->_data->bank_aba = null;
		$valid = $this->_rule->isValid($this->_data, $this->_state);

		$this->assertFalse($valid);
	}

	public function testFailsIfReactAndMissingBankAccount()
	{
		$this->setIsReact(TRUE);
		$this->_data->bank_account = null;
		$valid = $this->_rule->isValid($this->_data, $this->_state);

		$this->assertFalse($valid);
	}

	public function testCallsAPIWithReactAppId()
	{
		$this->setIsReact(TRUE);
		$this->setAppLoadByKeyReturn(TRUE);
		$this->_api->expects($this->atLeastOnce())
			->method('Get_Status_Date')
			->with($this->anything(), $this->anything(), 100);

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testLoadsModelWithReactAppId()
	{
		$this->setIsReact(TRUE);
		$this->_app->expects($this->atLeastOnce())
			->method('loadByKey')
			->with(100);
		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testRunsRuleWhenPastThreshold()
	{
		// make sure this stuff matches, so the only thing
		// that's causing us to run the rule will be the date
		$this->setIsReact(TRUE);
		$this->setAppLoadByKeyReturn(TRUE);

		$this->_api->expects($this->any())
			->method('Get_Status_Date')
			->will($this->returnValue(strtotime('-1 year')));

		$this->_inner_rule->expects($this->once())
			->method('isValid');

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testRunsRuleWhenABADoesntMatch()
	{
		// make sure this stuff is right (within threshold and matching)
		$this->setIsReact(TRUE);
		$this->_api->expects($this->any())
			->method('Get_Status_Date')
			->will($this->returnValue(strtotime('-1 day')));
		$this->setAppLoadByKeyReturn(TRUE);

		// this doesn't match the ABA in data
		$this->_app->bank_aba = '444444444';

		$this->_inner_rule->expects($this->once())
			->method('isValid');

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testRunsRuleWhenAccountDoesntMatch()
	{
		// make sure this stuff is right (within threshold and matching)
		$this->setIsReact(TRUE);
		$this->_api->expects($this->any())
			->method('Get_Status_Date')
			->will($this->returnValue(strtotime('-1 day')));
		$this->setAppLoadByKeyReturn(TRUE);
		$this->_app->bank_aba = '123123123';

		// this doesn't match the account in data
		$this->_app->bank_account = '444444444';

		$this->_inner_rule->expects($this->once())
			->method('isValid');

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testRunsRuleWhenReactAppCantBeLoaded()
	{
		// make sure this stuff is right (within threshold and matching)
		$this->setIsReact(TRUE);
		$this->_api->expects($this->never())
			->method('Get_Status_Date')
			->will($this->returnValue(strtotime('-1 day')));
		$this->setAppLoadByKeyReturn(FALSE);

		$this->_inner_rule->expects($this->once())
			->method('isValid');

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testDoesntRunRuleWhenWithinThresholdAndAccountMatches()
	{
		// make sure this stuff is right (within threshold and matching)
		$this->setIsReact(TRUE);
		$this->_api->expects($this->any())
			->method('Get_Status_Date')
			->will($this->returnValue(date('Y-m-d', strtotime('-1 day'))));
		$this->setAppLoadByKeyReturn(TRUE);
		$this->_app->bank_aba = '123123123';
		$this->_app->bank_account = '123456789';

		$this->_inner_rule->expects($this->never())
			->method('isValid');

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testIsValidWhenWithinThresholdAndAccountMatches()
	{
		// make sure this stuff is right (within threshold and matching)
		$this->setIsReact(TRUE);
		$this->_api->expects($this->any())
			->method('Get_Status_Date')
			->will($this->returnValue(date('Y-m-d', strtotime('-1 day'))));
		$this->setAppLoadByKeyReturn(TRUE);
		$this->_app->bank_aba = '123123123';
		$this->_app->bank_account = '123456789';

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertTrue($valid);
	}

	public function testInvalidWhenRuleReturnsFalse()
	{
		$this->setIsReact(TRUE);
		$this->setAppLoadByKeyReturn(TRUE);
		// make sure something is wrong
		$this->_api->expects($this->any())
			->method('Get_Status_Date')
			->will($this->returnValue(strtotime('-1 year')));

		$this->_inner_rule->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertFalse($valid);
	}

	public function testValidWhenRuleReturnsTrue()
	{
		$this->setIsReact(TRUE);
		$this->setAppLoadByKeyReturn(TRUE);
		// make sure something is wrong
		$this->_api->expects($this->any())
			->method('Get_Status_Date')
			->will($this->returnValue(strtotime('-1 year')));

		$this->_inner_rule->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertTrue($valid);
	}

	public function testReactIDInStateDataOverrides()
	{
		$this->setIsReact(TRUE, 200);
		$this->_state->react_application_id = 200;

		$this->_app->expects($this->atLeastOnce())
			->method('loadByKey')
			->with(200)
			->will($this->returnValue(TRUE));
		$this->_rule->isValid($this->_data, $this->_state);
	}
}

?>
