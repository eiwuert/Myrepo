<?php

require_once('crypt.3.php');

/**
 * Post action.
 *
 * This class handles posting to the vendor API. It runs both the Qualify action as well as saving the application
 * and associated data to the database.
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Actions_Post extends VendorAPI_Actions_Base
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
		WebServices_Client_AppClient $app_client = NULL,
		VendorAPI_ITokenProvider $provider
		)
	{
		$this->setDriver($driver);
		$this->setValidator($validator);
		$this->setApplicationFactory($application_factory);
		$this->setCfeRuleFactory($cfe_rule_factory);
		$this->setAppClient($app_client);
		$this->provider = $provider;
	}

	public function execute($data, $state_object = NULL)
	{
		if (is_object($data)) $data = $this->convertPostRequestToArray($data);

		// prevent any reference to application ID
		if (isset($data['application_id']))
		{
			$data['external_id'] = $data['application_id'];
			unset($data['application_id']);
		}


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

			$this->getDriver()->getTimer()->setExternalId($data['external_id']);

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

			$this->getDriver()->getTimer()->start(__CLASS__ . "::" . __METHOD__ . ":: ECash_CFE_Engine::executeEvent()");
			$results = $engine->executeEvent(
				'post',
				$cfe_data
			);
			$this->getDriver()->getTimer()->stop(__CLASS__ . "::" . __METHOD__ . ":: ECash_CFE_Engine::executeEvent()");

			$this->getDriver()->getTimer()->start(__CLASS__ . "::" . __METHOD__ . ":: Post Processing");
			// Merge them back to gether since CFE only returns changes
			$results = array_merge($cfe_data, $results);
			if ($results['blackbox_state'])
			{
				$this->processWinner($state, $results['blackbox_state'], $response_builder, $results['winner']);
			}
			if (!is_null($results['comment']))
			{
				// [#38273] See comment above.
				$this->application->addComment($this->getCallContext(), $results['comment']);
			}
			// Calculated Reacts - Update the application's qualify info if the
			// application is determined to be a react.
			// [#41293] - Loan Amounts desired wasn't being passed to calculateQualifyInfo

			$response_builder->addResult('amount', $results['qualify_info']->getLoanAmount());
			$response_builder->addResult('qualified', $results['qualified']);
			$response_builder->addResult('rework', $results['rework_result']);
			$this->getDriver()->getTimer()->stop(__CLASS__ . "::" . __METHOD__ . ":: Post Processing");

			if($results['save_now'] || $results['qualified'])
			{
				$state->external_id = $data['external_id'];
				$this->getDriver()->getTimer()->start(__CLASS__ . "::saveToAppService()");
				$application_id = $this->saveToAppService($state, $this->application, $this->getAppClient(), $data, $blackbox_persistor);
				$this->getDriver()->getTimer()->stop(__CLASS__ . "::saveToAppService()");
				$this->getApplicationFactory()->populateServerIp($application_id);
			}
			elseif (empty($data['ecash_application_id']))
			{
				$this->getDriver()->getTimer()->start(__CLASS__ . "::saveUnpurchasedLeadToAppService()");
				$application_id = $this->saveUnpurchasedLeadToAppService($state, $this->application, $this->getAppClient());
				$inquiry = new ECash_VendorAPI_BureauInquiry();
				$data['application_id'] = $application_id;
				$inquiry->sendInquiryToAppService($state, $data, $this->driver->getInquiryClient(), $blackbox_persistor);
				$this->getDriver()->getTimer()->stop(__CLASS__ . "::saveUnpurchasedLeadToAppService()");
			}
			else
			{
				$this->getDriver()->getTimer()->start(__CLASS__ . "::saveAlreadyRunAndFailedLeadToAppService()");
				$application_id = $data['ecash_application_id'];
				$inquiry = new ECash_VendorAPI_BureauInquiry();
				$data['application_id'] = $application_id;
				$inquiry->sendInquiryToAppService($state, $data, $this->driver->getInquiryClient(), $blackbox_persistor);
				$this->getDriver()->getTimer()->stop(__CLASS__ . "::saveAlreadyRunAndFailedLeadToAppService()");
				//$this->getApplicationFactory()->populateServerIp($application_id);
			}
			$blackbox_persistor->updateApplicationId($application_id);
			$blackbox_persistor->saveTo($state_persistor);
			$response_builder->addResult('application_id', $application_id);

			// post xml will trigger this at the appropriate spot
			if (!empty($results['save_loan_actions'])
				&& isset($state->loan_actions))
			{
				$this->application->handleLoanActions($state->loan_actions, $this->getCallContext());
				$this->application->handleTriggers();
				unset($state->loan_actions);
			}
			$this->application->save($state_persistor, $results['save_app']);
			$this->saveApplication($results['save_now'], $state);

			$this->getDriver()->getTimer()->setApplicationId($application_id);
			$this->getDriver()->getTimer()->setExternalId($state->external_id);

			$price_point = $results['blackbox_state']->lead_cost;
			if ($results['qualified'])
			{
				$debugs = "";
				if (isset($data['debug'])) {
					if ($data['debug']['NO_CHECKS']) $debugs .= "&no_checks=1";
					if ($data['debug']['DATAX_FRAUD']) $debugs .= "&datax_fraud=1";
				}

				$site_url = $this->driver->getEnterpriseSiteURL();

				$encoded_app_id = urlencode(base64_encode($application_id));

				$tokens = $this->provider->getTokens($this->application, TRUE, $loan_amount); 
				$response_builder->addResult('redirect_url', $tokens['eSigLink']);
			}
			else
			{

				$fail_type   = $results['fail_type']->getState();

				if($fail_type == 'FAIL_COMPANY' || $fail_type == 'FAIL_ENTERPRISE')
				{
					$price_point = 0;
				}

				$response_builder->addResult('fail_type', $fail_type);
			}
		}
		$response_builder->addResult('price_point', $price_point);
		$response = $response_builder->getResponse();
		return $response;
	}

	/**
	 * Set blackbox state stuff into the app
	 *
	 * @return void
	 */
	public function processWinner(VendorAPI_StateObject $state_object, VendorAPI_Blackbox_StateData $bb_state, VendorAPI_ResponseBuilder $response_builder, $winner = FALSE)
	{
		/* @var $winner VendorAPI_Blackbox_Winner */
		$state_object->uw_provider = $bb_state->uw_provider;
		$state_object->uw_track_hash = $bb_state->uw_track_hash;
		$state_object->uw_decision = $bb_state->uw_decision;
		$state_object->lead_cost = $bb_state->lead_cost;

		if ($winner instanceof VendorAPI_Blackbox_Winner)
		{
			$state_object->loan_actions = $winner->getLoanActions();
			$state_object->triggers = $winner->getTriggers();
		}

		if (($hist = $bb_state->customer_history) instanceof ECash_CustomerHistory)
		{
			/* @var $hist ECash_CustomerHistory */
			$is_react = $hist->getIsReact($this->driver->getCompany());
			$response_builder->addResult('is_react', $is_react);

			if ($is_react)
			{
				$react_id = $hist->getReactID($this->driver->getCompany());
				$response_builder->addResult('react_application_id', $react_id);

				$this->application->setIsReact($react_id, $this->getCallContext());
			}
		}

		$fail_reason = $bb_state->failure_reason;
		if ($fail_reason instanceof VendorAPI_Blackbox_FailureReason)
		{
			$response_builder->addResult(
				'fail',
				array(
					'short' => $fail_reason->short(),
					'comment' => $fail_reason->comment()
				)
			);
		}

		$response_builder->addResult('fail_callback', $bb_state->adverse_action != NULL);
		$state_object->adverse_action = $bb_state->adverse_action;

		if (!empty($bb_state->adverse_action))
		{
			$this->hitStat($bb_state->adverse_action, $state_object->track_key, $state_object->space_key);
		}
	}

	/**
	 * Saves an application
	 * @param boolean $save_now
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	protected function saveApplication($save_now, VendorAPI_StateObject $state)
	{
		if ($save_now)
		{
			$dao = new ECash_VendorAPI_DAO_Application($this->driver);
			$dao->save($state);
		}else{
		}

		$this->saveState($state);
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
	 * Set the AppClient we're going to be using
	 * @param AppClient $app_client
	 * @return void
	 */
	public function setAppClient($app_client)
	{
		$this->app_client = $app_client;
	}

	/**
	 *  Return the AppClient in use for this
	 *  action.
	 * @return WebServices_Client_AppClient
	 */
	public function getAppClient()
	{
		return $this->app_client ;
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
