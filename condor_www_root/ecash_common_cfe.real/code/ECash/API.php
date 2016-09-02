<?php

require_once 'security.8.php';

class ECash_API
{
	const INDEX_APPLICATION = 'Application';
	const INDEX_BUREAU_INQUIRY = 'BureauInquiry';
	const INDEX_CAMPAIGN_INFO = 'CampaignInfo';
	const INDEX_SITE = 'Site';
	const INDEX_DEMOGRAPHICS = 'Demographics';
	const INDEX_PERSONAL_REFERENCE = 'PersonalReference';
	const INDEX_DOCUMENT_EVENT = 'Document';
	const INDEX_LOAN_ACTIONS = 'LoanActions';
	const INDEX_LOAN_ACTION_HISTORY = 'LoanActionHistory';
	const INDEX_LOGIN = 'Login';
	const INDEX_COMMENT = 'Comment';
	const INDEX_CUSTOMER = 'Customer';
	const INDEX_CARD = 'Card';
	const INDEX_STATUS_HISTORY = 'StatusHistory';
	const INDEX_APPLICATION_CONTACT = 'ApplicationContact';
	const INDEX_ASYNCH_RESULT = 'AsynchResult';
	const INDEX_VEHICLE = 'Vehicle';

	protected $DIR_MODELS;
	protected $DIR_VALIDATION;

	private $required_data;
	private $site;

	protected $customer;
	protected $application;
	protected $inserts = array();
	protected $application_data;

	public function __construct()
	{
		$this->DIR_MODELS = substr( __FILE__ , 0 , strripos(__FILE__, "/") + 1) . 'Models/';
		$this->DIR_VALIDATION = substr(__FILE__, 0 , strripos(__FILE__, "/") + 1) . 'Validation/';
		//set the required data
		$this->required_data = array(
			self::INDEX_APPLICATION,
			self::INDEX_CAMPAIGN_INFO
		);
	}

	/**
	 * @param $mode string
	 * @param $enterprise string an enterprise level company (CLK)
	 * @param $company string an sub-level company (UFC)
	 */
	protected function loadConfig($mode, $enterprise, $company = NULL)
	{
		/**
		 * eCash supports only EXECUTION_MODE's internally, LIVE, RC, and LOCAL.
		 * Since we have a bunch of different environments, like the various QA
		 * environments we're going to try to load configs based on the name 
		 * that's being passed rather than trying to use the EXECUTION_MODE.
		 */
		
		$name = NULL;

		switch($mode)
		{
			case 'LIVE':
				$name = 'Live';
				break;

			case 'LOCAL':
				$name = 'Local';
				break;

			case 'QA':
			case 'QA_MANUAL':
				$name = 'QA_MANUAL';
				break;

			case 'QA_AUTOMATED':
				$name = 'QA_AUTOMATED';
				break;

			case 'RC':
			default:
				$name = 'RC';
				break;
		}
		require_once "{$enterprise}/Config/{$name}.php";
		if(!empty($company))
		{
			$company = strtolower($company);

			//Replacing constant with $enterprise GF #16842
			require_once "{$enterprise}/Config/{$company}.php";
			if(class_exists($enterprise . '_Config_' . $name))
			{
				ECash_Config::useConfig($enterprise . '_Config_' . $name,  strtoupper($company) . '_CompanyConfig');
			}
			else
			{
				ECash_Config::useConfig($enterprise . '_Config_' . EXECUTION_MODE,  strtoupper($company) . '_CompanyConfig');
			}
		}
		else
		{
			if(class_exists($enterprise . '_Config_' . $name))
			{
				ECash_Config::useConfig($enterprise . '_Config_' . $name);
			}
			else
			{
				ECash_Config::useConfig($enterprise . '_Config_' . EXECUTION_MODE);
			}
		}
	}

