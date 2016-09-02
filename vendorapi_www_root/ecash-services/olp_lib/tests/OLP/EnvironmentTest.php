<?php
/**
 * Test case for OLP_Environment class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_EnvironmentTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testGetOverrideEnvironment().
	 *
	 * @return array
	 */
	public static function dataProviderGetOverrideEnvironment()
	{
		return array(
			array(
				'TEST',
				NULL,
				'TEST',
			),
			
			array(
				'TEST',
				'QA_MANUAL',
				'QA_MANUAL',
			),
			
			array(
				'TEST_READONLY',
				'QA_MANUAL',
				'QA_MANUAL',
			),
			
			array(
				'TEST',
				'QA_AUTOMATED',
				'QA_AUTOMATED',
			),
			
			array(
				'TEST_READONLY',
				'QA_AUTOMATED',
				'QA_AUTOMATED',
			),
			
			array(
				'TEST',
				'STAGING',
				'STAGING',
			),
			
			array(
				'TEST_READONLY',
				'STAGING',
				'STAGING_READONLY',
			),
			
			array(
				'TEST',
				'BAD_ENVIRONMENT_VALUE',
				'TEST',
			),
		);
	}
	
	/**
	 * Tests getOverrideEnvironment().
	 *
	 * @dataProvider dataProviderGetOverrideEnvironment
	 *
	 * @param string $mode
	 * @param string $application_environment
	 * @param string $expected_mode
	 * @return void
	 */
	public function testGetOverrideEnvironment($mode, $application_environment, $expected_mode)
	{
		$message = NULL;
		
		if (is_string($application_environment))
		{
			$_SERVER['APPLICATION_ENVIRONMENT'] = $application_environment;
			$message = "Setting APPLICATION_ENVIRONMENT to '{$application_environment}' with current mode of '{$mode}'.";
		}
		else
		{
			unset($_SERVER['APPLICATION_ENVIRONMENT']);
			$message = "Unsetting APPLICATION_ENVIRONMENT with current mode of '{$mode}'.";
		}
		
		$observed_mode = OLP_Environment::getOverrideEnvironment($mode);
		$this->assertEquals($expected_mode, $observed_mode, $message);
	}
}

?>
