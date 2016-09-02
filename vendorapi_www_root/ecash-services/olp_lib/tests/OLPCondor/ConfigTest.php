<?php
/**
 * Test case for the OLPCondor_Config object.
 *
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 */
class OLPCondor_ConfigTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Test getConfig
	 *
	 * @return void
	 */
	public function testGetConfig()
	{
		$this->assertEquals('Unit Test ST Renewal', OLPCondor_Config::getConfig('UNIT_TEST')->templates->loan_types->ST->renewal);
	}
	
	/**
	 * Test getTemplates
	 *
	 * @return void
	 */
	public function testGetTemplates()
	{
		$this->assertEquals('Unit Test ST Renewal', OLPCondor_Config::getTemplates('UNIT_TEST')->loan_types->ST->renewal);
	}
	
	/**
	 * Test getStateTemplates
	 *
	 * @return void
	 */
	public function testGetStateTemplates()
	{
		$this->assertEquals('Unit Test ST Renewal', OLPCondor_Config::getTypeTemplates('UNIT_TEST', 'renewal')->ST);
	}
	
	/**
	 * Test getDefaultTemplates
	 *
	 * @return void
	 */
	public function testGetDefaultTemplates()
	{
		$this->assertEquals('Unit Test Default Loan', OLPCondor_Config::getDefaultTemplates('UNIT_TEST')->loan);
	}
	
		/**
	 * Test getTypeTemplates
	 *
	 * @return void
	 */
	public function testGetTypeTemplates()
	{
		$this->assertEquals('Unit Test ST Loan', OLPCondor_Config::getTypeTemplates('UNIT_TEST','loan')->ST);
		$this->assertEquals('Unit Test Default Title', OLPCondor_Config::getTypeTemplates('UNIT_TEST','title')->default);
	}
	
}
?>