	public static function loadPaydateModelData(array &$api_app, array $olp_pd_model)
	{
		// day string to day int array
		$days = array('sun' => 1, 'mon' => 2, 'tue' => 3, 'wed' => 4, 'thu' => 5, 'fri' => 6, 'sat' => 7);

		if( isset($olp_pd_model['paydate_model']['day_of_week']) )
		{
			$api_app['day_of_week'] = $olp_pd_model['paydate_model']['day_of_week'] + 1;
		}
		elseif ( isset($olp_pd_model['day_string_one']) )
		{
			if( is_numeric( $olp_pd_model['day_string_one'] ) )
			{
				$api_app['day_of_week'] = $olp_pd_model['paydate_model']['day_string_one'] + 1;
			}
			else
			{
				$api_app['day_of_week'] = strtolower($days[strtolower($olp_pd_model['day_string_one'])]);
			}
		}

		if( isset($olp_pd_model['next_pay_date']) )
		{
		 	$api_app['last_paydate'] = strtotime($olp_pd_model['next_pay_date']);
			if (!$api_app['last_paydate'])
			{
				throw new Exception("Invalid last_paydate {$api_app['last_paydate']}");
			}
		}
		if( isset($olp_pd_model['day_int_one']) )
		{
		 	$api_app['day_of_month_1'] = $olp_pd_model['day_int_one'];
		}
		if( isset($olp_pd_model['day_int_two']) )
		{
			$api_app['day_of_month_2'] = $olp_pd_model['day_int_two'];
		}
		if( isset($olp_pd_model['week_one']) )
		{
			$api_app['week_1'] = $olp_pd_model['week_one'];
		}
		if( isset($olp_pd_model['week_two']) )
		{
			$api_app['week_2'] = $olp_pd_model['week_two'];
		}
	}

	public static function getBureauInquiryData(array $olp_dx_data)
	{
		foreach($olp_dx_data as $title => $dx_entry)
		{
			if(substr($title,0,6) != 'DATAX_') continue;
			$type = substr($title,6);

			$field_array = array();
			$bureau_model = ECash::getFactory()->getModel('Bureau');
			$bureau_model->loadBy(array('name_short' => 'datax'));
			$field_array['bureau_id'] = isset($bureau_model->bureau_id) ? $bureau_model->bureau_id : 0;
			$field_array['inquiry_type'] = strtolower( $type );
			$field_array['sent_package'] = $olp_dx_data['DATAX_' . $type]['sent_package'];
			$field_array['received_package'] = $olp_dx_data['DATAX_' . $type]['received_package'];

			$field_array['trace_info'] =  $olp_dx_data['track_hash'];
			$field_array['outcome'] = $olp_dx_data['DATAX_' . $type]['score'];

			$field_array['date_created'] = time();

			if( strlen( trim( $field_array['received_package'] ) ) > 0 )
			{
				return $field_array;
			}
		}
		return NULL;
	}

	public static function getApplicationStatusID($olp_status, $preact = FALSE)
	{
		$string = '';
		//Get the status id
		switch ($olp_status)
		{
			case 'verification':
			case 'underwriting':
			case 'fraud':
			case 'high_risk':
				if($preact) $olp_status = 'preact';
				$string .= "queued::{$olp_status}::applicant";
				break;
			case 'denied':
				$string .= "{$olp_status}::applicant";
				break;
			case 'pending':
			case 'agree':
			case 'confirmed':
				//preact only goes with the above statuses, but all are prospects
				if($preact) $olp_status = "preact_" . $olp_status;
			case 'confirm_declined':
			case 'disagree':
			case 'declined':
			default:
				$string .= "{$olp_status}::prospect";
				break;
		}

		$string .= '::*root';

		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		return $status_list->toId($string);
	}

