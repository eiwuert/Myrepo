<?php

/**
 * Tests the OLP_ECashClient_Encryption class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_ECashClient_EncryptionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testGetApplicationData().
	 *
	 * @return array
	 */
	public static function dataProviderApplicationId()
	{
		return array(
			array(
				'PCL',
				'TEST',
				1000,
				FALSE,
				FALSE,
				'Empty test.',
			),
			
			array(
				'PCL',
				'TEST',
				1001,
				array(),
				array(),
				'Empty array test.',
			),
			
			array(
				'UFC',
				'TEST',
				1002,
				array(
					'application_id' => 1002,
					'ssn' => '012345678',
				),
				array(
					'application_id' => 1002,
					'ssn' => '012345678',
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				'SSN mapping test.',
			),
		);
	}
	
	/**
	 * Tests getApplicationData().
	 *
	 * @dataProvider dataProviderApplicationId
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @param int $application_id
	 * @param array $returned_data
	 * @param array $expected_data
	 * @param string $message
	 * @return void
	 */
	public function testGetApplicationData($property_short, $mode, $application_id, $returned_data, $expected_data, $message)
	{
		$api = $this->getMock(
			'RPC_Client_1',
			array(
				'getDataByApplicationId',
				'getDataByTrackKey',
			),
			array(),
			'',
			FALSE
		);
		$api->expects($this->once())
			->method('getDataByApplicationId')
			->with($application_id)
			->will($this->returnValue($returned_data));
		
		$olpapi_2 = $this->getMock(
			'OLP_ECashClient_Encryption',
			array(
				'getAPI',
			),
			array(
				$property_short,
				$mode,
			),
			'',
			TRUE
		);
		$olpapi_2->expects($this->once())
			->method('getAPI')
			->will($this->returnValue($api));
		
		$data = $olpapi_2->getApplicationData($application_id);
		
		$this->assertEquals($expected_data, $data, $message);
	}
	
	/**
	 * Data provider for testGetApplicationDataByTrackKey().
	 *
	 * @return array
	 */
	public static function dataProviderApplicationTrackKey()
	{
		return array(
			array(
				'D1',
				'TEST',
				array(
					'3206ddf8f1d69e0d9d57274f9bd',
					'4b122dbcf6da060e7a6398950d5',
				),
				array(
					'4b122dbcf6da060e7a6398950d5' => array(
						'ssn' => '012345678',
					),
				),
				array(
					'4b122dbcf6da060e7a6398950d5' => array(
						'ssn' => '012345678',
						'ssn_part_1' => '012',
						'ssn_part_2' => '34',
						'ssn_part_3' => '5678',
					),
				),
				'Stuff',
			),
		);
	}
	
	/**
	 * Tests getApplicationDataByTrackKey().
	 *
	 * @dataProvider dataProviderApplicationTrackKey
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @param int $application_id
	 * @param array $track_keys
	 * @param array $returned_data
	 * @param array $expected_data
	 * @param string $message
	 * @return void
	 */
	public function testGetApplicationDataByTrackKey($property_short, $mode, $track_keys, $returned_data, $expected_data, $message)
	{
		$api = $this->getMock(
			'RPC_Client_1',
			array(
				'getDataByApplicationId',
				'getDataByTrackKey',
			),
			array(),
			'',
			FALSE
		);
		$api->expects($this->once())
			->method('getDataByTrackKey')
			->with($track_keys)
			->will($this->returnValue($returned_data));
		
		$olpapi_2 = $this->getMock(
			'OLP_ECashClient_Encryption',
			array(
				'getAPI',
			),
			array(
				$property_short,
				$mode,
			),
			'',
			TRUE
		);
		$olpapi_2->expects($this->once())
			->method('getAPI')
			->will($this->returnValue($api));
		
		$data = $olpapi_2->getApplicationDataByTrackKey($track_keys);
		
		$this->assertEquals($expected_data, $data, $message);
	}
	
	/**
	 * Tests the caching ability of the driver.
	 *
	 * @return void
	 */
	public function testCache()
	{
		$property_short = 'PCL';
		$mode = 'LOCAL';
		$application_id = 1;
		$returned_data = array('application_id' => $application_id);
		
		$api = $this->getMock(
			'RPC_Client_1',
			array(
				'getDataByApplicationId',
				'getDataByTrackKey',
			),
			array(),
			'',
			FALSE
		);
		$api->expects($this->once())
			->method('getDataByApplicationId')
			->with($application_id)
			->will($this->returnValue($returned_data));
		
		$olpapi_2 = $this->getMock(
			'OLP_ECashClient_Encryption',
			array(
				'getAPI',
			),
			array(
				$property_short,
				$mode,
			),
			'',
			TRUE
		);
		$olpapi_2->expects($this->once())
			->method('getAPI')
			->will($this->returnValue($api));
		
		// This run will hit the getAPI().
		$data = $olpapi_2->getApplicationData($application_id);
		$this->assertEquals($returned_data, $data, 'Cache run 1');
		
		// And this one won't, since we will use the cache.
		$data = $olpapi_2->getApplicationData($application_id);
		$this->assertEquals($returned_data, $data, 'Cache run 2');
	}
	
	/**
	 * Tests that we thrown an exception if we cannot find the API.
	 *
	 * @expectedException Exception
	 *
	 * @return void
	 */
	public function testInvalidServer()
	{
		$olpapi_2 = $this->getMock(
			'OLP_ECashClient_Encryption',
			array(
				'getAPI',
			),
			array(
				$property_short,
				$mode,
			),
			'',
			TRUE
		);
		$olpapi_2->expects($this->once())
			->method('getAPI')
			->will($this->returnValue(NULL));
		
		$olpapi_2->getApplicationData(1);
	}
}

?>
