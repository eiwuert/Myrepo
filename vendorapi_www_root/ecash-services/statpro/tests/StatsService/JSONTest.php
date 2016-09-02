<?
/**
 * Unit tests for the JSON service 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class StatsService_JSONTest extends PHPUnit_Framework_TestCase {
	private $message_processor;
	private $service;
	private $request;
	private $log;

	/**
	 * Set up the mocks
	 */
	public function setUp() {
		$this->message_processor = $this->getMock(
			"StatsService_MessageProcessor_IMessageProcessor");
		$this->log = $this->getMock("Log_ILog_1");
		$this->service = new StatsService_JSON(
			$this->message_processor,
			$this->log);
		$this->request = $this->getMock('Site_Request', array(), array(array(), '', FALSE));
	}

	/**
	 * Remove the mocks
	 */
	public function tearDown() {
		$this->message_processor = NULL;
		$this->service = NULL;
		$this->request = NULL;
		$this->log = NULL;
	}

	/**
	 * Verify that a 500 error occurs and logs when there is no POST
	 */
	public function testReturns500WhenNotPost() {
		$this->request->expects($this->once())
			->method('getMethod')
			->will($this->returnValue(Site_Request::METHOD_GET));
		$this->message_processor->expects($this->never())
			->method('processMessage');
		$this->log->expects($this->once())
			->method('write');

		$response = $this->service->processRequest($this->request);
		$this->assertTrue($response instanceof Site_Response_Http);
		$this->assertEquals(500, $response->getStatusCode());
	}
	
	/**
	 * Verify that a 500 error occurs and logs when the POST
	 * is empty
	 */
	public function testReturns500WhenNoMessage() {
		$this->request->expects($this->any())
			->method('getMethod')
			->will($this->returnValue(Site_Request::METHOD_POST));
		$this->message_processor->expects($this->never())
			->method('isValidMessage');
		$this->message_processor->expects($this->never())
			->method('processMessage');
		$this->log->expects($this->once())
			->method('write');
			
		$response = $this->service->processRequest($this->request);
		$this->assertTrue($response instanceof Site_Response_Http);
		$this->assertEquals(500, $response->getStatusCode());
	}
	
	/**
	 * Verify that a 500 error occurs and logs when the POST
	 * contains an invalid message
	 */
	public function testReturns500WhenInvalidMessage() {
		$expected_message = new stdClass();
		$post_data = json_encode($expected_message);

		$this->request->expects($this->once())
			->method('getMethod')
			->will($this->returnValue(Site_Request::METHOD_POST));
		$this->request->expects($this->once())
			->method('getPostData')
			->will($this->returnValue($post_data));
		$this->message_processor->expects($this->once())
			->method('isValidMessage')
			->will($this->returnValue(false));
		$this->message_processor->expects($this->never())
			->method('processMessage');
		$this->log->expects($this->once())
			->method('write');
		$response = $this->service->processRequest($this->request);
		$this->assertTrue($response instanceof Site_Response_Http);
		$this->assertEquals(500, $response->getStatusCode());
	}
		
	/**
	 * Verify that a 500 error occurs and logs when the an exception
	 * is caught while processsing the message
	 */
	public function testReturns500OnException() {
		$expected_message = new stdClass();
		$post_data = json_encode($expected_message);

		$this->request->expects($this->once())
			->method('getMethod')
			->will($this->returnValue(Site_Request::METHOD_POST));
		$this->request->expects($this->once())
			->method('getPostData')
			->will($this->returnValue($post_data));
		$this->message_processor->expects($this->once())
			->method('isValidMessage')
			->will($this->returnValue(true));
		$this->message_processor->expects($this->once())
			->method('processMessage')
			->will($this->throwException(new Exception("")));
		$this->log->expects($this->once())
			->method('write');
		$response = $this->service->processRequest($this->request);
		$this->assertTrue($response instanceof Site_Response_Http);
		$this->assertEquals(500, $response->getStatusCode());
	}

	/**
	 * Verify  the correct information is passed to the message processor
	 * from the service bsaed on the request 
	 */
	public function testSendsJSONMessageToProcessor() {
		$expected_message = new stdClass();
		$expected_message->key1 = "value1";
		$expected_message->key2 = 2;
		
		$post_data = json_encode($expected_message);
		$this->request->expects($this->once())
			->method('getMethod')
			->will($this->returnValue(Site_Request::METHOD_POST));
		$this->request->expects($this->once())
			->method('getPostData')
			->will($this->returnValue($post_data));
		$this->message_processor->expects($this->once())
			->method('isValidMessage')
			->will($this->returnValue(true));
		$this->message_processor->expects($this->once())
			->method('processMessage')
			->with($expected_message);
		$this->log->expects($this->never())
			->method('write');
		$response = $this->service->processRequest($this->request);
		$this->assertTrue($response instanceof Site_Response_Http);
		$this->assertEquals(200, $response->getStatusCode());
	}
}