	public static function getCardData(array $olp_card_data)
	{
		//  Exception Insert if application is filled out by CSR
		$data = array();
		$data['card_number'] = $olp_card_data['card_number'];
		$data['card_ref_id'] = $olp_card_data['card_ref_id'];
		$data['card_bin'] = $olp_card_data['card_bin'];
		$data['card_stock'] = $olp_card_data['card_stock'];
		$data['card_account_number'] = $olp_card_data['card_account_number'];

		return $data;
	}

	public static function getNewCustomerData($company_id, $agent_id, $ssn, $name_first, $name_last)
	{
		// create base username (flastname_)
		$username = strtoupper($name_first{0} . $name_last . '_');
		$username = preg_replace('/[^a-zA-Z0-9\-_]+/', '', $username);
	
		$customer_model = ECash::getFactory()->getModel('Customer');
		$num = $customer_model->getUsernameCount($username);

		// add the count at the end
		$username .= ++$num;

		// create a random password
		$clear_pass = 'cash' . substr(microtime(), - 3);
		$password = Security_8::Encrypt_Password($clear_pass);

		// prepare array to insert record
		$field_array = array();
		$field_array['date_created'] = time();
		$field_array['company_id'] = $company_id;
		$field_array['modifying_agent_id'] = $agent_id;
		$field_array['login'] =  $username;
		$field_array['password'] =  $password;
		$field_array['ssn'] = $ssn;
		return $field_array;
	}

	public static function getAgentID($login)
	{
		$agent_list = ECash::getFactory()->getReferenceList('Agent');
		return $agent_list->toId($login);
	}

	public static function getCompanyID($company_short)
	{
		$company_list = ECash::getFactory()->getReferenceList('Company');
		return $company_list->toId($company_short);
	}

	public static function getSiteID($license_key, $name)
	{
		$site_list = ECash::getFactory()->getReferenceList('Site');

		$site_id = $site_list->toId($license_key);
		
		if ($site_id === FALSE)
		{
			$site =  ECash::getFactory()->getModel('Site');
			$site->license_key = $license_key;
			$site->name = $name;
			$site->active_status = 'active';
			$site->insert();

			$site_id = $site->site_id;
		}
		return $site_id;
	}
	
	/**
	 * During an update, atleast for now, we're only worried about status
	 * and documents.
	 *
	 * @param unknown_type $app_model
	* @param array $application_data
	 */
	
