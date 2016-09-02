<?php
class VendorAPI_Actions_PostTest extends PHPUnit_Framework_TestCase
{
	const COMPANY_ID = 12;
	const AGENT_ID = 61;
	const APPLICATION_ID = 9999999999;
	
	protected $_driver;
	protected $_validator;
	protected $_app_factory;
	protected $_state;
	protected $_rule_factory;
	protected $_context;
	protected $_mock_app;
	
	public function getPostAction()
	{
		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_validator = $this->getMock('VendorAPI_IValidator');
		$this->_app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$this->_rule_factory = new VendorAPI_CFE_RulesetFactory(new VendorAPI_CFE_Factory());
		
		
		$action = $this->getMock('VendorAPI_Actions_Post',
			array('saveApplication'),
			array(
				$this->_driver,
				$this->_validator,
				$this->_app_factory,
				$this->_rule_factory
			)
		);
		$domdocument = new DOMDocument();
		$domdocument->load(dirname(__FILE__).'/_fixtures/post.xml');
		

		$this->_driver->expects($this->any())->method('getPostConfig')
			->will($this->returnValue($domdocument));
		$this->_context = new VendorAPI_CallContext();
		$this->_context->setApiAgentId(self::AGENT_ID);
		$this->_context->setCompanyId(self::COMPANY_ID);
		$action->setCallContext($this->_context);
		return $action;
					
	}
	
	public function testValidatorSuccess()
	{
		$data = array(
			'application_id' => self::APPLICATION_ID,
		);
		$action = $this->getPostAction();
		$this->_state = new VendorAPI_StateObject();
		$this->createStateObjectExpectation($this->_state, $this->once());
		$this->createStubsForCreateApplication($this->once(), $data);
		$this->createQualifyMock($this->any(), $this->getValidQualifyInfo());
		$this->_validator->expects($this->once())->method('validate')->with($data)->will($this->returnValue(TRUE));
		$this->createBlackboxStubs();
		$response = $action->execute($data)->toArray();
		$this->assertTrue((bool)$response['outcome']);
	}
	

	
	public function testValidatorFailHasErrorInResponse()
	{
		$error = new stdClass();
		$error->field = 'test_field';
		$error->message = 'Test message';
		$errors = array($error);
		$this->_state = new VendorAPI_StateObject();
		
		$expected= array(array('field' => $error->field, 'message' => $error->message));
		
		$data = array(
			'application_id' => self::APPLICATION_ID
		);
		$action = $this->getPostAction();
		$this->_validator->expects($this->once())->method('validate')->with($data)->will($this->returnValue(FALSE));
		$this->_validator->expects($this->once())->method('getErrors')->will($this->returnValue($errors));
		$this->createStateObjectExpectation($this->_state, $this->once());
		$this->createBlackboxStubs();
		$response = $action->execute($data)->toArray();
		$this->assertFalse((bool)$response['outcome']);
		$this->assertEquals($expected, $response['error']);
	}
	
	public function testWhenPassedSerializedStateItGetsUsed()
	{
		$data = array(
			'application_id' => self::APPLICATION_ID,
		);
		$action = $this->getPostAction();
		$this->_state = new VendorAPI_StateObject();
		$this->_state->wut = "test";
		$this->_app_factory->expects($this->never())->method('createStateObject');
		
		$this->createStubsForCreateApplication($this->once(), $data);
		$this->createQualifyMock($this->exactly(3), $this->getValidQualifyInfo());
		$this->_validator->expects($this->once())->method('validate')->with($data)->will($this->returnValue(TRUE));
		$this->createBlackboxStubs();
		$response = $action->execute($data, serialize($this->_state))->toArray();
		$state = unserialize($response['state_object']);
		$this->assertEquals($this->_state->wut, $state->wut);
	}
	
	public function testTrackKeyIsSet()
	{
		$data = array(
			'application_id' => self::APPLICATION_ID,
			'track_id' => "hello"
		);
		$action = $this->getPostAction();
		$this->_state = new VendorAPI_StateObject();
		$this->_app_factory->expects($this->never())->method('createStateObject');
		
		$this->createStubsForCreateApplication($this->once(), $data);
		$this->createQualifyMock($this->any(), $this->getValidQualifyInfo());
		$this->_validator->expects($this->once())->method('validate')->with($data)->will($this->returnValue(TRUE));
		$this->createBlackboxStubs();
		$response = $action->execute($data, serialize($this->_state))->toArray();
		$state = unserialize($response['state_object']);
		$this->assertEquals($data['track_id'], $state->track_key);
	}
	
