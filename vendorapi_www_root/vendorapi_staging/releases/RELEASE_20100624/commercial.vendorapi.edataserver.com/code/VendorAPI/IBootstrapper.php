<?php
/**
 * Vendor API Boostrap Interface
 *
 * The bootstrapper is responsible for initializing the environment
 * for the specific ECash installation. This includes setting any
 * necessary environment variables, defining constants, and including
 * configuration files. It does setup work ONLY. Control is passed
 * from the bootloader to the driver via getDriver(); the driver is
 * the API's interface into the ECash installation.
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
interface VendorAPI_IBootstrapper
{
	/**
	 * @param string $enterprise ECash enterprise (eg., CLK)
	 * @param string $company Company/property short (eg., PCL)
	 * @param string $mode Operating mode (LIVE, RC, DEV)
	 */
	public function __construct($enterprise, $company, $mode);

	/**
	 * ECash Configuration Bootstrapper
	 *
	 * Provides the necessary includes and defines that will be needed for
	 * the to implement specific ECash Config.
	 * @return void
	 */
	public function bootstrap();

	/**
	 *  Vendor API Driver
	 *
	 *  Returns the driver class that will be used to implement Vendor API.
	 * @return VendorAPI_IDriver
	 */
	public function getDriver();

	/**
	 * Get Enterprise
	 *
	 * Returns Enterprise value
	 *
	 * @return string $enterprise
	 */
	public function getEnterprise();

	/**
	 * Get Company
	 *
	 * Returns Company value
	 *
	 * @return string $company
	 */
	public function getCompany();

	/**
	 * Get Mode
	 *
	 * Returns Mode value
	 *
	 * @return string $mode
	 */
	public function getMode();

	/**
	 * Returns the logging object for the api
	 *
	 * @return Log_ILog_1
	 */
	public function getLog();
}
?>