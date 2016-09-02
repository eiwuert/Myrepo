<?php
/**
 * Commercial version of VendorAPI_IDriver
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
class ECash_VendorAPI_Driver extends VendorAPI_BasicDriver
{
	/**
	 * @var ECash_Config
	 */
	protected $config;

	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @param ECash_Config $config
	 * @param ECash_Factory $factory
	 * @param DB_IConnection_1 $db
	 * @param ECash_Company $company
	 * @param Log_ILog_1 $log
	 */
	public function __construct(ECash_Config $config, ECash_Factory $factory, DB_IConnection_1 $db, ECash_Company $company, Log_ILog_1 $log)
	{
		$this->config = $config;
		$this->factory = $factory;
		$this->enterprise = $config->ENTERPRISE_PREFIX;
		$this->company = $company->getModel()->name_short;
		$this->company_id = $company->getCompanyId();
		$this->db = $db;
		$this->log = $log;
	}

	/**
	 * Gets the action class for the specified API call
	 *
	 * @param string $name
	 * @return object
	 */
	public function getAction($name)
	{
		$class = "ECash_VendorAPI_Actions_{$name}";
		if (class_exists($class))
		{
			return new $class($this);
		}

		return parent::getAction($name);
	}

	/**
	 * Returns the Vendor API Authenticator
	 * @return VendorAPI_Authenticator
	 */
	public function getAuthenticator()
	{
		if (!$this->auth instanceof VendorAPI_Authenticator)
		{
			$security = new ECash_Security(SESSION_EXPIRATION_HOURS, $this->db);
			$acl = ECash::getAcl($this->db);
			$this->auth = new ECash_VendorAPI_Authenticator($this->getCompanyId(), $security, $acl);
		}
		return $this->auth;
	}

	/**
	 * Gets a database connection
	 *
	 * NOTE: Since all commercial enterprises currently share databases
	 * between all companies, we don't do anything fancy with $company.
	 *
	 * @param string $company
	 * @return DB_IConnection_1
	 */
	public function getDatabase($company = NULL)
	{
		// failover order is determined in loader...
		return $this->db;
	}
}
?>