	public function testDeclinesAppWhenQualifyFails()
	{
		$this->_state = new VendorAPI_StateObject();
		$data = $this->getPostData();
		$action = $this->getPostAction();
		$this->createStateObjectExpectation($this->_state, $this->once());
		$this->_validator->expects($this->once())->method('validate')->with($data)->will($this->returnValue(TRUE));
		
		$domdocument = new DOMDocument();
		$domdocument->load(dirname(__FILE__).'/_fixtures/post.xml');
		

		$this->_driver->expects($this->once())->method('getPostConfig')
			->will($this->returnValue($domdocument));
			-
		$this->createStubsForCreateApplication($this->once(), $data);
		$this->createQualifyMock($this->any(), $this->getInValidQualifyInfo());
		$response = $action->execute($data)->toArray();
		$this->assertTrue(isset($response['result']['qualified']));
		$this->assertFalse((bool)$response['result']['qualified']);
		
	}
	
	public function testSuccessOnQualifyPass()
	{
		$this->_state = new VendorAPI_StateObject();
		$data = $this->getPostData();
		$action = $this->getPostAction();
		$this->createStateObjectExpectation($this->_state, $this->once());
		$this->_validator->expects($this->once())->method('validate')->with($data)->will($this->returnValue(TRUE));
		
		$domdocument = new DOMDocument();
		$domdocument->load(dirname(__FILE__).'/_fixtures/post.xml');
		

		$this->_driver->expects($this->once())->method('getPostConfig')
			->will($this->returnValue($domdocument));
			-
		$this->createStubsForCreateApplication($this->once(), $data);
		$this->createQualifyMock($this->any(), $this->getValidQualifyInfo());
		$this->createBlackboxStubs();
		$response = $action->execute($data)->toArray();
		$this->assertTrue(isset($response['result']['qualified']));
		$this->assertTrue((bool)$response['result']['qualified']);
		
	}
		
	public function getPostData()
	{
		return array(
			'application_id' => self::APPLICATION_ID,
			'pay_dates' => array(
				'2009-06-29',
				'2009-07-06',
				'2009-07-13',
				'2009-07-20',
				'2009-07-27'
			),
			'income_source' => 'EMPLOYMENT',
			'income_frequency' => 'WEEKLY',
			'paydate_model' => 'DW',
			'next_pay_date' => '2009-06-29',
			'last_paydate' => '2009-06-01',
			'day_of_month_1' => NULL,
			'day_of_month_2' => NULL,
			'week_1' => NULL,
			'week_2' => NULL,
			'day_string_one' => 'MON',
			'day_of_week' => 'MON',
			'page_id' => 68383,
			'campaign' => 'opm_bsc',
			'is_react' => FALSE,
			'olp_process' => 'online_confirmation',
			'loan_type' => 'standard'
		);
	}
	
	protected function createBlackboxStubs()
	{
		$this->_driver->expects($this->any())->method('getBlackboxFactory')
			->will($this->returnValue(new TestBlackBoxFactory()));
	}
	
	protected function createStubsForCreateApplication($at, $data)
	{
		$this->_mock_app = $this->getMock('VendorAPI_IApplication');
		$this->_mock_app->expects($this->once())->method('getCfeContext')
			->will($this->returnValue(new VendorAPI_CFE_ApplicationContext($this->_mock_app, $this->_context)));
		$this->_app_factory->expects($at)->method('createApplication')
			->with(
				$this->isInstanceOf('VendorAPI_IModelPersistor'), 
				$this->isInstanceOf('VendorAPI_StateObject'),
				$this->isInstanceOf('VendorAPI_CallContext'),
				$data
			)
			->will($this->returnValue($this->_mock_app));
		$this->_mock_app->expects($this->any())->method('getData')
			->will($this->returnValue($data));
	}
	
	protected function createQualifyMock($at, VendorAPI_QualifyInfo $info)
	{
		$this->_mock_app->expects($at)->method('calculateQualifyInfo')
			->will($this->returnValue($info));
	}
	
	protected function getValidQualifyInfo()
	{
		return new VendorAPI_QualifyInfo(300, 300, 856, '2009-06-02', date('2009-06-29'), '90', '390');
	}
	
	protected function getInValidQualifyInfo()
	{
		return new VendorAPI_QualifyInfo(0, 0, 0, 0, 0, 0, 0);
	}
	
	public function createStateObjectExpectation($state, $at)
	{
		$this->_app_factory->expects($at)->method('createStateObject')
			->with(self::APPLICATION_ID, $this->isInstanceOf('VendorAPI_CallContext'))
			->will($this->returnValue($state));
	}
}

class TestBlackBoxFactory 
{
	public function getBlackbox(
		$datax_rework = FALSE,
		Blackbox_IStateData $state_data = NULL)
		{
			return new FakeBlackBox(new ValidBlackboxWinner(new VendorAPI_Blackbox_Target(), new ECash_CustomerHistory()));
		}
}

class FakeBlackBox
{
	public function __construct($winner)
	{
		$this->winner = $winner;
	}
	
	public function pickWinner(Blackbox_Data $data)
	{
		return $this->winner;
	}
}

class ValidBlackboxWinner extends VendorAPI_Blackbox_Winner
{
	
}
