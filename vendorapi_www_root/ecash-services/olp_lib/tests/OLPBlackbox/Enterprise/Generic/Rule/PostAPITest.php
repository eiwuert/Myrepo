<?php
/**
 * Test case for the Post eCash Vendor API call rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_PostAPITest extends PHPUnit_Framework_TestCase
{
	/**
	 * Blackbox data
	 *
	 * @var OLPBlackbox_Data
	 */
	protected $_data;

	/**
	 * Blackbox state data
	 *
	 * @var OLPBlackbox_TargetStateData
	 */
	protected $_state;

	/**
	 * Blackbox config
	 *
	 * @var OLPBlackbox_Config
	 */
	protected $_config;

	/**
	 * OLPECash VendorAPI object
	 *
	 * @var OLPECash_VendorAPI
	 */
	protected $_api;

	/**
	 * ApplicationValue model.
	 *
	 * @var DB_Models_Decorator_ReferencedWritableModel_1
	 */
	protected $_app_value_model;

	/**
	 * App campaign manager
	 *
	 * @var App_Campaign_Manager
	 */
	protected $_app_mgr;

	/**
	 * OLPBlackbox DebugConf object
	 *
	 * @var OLPBlackbox_DebugConf
	 */
	protected $_debug;

	/**
	 * @var OLPBlackbox_Enterprise_Generic_Rule_PostAPI
	 */
	protected $_rule;

	protected $_deferred;

	/**
	 * Sets up mock objects for required classes in the constructor of the rule.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->markTestSkipped();
		$this->_data = new OLPBlackbox_Data();

		$this->_deferred = $this->getMock('OLPBlackbox_DeferredQueue');
		$root_state = new OLPBlackbox_StateData(array(
			'deferred' => $this->_deferred,
		));

		$this->_state = new OLPBlackbox_TargetStateData(array('name' => 'test'));
		$this->_state->addStateData($root_state);

		$this->_debug = new OLPBlackbox_DebugConf();
		$this->_config = $this->getMock('OLPBlackbox_Config');
		$this->_app_value_model = $this->getMock('DB_Models_Decorator_ReferencedWritableModel_1', array(), array(), '', FALSE);
		$this->_app_mgr = $this->getMock('App_Campaign_Manager', array('Get_Campaign_Info', 'Get_Olp_Process'), array(), '', FALSE);

		$ci = array(
			0 => array(
				'campaign_info_id' => 1,
				'application_id' => 100,
				'modified_date' => date('Y-m-d H:i:s'),
				'promo_id' => '99999',
				'promo_sub_code' => 'test',
				'license_key' => 'askdjflskndlkfahsldfhlsdf',
				'created_date' => date('Y-m-d H:i:s'),
				'active' => 'TRUE',
				'ip_address' => '192.168.1.1',
				'url' => 'http://getcashnow.com',
				'offers' => 'FALSE',
				'tel_app_proc' => 'FALSE',
				'reservation_id' => '1231212312',
			),
		);

		$this->_app_mgr->expects($this->any())
			->method('Get_Campaign_Info')
			->will($this->returnValue($ci));

		$this->_api = new TestAPI();

		$this->_rule = new TestPostAPIRule(
			$this->_config, $this->_debug, $this->_api, $this->_app_value_model, $this->_app_mgr, FALSE
		);
	}

	public function tearDown()
	{
		$this->_data = null;
		$this->_state = null;
		$this->_debug = null;
		$this->_config = null;
		$this->_api = null;
		$this->_loan_type = null;
		$this->_app_mgr = null;
		$this->_rule = null;
	}

	/**
	 * Tests if the result is a pass from the API that the rule returns TRUE.
	 *
	 * @return void
	 */
	public function testRunRulePass()
	{
		$response = array(
			'outcome' => TRUE,
			'result' => array(
				'qualified' => TRUE
			)
		);

		$this->_api->setResponse($response);

		$post_rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_PostAPI',
			array('buildECashData'),
			array($this->_config, $this->_debug, $this->_api, $this->_app_value_model, $this->_app_mgr, FALSE)
		);

		$this->assertTrue($post_rule->isValid($this->_data, $this->_state));
	}

	/**
	 * Tests if the result is a pass from the API the rule clears deferred actions.
	 *
	 * @return void
	 */
	public function testRuleClearsDeferredActionOnPass()
	{
		$response = array(
			'outcome' => TRUE,
			'result' => array(
				'qualified' => TRUE
			)
		);

		$this->_api->setResponse($response);

		$post_rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_PostAPI',
			array('buildECashData'),
			array($this->_config, $this->_debug, $this->_api, $this->_app_value_model, $this->_app_mgr, FALSE)
		);

		$this->_deferred->expects($this->once())
			->method('remove')
			->with('test');

		$post_rule->isValid($this->_data, $this->_state);
	}

	/**
	 * Tests if the result is a fail from the API that the rule returns FALSE.
	 *
	 * @return void
	 */
	public function testRunRuleFail()
	{
		$response = array(
			'outcome' => TRUE,
			'result' => array(
				'qualified' => FALSE
			)
		);

		$this->_api->setResponse($response);

		$post_rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_PostAPI',
			array('buildECashData'),
			array($this->_config, $this->_debug, $this->_api, $this->_app_value_model, $this->_app_mgr, FALSE)
		);

		$this->assertFalse($post_rule->isValid($this->_data, $this->_state));
	}

	/**
	 * Tests that if we determine it's a react, that the state data has the react app ID and that
	 * it is a react.
	 *
	 * @return void
	 */
	public function testRunRuleReact()
	{
		$response = array(
			'outcome' => TRUE,
			'result' => array(
				'qualified' => TRUE,
				'is_react' => TRUE,
				'react_application_id' => 123456
			)
		);

		$this->_api->setResponse($response);

		$post_rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_PostAPI',
			array('buildECashData'),
			array($this->_config, $this->_debug, $this->_api, $this->_app_value_model, $this->_app_mgr, FALSE)
		);

		$post_rule->isValid($this->_data, $this->_state);

		$this->assertTrue($this->_state->is_react);
		$this->assertEquals(123456, $this->_state->react_app_id);
	}

	/**
	 * Checks that we save the loan type if we get it back from the API.
	 *
	 * @return void
	 */
	public function testRunRuleSaveLoanType()
	{
		$app_id = 12345;

		$response = array(
			'outcome' => TRUE,
			'result' => array(
				'qualified' => TRUE,
				'loan_type_short' => 'standard'
			)
		);

		$this->_data->application_id = $app_id;

		$this->_api->setResponse($response);

		$this->_app_value_model->expects($this->once())
			->method('loadBy')
			->with($this->equalTo(array(
				'application_id' => $app_id,
				'name' => 'loan_type_short'
			)))
			->will($this->returnValue(FALSE));

		$this->_app_value_model->expects($this->once())->method('save');

		$post_rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_PostAPI',
			array('buildECashData'),
			array($this->_config, $this->_debug, $this->_api, $this->_app_value_model, $this->_app_mgr, FALSE)
		);

		$post_rule->isValid($this->_data, $this->_state);
	}

	/**
	 * Tests that when the API fails from a rule, it hits an event.
	 *
	 * @return void
	 */
	public function testRunRuleFailReason()
	{
		$response = array(
			'outcome' => TRUE,
			'result' => array(
				'qualified' => FALSE,
				'fail' => array(
					'short' => 'DATAX'
				)
			)
		);

		$this->_api->setResponse($response);

		$post_rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_PostAPI',
			array('buildECashData', 'hitEvent'),
			array($this->_config, $this->_debug, $this->_api, $this->_app_value_model, $this->_app_mgr, FALSE)
		);

		$post_rule->expects($this->once())
			->method('hitEvent')
			->with($this->equalTo('ECASH_API_DATAX'));

		$post_rule->isValid($this->_data, $this->_state);
	}

	public function testUnsetDebugFlagIsNullInRequest()
	{
		$this->_rule->isValid($this->_data, $this->_state);
		$request = $this->_api->getArguments();

		$debug = $request[0]['debug'];
		$this->assertNull($debug['DATAX']);
	}

	public function testTrueDebugFlagIsTrueInRequest()
	{
		$this->_debug->setFlag(OLPBlackbox_DebugConf::DATAX_PERF, TRUE);

		$this->_rule->isValid($this->_data, $this->_state);
		$request = $this->_api->getArguments();

		$debug = $request[0]['debug'];
		$this->assertTrue($debug['DATAX']);
	}

	public function testCampaignInfoIsPassedInRequest()
	{
		$this->_rule->isValid($this->_data, $this->_state);
		$request = $this->_api->getArguments();

		$this->assertArrayHasKey('campaign_info', $request[0]);
	}

	public function testReservationIDIsPassedInCampaignInfo()
	{
		$this->_rule->isValid($this->_data, $this->_state);
		$request = $this->_api->getArguments();

		$ci = $request[0]['campaign_info'][0];
		$this->assertEquals('1231212312', $ci['reservation_id']);
	}

	public function testVehicleInformationIsPassedInRequest()
	{
		$this->_rule->isValid($this->_data, $this->_state);
		$request = $this->_api->getArguments();

		$constraint = $this->logicalAnd(
			$this->arrayHasKey('vin'),
			$this->arrayHasKey('make'),
			$this->arrayHasKey('model'),
			$this->arrayHasKey('year'),
			$this->arrayHasKey('series'),
			$this->arrayHasKey('type'),
			$this->arrayHasKey('mileage')
		);

		$this->assertThat($request[0], $constraint);
	}
}

class TestAPI extends OLPECash_VendorAPI
{
	protected $response;
	protected $call;
	protected $args;

	public function __construct()
	{
	}

	public function setResponse($response)
	{
		$this->response = $response;
	}

	public function __call($method_name, array $method_arguments)
	{
		$this->call = $method_name;
		$this->args = $method_arguments;

		$r = $this->response;
		$this->response = null;
		return $r;
	}

	public function getCall()
	{
		return $this->call;
	}

	public function getArguments()
	{
		return $this->args;
	}
}

/**
 * Test class to perform mock type functionality without mocking the class
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class TestPostAPIRule extends OLPBlackbox_Enterprise_Generic_Rule_PostAPI
{
	/**
	 * Get a populated customer history object
	 *
	 * @param OLPBlackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	protected function getCustomerHistory(OLPBlackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$customer_history = new OLPBlackbox_Enterprise_CustomerHistory();
		$customer_history->addLoan(
			'COM',
			OLPBlackbox_Enterprise_CustomerHistory::STATUS_PAID,
			111111111,
			strtotime('-30 days')
		);
		return $customer_history;
	}
}
