<?php

/**
 * A test loader
 *
 * @package Tests
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_Loader implements VendorAPI_IBootstrapper
{
	/**
	 * @var VendorAPI_IDriver
	 */
	protected static $driver;

	/**
	 * @var bool
	 */
	protected static $bootstrap_called = FALSE;

	/**
	 * @var string
	 */
	protected static $expected_enterprise = '';

	/**
	 * @var string
	 */
	protected static $expected_company = '';

	/**
	 * @var string
	 */
	protected static $expected_mode = '';

	/**
	 * Creates the test bootloader.
	 *
	 * Validates that the correct $enterprise and $company are passed. These
	 * can be set using VendorAPI_Bootstrapper_Loader::setExpectedValues;
	 *
	 * @param string $enterprise
	 * @param string $company
	 */
	public function __construct($enterprise, $company, $mode)
	{
		PHPUnit_Framework_Assert::assertEquals(self::$expected_enterprise, $enterprise, "The Enterprise is not passed to the bootstrapper constructor.");
		PHPUnit_Framework_Assert::assertEquals(self::$expected_company, $company, "The Company is not passed to the bootstrapper constructor.");
		PHPUnit_Framework_Assert::assertEquals(self::$expected_mode, $mode, "The Mode is not passed to the bootstrapper constructor.");
	}

	/**
	 * Sets the driver returned by the test bootstrapper.
	 *
	 * @param VendorAPI_IDriver $driver
	 * @internal
	 */
	public static function setDriver($driver)
	{
		self::$driver = $driver;
	}

	/**
	 * Sets the expected enterprise and company
	 *
	 * @param string $enterprise
	 * @param string $company
	 * @param string $mode
	 * @internal
	 */
	public static function setExpectedValues($enterprise, $company, $mode)
	{
		self::$expected_enterprise = $enterprise;
		self::$expected_company = $company;
		self::$expected_mode = $mode;
	}


	/**
	 * Validates that the appropriate methods have been called on the bootloader.
	 *
	 * @internal
	 */
	public static function validateMethodCalls()
	{
		PHPUnit_Framework_Assert::assertTrue(self::$bootstrap_called, "The bootstrap function was not called");
	}

	/**
	 *  ECash Configuration Bootstrapper
	 *
	 *  Provides the necessary includes and defines that will be needed for
	 *  the to implement specific ECash Config.
	 */
	public function bootstrap()
	{
		self::$bootstrap_called = TRUE;
	}

	/**
	 *  Driver Name
	 *
	 *  Returns the name that will be used to implement the Config Driver.
	 *
	 * @return VendorAPI_IDriver
	 */
	public function getDriver()
	{
		return self::$driver;
	}

	/**
	 * Get Enterprise
	 *
	 * Returns Enterprise value
	 *
	 * @return string $enterprise
	 */
	public function getEnterprise()
	{
		return self::$expected_enterprise;
	}

	/**
	 * Get Company
	 *
	 * Returns Company value
	 *
	 * @return string $company
	 */
	public function getCompany()
	{
		return self::$expected_company;
	}


	/**
	 * Get Mode
	 *
	 * Returns Mode value
	 *
	 * @return string $mode
	 */
	public function getMode()
	{
		return self::$expected_mode;
	}
	
	/**
	 * Returns the logging object for the api
	 * 
	 * @return Log_ILog_1
	 */
	public function getLog()
	{
	}
}

?>