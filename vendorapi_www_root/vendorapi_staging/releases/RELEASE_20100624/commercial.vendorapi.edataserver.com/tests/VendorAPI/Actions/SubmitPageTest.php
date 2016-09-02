<?php
require_once 'qualify.2.php';
require_once 'pay_date_calc.3.php';

/**
 * This test case is to validate SubmitPage calls
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Actions_SubmitPageTest extends VendorApiBaseTest
{

	const TEST_APPID = "99999";
	/**
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * @var VendorAPI_Actions_SubmitPage
	 */
	protected $SubmitPage;

	protected $_app_factory;
	protected $_document;
	protected $_provider;
	protected $_application;
	protected $_driver;
	protected $_action;
	protected $_context;
	protected $_rule_factory;
	protected $_validator;
	protected $_app_service;

	public function setUp()
	{
		//todo: This is because someone used the wrong casing for Webservices_Client_AppClient in the IApplication interface...that should be fixed and this removed
		class_exists('WebServices_Client_AppClient', TRUE);
		/////

		//todo: This is because someone made a horrible dependency in vendor api on ecash. This should insead be looking for WebServices_Client_AppClient
		$this->_app_service = $this->getMock('ECash_WebService_AppClient', array(), array(), '', FALSE);


		$this->_app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$this->_document    = $this->getMock('VendorAPI_IDocument');
		$this->_provider    = $this->getMock('VendorAPI_ITokenProvider');
		$this->_application = $this->getMock('VendorAPI_IApplication');
		$this->_context     = $this->getMock('VendorAPI_CallContext');
		$this->_rule_factory = $this->getMock('VendorAPI_CFE_IRulesetFactory');
		$this->_cfe_context = $this->getMock('ECash_CFE_IContext');
		$this->_validator = $this->getMock('VendorAPI_IValidator');

		$this->_application->expects($this->any())
			->method('getCfeContext')
			->will($this->returnValue($this->_cfe_context));

		$this->_rule_factory->expects($this->any())
			->method('getRuleset')
			->will($this->returnValue(array()));

		$this->state = new VendorAPI_StateObject();
		$this->state->createPart('application', FALSE);
		$this->state->application_id = self::TEST_APPID;

		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_driver->expects($this->any())
			->method('getPageflowConfig')
			->will($this->returnValue(new DomDocument()));

		$this->_driver->expects($this->any())
			->method('getStatProClient')
			->will($this->returnValue($this->getMock('VendorAPI_StatProClient', array(), array(), '', FALSE)));

		$this->_app_factory->expects($this->any())
			->method('getApplication')
			->will($this->returnValue($this->_application));

 		$this->qualify = new VendorAPI_LegacyQualify($this->getMock('Qualify_2', array(), array(), '', FALSE), new Pay_Date_Calc_3());
	}

	protected function tearDown()
	{
		$this->_app_factory = null;
		$this->_document = nul;
		$this->_provider = null;
		$this->_application = null;
		$this->_driver = null;
		$this->_context = null;
		$this->_rule_factory = null;
	}

	/*
	 * Invalid Statet Exception Test
	 */
	public function testInvalidState()
	{
		$data = array();
		$submitpage = new VendorAPI_Actions_SubmitPage(
			$this->_driver,
			$this->_app_factory,
			$this->_provider,
			$this->_document,
			$this->_rule_factory,
			$this->_app_service,
			$this->_validator
		);
		$submitpage->setCallContext(new VendorAPI_CallContext());

		$this->setExpectedException('Exception');
		$submitpage->execute($data, serialize(new stdClass()));
	}

	public function testRefItems()
	{
		$data["ref_01_name_full"] 		= "First Name Full";
		$data["ref_01_phone_home"] 		= "First Phone Home";
		$data["ref_01_relationship"] 	= "First Relationship";
		$data["ref_02_name_full"] 		= "Sec Name Full";
		$data["ref_02_phone_home"] 		= "Sec Phone Home";
		$data["ref_02_relationship"] 	= "First Relationship";
		$data["ref_03_name_full"] 		= "Third Name Full";
		$data["ref_03_phone_home"] 		= "Third Phone Home";
		$data["ref_03_relationship"] 	= "First Relationship";

		$state = serialize($this->state);
		$submitpage = $this->getMock('VendorAPI_Actions_SubmitPage',
			array(
				'setQualifyItems',
				'setPayDateModel',
				'saveState'
			),
			array(
				$this->_driver,
				$this->_app_factory,
				$this->_provider,
				$this->_document,
				$this->_rule_factory,
				$this->_app_service,
				$this->_validator
			)
		);
		$submitpage->setCallContext(new VendorAPI_CallContext());
		$this->_application->expects($this->at(1))->method('addPersonalReference')
			->with(
				$this->isInstanceof('VendorAPI_CallContext'),
				$data['ref_01_name_full'],
				$data['ref_01_phone_home'],
				$data['ref_01_relationship']
			);
		$this->_application->expects($this->at(2))->method('addPersonalReference')
			->with(
				$this->isInstanceof('VendorAPI_CallContext'),
				$data['ref_02_name_full'],
				$data['ref_02_phone_home'],
				$data['ref_02_relationship']
			);
		$this->_application->expects($this->at(3))->method('addPersonalReference')
			->with(
				$this->isInstanceof('VendorAPI_CallContext'), 
				$data['ref_03_name_full'],
				$data['ref_03_phone_home'],
				$data['ref_03_relationship']
			);
			
		$result = $submitpage->execute($data, $state);
		$this->assertEquals($result->getOutcome(), 1);

	}
	
	public function testAddCampaignInfo() 
	{
		$promo_id = 99999;
		$sub_code = 'test';
		$license_key = '874e7285eef4392e874978f510d2652d';
		$site = 'test.com';
		$campaign = 'cam';
		
		$data = array(
			'campaign_info' => array(
				array(
					'promo_id' => $promo_id,
					'promo_sub_code' => $sub_code,
					'license_key' =>  $license_key,
					'name' => $site,
					'campaign_name' => $campaign
				)	
			)
		);
		$state = serialize($this->state);
		$submitpage = $this->getMock('VendorAPI_Actions_SubmitPage',
			array(
				'setQualifyItems',
				'setPayDateModel',
				'saveState'
			),
			array(
				$this->_driver,
				$this->_app_factory,
				$this->_provider,
				$this->_document,
				$this->_rule_factory,
				$this->_app_service,
				$this->_validator
			)
		);
		$submitpage->setCallContext(new VendorAPI_CallContext());
		$this->_application->expects($this->once())->method('addCampaignInfo')
			->with($this->isInstanceOf('VendorAPI_CallContext'), $license_key, $site, $promo_id, $sub_code, $campaign, NULL);
		$result = $submitpage->execute($data, $state);
		$this->assertEquals(1, $result->getOutcome());
	}

	public static function qualifyDataProvider() {
		$return = array();
		
		$data = array();
		$data['income_frequency'] = "WEEKLY";
		$data['paydate_model'] = "DW";
		$data['day_string_one'] = "TUE";
		$return[] = array($data);
		
		$data = array();
		$data['income_frequency'] = "BI_WEEKLY";
		$data['paydate_model'] = "DWPW";
		$data['day_string_one'] = "TUE";
		$data['paydate_model']['last_pay_date'] = "2009-03-03";
		$return[] = array($data);
		
		$data = array();
		$data['income_frequency'] = "FOUR_WEEKLY";
		$data['paydate_model'] = "FW";
		$data['day_string_one'] = "TUE";
		$data['last_pay_date'] = "2009-03-03";
		$return[] = array($data);
		
		$data = array();
		$data['income_frequency'] = "TWICE_MONTHLY";
		$data['paydate_model'] = "DMDM";
		$data['day_string_one'] = "TUE";
		$data['day_int_one'] = 3;
		$data['day_int_two'] = 12;
		$data['last_pay_date'] = "2009-03-03";
		$return[] = array($data);
		
		$data = array();
		$data['income_frequency'] = "TWICE_MONTHLY";
		$data['paydate_model'] = "WWDW";
		$data['day_string_one'] = "TUE";
		$data['week_one']	= 2;
		$data['week_two']	= 3;
		$return[] = array($data);
		
		$data = array();
		$data['income_frequency'] = "MONTHLY";
		$data['paydate_model'] = "DM";
		$data['last_pay_date'] = "2009-03-03";
		$data['day_int_one'] = 3;
		$return[] = array($data);
		
		$data = array();
		$data['income_frequency'] = "MONTHLY";
		$data['paydate_model'] = "WDW";
		$data['day_string_one'] = "TUE";
		$data['last_pay_date'] = "2009-03-03";
		$data['week_one']	= 2;
		$return[] = array($data);
		
		$data = array();
		$data['income_frequency'] = "MONTHLY";
		$data['paydate_model'] = "DWDM";
		$data['day_string_one'] = "TUE";
		$data['last_pay_date'] = "2009-03-03";
		$data['day_int_one'] = 3;
		$return[] = array($data);
		
		return $return;
	}
	
	/**
	 * @dataProvider qualifyDataProvider
	 */
	public function testSubmitPagePaydateInfo($qualify) 
	{
		$submitpage = $this->getMock('VendorAPI_Actions_SubmitPage',
			array('saveState', 'setQualifyItems', 'setReferences'),
			array(
				$this->_driver,
				$this->_app_factory,
				$this->_provider,
				$this->_document,
				$this->_rule_factory,
				$this->_app_service,
				$this->_validator
			)
		);
		$data = $this->toECashArray($qualify);
		$expected = array(
			'income_frequency' => $data['income_frequency'],
			'paydate_model'    => $data['paydate_model'],
			'day_of_week'      => $data['day_of_week'],
			'last_paydate'     => $data['last_paydate'],
			'day_of_month_1'   => $data['day_of_month_1'],
			'day_of_month_2'   => $data['day_of_month_2'],
			'week_1'           => $data['week_1'],
			'week_2'           => $data['week_2']
		);
		$expected = array_filter($expected);
		$this->_application->expects($this->at(1))
			->method('setApplicationData')
			->with($expected);
		$this->_application->expects($this->at(2))
			->method('setApplicationData')
			->with(array('date_first_payment' => NULL));

		$submitpage->setCallContext(new VendorAPI_CallContext());
		$state = serialize($this->state);
		$result = $submitpage->execute($data, $state);
		$this->assertEquals(1, $result->getOutcome());
	}
	
	public function testSubmitPageQualify()
	{
		$this->_application->expects($this->once())
			->method('calculateQualifyInfo')
			->will($this->returnValue(null));

		$data['qualify'] = array(
			'fund_date' => '2009-02-20',
			'payoff_date' => '2009-02-20',
			'fund_amount' => 100,
			'apr' => 3,
			'finance_charge' => 50,
			'total_payments' => 10
		);
		$data['fund_amount'] = 300;

		$state = serialize($this->state);

		$SubmitPage = $this->getMock(	'VendorAPI_Actions_SubmitPage',
			array(
				'setPayDateModel',
				'setReferences',
				'saveState'
			),
			array(
				$this->_driver,
				$this->_app_factory,
				$this->_provider,
				$this->_document,
				$this->_rule_factory,
				$this->_app_service,
				$this->_validator
			)
		);
		$SubmitPage->setCallContext(new VendorAPI_CallContext());

		$result = $SubmitPage->execute($data, $state);
		$this->assertEquals($result->getOutcome(), 1);
		$state_result = $result->getStateObject()->application;
	}

	protected  function toECashArray($data)
	{
		// maps OLP data to the eCash equivalent required by the eCash API
		// OLP => eCash
		$ecash_data_map = array(
			'first_name'				=> 'name_first',
			'last_name'					=> 'name_last',
			'middle_name'				=> 'name_middle',
			'home_street'				=> 'street',
			'home_unit'					=> 'unit',
			'home_city'					=> 'city',
			'home_state'				=> 'state',
			'home_zip'					=> 'zip_code',
			'ext_work'					=> 'phone_work_ext',
			'email_primary'				=> 'email',
			'state_id_number'			=> 'legal_id_number',
			'state_issued_id'			=> 'legal_id_state',
			'react_app_id'				=> 'react_application_id',
			'income_monthly_net'		=> 'income_monthly',
			'income_type'				=> 'income_source',
			'model_name'				=> 'paydate_model',
			'social_security_number'	=> 'ssn',
			'client_ip_address'			=> 'ip_address',
			'week_one'					=> 'week_1',
			'week_two'					=> 'week_2',
			'day_int_one'				=> 'day_of_month_1',
			'day_int_two'				=> 'day_of_month_2',
			'last_pay_date'				=> 'last_paydate',
			'track_key'					=> 'track_id',
			'work_title'				=> 'job_title',
			'date_of_hire'				=> 'date_hire',

			// @todo probably better represented as an array?
			'vehicle_vin' => 'vin',
			'vehicle_make' => 'make',
			'vehicle_year' => 'year',
			'vehicle_type' => 'type',
			'vehicle_model' => 'model',
			'vehicle_style' => 'style',
			'vehicle_series' => 'series',
			'vehicle_mileage' => 'mileage',
			'vehicle_license_plate' => 'license_plate',
			'vehicle_color' => 'color',
			'vehicle_value' => 'value',
			'vehicle_title_state' => 'title_state',
		);

		foreach ($data as $key => $value)
		{
			if (array_key_exists($key, $ecash_data_map))
			{
				$ecash_data[$ecash_data_map[$key]] = $data[$key];
			}
			else
			{
				$ecash_data[$key] = $value;
			}
		}

		$ecash_data['day_of_week'] = $ecash_data['day_string_one'];
		return $ecash_data;
	}

	public function testSuccess()
	{
		$this->setGetApplicationExpectation($this->_app_factory, self::TEST_APPID, $this->_application);
		
		$action = $this->getMock(
			'VendorAPI_Actions_SubmitPage',
			array('setQualifyItems',
				'setReferences',
				'saveState'),
			array(
				$this->_driver,
				$this->_app_factory,
				$this->_provider,
				$this->_document,
				$this->_rule_factory,
				$this->_app_service,
				$this->_validator
			)
		);
		$action->setCallContext($this->_context);
		$result = $action->execute(array(
			'application_id' => self::TEST_APPID,
		), serialize($this->state));

		$result = $result->toArray();
		$this->assertEquals(1, $result['outcome']);
	}

	public function testDocumentHashExpiration()
	{
		/* @todo fix this just like it is in the method above when model_persistor is merged in */
		$this->_app_factory->expects($this->once())->method('getApplication')
			->with(self::TEST_APPID, $this->isInstanceOf('VendorAPI_IModelPersistor'), $this->isInstanceOf('VendorAPI_StateObject'))
			->will($this->returnValue($this->_application));

		$doc_data = $this->getMock('VendorAPI_DocumentData', array('getDocumentId'));
		$doc_data->expects($this->any())->method('getDocumentId')
			->will($this->returnvalue(11));

		$this->_document->expects($this->any())->method('create')
			->will($this->returnValue($doc_data));

		$this->_document->expects($this->any())->method('signDocument')
			->will($this->returnValue(TRUE));

		$this->_application->expects($this->once())->method('expireDocumentHash')
			->with($this->equalTo($doc_data), $this->equalTo($this->_context));

		$action = $this->getMock(
			'VendorAPI_Actions_SubmitPage',
			array('setQualifyItems',
				'setReferences',
				'saveState',
				'getPageData',
				'haveDocumentsChanged'),
			array(
				$this->_driver,
				$this->_app_factory,
				$this->_provider,
				$this->_document,
				$this->_rule_factory,
				$this->_app_service,
				$this->_validator
			)
		);

		$action->expects($this->any())->method('haveDocumentsChanged')
			->will($this->returnValue(FALSE));
		
		$page_data = new ArrayObject();
		$page_data['document_templates'] = array('mytemplate');
		$action->expects($this->any())->method('getPageData')
			->will($this->returnValue($page_data));

		$action->setCallContext($this->_context);
		
		$result = $action->execute(array(
			'application_id' => self::TEST_APPID,
		), serialize($this->state));
	}
	
	public function testBlackboxConfig()
	{
		//todo: fix this >:O
		$this->markTestIncomplete('This test cannot be implemented until getBlackboxConfig is no longer responsible for setting the event_log property.');

		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_driver->expects($this->any())
			->method('getStatProClient')
			->will($this->returnValue($this->getMock('VendorAPI_StatProClient', array(), array(), '', FALSE)));

		$bb_config = new VendorAPI_Blackbox_Config();
		$flow_config = new DOMDocument();
		$file = dirname(__FILE__).'/_fixtures/submitpage_bbconfig.xml';
		if (!file_exists($file))
		{
			$this->markTestSkipped("There is no flow config fixture.");
		}
		$flow_config->load($file);
		$action = $this->getMock(
			'VendorAPI_Actions_SubmitPage',
			array('setQualifyItems',
				'setReferences',
				'saveState',
				'getPageData',
				'haveDocumentsChanged',
				'getBlackboxConfig',
			),
			array(
				$this->_driver,
				$this->_app_factory,
				$this->_provider,
				$this->_document,
				new VendorAPI_CFE_RulesetFactory(new VendorAPI_CFE_Factory()),
				$this->_app_service,
				$this->_validator
			)
		);
		$action->setCallContext($this->_context);
		$action->expects($this->once())->method('getBlackboxConfig')
			->will($this->returnValue($bb_config));
		$this->_driver->expects($this->once())
			->method('getPageflowConfig')
			->will($this->returnValue($flow_config));
		$result = $action->execute(array(
			'application_id' => self::TEST_APPID,
		), serialize($this->state));
		$this->assertEquals(1, $bb_config->test_thing);	
	}

}
?>
