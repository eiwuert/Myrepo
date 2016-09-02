<?php

require_once 'mode_test.php'; // For getting machine name

/** Class to handling initializing the Vendor API.
 *
 * How to use: Create a new instance with the property short of the company
 * you want to call. Requires that you pass in the mode. Application ID is
 * optional and is only required if you want the VendorAPI to handle state
 * objects for you.
 *
 * PRPC class objects are cached based upon the hash of the connection. At
 * the time of writing this, it hashes upon the property short and mode.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPECash_VendorAPI
{
	const CONNECTION_TIMEOUT = 22; // Seconds for the RPC timeout

	/**
	 * @var array
	 */
	protected static $prpc_client = array();

	/**
	 * Default URLS.
	 *
	 * @var array
	 */
	protected $default_url = array(
		'QA_MANUAL' => 'http://vendor-api-%%%API%%%.qa.tss/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
		'QA2_MANUAL' => 'http://vendor-api-%%%API%%%.qa2.tss/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
		'QA_AUTOMATED' => 'http://vendor-api-%%%API%%%.aqa.tss/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
		'QA_SEMIAUTOMATED' => 'http://vendor-api-%%%API%%%.saqa.tss/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
//		'LOCAL' => 'http://vendor_api_commercial.%%%MACHINENAME%%%.tss/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
		'LOCAL' => 'http://rc.vendorapi-%%%API%%%.edataserver.com/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
		'RC' => 'https://rc.vendorapi-%%%API%%%.edataserver.com/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
		'LIVE' => 'https://%%%API%%%.vendorapi.edataserver.com/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
		'STAGING' => 'https://%%%API%%%.vendorapi.edataserver.com/index.php?enterprise=%%%ENTERPRISE%%%&company=%%%COMPANY%%%&username=%%%USER%%%&password=%%%PASSWORD%%%',
	);

	/**
	 * Always lowercased.
	 *
	 * @var string
	 */
	protected $property_short;

	/**
	 * Always uppercased.
	 *
	 * @var string mode
	 */
	protected $mode;

	/**
	 * May not always be set.
	 *
	 * @var int
	 */
	protected $application_id;

	/**
	 * @var string
	 */
	protected $hash;

	/**
	 * @var Integer
	 */
	protected $target_id;

	public function __construct($mode, $application_id, $property_short, OLP_Factory $factory)
	{
		$this->mode           = $mode;
		$this->application_id = $application_id;
		$this->property_short = EnterpriseData::resolveAlias($property_short);
		$this->factory        = $factory;
	}

	/**
	 * Makes the call. Does NOT try to safely handle exceptions! Does handle
	 * sending and storing of state objects.
	 *
	 * @param string $method_name
	 * @param array $method_arguments
	 * @return mixed
	 */
	public function __call($method_name, $method_arguments)
	{
		if (!is_array($method_arguments)) $method_arguments = array();
		$data_sent = $method_arguments;
		// Merge in state object into method_arguments
		$state_object_model = $this->getStateObjectModel();
		if (!empty($state_object_model->state_object))
		{
			$data_sent['state_object'] = $state_object_model->state_object;
		}


		// Make the call
		$response_time = microtime(TRUE);
		try
		{
			$data_received = $this->call($method_name, $data_sent);
			$response_time = microtime(TRUE) - $response_time;
		}
		catch (Exception $e)
		{
			// If an exception occurred, marked the log as failed.
			// Record the exception error as the response.
			$response_time = microtime(TRUE) - $response_time;
			$this->storePackets($method_name, $method_arguments, $e->getMessage(), $response_time, FALSE);

			throw $e;
		}

		// Pull state object out and save it
		if (isset($data_received['state_object']) 
			&& $data_sent['state_object'] != $data_received['state_object'])
		{
			// Pull state object out and save it
			if (isset($data_received['state_object']) && $data_received['outcome'])
			{
				$state_object_model->state_object = $data_received['state_object'];
				$state_object_model->save();
				unset($data_received['state_object']);
			}
		}

		// Store the raw data (sans state_object)
		$this->storePackets($method_name, $method_arguments, $data_received, $response_time, TRUE);

		return $data_received;
	}

	/**
	 * Executes the call.
	 *
	 * @param string $method_name
	 * @param array $method_arguments
	 * @return mixed
	 */
	protected function call($method_name, $method_arguments)
	{
		return call_user_func_array(array($this->getConnection(), $method_name), $method_arguments);
	}


	/**
	 * Loads state object.
	 *
	 * @return object
	 */
	protected function getStateObjectModel()
	{
		$state_obj = $this->factory->getModel('VendorStateObject');
		if (!$state_obj->loadByApplicationTarget($this->application_id, $this->getTargetId()))
		{
			$state_obj->application_id = $this->application_id;
			$state_obj->target_id      = $this->getTargetId();
		}
		return $state_obj;
	}


	/**
	 * Gets an instance of memcache.
	 *
	 * @return Cache_Memcache
	 */
	protected function getMemcache()
	{
		return Cache_OLPMemcache::getInstance();
	}

	/**
	 * Tool to make a memcache key.
	 *
	 * @return string
	 */
	protected function getMemcacheKey()
	{
		$key = sprintf(
			'vendor_api/state/%s/%d',
			$this->mode,
			$this->application_id
		);

		return $key;
	}

	/**
	 * Stores the sent and received packet.
	 *
	 * @param string $method_name
	 * @param mixed $data_sent
	 * @param mixed $data_received
	 * @param float $response_time
	 * @param bool $success
	 * @return bool
	 */
	protected function storePackets($method_name, $data_sent, $data_received, $response_time, $success)
	{
		$result = FALSE;

		if ($this->application_id)
		{
			$log = $this->factory->getReferencedModel('VendorApiLog');

			$log->application_id = $this->application_id;
			$log->property_short = $this->property_short;
			$log->method_name = $method_name;
			$log->data_sent = $data_sent;
			$log->data_received = $data_received;
			$log->response_time = $response_time;
			$log->success = $success;

			$result = $log->save();
		}

		return $result;
	}


	/**
	 * For storing the connection, convert the data into a string.
	 *
	 * @param string $property_short
	 * @param string $mode
	 */
	protected function hashConnection($property_short, $mode)
	{
		return "{$property_short}:{$mode}";
	}

	/**
	 * Gets a Rpc connection to the VendorAPI.
	 *
	 * @return Rpc_Client_1
	 */
	protected function getConnection()
	{
		if (!$this->hash)
		{
			$this->hash = $this->hashConnection($this->property_short, $this->getMode());
		}

		if (!isset(self::$prpc_client[$this->hash]))
		{
			$url = $this->getUrl();

			self::$prpc_client[$this->hash] = new Rpc_Client_1($url, self::CONNECTION_TIMEOUT, self::CONNECTION_TIMEOUT);
		}

		return self::$prpc_client[$this->hash];
	}

	protected function getTargetId()
	{
		if (!is_numeric($this->target_id))
		{
			$type_ref   = $this->factory->getBlackboxModelFactory()->getReferenceTable('BlackboxType');
			$target_ref = $this->factory->getBlackboxModelFactory()->getReferenceTable(
				'TargetPropertyShort',
				FALSE,
				array('blackbox_type_id' => $type_ref->toId('TARGET')));
			$this->target_id = (int)$target_ref->toId($this->property_short);
		}
		return $this->target_id;
	}

	/**
	 * Gets the URL of the vendor we will call.
	 *
	 * @return string
	 */
	protected function getUrl()
	{
		$property_short = strtolower($this->property_short);
		// Make sure property_short is enterprise, otherwise we fail.
		if (!EnterpriseData::isEnterprise($property_short))
		{
			throw new Exception(sprintf(
				'Property short "%s" is not enterprise.',
				$this->property_short
			));
		}

		// Load default urls
		$urls = $this->default_url;

		// Overwrite with any from enterprise data
		$enterprise_data = EnterpriseData::getEnterpriseData($property_short);
		$enterprise = isset($enterprise_data['vendor_api_enterprise'])
			?  $enterprise_data['vendor_api_enterprise']
			: strtolower(EnterpriseData::getCompany($this->property_short));

		// Check that we have a url for this mode
		if (!isset($urls[$this->getMode()]) || !$urls[$this->getMode()])
		{
			throw new Exception(sprintf(
				'Property short "%s" does not contain a VendorAPI url for mode "%s"',
				$property_short,
				$this->getMode()
			));
		}

		// Replace tokens
		$url = str_replace(
			array(
				'%%%API%%%',
				'%%%ENTERPRISE%%%',
				'%%%COMPANY%%%',
				'%%%MACHINENAME%%%',
				'%%%USER%%%',
				'%%%PASSWORD%%%',
			),
			array(
				($enterprise === 'clk' ? 'amg' : 'commercial'),
				$enterprise,
				$property_short,
				Mode_Test::Get_Local_Machine_Name(),
				($enterprise === 'clk' ? 'olp_user' : 'api_user'),
				($enterprise === 'clk' ? '0L9_7u53R' : 'api_pass'),
			),
			$urls[$this->getMode()]
		);

		return $url;
	}

	/**
	 * Returns the mode.
	 *
	 * @return string
	 */
	protected function getMode()
	{
		$mode = strtoupper(OLP_Environment::getOverrideEnvironment($this->mode));
		
		return $mode;
	}

	/**
	 * Converts OLP data into VendorAPI/LDB data array
	 *
	 * @param object $data
	 * @return array
	 */
	public static function toECashArray($data)
	{
		// maps OLP data to the eCash equivalent required by the eCash API
		// OLP => eCash
		$ecash_data_map = array(
			'first_name'				=> 'name_first',
			'last_name'					=> 'name_last',
			'middle_name'				=> 'name_middle',
			'home_street'				=> 'street',
			'home_unit'					=> 'unit',
			'home_city'					=> 'city',
			'home_state'				=> 'state',
			'home_zip'					=> 'zip_code',
			'ext_work'					=> 'phone_work_ext',
			'email_primary'				=> 'email',
			'state_id_number'			=> 'legal_id_number',
			'state_issued_id'			=> 'legal_id_state',
			'react_type'				=> 'react_type',
			'react_app_id'				=> 'react_application_id',
			'income_monthly_net'		=> 'income_monthly',
			'income_type'				=> 'income_source',
			'model_name'				=> 'paydate_model',
			'social_security_number'	=> 'ssn',
			'client_ip_address'			=> 'ip_address',
			'week_one'					=> 'week_1',
			'week_two'					=> 'week_2',
			'day_int_one'				=> 'day_of_month_1',
			'day_int_two'				=> 'day_of_month_2',
			'last_pay_date'				=> 'last_paydate',
			'track_key'					=> 'track_id',
			'work_title'				=> 'job_title',
			'date_of_hire'				=> 'date_hire',

			// @todo probably better represented as an array?
			'vehicle_vin' => 'vin',
			'vehicle_make' => 'make',
			'vehicle_year' => 'year',
			'vehicle_type' => 'type',
			'vehicle_model' => 'model',
			'vehicle_style' => 'style',
			'vehicle_series' => 'series',
			'vehicle_mileage' => 'mileage',
			'vehicle_license_plate' => 'license_plate',
			'vehicle_color' => 'color',
			'vehicle_value' => 'value',
			'vehicle_title_state' => 'title_state',
			'customer_id' => 'customer_id'
		);

		foreach ($data as $key => $value)
		{
			if (array_key_exists($key, $ecash_data_map))
			{
				$ecash_data[$ecash_data_map[$key]] = $data[$key];
			}
			else
			{
				$ecash_data[$key] = $value;
			}
		}


		if (!isset($ecash_data['legal_id_state']) && isset($ecash_data['state']))
		{
			$ecash_data['legal_id_state'] = $ecash_data['state'];
		}

		$ecash_data['day_of_week'] = $ecash_data['day_string_one'];

		$ecash_data['income_direct_deposit'] = (strcasecmp($data['income_direct_deposit'], 'TRUE') == 0);

		if (!isset($data['dob']))
		{
			$ecash_data['dob'] = sprintf(
				'%d/%d/%d',
				$data['date_dob_m'],
				$data['date_dob_d'],
				$data['date_dob_y']
			);
		}
		$ecash_data['age'] = Date_Util_1::getAge(strtotime($ecash_data['dob']));

		$ecash_data['personal_reference'] = array();

		for ($i = 1; $i <= 10; $i++)
		{
			$index = str_pad($i, 2, '0', STR_PAD_LEFT);

			if (!empty($data["ref_{$index}_name_full"]))
			{
				$ecash_data['personal_reference'][] = array(
					'name_full' => $data["ref_{$index}_name_full"],
					'phone_home' => $data["ref_{$index}_phone_home"],
					'relationship' => $data["ref_{$index}_relationship"],
				);
			}
		}

		$ecash_data['paydates'] = is_array($data['paydates']) ? array_values($data['paydates']) : array();

		return $ecash_data;
	}

	/**
	 * Is the OLP process a react for a fail call.
	 * Fail calls need to send is_react based on the OLP process and not whether or not an application
	 * is actually a react.  
	 *
	 * @param string $process
	 * @return bool
	 */
	public static function isProcessReactForFail($process)
	{
		return in_array(strtolower($process), array('ecashapp_react', 'cs_react', 'email_react'));
	}
}
