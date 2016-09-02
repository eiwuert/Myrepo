<?php

/**
 * Tests the OLP_ECashClient_Authentication class
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLP_ECashClient_AuthenticationTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Mode to pass into stuff
	 * 
	 * @var string
	 */
	protected $mode = 'unittest';
	
	/**
	 * 
	 * @var Mock_OLP_ECashClient_AuthenticationAPI
	 */
	protected $api;
	
	/**
	 * Returns a mock ecash authentication client
	 * 
	 * @param bool $validate Value validate() returns
	 * @param bool $locked Value isLocked() returns
	 * @param bool $authenticated Value authenticated() returns
	 * @param array $additional_functions Any additional functions to mock
	 * @return OLP_ECashClient_Authentication
	 */
	protected function getMockClient(
		$validate = OLP_ECashClient_Authentication::STATUS_PASS,
		$locked = FALSE,
		$additional_functions = array())
	{
		$client = $this->getMock(
			'OLP_ECashClient_Authentication',
			array_merge(array('getAPI'), $additional_functions),
			array(
				$this->mode,
				'ufc'
			)
		);
		
		$client->expects($this->any())
			->method('getAPI')
			->will($this->returnValue($this->getMockAPI($validate, $locked)));
			
		return $client;
	}
	
	/**
	 * Gets a mock version of eCash's API (not the client)
	 * 
	 * @param bool $validate
	 * @param bool $locked
	 * @return Mock_OLP_ECashClient_AuthenticationAPI
	 */
	protected function getMockAPI($validate = OLP_ECashClient_Authentication::STATUS_PASS, $locked = FALSE)
	{
		$this->api = $this->getMock(
			'Mock_OLP_ECashClient_AuthenticationAPI',
			array('validate', 'isLocked')
		);
		
		$this->api->expects($this->any())
			->method('validate')
			->will($this->returnValue($validate));
		$this->api->expects($this->any())
			->method('isLocked')
			->will($this->returnValue($locked));
		
		return $this->api;
	}
	
	
	/**
	 * Provider for validate
	 * 
	 * @return array
	 */
	public function dataProviderTestValidate()
	{
		return array(
			array(OLP_ECashClient_Authentication::STATUS_PASS, TRUE, FALSE),
			array(OLP_ECashClient_Authentication::STATUS_FAIL, FALSE, FALSE),
			array(OLP_ECashClient_Authentication::STATUS_LAST_ATTEMPT, FALSE, FALSE),
			array(OLP_ECashClient_Authentication::STATUS_LOCKED, FALSE, FALSE),
			array('invalid_return', FALSE, TRUE)
		);
	}
	
	/**
	 * Test validate returns correctly
	 * 
	 * @dataProvider dataProviderTestValidate
	 * @param string $return
	 * @param bool $expected_result
	 * @param bool $call_failed
	 * @return NULL
	 */
	public function testValidate($return, $expected_result, $call_failed)
	{
		$client = $this->getMockClient($return);
		
		$this->assertEquals($expected_result, $client->validate(1, '5551234567', '12/31/1969'));
		$this->assertEquals($call_failed, $client->callFailed());
	}
	
	
	/**
	 * Data provider for testIsLocked
	 * 
	 * @return array
	 */
	public function dataProviderLocked()
	{
		return array(
			array(FALSE),
			array(TRUE),
		);
	}
	
	/**
	 * Tests isLockedOut() function
	 * 
	 * @dataProvider dataProviderLocked
	 * @param bool $locked
	 * @return NULL
	 */
	public function testIsLocked($locked)
	{
		$client = $this->getMockClient('', $locked);
		$this->assertEquals($locked, $client->isLocked(1));
	}
	
	/**
	 * Tests to ensure locked out returns TRUE when the result
	 * is returned from the validate() call
	 * 
	 * @return NULL
	 */
	public function testLockedResult()
	{
		$client = $this->getMockClient(OLP_ECashClient_Authentication::STATUS_LOCKED);
		
		$this->assertFalse($client->validate(1, '5555555555', '12/31/1969'));
		$this->assertTrue($client->isLocked(1));
	}
	
	/**
	 * Ensure parameters passed in are properly passed through
	 * to the API's function calls
	 * 
	 * @return NULL
	 */
	public function testValuePassthrough()
	{
		$data = array(
			'app_id' => 1,
			'work_phone' => '5555555555',
			'dob' => '12/31/1969',
			'page' => 'react'
		);
		
		
		$client = $this->getMockClient();
		$this->api->expects($this->once())
			->method('validate')
			->with($data['app_id'], $data['work_phone'], $data['dob'], $data['page'])
			->will($this->returnValue('pass'));
		$this->api->expects($this->once())
			->method('isLocked')
			->with($data['app_id'], $data['page'])
			->will($this->returnValue(FALSE));

		$client->isLocked($data['app_id'], $data['page']);
		$client->validate($data['app_id'], $data['work_phone'], $data['dob'], $data['page']);
	}
}