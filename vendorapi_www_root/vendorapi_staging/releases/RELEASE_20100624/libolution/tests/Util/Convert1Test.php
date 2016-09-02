<?php


class Util_Convert1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Provides ips and expected results for ip2Float
	 *
	 * @return array
	 */
	public static function ipProvider()
	{
		return array(
			array('1.1.1.1', 16843009),
			array('255.255.255.255', 4294967295),
			array('blah', FALSE),
		);
	}

	/**
	 * Tests the ipToFloat conversion
	 *
	 * @dataProvider ipProvider
	 *
	 * @param string $ip
	 * @param float $expected
	 * @return void
	 */
	public function testIPToFloat($ip, $expected)
	{
		$actual = Util_Convert_1::ip2Float($ip);
		$this->assertEquals($expected, $actual);
	}
}

?>