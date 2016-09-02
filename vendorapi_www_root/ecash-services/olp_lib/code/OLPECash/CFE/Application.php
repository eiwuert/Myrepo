<?php

/**
 * Create a CFE Application and
 * handle it appropriately
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class OLPECash_CFE_Application
{
	const APPLOG_SUB_DIR = 'cfe';
	const APPLOG_SIZE_LIMIT = '1000000000';
	const APPLOG_FILE_LIMIT = '20';
	const APPLOG_ROTATE = FALSE;

	/**
	 * Is this a test app?
	 *
	 * @var boolean
	 */
	protected $is_test;

	/**
	 * Array of data containing
	 * the application
	 *
	 * @var Array
	 */
	protected $data;

	/**
	 * OLP database connection.
	 * Expects a MySQL_Wrapper around
	 * a MySQL_4 object
	 *
	 * @var MySQL_Wrapper
	 */
	protected $olp_db;

	/**
	 * LDB database connection.
	 *
	 * @var DB_Database_1
	 */
	protected $ldb_db;

	/**
	 * The already resolved property
	 * short.
	 *
	 * @var string
	 */
	protected $property_short;

	/**
	 * The execution mode
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * A Asynch Result object
	 *
	 * @var ECash_CFE_AsynchResult
	 */
	protected $asynch_result;

	/**
	 * Static array containing valid keys
	 * for CFE Applications
	 *
	 * @var Array
	 */
	protected static $valid_keys = array(
		'ip_address',        'name_first',            'name_last',
		'email',             'phone_home',            'phone_work',
		'phone_cell',        'phone_work_ext',        'call_time_pref',
		'street',            'unit',                  'city',
		'state',             'zip_code',              'employer_name',
		'date_hire',         'legal_id_number',       'legal_id_state',
		'legal_id_type',     'income_direct_deposit', 'income_source',
		'income_frequency',	 'bank_name',             'bank_account_type',
		'ssn',               'dob',                   'bank_aba',
		'bank_account',      'paydate_model', 	 	  'application_id',
		'phone_fax',         'application_type',      'income_monthly',
		'pwadvid',         	 'olp_process',           'track_key',
		'enterprise_site_id','is_react',              'is_title_loan',
		'vehicle_vin',       'vehicle_make',          'vehicle_year',
		'vehicle_type',      'vehicle_model',         'vehicle_style',
		'vehicle_series',    'vehicle_mileage',       'react_type',
		'esig_ip_address',
	);

	/**
	 * Construct the application with data
	 *
	 * @param string $mode
	 * @param string $property_short
	 * @param array $data
	 */
	public function __construct($mode, $property_short, array $data = NULL)
	{
		$this->is_test = FALSE;
		$this->mode = $mode;
		$this->property_short = EnterpriseData::resolveAlias($property_short);
		$this->data = array();
		if (is_array($data) && count($data))
		{
			$this->fromArray($data);
		}
		$this->asynch_result = FALSE;
	}

	/**
	 * Runs the CFE engines begin asynch
	 * process
	 *
	 * @return ECash_CFE_AsynchResult
	 */
	public function asynchBegin()
	{
		$ldb = $this->getLDB();
		$company_id = OLPECash_Util::getCompanyId($ldb, $this->property_short);
		$cfe = new ECash_CFE_AsynchEngine($ldb, $company_id);
		$this->asynch_result = $cfe->beginExecution($this->data, $this->isTest());
		return $this->asynch_result;
	}

	/**
	 * Return the current asynch result object
	 *
	 * @return ECash_CFE_AsynchResult|boolean
	 */
	public function getAsynchResult()
	{
		return $this->asynch_result;
	}

	/**
	 * Return the current asynch result object
	 *
	 * @return ECash_CFE_AsynchResult|boolean
	 */
	public function setAsynchResult(ECash_CFE_AsynchResult $result)
	{
		$this->asynch_result = $result;
	}

	/**
	 * Insert an AsynchResult object for this application.
	 *
	 * @param string $bb_mode
	 * @param string|NULL $target_name
	 * @return void
	 */
	public function saveResult($bb_mode = 'BROKER', $target_name = NULL)
	{
		if (is_null($target_name))
		{
			$target_name = $this->property_short;
		}
		if (!is_numeric($this->application_id))
		{
			throw new RuntimeException('Invalid application id.');
		}
		$asynch_data = mysql_escape_string(gzcompress(serialize($this->asynch_result)));
		$query = "
			INSERT INTO
				asynch_result
			(
				application_id,
				date_created,
				asynch_result_object,
				mode,
				target_id
			)
			VALUES
			(
				'$this->application_id',
				NOW(),
				'$asynch_data',
				'$bb_mode',
				(
					SELECT
						t.target_id
					FROM
						olp_blackbox.target as t
					JOIN
						olp_blackbox.blackbox_type as bt
					ON
						(bt.blackbox_type_id = t.blackbox_type_id)
					WHERE
						t.property_short = '$target_name'
					AND
						bt.name='CAMPAIGN'
					LIMIT 1
				)
			)
			ON DUPLICATE KEY UPDATE
				asynch_result_object = VALUES(asynch_result_object)
		";
		$olp = $this->getOLP();
		$olp->query($olp->db_info['db'], $query);
	}

	/**
	 * Returns a LDB Connection
	 *
	 * @return DB_Database_1
	 */
	protected function getLDB()
	{
		if (!isset($this->ldb_db))
		{
			$this->setLDB(Setup_DB::Get_PDO_Instance('mysql', $this->mode.'_READONLY', $this->property_short));
		}
		return $this->ldb_db;
	}

	/**
	 * Set the LDB Instance to Use
	 *
	 * @param MySQL_Wrapper $ldb
	 * @return void
	 */
	public function setLDB(DB_Database_1 $ldb)
	{
		$this->ldb_db = $ldb;
	}

	/**
	 * Returns a OLP Connection
	 *
	 * @return MySQL_Wrapper
	 */
	protected function getOLP()
	{
		if (!isset($this->olp_db))
		{
			$this->setOLP(Setup_DB::Get_Instance('BLACKBOX', $this->mode, $this->property_short));
		}
		return $this->olp_db;
	}

	/**
	 * Sets
	 *
	 * @param MySQL_Wrapper $olp
	 * @return void
	 */
	public function setOLP(MySQL_Wrapper $olp)
	{
		$this->olp_db = $olp;
	}

	/**
	 * Is this a test app?
	 *
	 * @param boolean $val The new value for test
	 * @return boolean
	 */
	public function isTest($val = NULL)
	{
		if (is_bool($val))
		{
			$this->is_test = $val;
		}
		return $this->is_test;
	}

	/**
	 * Sets each element in the array
	 * as data in the application
	 *
	 * @param array $data
	 * @return void
	 */
	public function fromArray(array $data)
	{
		foreach ($data as $key => $val)
		{
			$this->__set($key, $val);
		}
	}

	/**
	 * Fetch some data thats set.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return (isset($this->data[$key])) ? $this->data[$key] : NULL;
	}

	/**
	 * Set data in the app
	 *
	 * @param string $key
	 * @param mixed $val
	 * @return void
	 */
	public function __set($key, $val)
	{
		if (in_array($key, self::$valid_keys))
		{
			$this->data[$key] = $val;
		}
		else
		{
			throw new InvalidArgumentException("$key is not a valid CFE_Application key.");
		}
	}

	/**
	 * Take an array of data
	 * from OLP and turn it into a
	 * cfe application
	 *
	 * @param array $data
	 * @return OLPECash_CFE_Application
	 */
	public static function loadFromOLPArray(array $data)
	{
		$mode = $data['config']->mode;
		$property_short =  EnterpriseData::resolveAlias($data['property_short']);
		$ldb = Setup_DB::Get_PDO_Instance('mysql', $mode.'_READONLY', $property_short);
		$olp = Setup_DB::Get_Instance('BLACKBOX', $mode, $property_short);
		$apc = new App_Campaign_Manager($olp, $olp->db_info['db'], self::applog());

		$asynch_data = array(
			'ip_address'          => $data['client_ip_address'],
			'esig_ip_address'     => $data['esig_ip_address'],
			'name_first'          => $data['name_first'],
			'name_last'           => $data['name_last'],
			'email'               => $data['email_primary'],
			'phone_home'          => $data['phone_home'],
			'phone_work'          => $data['phone_work'],
			'phone_cell'          => $data->phone_cell,
			'phone_work_ext'      => empty($data['ext_work']) ? NULL : $data['ext_work'],
			'call_time_pref'      => $data['best_call_time'],
			'street'              => $data['home_street'],
			'unit'                => $data['home_unit'],
			'city'                => $data['home_city'],
			'state'               => $data['home_state'],
			'zip_code'            => $data['home_zip'],
			'employer_name'       => $data['employer_name'],
			'date_hire'           => $data['date_of_hire'],
			'legal_id_number'     => $data['state_id_number'],
			'legal_id_state'      => empty($data['state_issued_id']) ? $data['home_state'] : $data['state_issued_id'],
			'legal_id_type'       => 'dl',
			'income_direct_deposit' => ((strtoupper($data['income_direct_deposit']) == 'TRUE') ? 'yes' : 'no'),
			'income_source'       => $data['income_type'],
			'income_frequency'    => $data['income_frequency'],
			'bank_name'           => $data['bank_name'],
			'bank_account_type'   => $data['bank_account_type'],
			'ssn'                 => $data['social_security_number'],
			'dob'                 => $data['date_dob_y'].'/'.$data['date_dob_m'].'/'.$data['date_dob_d'],
			'bank_aba'            => $data['bank_aba'],
			'bank_account'        => $data['bank_account'],
			'paydate_model'       => $data['paydate_model']['model_name'],
			'application_id'      => $data['application_id'],
			'phone_fax'           => empty($data['phone_fax']) ? NULL : $data['phone_fax'],
			'application_type'    => 'paperless',
			'income_monthly'      => $data['income_monthly_net'],
			'pwadvid'             => (isset($data['pwadvid'])) ? $data['pwadvid'] : NULL,
			'olp_process'         => $apc->Get_Olp_Process($data['application_id']),
			'react_type'	      => $data['react_type'],
			'track_key'           => $data['track_key'],
			'enterprise_site_id' => self::getSiteId($ldb, $mode, $property_short),
			'is_title_loan' => FALSE,
		);
		$is_react = is_numeric($data['is_react']) ? (bool)$data['is_react'] : $data['is_react'];
		$asynch_data['is_react'] = (is_bool($is_react)) ? $is_react : FALSE;
		if (is_string($data['vehicle_make']) && !empty($data['vehicle_make']))
		{
			$asynch_data['is_title_loan'] = TRUE;
			$vehicle_data = array(
				'vehicle_vin'      => $data['vehicle_vin'],
				'vehicle_make'     => $data['vehicle_make'],
				'vehicle_year'     => $data['vehicle_year'],
				'vehicle_type'     => $data['vehicle_type'],
				'vehicle_model'    => $data['vehicle_model'],
				'vehicle_style'    => $data['vehicle_style'],
				'vehicle_series'   => $data['vehicle_series'],
				'vehicle_mileage'  => $data['vehicle_mileage']
			);
			$asynch_data = array_merge($asynch_data, $vehicle_data);
		}
		$return = new self($mode, $property_short, $asynch_data);
		$return->setOLP($olp);
		$return->setLDB($ldb);
		return $return;
	}
	/**
	 * Creates a new CFE_Application object and maps
	 * the data from blackbox
	 *
	 * @param string $mode
	 * @param Blackbox_Data $data
	 * @param string $track_key Passed in because blackbox sucks and keeps it in the config
	 * @param Blackbox_IStateData $state_data
	 * @return OLPECash_CFE_Application
	 */
	public static function loadByBlackboxData(
		$mode,
		Blackbox_Data $data,
		$track_key,
		Blackbox_IStateData $state_data
	)
	{
		$property_short = EnterpriseData::resolveAlias($state_data->campaign_name);
		$ldb = Setup_DB::Get_PDO_Instance('mysql', $mode.'_READONLY', $property_short);
		$olp = Setup_DB::Get_Instance('BLACKBOX', $mode, $property_short);
		$apc = new App_Campaign_Manager($olp, $olp->db_info['db'], self::applog());

		$asynch_data = array(
			'ip_address'          => $data->client_ip_address,
			'esig_ip_address'     => $data->esig_ip_address,
			'name_first'          => $data->name_first,
			'name_last'          => $data->name_last,
			'email'                  => $data->email_primary,
			'phone_home'      => $data->phone_home,
			'phone_work'        => $data->phone_work,
			'phone_cell'          => $data->phone_cell,
			'phone_work_ext' => empty($data->ext_work) ? NULL : $data->ext_work,
			'call_time_pref'     => $data->best_call_time,
			'street'                  => $data->home_street,
			'unit'                     => $data->home_unit,
			'city'                      => $data->home_city,
			'state'                   => $data->home_state,
			'zip_code'             => $data->home_zip,
			'employer_name' => $data->employer_name,
			'date_hire'            => $data->date_hire,
			'legal_id_number' => $data->state_id_number,
			'legal_id_state'     => empty($data->state_issued_id) ? $data->home_state : $data->state_issued_id,
			'legal_id_type'      => 'dl',
			'income_direct_deposit' => ((strtoupper($data->income_direct_deposit) == 'TRUE') ? 'yes' : 'no'),
			'income_source'   => $data->income_type,
			'income_frequency' => $data->income_frequency,
			'bank_name' => $data->bank_name,
			'bank_account_type' => $data->bank_account_type,
			'ssn' => $data->social_security_number,
			'dob' => $data->dob,
			'bank_aba' => $data->bank_aba,
			'bank_account' => $data->bank_account,
			'paydate_model' => $data->model_name,
			'application_id' => $data->application_id,
			'phone_fax' => empty($data->phone_fax) ? NULL : $data->phone_fax,
			'application_type' => 'paperless',
			'income_monthly' => $data->income_monthly_net,
			'pwadvid' => (isset($data->pwadvid)) ? $data->pwadvid : NULL,
			'olp_process' => $apc->Get_Olp_Process($data->application_id),
			'react_type' => $data['react_type'],
			'track_key' => $track_key,
			'enterprise_site_id' => self::getSiteId($ldb, $mode, $property_short),
			'is_title_loan' => FALSE,
		);
		$is_react = $state_data->is_react;
		$asynch_data['is_react'] = (is_bool($is_react)) ? $is_react : FALSE;
		if (is_string($data->vehicle_make) && !empty($data->vehicle_make))
		{
			$asynch_data['is_title_loan'] = TRUE;
			$vehicle_data = array(
				'vehicle_vin'          => $data->vehicle_vin,
				'vehicle_make'      => $data->vehicle_make,
				'vehicle_year'       => $data->vehicle_year,
				'vehicle_type'       => $data->vehicle_type,
				'vehicle_model'    => $data->vehicle_model,
				'vehicle_style'      => $data->vehicle_style,
				'vehicle_series'    => $data->vehicle_series,
				'vehicle_mileage' => $data->vehicle_mileage
			);
			$asynch_data = array_merge($asynch_data, $vehicle_data);
		}

		$return = new self($mode, $property_short, $asynch_data);
		$return->setOLP($olp);
		$return->setLDB($ldb);
		return $return;
	}

	/**
	 * Returns the eCash site id associated with a campaign
	 *
	 * @param DB_Database_1 $ldb DB connection to LDB
	 * @param string $mode Execution mode.
	 * @param string $campaign_name The property short of the campaign to get the license key for
	 * @return int
	 */
	protected static function getSiteId($ldb, $mode, $campaign_name)
	{
		$keys = EnterpriseData::getEnterpriseOption($campaign_name, 'license');

		$site_id = FALSE;
		if (is_array($keys))
		{
			if (isset($keys[$mode]))
			{
				$site_id = OLPECash_Util::getSiteId($ldb, $keys[$mode]);

			}
		}
		return $site_id;
	}

	/**
	 * Creates an applog object
	 *
	 * @return Applog_1
	 */
	protected static function applog()
	{
		// If we have no site config class
		// we don't care about the sitename,
		if (class_exists('SiteConfig', FALSE))
		{
			$site = SiteConfig::getInstance()->site_name;
		}
		else
		{
			$site = '';
		}
		return Applog_Singleton::Get_Instance(
			self::APPLOG_SUB_DIR,
			self::APPLOG_SIZE_LIMIT,
			self::APPLOG_FILE_LIMIT,
			$site,
			self::APPLOG_ROTATE
		);
	}
}
?>