    public function updateApplication($app_model, array $application_data)
    {
        $this->inserts = array();$application_data = $this->reorderArray($application_data);
        $this->application = $app_model;
        $this->inserts[self::INDEX_APPLICATION] = $app_model;

		$company = ECash::getFactory()->getCompanyById($this->application->company_id);
		
        ECash::setCompany($company);
        ECash::setAgent($this->getAgent($this->application->modifying_agent_id));
      	
		//We only want to overwrite the old 
		// data if we've not funded a loan
		// yet.
		if (empty($app_model->fund_actual))
		{
			$sync_columns = array(
				'date_fund_estimated',
				'date_first_payment',
				'fund_qualified',
				'finance_charge',
				'payment_total',
				'apr',
				'day_of_week',
				'last_paydate',
				'day_of_month_1',
				'day_of_month_2',
				'week_1',
				'week_2',
				'income_frequency',
				'paydate_model',
			);
			foreach($sync_columns as $col)
			{
				if (!empty($application_data[self::INDEX_APPLICATION][$col]))
				{
					$app_model->$col = $application_data[self::INDEX_APPLICATION][$col];
				}
			}
		}
		if (isset($application_data[self::INDEX_LOAN_ACTION_HISTORY]) && is_array($application_data[self::INDEX_LOAN_ACTION_HISTORY]))
		{
			foreach($application_data[self::INDEX_LOAN_ACTION_HISTORY] as $loan_action_data)
			{
				$model = $this->getModel(self::INDEX_LOAN_ACTION_HISTORY);
				$vals = $loan_action_data;
				$vals['application_id'] = $this->application->application_id;
				$model->loadBy($vals);
				if (!is_object($model) || !is_numeric($model->loan_action_history_id))
				{
					$this->loadModel(self::INDEX_LOAN_ACTION_HISTORY, $loan_action_data);	
				}
			}
		}
	    if (isset($application_data[self::INDEX_STATUS_HISTORY]) && is_array($application_data[self::INDEX_STATUS_HISTORY]))
    	{
			$model = $this->getModel(self::INDEX_STATUS_HISTORY);
			foreach ($application_data[self::INDEX_STATUS_HISTORY] as $k => $status_data)
			{
				if (!$model->getStatusExists(
							$this->application->application_id,
							$status_data['application_status_id'],
							$status_data['date_created'])
				   )
				{
					$this->loadModel(self::INDEX_STATUS_HISTORY, $status_data);
				}
			}
		}
       	if (isset($application_data[self::INDEX_DOCUMENT_EVENT]))
        {
        	if (isset($application_data[self::INDEX_DOCUMENT_EVENT]['company_id']))
        	{
        		$application_data[self::INDEX_DOCUMENT_EVENT] = array($application_data[self::INDEX_DOCUMENT_EVENT]);
        	}
        	foreach ($application_data[self::INDEX_DOCUMENT_EVENT] as $document_event)
        	{
        	$getby_vals = array(
	        		'company_id' => $document_event['company_id'],
	        		'application_id' => $document_event['application_id'],
	        		'document_list_id' => $document_event['document_list_id'],
	        		'transport_method' => $document_event['transport_method'],
	        		'agent_id' => $document_event['agent_id'],
	        		'archive_id' => $document_event['archive_id'],
	        		'document_event_type' => $document_event['document_event_type'],
        	);

        	// Make sure we don't insert the document twice.
			$model = $this->getModel(self::INDEX_DOCUMENT_EVENT);
			$model->loadBy($getby_vals);
       		if (!is_object($model) || !is_numeric($model->document_id))
       		{
	           		$this->loadModel(self::INDEX_DOCUMENT_EVENT, $document_event);
	        	}
        	}
   		}
   		if (isset($application_data[self::INDEX_CAMPAIGN_INFO]))
   		{
   			if (!is_array($application_data[self::INDEX_CAMPAIGN_INFO]))
   			{
   				$application_data[self::INDEX_CAMPAIGN_INFO] = array($application_data[self::INDEX_CAMPAIGN_INFO]);
   			}
   			foreach ($application_data[self::INDEX_CAMPAIGN_INFO] as $info_data)
   			{
   				$model = $this->getModel(self::INDEX_CAMPAIGN_INFO);
   				$getby_vals = $info_data;
   				unset($getby_vals['date_created']);
   				$getby_vals = array_filter($getby_vals);
   				if (!$model->loadBy($getby_vals) || !is_numeric($model->campaign_info_id))
   				{
   					$this->loadModel(self::INDEX_CAMPAIGN_INFO, $info_data);
   				}
   			}
		}
   		
        if (isset($this->inserts[self::INDEX_STATUS_HISTORY]))
   	    {
       	    $this->prepareStatus();
       	}
        $this->saveApp();
   	    if (isset($this->inserts[self::INDEX_STATUS_HISTORY]))
       	{
           	$this->updateStatuses();
        }
   	    $this->saveModels();
	}
    
