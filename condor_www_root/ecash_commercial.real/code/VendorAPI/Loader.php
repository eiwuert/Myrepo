<?php
/**
 * VendorAPI_Loader
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
class VendorAPI_Loader extends VendorAPI_BasicLoader
{
	/**
	 * @var ECash_Factory
	 */
	private $factory;

	/**
	 * @var ECash_Company
	 */
	private $company_obj;

	/**
	 * @var ECash_Config
	 */
	private $config;

	/**
	 * @var DB_IConnection_1
	 */
	private $db;

	/**
	 * ECash Configuration Bootstrapper
	 *
	 * Provides the necessary includes and defines that will be needed for
	 * the to implement specific ECash Config.
	 * @return void
	 */
	public function bootstrap()
	{
		AutoLoad_1::addSearchPath(dirname(__FILE__)."/../");
		$company 	= $this->getCompany();
		$enterprise = $this->getEnterprise();
		$mode_map = array (
			"LIVE" 	=> "Live",
			"RC"	=> "RC",
			"DEV"	=> "Local",
							);
		$mode = $mode_map[$this->getMode()];

		$ecash_commercial_directory = "/virtualhosts/ecash_commercial/";
		$ecash_common_directory 	= "/virtualhosts/ecash_common_cfe/";
		$ecash_customer_directory 	= "/virtualhosts/ecash_".strtolower($enterprise).'/';

		putenv("EXECUTION_MODE=".$this->getMode());
		putenv("ECASH_CUSTOMER=".strtoupper($enterprise));
		putenv("ECASH_CUSTOMER_DIR=$ecash_customer_directory");
		putenv("ECASH_COMMON_DIR=$ecash_common_directory");
		putenv("ECASH_COMMON_CODE_DIR={$ecash_common_directory}/code/");
		putenv("LIBOLUTION_DIR=/virtualhosts/libolution/");
		putenv("ECASH_WWW_DIR={$ecash_commercial_directory}/www/");
		putenv("ECASH_CODE_DIR={$ecash_commercial_directory}/code/");
		putenv("ECASH_EXEC_MODE=$mode");
		putenv("COMMON_LIB_DIR=/virtualhosts/lib/");
		putenv("CUSTOMER_CODE_DIR={$ecash_customer_directory}/code/");

		require_once($ecash_commercial_directory.'/www/config.php');

		$this->config = ECash_Config::getInstance();
		$this->factory = ECash::getFactory();
		$this->company_obj = $this->factory->getCompanyByNameShort($company, $this->getDatabase());

		ECash::setCompany($this->company_obj);
	}

	/**
	 * Gets the Commercial driver implementation
	 * @return ECash_VendorAPI_Driver
	 */
	public function getDriver()
	{
		if (!$this->driver)
		{
			$this->driver = new ECash_VendorAPI_Driver(
				$this->config,
				$this->factory,
				$this->getDatabase(),
				$this->company_obj,
				$this->getLog()
			);
		}
		return $this->driver;
	}

	/**
	 * Gets a database connection
	 *
	 * This will attempt to connect to each defined database in the failover order
	 *
	 * @return DB_IConnection_1
	 */
	public function getDatabase()
	{
		if (!$this->db)
		{
			$db = new VendorAPI_DB_FailoverConfig();
			$db->addConfig($this->config->DB_API_CONFIG);
			$db->addConfig($this->config->DB_SLAVE_CONFIG);
			$db->addConfig($this->config->DB_MASTER_CONFIG);
			$this->db = $db->getConnection();
		}
		return $this->db;
	}
}
?>
