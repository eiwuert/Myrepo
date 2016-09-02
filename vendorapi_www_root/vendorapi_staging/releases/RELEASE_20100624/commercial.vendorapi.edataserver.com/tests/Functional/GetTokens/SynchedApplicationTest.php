<?php

class Functional_GetTokens_SynchedApplicationTest extends FunctionalTestCase
{
	public function testTokens()
	{
		$service = TestAPI::getInstance('clk', 'pcl', 'DEV', 'vendor_api', 'vendor_api');
		$r = $service->executeAction('getTokens', array(1, TRUE));

		$this->assertEquals(1, $r['outcome']);
		$this->assertArrayHasKey('tokens', $r['result']);
		$this->assertType('array', $r['result']['tokens']);

		$tokens = $r['result']['tokens'];
		$this->assertToken($tokens, 'CustomerNameFirst', 'SPEAKER');
		$this->assertToken($tokens, 'CustomerNameLast', 'MASON');
		$this->assertToken($tokens, 'CustomerCity', 'DETROIT');
		$this->assertToken($tokens, 'CustomerZip', '79496');
		$this->assertToken($tokens, 'CustomerIPAddress', '10.10.10.1');
	}

	public function getDataSet()
	{
		return new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array(
			$this->getFixture('base'),
			$this->getFixture('synched_application')
		));
	}

	protected function assertToken(array $tokens, $name, $value)
	{
		$this->assertArrayHasKey($name, $tokens);
		$this->assertEquals($value, $tokens[$name]);
	}
}

?>