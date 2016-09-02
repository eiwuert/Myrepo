<?php
class VendorAPI_ResponseBuilderTest extends PHPUnit_Framework_TestCase
{
	public function testgetResponseReturns()
	{
		$builder = new VendorAPI_ResponseBuilder();
		$builder->setState(new VendorAPI_StateObject());
		$builder->setOutcome(VendorAPI_Response::SUCCESS);
		$this->assertThat($builder->getResponse(), $this->isInstanceOf('VendorAPI_Response'));
	}

	/**
	 * @dataProvider responseProvider 
	 */
	public function testgetResponseHasProperOutcome($expected)
	{
		$builder = new VendorAPI_ResponseBuilder();
		$builder->setState(new VendorAPI_StateObject());
		$builder->setOutcome($expected);
		$response = $builder->getResponse()->toArray();
		$this->assertEquals($expected, $response['outcome']);
	}
	
	/**
	 * 
	 * @expectedException InvalidArgumentException
	 */
	public function testgetOutcomeExceptionOnInvalidParam()
	{
		$builder = new VendorAPI_ResponseBuilder();
		$builder->setOutcome('somevaluethatisntright');		
	}
	
	public function testAddResultIsInTheOutcome()
	{
		$builder = new VendorAPI_ResponseBuilder();
		$builder->setState(new VendorAPI_StateObject());
		$builder->setOutcome(VendorAPI_Response::SUCCESS);
		$builder->addResult('result_key', 'result_val');
		$response = $builder->getResponse()->toArray();
		$this->assertEquals($response['result']['result_key'], 'result_val');
	}
	
	public function testAddResultsToTheResponse()
	{
		$expected = array(
			'key1' => 'val1',
			'key2' => 'val2'
		);
		$builder = new VendorAPI_ResponseBuilder();
		$builder->setState(new VendorAPI_StateObject());
		$builder->setOutcome(VendorAPI_Response::SUCCESS);
		$builder->addResults($expected);
		$response = $builder->getResponse()->toArray();
		$this->assertEquals($expected, $response['result']);
	}
	
	public function testSetError()
	{
		$error = "sweet poop";
		$builder = new VendorAPI_ResponseBuilder();
		$builder->setState(new VendorAPI_StateObject());
		$builder->setOutcome(VendorAPI_Response::SUCCESS);
		$builder->setError($error);
		$response = $builder->getResponse()->toArray();
		$this->assertEquals($error, $response['error']);
		$this->assertEquals(VendorAPI_Response::ERROR, $response['outcome']);
	}
	
	public static function responseProvider()
	{
		return array(
			array(VendorAPI_Response::SUCCESS),
			array(VendorAPI_Response::ERROR)
		);
	}
}