<?php

class VendorAPI_Actions_GetTokensTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_StateObject
	 */
	protected $_good_state;

	/**
	 * @var VendorAPI_IDriver
	 */
	protected $_driver;

	/**
	 * @var VendorAPI_Actions_GetTokens
	 */
	protected $_action;

	/**
	 * @var VendorAPI_IQualify
	 */
	protected $_qualify;

	protected $_appfactory;
	protected $_app;

	public function setUp()
	{
		$this->_good_state = new VendorAPI_StateObject();
		$this->_good_state->createPart('application', FALSE);
		$this->_good_state->application->application_id = 1;

		$model = $this->getMock('stdClass', array('loadByKey', 'getColumnData'));
		$model->expects($this->any())
			->method('loadByKey')
			->will($this->returnValue(TRUE));
		$model->expects($this->any())
			->method('getColumnData')
			->will($this->returnValue(array('application_id' => 1)));

		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_driver->expects($this->any())
			->method('getDataModelByTable')
			->will($this->returnValue($model));

		$this->_qualify = $this->getMock('VendorAPI_IQualify');
		$this->_appfactory = $this->getMock('VendorAPI_IApplicationFactory');
		$this->_app = $this->getMock('VendorAPI_IApplication');
		$this->_appfactory->expects($this->once())->method('getApplication')->will($this->returnValue($this->_app));
		$mock_provider = $this->getMock('VendorAPI_ITokenProvider', array('getTokens'));
		$mock_provider->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		//$this->_action = new VendorAPI_Actions_GetTokens($this->_driver, $mock_provider, $this->_qualify);
		$this->_action = $this->getMock('VendorAPI_Actions_GetTokens', array('saveState'), array($this->_driver, $mock_provider, $this->_appfactory));
		$call_context = $this->getMock('VendorAPI_CallContext');
		$this->_action->setCallContext($call_context);
	}

	public function tearDown()
	{
		$this->_good_state = NULL;
		$this->_driver = NULL;
		$this->_action = NULL;
	}

	public function testResponseOutcomeIsSuccess()
	{
		$response = $this->_action->execute(0, FALSE, array(), serialize($this->_good_state));
		$this->assertEquals(VendorAPI_Response::SUCCESS, $response->getOutcome());
	}

	public function testResponseContainsTokensArray()
	{
		$response = $this->_action->execute(0, FALSE, array(), serialize($this->_good_state));
		$this->assertArrayHasKey('tokens', $response->getResult());
	}
}

?>
