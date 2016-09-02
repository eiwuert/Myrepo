<?php
/**
 * CS Client class to interface with RPC services in eCash
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPECash_CS_Client
{
	const PROCESS_CSO = 'CSO';
	const PROCESS_STANDARD = 'STANDARD';
	const PROCESS_COMBINED = 'COMBINED';
	
	const PAGE_TYPE_CONFIRM = 'CONFIRM';
	const PAGE_TYPE_AGREE = 'AGREE';
	
	/**
	 * Array of CS process types by name short and loan type
	 *
	 * @var array
	 */
	protected static $cs_process = array(
		'UFC' => array(
			'default' => self::PROCESS_STANDARD, 
			'online_confirmation'  => self::PROCESS_COMBINED,
		),
		'D1' => array(
			'default' => self::PROCESS_STANDARD, 
			'online_confirmation'  => self::PROCESS_COMBINED,
		),
		'CA' => array(
			'default' => self::PROCESS_STANDARD, 
			'online_confirmation'  => self::PROCESS_COMBINED,
		),
		'UCL' => array(
			'default' => self::PROCESS_STANDARD, 
			'online_confirmation'  => self::PROCESS_COMBINED,
		),
		'PCL' => array(
			'default' => self::PROCESS_STANDARD, 
			'online_confirmation'  => self::PROCESS_COMBINED,
		)
	);

	/**
	 * Array of pages based on process type and page type
	 *
	 * @var array
	 */
	protected static $cs_process_pages = array(
		self::PROCESS_STANDARD => array(
			self::PAGE_TYPE_CONFIRM => 'ent_online_confirm',
			self::PAGE_TYPE_AGREE => 'ent_online_confirm_legal',
		),
		self::PROCESS_CSO => array(
			self::PAGE_TYPE_CONFIRM => 'ent_cso_online_confirm',
			self::PAGE_TYPE_AGREE => 'ent_cso_online_confirm_legal',
		),
		self::PROCESS_COMBINED => array(
			self::PAGE_TYPE_CONFIRM => 'ent_online_combined',
			self::PAGE_TYPE_AGREE => 'ent_online_combined'
		)
	);

	/**
	 * Current OLPECash_CS_Config object
	 *
	 * @var OLPECash_CS_Config
	 */
	protected $config;
	
	/**
	 * CSO RPC PRPC Object
	 * 
	 * @var Prpc_Client2
	 */
	protected $rpc_cso;
	
	/**
	 * Token RPC PRPC Object
	 * 
	 * @var Prpc_Client2
	 */
	protected $rpc_token;
	
	/**
	 * Property Short
	 *
	 * @var string
	 */
	protected $property_short;
	
	/**
	 * Mode
	 *
	 * @var string
	 */
	protected $mode;
	
	/**
	 * Debug RPC calls
	 *
	 * @var bool
	 */
	protected $debug;
	
	/**
	 * Constructor
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @param bool $debug
	 * @return void
	 */
	public function __construct($property_short, $mode, $debug = FALSE)
	{
		$this->property_short = $property_short;
		$this->mode = $mode;
		$this->debug = $debug;
	}
	
	/**
	 * Get roll over eligibility for an application
	 *
	 * @param integer $application_id
	 * @return array
	 */
	public function getRolloverEligible($application_id)
	{
		$return = $this->getCSORPC()->getRolloverEligible($application_id);
		$this->validateReturnType($return, 'array');
		return $return;
	}
	
	/**
	 * Request to create a rollover for an application
	 *
	 * @param integer $application_id
	 * @return array
	 */
	public function requestRollover($application_id)
	{
		$return = $this->getCSORPC()->createRollover($application_id);
		$this->validateReturnType($return, 'array');
		return $return;
	}
	
	/**
	 * Request to create a roll-over for an application
	 *
	 * @param string $loan_type ECash loan type name short
	 * @param int $company_id ECash company ID
	 * @param string $fee_name Fee name for which to get the description
	 * @return array
	 */
	public function getCSOFeeDescription($loan_type, $company_id, $fee_name)
	{
		$return  = $this->getCSORPC()->getCSOFeeDescription($fee_name, $loan_type, $company_id);
		$this->validateReturnType($return, 'string');
		return $return;
	}
	
	/**
	 * Get ECash tokens by loan type
	 *
	 * @param integer $loan_type_id
	 * @return array
	 */
	public function getTokensByLoanTypeID($loan_type_id)
	{
		$return  = $this->getTokenRPC()->getTokensByLoanType($loan_type_id);
		$this->validateReturnType($return, 'array');
		return $return;
	}
	
	/**
	 * Get a PRPC CSO client instance
	 *
	 * @return Prpc_Client
	 */
	protected function getCSORPC()
	{
		if (!isset($this->rpc_cso))
		{
			$prpc_url = OLPECash_CS_Config::getCSORpcUrl($this->property_short, $this->mode);
			$this->rpc_cso = new Prpc_Client($prpc_url, $this->debug);
		}
		return $this->rpc_cso;
	}
	
	/**
	 * Get a PRPC CSO client instance
	 *
	 * @return Prpc_Client
	 */
	protected function getTokenRPC()
	{
		if (!isset($this->rpc_token))
		{
			$prpc_url = OLPECash_CS_Config::getTokenRpcUrl($this->property_short, $this->mode);
			$this->rpc_token = new Prpc_Client($prpc_url, $this->debug);
		}
		return $this->rpc_token;
	}
	
	/**
	 * Can a property short request a rollover
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return bool
	 */
	public static function canRollover($property_short, $mode)
	{
		return OLPECash_CS_Config::canRollover($property_short, $mode);
	}
		
	/**
	 * Can a property short get tokens from the Token RPC call
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return bool
	 */
	public static function tokenRpcReady($property_short, $mode)
	{
		return OLPECash_CS_Config::tokenRpcReady($property_short, $mode);
	}
		
	/**
	 * Determines if a property short/loan type is configured for CSO
	 *
	 * @param string $property_short
	 * @param string $loan_type_short ECash Loan Type Name Short
	 * @return bool
	 */
	public static function isCSO($property_short, $loan_type_short)
	{
		return self::getProcess($property_short, $loan_type_short, NULL) == self::PROCESS_CSO;
	}

	/**
	 * Get the CS Process based on the property short and loan type short
	 *
	 * @param string $property_short
	 * @param string $olp_process
	 * @return string
	 */
	public static function getProcess($property_short, $olp_process) 
	{
		// Resolve the property short to get the company name short
		$name_short = EnterpriseData::resolveAlias($property_short);
		if (!empty(self::$cs_process[$name_short][$olp_process]))
		{
			return self::$cs_process[$name_short][$olp_process];
		}
		if (!empty(self::$cs_process[$name_short]['default']))
		{
			return self::$cs_process[$name_short]['default'];
		}
		return self::PROCESS_STANDARD;
		
	}
	
	/**
	 * Get the page name by process and mode
	 *
	 * @param string $page_type
	 * @param string $process
	 * @return string
	 */
	public static function getPage($page_type, $process)
	{
		if (!isset(self::$cs_process_pages[$process]))
		{
			throw new InvalidArgumentException("Cannot get page. Process $process is invalid");
		}
		if (!isset(self::$cs_process_pages[$process][$page_type]))
		{
			throw new InvalidArgumentException("Cannot get page. Page type $page_type is not valid for process $process");
		}
		return self::$cs_process_pages[$process][$page_type];
	}
	
	/**
	 * Validate that an item is the expected type
	 * Throws a OLPECash_CS_InvalidReturnObject when there is a failure 
	 *
	 * @param mixed $item Return item to verify
	 * @param string $expected Expected type of the item
	 * @return bool
	 */
	protected function validateReturnType($item, $expected)
	{
		// Always default to FALSE
		$valid = FALSE;
		switch ($expected)
		{
			case 'int':
				$valid = is_int($item);
				break;
			case 'string':
				$valid = is_string($item);
				break;
			case 'array':
				$valid = is_array($item);
				break;
			case 'bool':
				$valid = is_bool($item);
				break;
			case 'float':
				$valid = is_float($item);
				break;
			case 'object':
				$valid = is_object($item);
				break;
			default:
				// Ensure that we won't throw an exception checking an
				// object type by making sure the item is an object
				// and the class specified exists
				$valid = (is_object($item)
							&& class_exists($expected, TRUE)
							&& is_a($item, $expected));
				break;
		}
		
		// If not valid, throw an invalid return object exception
		if (!$valid)
		{
			$message = "Invalid object returned.  Expected $expected and received ".gettype($return);
			$e = new OLPECash_CS_InvalidReturnObject($message);
			$e->setObject($return);
			throw $e;
		}
		return TRUE;
	}
}

?>
