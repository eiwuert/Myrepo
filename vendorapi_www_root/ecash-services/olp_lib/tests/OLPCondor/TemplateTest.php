<?php
/**
 * Test case for the OLPCondor_Template object.
 *
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 */
class OLPCondor_TemplateTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Test getName
	 *
	 * @return void
	 */
	public function testGetName()
	{
		// Value for loan type and type
		$this->assertEquals('Unit Test ST Renewal', OLPCondor_Template::getName('UNIT_TEST',OLPCondor_Template::TYPE_RENEWAL,'ST'));
		// No value for loan type or type
		$this->assertEquals('Unit Test Default Title', OLPCondor_Template::getName('UNIT_TEST',OLPCondor_Template::TYPE_TITLE,'ST'));
		// Default for Property short
		$this->assertEquals('Unit Test Default Loan', OLPCondor_Template::getName('UNIT_TEST',OLPCondor_Template::TYPE_LOAN,'ZZ'));
		// Global Default
		$this->assertEquals('Loan Document', OLPCondor_Template::getName('INVALID_PROP',OLPCondor_Template::TYPE_LOAN,'ZZ'));
	}
	
	/**
	 * Test isValid
	 *
	 * @return void
	 */
	public function testIsValid()
	{
		// All data supplied
		$this->assertTrue(OLPCondor_Template::valid('Unit Test ST Renewal','UNIT_TEST',OLPCondor_Template::TYPE_RENEWAL,'ST'));
		// All but doc type
		$this->assertTrue(OLPCondor_Template::valid('Unit Test ST Renewal','UNIT_TEST',NULL,'ST'));
		// No loan_type
		$this->assertTrue(OLPCondor_Template::valid('Unit Test ST Renewal','UNIT_TEST',OLPCondor_Template::TYPE_RENEWAL));
		// No type
		$this->assertTrue(OLPCondor_Template::valid('Unit Test ST Renewal','UNIT_TEST'));
		// Default doc all data supplied
		$this->assertTrue(OLPCondor_Template::valid('Loan Document','INVALID_PROP',OLPCondor_Template::TYPE_LOAN,'ZZ'));
		// Default doc no loan type
		$this->assertTrue(OLPCondor_Template::valid('Loan Document','INVALID_PROP',OLPCondor_Template::TYPE_LOAN));
		// Default doc no type
		$this->assertTrue(OLPCondor_Template::valid('Loan Document','INVALID_PROP'));
		// Invalid doc name
		$this->assertFalse(OLPCondor_Template::valid('No valid doc','UNIT_TEST',OLPCondor_Template::TYPE_RENEWAL,'ST'));
	}
	
	
}
?>
