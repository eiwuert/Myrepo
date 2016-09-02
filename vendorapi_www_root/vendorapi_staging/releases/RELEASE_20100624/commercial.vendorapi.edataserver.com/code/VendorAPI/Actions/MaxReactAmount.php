<?php

require_once('crypt.3.php');

/**
 * maxReactAmount action.
 *
 * This class handles getting an new loan amount for a react application from vendor API. It runs the Qualify action and returns the result.
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Actions_MaxReactAmount extends VendorAPI_Actions_Base
{
	/**
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $application_factory;
	protected $validator;
	protected $cfe_rule_factory;

	/**
	 * @var VendorAPI_IApplication
	 */
	protected $application;
	protected $app_client;
	protected $provider;

	public function __construct(
		VendorAPI_IDriver $driver,
		VendorAPI_IValidator $validator,
		VendorAPI_IApplicationFactory $application_factory,
		VendorAPI_CFE_IRulesetFactory $cfe_rule_factory,
		VendorAPI_ITokenProvider $provider
		)
	{
		$this->setDriver($driver);
		$this->setValidator($validator);
		$this->setApplicationFactory($application_factory);
		$this->setCfeRuleFactory($cfe_rule_factory);
		$this->provider = $provider;
	}

	public function execute($application_id, $state_object = NULL)
	{
		$this->call_context->setApplicationId($application_id);

		if ($serialized_state == NULL)
		{
			$state_object = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state_object = $this->getStateObject($serialized_state);
		}

		$persistor = new VendorAPI_StateObjectPersistor($state_object);
		/* @var $application VendorAPI_IApplication */
		$application = $this->application_factory->getApplication($application_id, $persistor, $state_object);

		$data = $application->getData();
		$data['prev_max_qualify'] = $data['fund_qualified'];
		$data['fund_requested'] = 0;
		$data['fund_qualified'] = 0;
		$data['fund_actual'] = 0;
		$data['finance_charge'] = 0;
		$data['payment_total'] = 0;
		$data['is_react'] = 'yes';
			 
		if (!empty($data['ecash_application_id']))
		{
			$data['application_id'] = $data['ecash_application_id'];
			$state = $this->getStateObjectByApplicationID($data['ecash_application_id']);
		}
		else
		{
			$state = $this->application_factory->createStateObject($this->getCallContext());
		}

		$response_builder = new VendorAPI_ResponseBuilder();
		$response_builder->setState($state);
		$response_builder->addResult('target', $this->getCallContext()->getCompany());
		$response_builder->addResult('campaign', $data['campaign']);

		if (($data = $this->validate($data, $response_builder)) !== FALSE)
		{
			$state->track_key = $data['track_id'];
			$state->external_id = $data['external_id'];

			$space_key = $this->setSpaceKey($data['track_id'], $data['page_id'], $data['promo_id'], $data['promo_sub_code']);
			$state->space_key = $space_key;

			$state_persistor = new VendorAPI_StateObjectPersistor($state);
			$persistor = new VendorAPI_TemporaryPersistor();
			$blackbox_persistor = new VendorAPI_TemporaryPersistor();
			$this->application = $this->getApplicationFactory()->createApplication($persistor, $state, $this->getCallContext(), $data);

			$engine = $this->getCfeEngine(
				$this->application->getCfeContext($this->getCallContext()),
				$this->getCfeRuleFactory()->getRuleset($this->driver->getPostConfig())
			);

			$campaign_count = count($data['campaign_info']);
			$cfe_data = array(
				'client_url_root' => $campaign_count > 0 ? $data['campaign_info'][$campaign_count-1]['name'] : '',
				'military' => $this->isMilitary($data),
				'track_key' => $state->track_key,
				'space_key' => $state->space_key,
				'customer_history' => isset($data['customer_history']) ? $data['customer_history'] : array(),
				'driver' => $this->driver,
				'blackbox_config' => $this->getBlackboxConfig($data, $state, $blackbox_persistor),
				'denied' => FALSE,
				'application' => $this->application,
				'qualified' => TRUE,
				'winner' => FALSE,
				'save_now' => FALSE,
				'save_app' => TRUE,
				'qualify_info' => new VendorAPI_QualifyInfo(NULL, NULL, NULL, NULL, NULL, NULL, NULL),
				'idv_increase_eligible' => FALSE,
				'blackbox_state' => FALSE,
				'rework' => (isset($data['rework']) && $data['rework']),
				'rework_result' => FALSE,
				'loan_amount_desired' => is_null($data['loan_amount_desired']) ? FALSE : $data['loan_amount_desired'],
				'state_object' => $state,
				'comment' => NULL, // This is used for AMG. If it ever gets modified
				// by anyone else's post config the line marked below with [#38273] will
				// break if addComment isn't added to the subclass of VendorAPI_IApplication
				'vendor_customer_id' => isset($data['vendor_customer_id']) ? $data['vendor_customer_id'] : NULL, //[#52310] for agean's eUW service
				'fail_type' => new VendorAPI_Blackbox_FailType(), // Used to store Soft/Hard Fails
			);

			$results = $engine->executeEvent(
				'post',
				$cfe_data
			);
			// Merge them back to gether since CFE only returns changes
			$results = array_merge($cfe_data, $results);

			// Calculated Reacts - Update the application's qualify info if the
			// application is determined to be a react.
			// [#41293] - Loan Amounts desired wasn't being passed to calculateQualifyInfo
			$response_builder->addResult('amount', $results['qualify_info']->getLoanAmount());
			$response_builder->addResult('qualified', $results['qualified']);
			$response_builder->addResult('rework', $results['rework_result']);
		}
		$response = $response_builder->getResponse();
		return $response;
	}
	
	/**
	 * Run the validator and add any errors to the response
	 * @param array $data
	 * @param VendorAPI_ResponseBuilder $builder
	 * @return boolean
	 */
	protected function validate(array $data, VendorAPI_ResponseBuilder $builder)
	{
		if (!$this->getValidator()->validate($data))
		{
			$errors = $this->getValidator()->getErrors();
			$res_errors = new ArrayObject();
			$fail_reason_string = 'VL:';
			foreach ($errors as $error)
			{
				$fail_reason_string .= $error->field . ',';
				$res_errors->append(array('field' => $error->field, 'message' => $error->message));
			}
			
			$builder->addResult(
				'fail',
				array(
					'short' => trim($fail_reason_string, ',')
				)
			);

			$builder->setError($res_errors->getArrayCopy());
			return FALSE;
		}
		$filtered = $this->getValidator()->getFilteredData();
		return is_array($filtered) ? array_merge($data, $filtered) : $data;
	}

	/**
	 * Set the validator we're going to be using
	 * @param VendorAPI_IValidator $validator
	 * @return void
	 */
	public function setValidator(VendorAPI_IValidator $validator)
	{
		$this->validator = $validator;
	}

	/**
	 *  Return the validator in use for this
	 *  action.
	 * @return VendorAPI_IValidator
	 */
	public function getValidator()
	{
		return $this->validator;
	}

	/**
	 * Set the application factory
	 * @param VendorAPI_IApplicationFactory $factory
	 * @return void
	 */
	public function setApplicationFactory(VendorAPI_IApplicationFactory $factory)
	{
		$this->application_factory = $factory;
	}

	/**
	 * Return the cfe rule factory
	 * @return VendorAPI_CFE_RuleFactory
	 */
	public function getCfeRuleFactory()
	{
		return $this->cfe_rule_factory;
	}

	/**
	 * Set the cfe rule factory
	 * @param VendorAPI_CFE_IRuleFactory $factory
	 * @return void
	 */
	public function setCfeRuleFactory(VendorAPI_CFE_IRulesetFactory $factory)
	{
		$this->cfe_rule_factory = $factory;
	}

	/**
	 * Returns a cfe engine
	 * @param ECash_CFE_IContext $context
	 * @param array $ruleset
	 * @return ECash_CFE_Engine
	 */
	protected function getCfeEngine(ECash_CFE_IContext $context, array $ruleset)
	{
		$e = ECash_CFE_Engine::getInstance($context);
		$e->setRuleset($ruleset);
		return $e;
	}

	/**
	 * getBlackboxConfig
	 * @return VendorAPI_Blackbox_Config
	 */
	protected function getBlackboxConfig(array $data, VendorAPI_StateObject $state, VendorAPI_IModelPersistor $persistor)
	{
		$mode = $this->deriveBlackboxMode($data['olp_process']);

		$debug = NULL;
		if (isset($data['debug'])
			&& is_array($data['debug']))
		{
			$debug = new VendorAPI_Blackbox_DebugConfig($data['debug']);
		}
		elseif ($mode == VendorAPI_Blackbox_Config::MODE_ECASH_REACT)
		{
			// This is an unpleasant way of handling blackbox configuration
			// without doing it in the factory but will replicate the functionality
			// previously provided by OLP [AE]
			$debug =  new VendorAPI_Blackbox_DebugConfig();
			$debug->setFlag(VendorAPI_Blackbox_DebugConfig::DATAX, FALSE);
			$debug->setFlag(VendorAPI_Blackbox_DebugConfig::RULES, FALSE);
			$debug->setFlag(VendorAPI_Blackbox_DebugConfig::USED_INFO, FALSE);
		}

		$config = new VendorAPI_Blackbox_Config($debug);
		$config->blackbox_mode = $mode;
		$config->enterprise = $this->driver->getEnterprise();
		$config->company = $this->driver->getCompany();
		$config->campaign = $data['campaign'];
		$config->is_enterprise = $data['is_enterprise'];
		$config->is_react = $data['is_react'];
		$config->react_type = $data['react_type'];
		$config->price_point = isset($data['price_point']) ? $data['price_point'] : 0;
		$config->event_log = new VendorAPI_Blackbox_EventLog(
			$state,
			$data['external_id'],
			$config->campaign,
			VendorAPI_Blackbox_EventLog::ALL
		);
		$config->site_name = isset($data['site_name']) ? $data['site_name'] : NULL;
		$config->persistor = $persistor;
		$config->call_context = $this->call_context;
		return $config;
	}

	/**
	 * Determine the proper mode from the process type
	 * @param string $process The application process type (i.e., online_confirmation, ecashapp_react)
	 * @return string
	 */
	protected function deriveBlackboxMode($process)
	{
		switch (strtolower($process))
		{
			case 'ecashapp_react':
			case 'cs_react':
			case 'email_react':
				return VendorAPI_Blackbox_Config::MODE_ECASH_REACT;
		}
		return VendorAPI_Blackbox_Config::MODE_BROKER;
	}

	/**
	 * Sets the space key based on the given track and campaign info
	 *
	 * @param string $track_key
	 * @param int $page_id
	 * @param int $promo_id
	 * @param string $promo_sub_code
	 * @return string returns the generated space key
	 */
	protected function setSpaceKey($track_key, $page_id, $promo_id, $promo_sub_code)
	{
		if (($client = $this->driver->getStatProClient()))
		{
			$client->setTrackKeyValue($track_key);
			$client->setSpaceKeyFromCampaign(
				$page_id,
				$promo_id,
				$promo_sub_code
			);
			return $client->getSpaceKeyValue();
		}
	}

	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->application_factory;

	}

	private function convertPostRequestToArray($post_request) {
		$data = $this->collaspe_obj($post_request, array("personal_references", "campaigns", "debug",));

		$data["olp_process"] = strtolower($data["olp_process"]);
		if (isset($post_request->applicant->personal_references)) {
			if (is_object($post_request->applicant->personal_references))
				$post_request->applicant->personal_references = array($post_request->applicant->personal_references);
			$data['personal_reference'] = $this->obj_to_array($post_request->applicant->personal_references);
		}

		if (isset($post_request->campaigns)) {
			if (is_object($post_request->campaigns))
				$post_request->campaigns = array($post_request->campaigns);
			$data['campaign_info'] = $this->obj_to_array($post_request->campaigns);
			$campaign_count = count($data['campaign_info']);
			if ($campaign_count > 0) {
				$data['page_id'] = $data['campaign_info'][$campaign_count-1]['page_id'];
				$data['promo_id'] = $data['campaign_info'][$campaign_count-1]['promo_id'];
				$data['promo_sub_code'] = $data['campaign_info'][$campaign_count-1]['promo_sub_code'];
			}
		}
		$is_title = false;
		if(isset($post_request->vehicle))
		{
			foreach($post_request->vehicle as $element)
			{
				if(!empty($element))
				{
					$is_title = true;
				}	
			
			}

		}

		$data['is_title_loan'] = $is_title;

		if (isset($post_request->debug))
			$data['debug'] = $this->obj_to_array($post_request->debug);

		if (isset($data['military']))
		{
			if (strtoupper($data['military']) == 'FALSE')
			{
				$data['military'] = FALSE;
			}
			else
			{
				$data['military'] = (bool) $data['military'];
			}
		}

		$data['day_string_one'] = $data['day_of_week'];

		if (trim($data['dob']) != "") {
			$data['date_dob_y'] = date("Y", strtotime($data['dob']));
			$data['date_dob_m'] = date("m", strtotime($data['dob']));
			$data['date_dob_d'] = date("d", strtotime($data['dob']));
		}

		$data = $this->convert_dates($data, array("dob", "banking_start_date", "residence_start_date", "date_hire", "last_paydate"));

		$data['is_react'] = ($this->deriveBlackboxMode($data['olp_process']) == VendorAPI_Blackbox_Config::MODE_ECASH_REACT);
		$data['react_type'] = $data['react_type'];
		return $data;
	}

	private function convert_dates($array, $keys) {
		foreach($keys as $key) {
			if (isset($array[$key])) {
				$array[$key] = date("Y-m-d", strtotime($array[$key]));
			}
		}
		return $array;
	}

	private function collaspe_obj($obj, $excludeKeys) {
		$array = array();
		foreach($obj as $key => $val) {
			if (in_array($key, $excludeKeys)) continue;

			if (is_array($val) || is_object($val)) {
				$array = array_merge($array, $this->collaspe_obj($val, $excludeKeys));
			} else {
				$array[$key] = $val;
			}
		}
		return $array;
	}

	private function obj_to_array($obj) {
		$array = array();
		foreach($obj as $key => $val) {
			if (is_array($val) || is_object($val)) {
				$array[$key] = $this->obj_to_array($val);
			} else {
				$array[$key] = $val;
			}
		}
		return $array;
	}
	
	private function isMilitary(array $data)
	{
		if (isset($data['military'])) 
		{
			if (strtoupper($data['military']) == 'FALSE')
			{
				$military = FALSE;
			}
			else
			{
				$military = (bool) $data['military'];
			}
		} else {
			$military = FALSE;
		}
		
		return $military;
	} 
}
