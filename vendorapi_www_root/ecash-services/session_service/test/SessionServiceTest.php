<?php

class SessionServiceTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var SessionService
	 */
	protected $service;
	
	protected $session_id_1 = '001e42f3ffcae46d7ae67951cee60ea2';
	
	public function setUp()
	{
		// $wsdl = bootstrap_config()->wsdl_location ? bootstrap_config()->wsdl_location : DEFAULT_WSDL_LOCATION;
		$this->service = new SessionService(new Session(bootstrap_config()->pdo));	// new SoapClient($wsdl);
		parent::setUp();
	}
	
	public function testChangeKeyThing()
	{
		$service = new SessionService(new Session(bootstrap_config()->pdo));
		
		$data = array(
			'config' => array(
				'promo_id' => 12323,
				'promo_status' => array(
					'valid' => 'valid',
				),
				'other_child' => array(
					'invalid' => 'invalid',
					'reject_reason' => 'too many mustaches',
				),
				'cost_action' => 'confirmed',
			),
			'data' => array('ssn_part_1' => '881',),
			'unique_stat' => array(),
		);
		$path = array('config', '*');
		$service->findAndChangeKey($path, $data);
		
		$this->assertTrue(is_array($data));
		$this->assertArrayHasKey('config', $data);
		$this->assertTrue(is_array($data['config']));
		$this->assertTrue($data['config']['promo_status'] instanceof stdClass);
	}
	
	public function testServiceLifecycle()
	{
		$new_session_data = array('data' => array('name_first' => 'Tom'));
		$sid = md5(time() . rand(0, getrandmax()));
		
		$create_response = $this->service->createSessionAndReadAsJson($sid, 60);
		$session_data = json_decode($create_response->session, TRUE);
		$this->assertEquals($sid, $create_response->session_id);
		$this->assertTrue(is_array($session_data));
		$this->assertEquals(0, count($session_data));
		
		$this->service->jsonSaveAndRelease(
			$create_response->session_id, 
			$create_response->session_lock_key, 
			json_encode($new_session_data)
		);
		
		$read_response = $this->service->acquireAndReadAsJson($create_response->session_id, 0, 30);
		$decoded_read = json_decode($read_response->session, TRUE);
		$this->assertTrue(
			is_array($decoded_read), 
			'Decoded read for ' . $create_response->session_id 
			. ' data was not array, was ' . var_export($decoded_read, TRUE)
		);
		$this->assertArrayHasKey('data', $decoded_read);
		$this->assertArrayHasKey('name_first', $decoded_read['data']);
		
		// should cause fault since last acquire call locked for 30 seconds.
		$this->setExpectedException('SenderException');
		$this->service->acquireAndReadAsJson($create_response->session_id, 0, 30);
	}
	
	public function testCantCreateSessionThatExists()
	{
		$this->setExpectedException('Exception');
		$this->service->createSessionAndReadAsJson($this->session_id_1, 5);
	}
	
	public function testReadSessionData()
	{
		$response = $this->service->acquireAndReadAsJson($this->session_id_1, 1, 1);
		
		$this->assertEquals(
			json_encode(array('fruit' => 'mango')),
			$response->session,
			'Session information was not read properly.'
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection()
	{
		return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection(
			bootstrap_config()->pdo, bootstrap_config()->schema
		);
	}
	
	/**
	 * 
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/_fixtures/SessionTest.xml');
	}
}

?>