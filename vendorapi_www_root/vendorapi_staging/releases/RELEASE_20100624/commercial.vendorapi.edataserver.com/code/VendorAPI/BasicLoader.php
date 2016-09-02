<?php
/**
 * VendorAPI_BasicLoader
 *
 * Abstract Class used for the base creation of client
 * specific VendorAPI_Loader implementations.
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
abstract class VendorAPI_BasicLoader implements VendorAPI_IBootstrapper
{
	/**
	 * @var string
	 */
	protected $enterprise;

	/**
	 * @var string
	 */
	protected $company;

	/**
	 * @var string
	 */
	protected $mode;

	/**
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * @var Log_ILog_1
	 */
	protected $log;
	
	/**
	 * @var boolean
	 */
	protected $use_master;


	/**
	 * ECash Load Construct
	 *
	 * Acquires needed enterprise and company information along
	 * with obtaining the environment mode.
	 * @param string $enterprise
	 * @param string $company
	 * @param string $mode
	 * @param boolean $use_master
	 * @return void
	 */
	public function __construct($enterprise, $company, $mode, $use_master = FALSE)
	{
		$this->enterprise	= $enterprise;
		$this->company		= $company;
		$this->mode	 		= $mode;
		$this->use_master   = $use_master;
		$this->log = new Log_SysLog_1('vendor_api_' . $enterprise);
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
		return $this->enterprise;
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
		return $this->company;
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
		return $this->mode;
	}

	/**
	 * Returns the logging object for the api
	 *
	 * @return Log_ILog_1
	 */
	public function getLog()
	{
		return $this->log;
	}
}
?>
