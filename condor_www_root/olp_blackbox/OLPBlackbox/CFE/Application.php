<?php
/**
 * Handles CFE Applications inside BlackBox?
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class OLPBlackbox_CFE_Application 
{
	/**
	 * Application data
	 *
	 * @var BlackBox_Data
	 */
	protected $data;
	
	/**
	 * Config object ot use
	 *
	 * @var OLPBlackbox_Config
	 */
	protected $config;
	
	/**
	 * Array containing all the enterprise data
	 * for the target.
	 *
	 * @var array
	 */
	protected $enterprise_data;
	
	/**
	 * A connection to LDB
	 *
	 * @var DB_IConnection_1
	 */
	protected $ldb;
	
	/**
	 * A connection to OLP
	 * 
	 * @var MySQL_4
	 */
	protected $olp;
	
	/**
	 * Consturct?
	 *
	 * @param Blackbox_Data $data Application Data
	 * @param Blackbox_IStateData $state_data State Data
	 */
	public function __construct(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->data = $data;
		$this->async_data = FALSE;
		$this->state_data = $state_data;
		$this->config = OLPBlackbox_Config::getInstance();
		$this->ldb = FALSE;
		$this->enterprise_data = EnterpriseData::getEnterpriseData(EnterpriseData::resolveAlias($this->state_data->target_name));
	}
	
	/**
	 * Set the config object to use.
	 *
	 * @param object $config The new config object ot use
	 * @return void void
	 */
	public function setConfig($config)
	{
		$this->config = $config;
	}
	
	/**
	 * Set an instance of LDB to use.
	 *
	 * @param DB_IConnection_1 $ldb New ldb connection to use
	 * @return void void
	 */
	public function setLDB(DB_IConnection_1 $ldb)
	{
		$this->ldb = $ldb;
	}
	
	/**
	 * Set the instance of OLP to use.
	 *
	 * @param MySQL_4 $olp New olp connection
	 * @return void void
	 */
	public function setOLP($olp)
	{
		$this->olp = $olp;
	}
	
	/**
	 * Return the ecash site id based on a license key. Will return false 
	 * if it can not find the license key in the ldb instance that is passed in
	 *
	 * @param DB_IConnection_1 $ldb_instance ldb connection
	 * @param string $license_key license key
	 * @return int site id
	 */
	public static function getECashEnterpriseSiteId(DB_IConnection_1 $ldb_instance, $license_key)
	{
		$query = 'SELECT 
			site_id
		FROM
			site
		WHERE
			license_key = ?
		';
		$return = FALSE;
		$stmt = $ldb_instance->queryPrepared($query, array($license_key));
		if (($row = $stmt->fetch(PDO::FETCH_OBJ)))
		{
			$return = $row->site_id;
		}
		return $return;
	}
	
	/**
	 * Get a company id based on property short from LDB.
	 * Returns false if it can not find the company.
	 *
	 * @param DB_IConnection $ldb_instance ldb instance
	 * @param string $property_short property_short
	 * @return int company_id
	 */
	public static function getECashCompanyId(DB_IConnection_1 $ldb_instance, $property_short)
	{
		$query = 'SELECT
				company_id
			FROM
				company
			WHERE
				name_short=?
			AND
				active_status = \'ACTIVE\'
			LIMIT 1
		';

		$stmt = $ldb_instance->prepare($query);
		$stmt->execute(array($property_short));
		if (($row = $stmt->fetch(PDO::FETCH_OBJ)))
		{
			$return = $row->company_id;
		}
		return $return;
	}
	
	/**
	 * Stores the asynch object into the OLP database. That table has
	 * application_id - bb_mode as a combined key. If it's a duplicate key
	 * match, it'll update that row. 
	 *
	 * @param object $olp_db Database connection to OLP
	 * @param int $application_id Application id to search for
	 * @param string $bb_mode The blackbox mode to store it in
	 * @param AsynchResult $asynch_result The result object to store
	 * @param string $db_name The database name. Wil ltry and pull it from olp_db if it's not set.
	 * @param int $target_id The id to save for a target
	 * @return void
	 */
	public static function saveAsynchResult($olp_db, $application_id, $bb_mode, $asynch_result, $db_name = NULL, $target_id)
	{
		$asynch_data = mysql_escape_string(gzcompress(serialize($asynch_result)));
		$application_id = mysql_escape_string($application_id);
		$bb_mode = mysql_escape_string($bb_mode);
		
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
				'$application_id',
				NOW(),
				'$asynch_data',
				'$bb_mode',
				'$target_id'
			)
			ON DUPLICATE KEY UPDATE
				asynch_result_object = VALUES(asynch_result_object)
		";
		
		if (empty($db_name))
		{
			$db_name = $olp_db->db_info['db'];
		}
		$olp_db->query($db_name, $query);
	}
	
	/**
	 * Returns the olp process for an application id
	 *
	 * @param object $olp_db OLP database connection to connect to
	 * @param int $application_id Application id to find process for
	 * @param string $db_name DB name to check. Will try to pull from olp_db object if not set
	 * @return string
	 */
	public static function getOLPProcess($olp_db, $application_id, $db_name = NULL)
	{
		$app_id = mysql_escape_string($application_id);
		$query = "SELECT 
			olp_process 
		FROM 
			application 
		WHERE 
			application_id='$app_id'
		";
		if (empty($db_name))
		{
			$db_name = $olp_db->db_info['db'];
		}
		
		$res = $olp_db->Query($db_name, $query);
		$return = FALSE;
		if (($row = $olp_db->Fetch_Object_Row($res)))
		{
			$return = $row->olp_process;
		}
		$olp_db->Free_Result($res);
		return $return;
	}
	
	/**
	 * Runs the CFE engine and stores it's result in the state data.
	 * 
	 * @return void
	 */
	public function runCFEEngine()
	{
		$cfe_db = $this->getLDBInstance();
		$company_id = self::getECashCompanyId($this->getLDBInstance(), $this->enterprise_data['property_short']);
		$cfe = new ECash_CFE_AsynchEngine($cfe_db, $company_id);
		$asynch_data = $this->mapToAsyncData();
		$debug = OLPBlackbox_Config::getInstance()->debug;
		if ($debug->debugSkipRule() || $debug->debugSkipRule(OLPBlackbox_DebugConf::CFE_RULES))
		{
			$is_test = TRUE;
		}
		else
		{
			$is_test = FALSE;
		}
		
		$cfe_result = $cfe->beginExecution($asynch_data, $is_test);
		$attr = $cfe_result->getAttributes();
		if (!empty($attr))
		{
			$_SESSION['cfe_attributes'] = $attr;
		}
		$this->state_data->addStateData(new OLPBlackbox_CFE_StateData(array('asynch_object' => $cfe_result)));
		
		self::saveAsynchResult($this->getOLPInstance(), $this->data->application_id, OLPBlackbox_Config::getInstance()->blackbox_mode, $cfe_result, NULL, $this->state_data->target_id);
	}
	
	
	
	/**
	 * Gets a DB_IConnection_1 object connected 
	 * to LDB.
	 *
	 * @return DB_IConnection_1
	 */
	protected function getLDBInstance()
	{
		if (!$this->ldb instanceof DB_IConnection_1)
		{
			$this->ldb = Setup_DB::Get_PDO_Instance('mysql', $this->config->mode, $this->enterprise_data['property_short']);
		}
		return $this->ldb;
	}
	
	/**
	 * Return an instance of an OLP connection
	 *
	 * @return MySQL_4
	 */
	protected function getOLPInstance()
	{
		if (!is_object($this->olp))
		{
			$this->olp = Setup_DB::Get_Instance('blackbox', $this->config->mode, $this->enterprise_data['property_short']);
		}
		
		return $this->olp;
	}
	
