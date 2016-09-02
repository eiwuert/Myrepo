<?php
class ECash_DataX_Responses_PerfTest extends PHPUnit_Framework_TestCase
{
	public static function dpTestIsIDVFailure()
	{
		return array(
			array('N', TRUE),
			array('Y', FALSE),
			array('', FALSE)
		);
	}
	/**
	 * Tests the isIDVFailure method
	 *
	 * @dataProvider dpTestIsIDVFailure
	 * @param string $result
	 * @param bool $expected
	 * @return void
	 */
	public function testIsIDVFailure($result, $expected)
	{
		$xml = sprintf(
			'<?xml version="1.0" ?><ConsumerIDVerificationSegment><CustomDecision><Result>%s</Result></CustomDecision></ConsumerIDVerificationSegment>',
			$result
		);

		$response = new ECash_DataX_Responses_Perf();
		$response->parseXML($xml);
		$this->assertEquals($expected, $response->isIDVFailure());
	}
}
