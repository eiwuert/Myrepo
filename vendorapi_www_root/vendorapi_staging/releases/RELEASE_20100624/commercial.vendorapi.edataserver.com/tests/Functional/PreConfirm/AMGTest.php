<?php 
class Functional_PreConfirm_AMGTest extends PHPUnit_Framework_TestCase
{
	protected $_client;

	public function setup()
	{
		$this->_client = getTestClient('clk', 'pcl', 'vendor_api', 'vendor_api');
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
		$state_object->application->fund_qualified = 300;
		$state_object->application->loan_type_id=1;
		$state_object->application->rule_set_id=277;		
		$result = $this->_client->preConfirm($app_id, serialize($state_object));
		$this->assertEquals(300, $result['result']['maximum_loan_amount']);
		$this->assertEquals(array(150, 200, 250, 300), $result['result']['fund_amounts']);
	}
	
	public function testStateObjectOnlyReact()
	{
		$app_id = 99999;
		$state_object = new VendorAPI_StateObject();
		$state_object->createPart('application');
		$state_object->application->is_react = "yes";
		$state_object->application_id = $app_id;
		$state_object->application->application_id = $app_id;
		$state_object->application->fund_qualified = 300;
		$state_object->application->loan_type_id=1;
		$state_object->application->rule_set_id=277;
		
		$result = $this->_client->preConfirm($app_id, serialize($state_object));
		$this->assertEquals(300, $result['result']['maximum_loan_amount']);
		$this->assertEquals(array(150, 200, 250, 300), $result['result']['fund_amounts']);
	}
}