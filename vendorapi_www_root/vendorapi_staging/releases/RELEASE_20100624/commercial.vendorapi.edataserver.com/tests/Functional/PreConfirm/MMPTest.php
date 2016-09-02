<?php 
class Functional_PreConfirm_MMPTest extends PHPUnit_Framework_TestCase
{
	protected $_client;

	public function setup()
	{
		$this->_client = getTestClient('mmp', 'mmp');
	}

	public function tearDown()
	{
		$this->_client = null;
	}
	
	public function testStateObjectOnlyNonReact()
	{
		$app_id = 99999;
		$state_object = new VendorAPI_StateObject();
		$state_object->createPart('application');
		$state_object->application->is_react = "no";
		$state_object->application_id = $app_id;
		$state_object->application->application_id = $app_id;
		$state_object->application->fund_qualified = 500;
		$state_object->application->loan_type_id = 1;
		$state_object->application->rule_set_id = 457;
		
		$result = $this->_client->preConfirm($app_id, serialize($state_object));
		$this->assertEquals(500, $result['result']['maximum_loan_amount']);
		$this->assertEquals(array(200, 250, 300, 350, 400, 450, 500), $result['result']['fund_amounts']);
	}
	
	public function testStateObjectOnlyReact()
	{
		$app_id = 99999;
		$state_object = new VendorAPI_StateObject();
		$state_object->createPart('application');
		$state_object->application->is_react = "yes";
		$state_object->application_id = $app_id;
		$state_object->application->application_id = $app_id;
		$state_object->application->fund_qualified = 500;
		$state_object->application->loan_type_id = 1;
		$state_object->application->rule_set_id = 457;
		
		$result = $this->_client->preConfirm($app_id, serialize($state_object));
		$this->assertEquals(500, $result['result']['maximum_loan_amount']);
		$this->assertEquals(array(100, 150, 200, 250, 300, 350, 400, 450, 500), $result['result']['fund_amounts']);
	}
}