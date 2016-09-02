<?php

class VendorAPI_Blackbox_DecisionTest extends PHPUnit_Framework_TestCase
{
	public function testUnknownDecisionThrowsException()
	{
		$this->setExpectedException('InvalidArgumentException', 'unknown decision happy');
		$d = new VendorAPI_Blackbox_Generic_Decision('happy');
	}

	public static function validDecisions()
	{
		return array(
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_REACT),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_UNDERACTIVE),
		);
	}

	public static function invalidDecisions()
	{
		return array(
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_WITHDRAWN),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DISAGREED),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DONOTLOAN),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE),
		);
	}

	/**
	 * @dataProvider validDecisions
	 * @param string $decision
	 */
	public function testValidDecisionIsValid($decision)
	{
		$d = new VendorAPI_Blackbox_Generic_Decision($decision);
		$this->assertTrue($d->isValid());
	}

	/**
	 * @dataProvider invalidDecisions
	 * @param string $decision
	 */
	public function testInvalidDecisionIsNotValid($decision)
	{
		$d = new VendorAPI_Blackbox_Generic_Decision($decision);
		$this->assertFalse($d->isValid());
	}

	public function testToStringReturnsDecision()
	{
		$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD;
		$d = new VendorAPI_Blackbox_Generic_Decision($decision);
		$this->assertEquals($decision, (string)$d);
	}
}

?>