	public function insertApplication(array $application_data)
	{
		$this->application_data = $application_data;
		$req_idx = array_flip($this->required_data);
		$missing_data = array_diff_key($req_idx, $application_data);

		if(count($missing_data))
		{
			throw new Exception('Data for '.implode(', ', array_keys($missing_data)).' is/are required.');
		}

		$application_data = $this->reorderArray($application_data);
		$this->inserts = array();
		foreach($application_data as $index => $data)
		{
			//put something in here so that if $data's indexes are
			//numeric and their values are arrays, then save multiple
			//of those types. This would be used for PersonalReference,
			//LoanActionHistory, etc.
			if(is_array($data) && is_numeric(key($data)))
			{
				foreach($data as $multi_row_data)
				{
					$this->loadModel($index, $multi_row_data);
				}
			}
			else
			{
				$this->loadModel($index, $data);
			}
		}
		if(isset($this->inserts[self::INDEX_CUSTOMER]))
			$this->saveCustomer();
		//prepare the status, incase there's several
		$this->prepareStatus();
		$this->saveApp();
		if(isset($this->inserts[self::INDEX_STATUS_HISTORY]))
			$this->updateStatuses();
		$this->saveModels();
		return $this->application->application_id;
	}
	public function getApplication()
	{
		return $this->application;
	}

	private function loadModel($index, $data)
	{

		$model = NULL;

		//site could be new or existing
		if($index == self::INDEX_SITE)
		{
			//try to get an existing model
			$model = $this->getSite($data['license_key']);
		}

		if($model === NULL)
		{
			$model = $this->getModel($index);
			foreach($data as $column => $value)
			{
				//echo 'Setting ', $column, ' to ', $value, ' on ', $index, PHP_EOL;
				//set the data on the model
				$model->{$column} = $value;
			}
		}

		$validator = $this->getValidator($index);

		//validate the model, $validator could be FALSE (don't check this model)
		if($validator !== FALSE && !$validator->validate($model))
		{
			throw new Exception(join('\n', $validator->getErrors()));
		}
		//echo "Adding index {$index}", PHP_EOL;
		if(isset($this->inserts[$index]))
		{
			if(!is_array($this->inserts[$index]))
			{
				$this->inserts[$index] = array($this->inserts[$index]);
			}
			$this->inserts[$index][] = $model;
		}
	   	else
		{
			$this->inserts[$index] = $model;
		}
	}

	protected function addRequiredIndex($index)
	{
		if(is_array($index))
		{
			foreach($index as $idx)
			{
				$this->required_data[] = $idx;
			}
		}
		else
		{
			$this->required_data[] = $index;
		}
	}

	protected function getSite($license_key)
	{
		$site_list = ECash::getFactory()->getReferenceList('Site');
		return $site_list->{$license_key};
	}

	protected function prepareStatus()
	{
		if(isset($this->inserts[self::INDEX_STATUS_HISTORY])
			&& is_array($this->inserts[self::INDEX_STATUS_HISTORY]))
		{
			$first_status = $this->inserts[self::INDEX_STATUS_HISTORY][0];
			$application = $this->inserts[self::INDEX_APPLICATION];
			$application->application_status_id = $first_status->application_status_id;
		}
	}

	protected function saveCustomer()
	{
		$this->customer = $this->inserts[self::INDEX_CUSTOMER];
		unset($this->inserts[self::INDEX_CUSTOMER]);

		if ($this->customer->isAltered())
		{
			$this->saveModel($this->customer);
		}
	}

	protected function saveApp()
	{
		$this->application = $this->inserts[self::INDEX_APPLICATION];
		unset($this->inserts[self::INDEX_APPLICATION]);
		$this->saveModel($this->application);
	}

	protected function updateStatuses()
	{
		//instead of inserting into status history, we need to update
		//the statuses on the application so the trigger runs.  Then
		//we'll update the statuses with the correct dates.
		$statuses = $this->inserts[self::INDEX_STATUS_HISTORY];
		unset($this->inserts[self::INDEX_STATUS_HISTORY]);

		if (is_array($statuses))
		{
			foreach($statuses as $index => $status)
			{
				//skip the first one, it was already inserted (via trigger) from the application insert
				if($index > 0)
				{
					$this->application->application_status_id = $status->application_status_id;
					$this->saveModel($this->application);
				}
				$status->setApplicationData($this->application);
				//echo 'Would update: StatusHistory', print_r($status, TRUE), PHP_EOL;
				$status->updateDateCreated();
			}
		}
		elseif (is_object($statuses))
		{
			$this->application->application_status_id = $statuses->application_status_id;
			$this->saveModel($this->application);
			$statuses->setApplicationData($this->application);
			$statuses->updateDateCreated();
		}
	}

