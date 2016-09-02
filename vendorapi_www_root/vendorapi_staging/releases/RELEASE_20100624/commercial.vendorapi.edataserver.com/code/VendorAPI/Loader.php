<?php
/**
 * Dynamically includes either the Client module or Commercial module's Loader class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

class VendorAPI_Loader implements VendorAPI_IBootstrapper
{
	/**
	 * @var VendorAPI_IBootstrapper
	 */
	protected $loader;

	/**
	 * @param string $enterprise ECash enterprise (eg., CLK)
	 * @param string $company Company/property short (eg., PCL)
	 * @param string $mode Operating mode (LIVE, RC, DEV)
	 */
	public function __construct($enterprise, $company, $mode)
	{
		$this->loader = $this->getLoader($enterprise, $company, $mode);
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
		return $this->loader->getEnterprise();
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
		return $this->loader->getCompany();
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
		return $this->loader->getMode();
	}

	/**
	 * Returns the logging object for the api
	 *
	 * @return Log_ILog_1
	 */
	public function getLog()
	{
		return $this->loader->getLog();
	}

	/**
	 *  Vendor API Driver
	 *
	 *  Returns the driver class that will be used to implement Vendor API.
	 * @return VendorAPI_IDriver
	 */
	public function getDriver()
	{
		return $this->loader->getDriver();
	}

	/**
	 * ECash Configuration Bootstrapper
	 *
	 * Provides the necessary includes and defines that will be needed for
	 * the to implement specific ECash Config.
	 * @return void
	 */
	public function bootstrap()
	{
		$this->loader->bootstrap();
	}

	/**
	 * @param $enterprise
	 * @param $company
	 * @param $mode
	 * @return VendorAPI_IBootstrapper
	 */
	protected function getLoader($enterprise, $company, $mode)
	{
		if (strcasecmp($enterprise, 'clk') == 0)
		{
			$module_path = isset($_SERVER['AMG_MODULE_PATH'])
				? $_SERVER['AMG_MODULE_PATH']
				: VENDORAPI_BASE_DIR . '/../ecash_clk';
		}
		else
		{
			$module_path = isset($_SERVER['COMMERCIAL_MODULE_PATH'])
				? $_SERVER['COMMERCIAL_MODULE_PATH']
				: VENDORAPI_BASE_DIR . '/../ecash_commercial';
		}

		include_once $module_path . '/code/ECash/VendorAPI/Loader.php';
		return new ECash_VendorAPI_Loader($enterprise, $company, $mode);
	}
}

?>
