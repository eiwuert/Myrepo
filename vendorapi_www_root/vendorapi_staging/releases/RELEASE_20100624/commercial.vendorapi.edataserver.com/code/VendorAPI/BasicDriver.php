<?php
/**
 * VendorAPI_BasicDriver
 *
 * Abstract Class used for the base creation of client
 * specific VendorAPI_Driver implementations.
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
abstract class VendorAPI_BasicDriver implements VendorAPI_IDriver
{
	const MODE_LOCAL = 'local';
	const MODE_RC = 'rc';
	const MODE_LIVE = 'live';
	const MODE_QA = 'qa';
	const MODE_QA_MANUAL = 'qa_manual';

	/**
	 * @var VendorAPI_IAuthenticator
	 */
	protected $auth;

	/**
	 * @var int
	 */
	protected $enterprise;

	/**
	 * @var string
	 */
	protected $company;

	/**
	 * @var int
	 */
	protected $company_id;

	/**
	 * @var Log_ILog_1
	 */
	protected $log;

	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * Use the master database connection
	 *
	 * @var boolean
	 */
	protected $use_master;

	/**
	 * @var String
	 */
	protected $environment;

	/**
	 * @var Boolean
	 */
	public $use_bfw_prpc = false;

	/**
	 * Co
	 *
	 * @param boolean $use_master
	 */
	public function __construct($use_master = FALSE)
	{
		$this->use_master = $use_master;
	}

	/**
	 * Returns a handler for the given action
	 *
	 * @param string $name Action being requested
	 * @return object
	 */
	public function getAction($name)
	{
		$class_object = FALSE;
		$class_name = "VendorAPI_Actions_{$name}";
		if (class_exists($class_name))
		{
			$class_object = new $class_name($this);
		}

		return $class_object;
	}

	/**
	 * Returns the wsdl used by the implementations enterprise API.
	 *
	 * (Not supported by default.)
	 *
	 * @param string $soap_url
	 * @return string
	 */
	public function getEnterpriseSoapWsdl($soap_url)
	{
		throw new RuntimeException("Enterprise Soap not supported by this driver");
	}

	/**
	 * Returns the enterprise for the driver
	 *
	 * @return string
	 */
	public function getEnterprise()
	{
		return $this->enterprise;
	}

	/**
	 * Returns the property short for the driver
	 *
	 * @return string
	 */
	public function getCompany()
	{
		return $this->company;
	}

	/**
	 * Returns the company ID for the driver
	 *
	 * @return int
	 */
	public function getCompanyID()
	{
		return $this->company_id;
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

	/**
	 * Returns the blackbox factory for the api.
	 *
	 * @param Blackbox_Config $config
	 * @param int $loan_type_id
	 * @return VendorAPI_Blackbox_Factory
	 */
	public function getBlackboxFactory(Blackbox_Config $config, $loan_type_id)
	{
		return new VendorAPI_Blackbox_Factory(
		$this,
		$config,
		$this->getBlackboxRuleFactory($config, $loan_type_id)
		);
	}

	/**
	 * Creates and returns a VendorAPI_Blackbox_Rule_Factory instance.
	 *
	 * @param Blackbox_Config $config
	 * @param int $loan_type_id
	 * @return VendorAPI_Blackbox_Rule_Factory
	 */
	public function getBlackboxRuleFactory(Blackbox_Config $config, $loan_type_id)
	{
		return new VendorAPI_Blackbox_Rule_Factory($this, $config, $loan_type_id);
	}

	/**
	 * Returns a new FactorTrust_Call object.
	 *
	 * Uses eCash config files to get required information.
	 *
	 * @param int $loan_type_id
	 * @return FactorTrust_Call
	 */
	public function getFactorTrustCall($inquiry, $store, $loan_type_id)
	{
		return new FactorTrust_UW_Call(FT_URL, $this->getFactorTrustRequest($inquiry, $store, $loan_type_id), $this->getFactorTrustResponse($loan_type_id));
	}

	/**
	 * Returns a new Clarity_Call object.
	 *
	 * Uses eCash config files to get required information.
	 *
	 * @param int $loan_type_id
	 * @return Clarity_Call
	 */
	public function getClarityCall($inquiry, $store, $loan_type_id)
	{
		return new Clarity_UW_Call(CL_URL, $this->getClarityRequest($inquiry, $store, $loan_type_id), $this->getClarityResponse($loan_type_id));
	}

	/**
	* Returns a new Tribal_Call object.
	*
	* Uses eCash config files to get required information.
	*
	* @param int $loan_type_id
	* @return Tribal_Call
	*/
	public function getTribalCall($loan_type_id)
	{
		return new TSS_Tribal_Call($this->getTribalRequest($loan_type_id), $this->getTribalResponse($loan_type_id));
	}

	/**
	 * Returns a new DataX_Call object.
	 *
	 * Uses eCash config files to get required information.
	 *
	 * @param int $loan_type_id
	 * @return DataX_Call
	 */
	public function getDataXCall($loan_type_id)
	{
		return new TSS_DataX_Call(DATAX_URL, $this->getDataXRequest($loan_type_id), $this->getDataXResponse($loan_type_id));
	}
	
	/**
	 * Returns a new DataX_Call object.
	 *
	 * Uses eCash config files to get required information.
	 *
	 * @return DataX_Call
	 */
	public function getDataXFraudCall()
	{
		return new TSS_DataX_Call(DATAX_URL, $this->getDataXFraudRequest(), $this->getDataXFraudResponse());
	}

	/**
	 * Returns the environment we're running in (local, rc, live).
	 *
	 * @return string
	 */
	public function getEnvironment()
	{
		return $this->environment;
	}

	/**
	 * Sets the environment we're running in (local, rc, live).
	 *
	 * @return string
	 */
	public function setEnvironment($environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Really does nothing?
	 * @return NULL
	 */
	public function getPageflowConfig() { }

	/**
	 * Returns url to make a prpc call to bfw (for state object)
	 *
	 * @return string
	 */
	public function getBFW_PRPC_URL()
	{
		// @todo this really needs to go in a config
		switch (strtolower($this->getEnvironment()))
		{
			case self::MODE_LIVE:
				return "prpc://bfw.1.edataserver.com/";
			case self::MODE_QA:
			case self::MODE_QA_MANUAL:
				return "prpc://qa.bfw.1.edataserver.com/";
			case self::MODE_RC:
				return "prpc://staging.bfw.1.edataserver.com/";
		}
		return "prpc://rc.bfw.1.edataserver.com/";
	}
}
?>