/**
	 * Take all the application data from blackbox and map it
	 * to an array of data that is acceptable for the CFE engine.
	 *
	 * @return Array The async data that is acceptable to CFE
	 */
	protected function mapToAsyncData()
	{
		$asynch_data = array(
			'ip_address'          => $this->data->client_ip_address,
			'name_first'          => $this->data->name_first,
			'name_last'          => $this->data->name_last,
			'email'                  => $this->data->email_primary,
			'phone_home'      => $this->data->phone_home,
			'phone_work'        => $this->data->phone_work,
			'phone_cell'          => $this->data->phone_cell,
			'phone_work_ext' => empty($this->data->ext_work) ? NULL : $this->data->ext_work,
			'call_time_pref'     => $this->data->best_call_time,
			'street'                  => $this->data->home_street,
			'unit'                     => $this->data->home_unit,
			'city'                      => $this->data->home_city,
			'state'                   => $this->data->home_state,
			'zip_code'             => $this->data->home_zip,
			'employer_name' => $this->data->employer_name,
			'date_hire'            => $this->data->date_hire,
			'legal_id_number' => $this->data->state_id_number,
			'legal_id_state'     => empty($this->data->state_issued_id) ? $this->data->home_state : $this->data->state_issued_id,
			'legal_id_type'      => 'dl',
			'income_direct_depost' => ((strtoupper($this->data->income_direct_deposit) == 'TRUE') ? 'yes' : 'no'),
			'income_source'   => $this->data->income_type,
			'income_frequency' => $this->data->income_frequency,
			'bank_name' => $this->data->bank_name,
			'bank_account_type' => $this->data->bank_account_type,
			'ssn' => $this->data->social_security_number,
			'dob' => $this->data->dob,
			'bank_aba' => $this->data->bank_aba,
			'bank_account' => $this->data->bank_account,
			'paydate_model' => $this->data->model_name,
			'olp_process' => self::getOLPProcess($this->getOLPInstance(), $this->data->application_id),
			'application_id' => $this->data->application_id,
			'track_id' => empty($_SESSION['statpro']['track_key']) ? NULL : $_SESSION['statpro']['track_key'],
			'phone_fax' => empty($this->data->phone_fax) ? NULL : $this->data->phone_fax,
			'application_type' => 'paperless',
			'income_monthly' => $this->data->income_monthly_net,
			'is_react' => $this->isReact() ? 'yes' : 'no',
			'pwadvid' => (isset($this->data->pwadvid)) ? $this->data->pwadvid : NULL,
			'enterprise_site_id' => self::getECashEnterpriseSiteId($this->getLDBInstance(), $this->enterprise_data['license'][$this->config->mode]),
		);
		return $asynch_data;
	}
	
	/**
	 * Determines whether this is a react application or not
	 *
	 * @return boolean
	 */
	protected function isReact()
	{
		$is_react = $this->state_data->is_react;
		return is_bool($is_react) ? $is_react : FALSE;
	}
}
?>
