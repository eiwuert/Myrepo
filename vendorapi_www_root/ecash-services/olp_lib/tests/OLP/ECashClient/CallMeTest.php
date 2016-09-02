<?php

/**
 * Tests the OLP_ECashClient_CallMe class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_ECashClient_CallMeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testAddCallMe().
	 *
	 * @return array
	 */
	public static function dataProviderAllCallMe()
	{
		return array(
			array(
				'PCL',
				'TEST',
				1000,
				'tomorrow',
				'7025551234',
				'complete',
				TRUE,
				'Simple pass test.',
			),
			
			array(
				'PCL',
				'TEST',
				1001,
				'',
				'',
				FALSE,
				FALSE,
				'Simple fail test.',
			),
		);
	}
	
	/**
	 * Tests addCallMe().
	 *
	 * @dataProvider dataProviderAllCallMe
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @param int $application_id
	 * @param string $call_time
	 * @param string $phone_number
	 * @param array $returned_data
	 * @param array $expected_data
	 * @param string $message
	 * @return void
	 */
	public function testAddCallMe($property_short, $mode, $application_id, $call_time, $phone_number, $returned_data, $expected_data, $message)
	{
		$api = $this->getMock(
			'Prpc_Client2',
			array(
				'addCallMe',
			),
			array(),
			'',
			FALSE
		);
		$api->expects($this->once())
			->method('addCallMe')
			->with($application_id, $call_time, $phone_number)
			->will($this->returnValue($returned_data));
		
		$ecash_client = $this->getMock(
			'OLP_ECashClient_CallMe',
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
		$ecash_client->expects($this->once())
			->method('getAPI')
			->will($this->returnValue($api));
		
		$data = $ecash_client->addCallMe($application_id, $call_time, $phone_number);
		
		$this->assertEquals($expected_data, $data, $message);
	}
}

?>