	protected function saveModels()
	{
		$site = NULL;

		foreach($this->inserts as $model)
		{
			//put something in here so that if $this->inserts values
			//are arrays, then save multiple of those types. This
			//would be used for PersonalReference, LoanActionHistory,
			//etc.

			if(is_array($model))
			{
				foreach($model as $multi_model)
				{
					$this->saveModel($multi_model);
				}
			}
			else
			{
				$this->saveModel($model);
			}
		}
	}

	private function saveModel($model)
	{
		//I generally don't like these next few lines, as we've
		//created dependencies between the seperate models.  Better
		//would be to implement some sort of observer/listener to be
		//an arbitrator of exchanging common data between these
		//objects when it changes or becomes available. [JustinF]
		//disperse stuff like application_id, company_id, etc.
		if($model instanceof ECash_Models_ICustomerFriend
			&& $this->customer)
		{
			$model->setCustomerData($this->customer);
		}

		if($model instanceof ECash_Models_IApplicationFriend)
		{
			$model->setApplicationData($this->application);
		}

		/* @var $model CFE_Models_Application */
		$model->save();
	}

	protected function getModel($index)
	{
		return ECash::getFactory()->getModel($index);
	}

	protected function getModelClass($index)
	{
		include_once $this->DIR_MODELS . "{$index}.php";
		return "ECash_Models_{$index}";
	}

	protected function getValidator($index)
	{
		if($validator_name = $this->getValidatorClass($index))
			return new $validator_name();
		return FALSE;
	}

	protected function getValidatorClass($index)
	{
		$class = "ECash_Validation_{$index}";
		@include_once $this->DIR_VALIDATION . "{$index}.php";
		if(class_exists($class))
			return $class;
		return FALSE;
	}

	private function reorderArray($data)
	{
		$return_array = array();

		//do the required things first
		foreach($this->required_data as $index)
		{
			$return_array[$index] = $data[$index];
			unset($data[$index]);
		}

		//then the rest
		foreach($data as $index => $value)
		{
			$return_array[$index] = $value;
		}

		return $return_array;
	}

	public function loadLoanActionsModel($name_short)
	{
		$model_class = ECash::getFactory()->getModel('LoanActions');
		$model_class->loadBy(array('name_short' => $name_short));
		return $model_class;

	}
	protected function getAgent($id)
	{
		$a = ECash::getAgentById($id);
		try
		{
			$a->getModel();
		}
		catch (Exception $e)
		{
			throw new Exception('Invalid agent ID, '.$id);
		}
		return $a;
	}
	/**
	 * Get the company model to prime the ECash class
	 *
	 * @param int $id
	 * @return ECash_Models_Company
	 */
	protected function getCompany($id)
	{
		$c = ECash::getFactory()->getCompanyById($id);
		return $c;
	}
	/**
	 * Gets an ECash Business Ruleset by CFE ruleset ID
	 *
	 * @param int $cfe_ruleset_id
	 */
	protected function getRuleset($cfe_ruleset_id)
	{
		$query = "
			SELECT lt.loan_type_id,
				rs.rule_set_id
			FROM cfe_rule_set cr
				JOIN loan_type lt ON (lt.loan_type_id = cr.loan_type_id)
				JOIN rule_set rs ON (rs.loan_type_id = lt.loan_type_id)
			WHERE rs.active_status='active'
				AND rs.date_effective <= NOW()
				AND cr.cfe_rule_set_id = ?
      ORDER BY rs.date_effective DESC
      LIMIT 1
		";

		$db = ECash_Config::getMasterDbConnection();
		return $db->querySingleRow($query, array($cfe_ruleset_id));
	}
}

?>
