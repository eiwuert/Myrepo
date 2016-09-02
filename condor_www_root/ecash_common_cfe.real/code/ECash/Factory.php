<?php

/**
 * The base factory class for ecash.
 *
 * Each company should extend this class and place it in
 * $customer_dir/$customer_name/Factory.php and also be named accordingly.
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
class ECash_Factory
{
	/**
	 * @var string
	 */
	protected $customer_dir;

	/**
	 * @var string
	 */
	protected $customer_name;

	/**
	 * The path of the customer overrides.
	 *
	 * @var string
	 */
	protected $customer_path;

	/**
	 * @var DB_IConnection_1
	 */
	protected $master_db;

    /**
     * @param string $customer_dir customer module directory (must contain trailing slash)
     * @param string $customer_name name of customer namespace directory
     * @param DB_IDatbaseConfig_1 $master_config The master database to use for factory connections.
     */
    final protected function __construct($customer_dir, $customer_name, DB_IDatabaseConfig_1 $master_config)
    {
		$this->customer_dir = $customer_dir;
		$this->customer_name = $customer_name;
		$this->customer_path = $customer_dir . $customer_name . '/';
		$this->db_config = $master_config;
	}

	public function getDB()
	{
		if(empty($this->master_db))
		{
			$this->master_db = $this->db_config->getConnection();
			$exec_mode = getenv('ECASH_EXEC_MODE');
			if(!empty($exec_mode))
			{
				$time_zone = ECash_Config::getInstance()->TIME_ZONE;
				$tz_set = "SET time_zone = '{$time_zone}'";
				$this->master_db->exec($tz_set);
			}
		}
		return $this->master_db;
	}
    /**
     * @param string $customer_dir
     * @param string $customer_name
     * @param DB_IDatbaseConfig_1 $master_config The master database to use for factory connections.
     * @return ECash_Factory
     */
    public static function getFactory($customer_dir, $customer_name, DB_IDatabaseConfig_1 $master_config)
    {
		$factory_file = $customer_dir . '/' . $customer_name . '/Factory.php';
		$factory_class = $customer_name . '_Factory';

		if (file_exists($factory_file))
		{
			require_once($factory_file);
			return new $factory_class($customer_dir, $customer_name, $master_config);
		}
		return new self($customer_dir, $customer_name, $master_config);
    }

        /**
	 * @var ECash_Queues_QueueManager
	 */
	protected $queue_manager = NULL;

	/**
	 * @return ECash_Queues_QueueManager
	 */
	public function getQueueManager(DB_IConnection_1 $database = NULL)
    {
        if ($database === NULL) $database = $this->getDB();

		if ($this->queue_manager === NULL)
		{
			$class_name = $this->getClassString('Queues_QueueManager');
			$this->queue_manager = new $class_name($database);
		}
        return $this->queue_manager;
    }

    /**
     * @var ECash_Fraud_Manager
     */
    protected $fraud_manager = NULL;

    /**
     * @return ECash_Fraud_Manager
     */
    public function getFraudManager()
    {
		if ($this->fraud_manager === NULL)
		{
			$class_name = $this->getClassString("Fraud_Manager");
			$this->fraud_manager = new $class_name();
		}
		return $this->fraud_manager;
    }

    /**
     * @var ECash_LoanActions_Manager
     */
    protected $loanactions_manager = NULL;

    /**
     * @return ECash_LoanActions_Manager
     */
    public function getLoanActionsManager()
    {
		if ($this->loanactions_manager === NULL)
		{
			$class_name = $this->getClassString("LoanActions_Manager");
			$this->loanactions_manager = new $class_name();
		}
		return $this->loanactions_manager;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $application_id
     * @return unknown
     */
    public function getRenewalClassByApplicationID($application_id)
    {
    	$default_type = 'Renewal_CSO';
    	
    	$application =  ECash::getApplicationByID($application_id);
		$business_rules = new ECash_BusinessRulesCache(ECash_Config::getMasterDbConnection());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		$rule = $rules['renewals'];
		$renewal_type = $rule['renewal_class'];
    	$renewal_type = $renewal_type ? $renewal_type : $default_type;
    	$renew = $this->getClassString($renewal_type);
    	$renewal = new $renew;
    	return $renewal;
    }
    /**
     * Enter description here...
     *
     * @param unknown_type $loan_type_name_short
     * @param unknown_type $company_id
     * @return unknown
     */
    public function getRenewalClassByLoanType($loan_type_name_short,$company_id = null)
    {
    	$default_type = 'Renewal_CSO';
		$business_rules = new ECash_BusinessRulesCache(ECash_Config::getMasterDbConnection());
		$search = array();
		if($company_id)
		{
			$search['company_id'] = $company_id;
		}
		$search['name_short'] = $loan_type_name_short;
		$loan_type = ECash::getFactory()->getModel('LoanType');
		$loan_type->loadBy($search);
		$loan_type_id = $loan_type->loan_type_id;
		$rule_set_id = $business_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		$rule = $rules['renewals'];
		$renewal_type = $rule['renewal_class'];
    	$renewal_type = $renewal_type ? $renewal_type : $default_type;
    	$renew = $this->getClassString($renewal_type);
    	$renewal = new $renew;
    	return $renewal;
    }

    /**
     * @var ECash_Monitoring_Manager
     */
    protected $monitoring_manager = NULL;

    /**
     * @return ECash_Monitoring_Manager
     */
    public function getMonitoringManager()
    {
		if ($this->monitoring_manager === NULL)
		{
			$class_name = $this->getClassString("Monitoring_Manager");
			$this->monitoring_manager = new $class_name();
		}
		return $this->monitoring_manager;
    }

	/**
	 * @var ECash_Transport
	 */
	protected $transport = NULL;

	/**
	 * @return ECash_Transport
	 */
	public function getTransport()
	{
		if($this->transport == NULL)
		{
			$this->transport = new ECash_Transport();
		}
		return $this->transport;
	}

	/**
	 * @var ECash_Module
	 */
	protected $module = NULL;

	/**
	 * @return ECash_Module
	 */
	public function getModule()
	{
		if($this->module == NULL)
		{
			$this->module = new ECash_Module();
		}
		return $this->module;
	}

	/**
	 * @var ECash_ACL
	 */
	protected $acl = NULL;
	/**
	 * @return ECash_ACL
	 */
	public function getACL(DB_IConnection_1 $db = NULL)
	{
		if (!$db)
		{
			$db = $this->getDB();
		}

		if($this->acl == NULL)
		{
			if(class_exists('ECash_ACL'))
			{
				$this->acl = new ECash_ACL($db);
			}
			else
			{
				$this->acl = null;
			}
		}
		return $this->acl;
	}

	/**
	 * @var ECash_ACL
	 */
	protected $request = NULL;
	/**
	 * @return ECash_ACL
	 */
	public function getRequest()
	{
		if($this->request == NULL)
		{
			$this->request = new ECash_Request();
		}
		return $this->request;
	}

	/**
	 * Returns an application by company and application id.
	 *
	 * @param int $application_id
	 * @param int $company_id
	 * @param DB_IConnection_1 $database
	 * @return ECash_Application
	 */
    public function getApplication($application_id, $company_id, DB_IConnection_1 $database = NULL)
    {
                if ($database === NULL) $database = $this->getDB();

                return new ECash_Application($database, $application_id);
    }

    /**
     * @param string $model_name
     * @return DB_Models_WritableModel_1
     */
    public function getData($data_class_name, DB_IConnection_1 $database = NULL)
    {
                if ($database === NULL)
                {
                        $database = $this->getDB();
                }
                $data_class_name = $this->getClassString("Data_{$data_class_name}");
                return new $data_class_name($database);
    }

    /**
     * @param string $model_name
     * @param DB_IConnection_1 Optional override database
     * @return DB_Models_WritableModel_1
     */
	public function getModel($model_name, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		$class_name  = $this->getClassString("Models_{$model_name}");
		return new $class_name($database);
	}

	/**
	 * This convenience method is mostly for libraries that use the class name
	 * of a model, such as DB_Models_ModelList_1
	 *
	 * @params string $model_name
	 * @return string model class name
	 */
	public function getModelClass($model_name)
	{
		return $this->getClassString("Models_{$model_name}");
	}

    /**
     * Returns an instance of the given display class.
     *
     * @param string $display_name
     * @return mixed
     */
    public function getDisplay($display_name)
    {
		return $this->getClass("Display_{$display_name}");
    }

    /**
     * @param string $model_name
     * @return DB_Models_ReferenceModel_1
     */
    public function getReferenceModel($model_name, DB_IConnection_1 $database = NULL)
    {
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		$class_name = $this->getClassString("Models_Reference_{$model_name}");
		return new $class_name($database);
    }

    /**
     * @param string $class_name
     * @return mixed
     */
    public function getClass($class_name)
    {
        $class = $this->getClassString($class_name);
    	return new $class();
    }

    /**
     * @param string $class_name
     * @return string
     */
    public function getClassString($class_name)
    {
		$customer_class = $this->customer_name . '_' . $class_name;
		$customer_file = AutoLoad_1::classToPath($customer_class);

		if (file_exists($this->customer_dir . $customer_file))
		{
			include_once $this->customer_dir . $customer_file;
			return $customer_class;
		}
		return "ECash_{$class_name}";
    }

    /**
     * Returns a reference list iterator for the given reference model.
     *
     * @param string $model_name
     * @param array $selection_args
     * @return ECash_Models_Reference_List
     */
	public function getReferenceList($model_name, $database = NULL, $selection_args = array())
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}
	//	else
	//	{
	//		echo '<pre>' . print_r(debug_backtrace(),true);
	//		exit;
	//	}
		// Arguments:
		// Model name
		// Cache_IStore = null // No cache for now
		// prefetch = TRUE // Defaults to TRUE, but will switch to FALSE if selection args are passed
		// selection_args
		return new ECash_Models_Reference_List($this->getReferenceModel($model_name, $database), null, TRUE,  $selection_args);
		//return new DB_Models_ReferenceTable_1($this->getReferenceModel($model_name, $database), TRUE,  $selection_args);
	}

    /**
     * Returns a scheduling event of the given type
     *
     * @param string $event_name
     * @return ECash_Scheduling_Events_ISchedulable
     */
	public function getEvent($event_name)
	{
		$event_class_name = $this->getClassString("Scheduling_Events_{$event_name}");
		return new $event_class_name();
	}

	public function getCustomerBySSN($ssn, $company_id, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		return ECash_Customer::getBySSN($database, $ssn, $company_id);
	}

	public function getCustomerById($customer_id, $company_id, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		return ECash_Customer::getByCustomerId($database, $customer_id, $company_id);
	}

	public function getCustomerByApplicationId($application_id, $company_id, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		return ECash_Customer::getByApplicationId($database, $application_id, $company_id);
	}


	/**
	 * @param int $agent_id
	 * @param DB_IConnection_1 $database
	 * @return ECash_Agent
	 */
	public function getAgentById($agent_id, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		return ECash_Agent::getByAgentId($database, $agent_id);
	}

	public function getAgentBySystemLogin($system_name_short, $login, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		return ECash_Agent::getBySystemLogin($database, $system_name_short, $login);
	}

	public function getCompanyById($company_id, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		return new ECash_Company($database, $company_id);
	}

	public function getCompanyByNameShort($name_short, DB_IConnection_1 $database = NULL)
	{
		if ($database === NULL)
		{
			$database = $this->getDB();
		}

		$company_model = ECash::getFactory()->getModel('Company', $database);
		$company_model->loadBy(array('name_short' => $name_short));
		return new ECash_Company($database, $company_model->company_id);

	}


	protected $mailer = NULL;

	public function getMailer()
	{
		if ($this->mailer === NULL)
		{
			$this->mailer = new Mail_Trendex_1(strtolower(EXECUTION_MODE));
		}
		return $this->mailer;
	}
	/**
	 * @var Log_ILog_1
	 */
	protected $logger = NULL;

	/**
	 * @return Log_ILog_1
	 */
	public function getLog($Log_Name = null)
	{
		if(empty($Log_Name))
			$Log_Name = 'main';

		if ($this->logger[$Log_Name] === NULL)
		{
			//$this->logger = new Log_MultiLogger_1();
			//$this->logger->addLogger(new Log_SysLog_1(ECash_Config::getInstance()->LOG_ID));
			$this->logger[$Log_Name] = new Applog(APPLOG_SUBDIRECTORY.'/' . $Log_Name , APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);
		}
		return $this->logger[$Log_Name];
	}

    /**
     * @TODO this should return an API in company context
     * @return ECash_API_Information
     */
    public function getInformationAPI($application_id)
    {
            return new ECash_API_Information($application_id);
    }

    /**
     * Returns a new instance of a date normalizer.
     *
     * If you are looking for past dates you should pass the earliest date you
     * would retrieve in as the first parameter.
     *
     * @param int $earliest_date Unix timestamp
     * @return Date_Normalizer_1
     */
    public function getDateNormalizer($earliest_date = NULL)
    {
        return new Date_Normalizer_1(new Date_BankHolidays_1($earliest_date), $earliest_date);
    }
}

?>
