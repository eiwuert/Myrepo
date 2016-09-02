<?php

/**
 *
 *
 * This call is so we get recalculated pay date information if they
 * have to fix them on the confirmation page and sign documents
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Actions_SubmitPage extends VendorAPI_Actions_Base
{

	/**
	 * Application Factory
	 *
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $factory;

	/**
	 * VendorAPI Driver
	 *
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 *
	 * @var VendorAPI_IDocument
	 */
	protected $document;

	/**
	 *
	 * @var VendorAPI_ITokenProvider
	 */
	protected $provider;

	/**
	 *
	 * @var VendorAPI_IApplication
	 */
	protected $application;

	/**
	 *
	 * @var array
	 */
	protected $error;

	/**
	 *
	 * @var array
	 */
	protected $result = array();

	/**
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * @var VendorAPI_IValidator
	 */
	protected $validator;

	/**
	 *
	 * @var AppClient
	 */
	protected $app_client;

	/**
	 *
	 * @var VendorAPI_Blackbox_EventLog
	 */
	protected $eventlog;

	/**
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_IApplicationFactory $factory
	 * @param VendorAPI_ITokenProvider $provider
	 * @param VendorAPI_IDocument $document
	 */
	public function __construct(
		VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $factory,
		VendorAPI_ITokenProvider $provider,
		VendorAPI_IDocument $document,
		VendorAPI_CFE_IRulesetFactory $rule_factory,
		ECash_WebService_AppClient $app_client,
		VendorAPI_IValidator $validator
	)
	{
		parent::__construct($driver);
		$this->factory = $factory;
		$this->document = $document;
		$this->provider = $provider;
		$this->rule_factory = $rule_factory;
		$this->app_client = $app_client;
		$this->validator = $validator;
	}

	/**
	 * Executes the PreAgree action Qualifiers
	 *
	 * @param array $data Application data
	 * @param VendorAPI_StateObject $state
	 * @return VendorAPI_Response
	 */
	public function execute(array $data = NULL, $state = NULL)
	{
		$this->validator->validate($data);
		$filtered = $this->validator->getFilteredData();
		$data = is_array($filtered) ? array_merge($data, $filtered) : $data;

		// add this for logging, etc.
		$this->call_context->setApplicationId($data['application_id']);

		if ($state == NULL)
		{
			$this->state = $this->getStateObjectByApplicationID($data['application_id']);
		}
		else
		{
			$this->state = $this->getStateObject($state);
		}

		$persistor = new VendorAPI_StateObjectPersistor($this->state);
		$this->application = $this->factory->getApplication($this->state->application_id, $persistor, $this->state);

		// are they declining?
		$declined = !empty($data['customer_decline']);
		$ecash_sign_docs = !empty($data['ecash_sign_docs']);
		$has_triggers = ($this->state->triggers instanceof VendorAPI_Blackbox_Triggers
			&& $this->state->triggers->hasHitTriggers());

		$ruleset = $this->rule_factory->getRuleset($this->getPageflowConfig());
		$engine = $this->getCfeEngine(
			$this->application->getCfeContext($this->call_context),
			$ruleset
		);
		if (!$declined)
		{
                        if ((isset($data['client_ip_address'])) && (is_string($data['client_ip_address']))) $this->setESigIPAddress($data['client_ip_address']);
			$page_data = $this->getPageData($engine, $ecash_sign_docs);
			$this->setPayDateModel($data);
			$this->setQualifyItems($data);
			$this->setReferences($data);
		}

		$space_key = $this->application->getSpaceKey($this->driver, $this->driver->getStatProClient());
		$results = $engine->executeEvent(
			'submitPage',
			array(
				'customer_history' => isset($data['customer_history']) ? $data['customer_history'] : array(),
				'driver' => $this->getDriver(),
				'blackbox_config' => $this->getBlackboxConfig($data),
				'ecash_sign_docs' => $ecash_sign_docs,
				'save_loan_actions' => FALSE,
				'customer_decline' => $declined,
				'has_triggers' => $has_triggers,
				'invalid_paydates' => FALSE,
				'denied' => FALSE,
				'no_checks' => isset($data['no_checks']) ? $data['no_checks'] : FALSE,
				'has_loan_actions' => $this->application->hasLoanActions($this->call_context->getApiAgentId()),
				'track_key' => $this->application->getTrackId(),
				'space_key' => $space_key,
				'application_id' => $this->application->application_id,
				'application' => $this->application,
				'call_context' => $this->call_context,
				'ioBlackBox' => $data['ioBlackBox']
			)
		);

		if (isset($results['winner'])
			&& $results['winner'] instanceof VendorAPI_Blackbox_Winner)
		{
			$this->processWinner($results['winner'], $this->state);
		}

		// We store this, because the DAO object  will
		// need it for it's CFE execution
		$this->state->ecash_sign_docs = $ecash_sign_docs;

		$factory = ECash::getFactory();

		if (!empty($results['application_expired']))
		{
			$this->expireApplications();
		}
		if (!empty($results['denied']))
		{
			$this->result['denied'] = TRUE;
		}

		if (!empty($page_data['document_templates']) && !$this->result['denied'] && !$declined)
		{
				$documents = array_unique((array)$page_data['document_templates']);
				if (!$this->haveDocumentsChanged($documents))
				{
					$this->signDocuments($documents);
				}
				else
				{
					$this->error = "document_preview";
					return new VendorAPI_Response(
						$this->state,
						empty($this->error) ? VendorAPI_Response::SUCCESS : VendorAPI_Response::ERROR,
						$this->result,
						$this->error
					);
				}
		}

		/**
		 * add eventlog information to the application service
		 */
		$events = $this->eventlog->getEventlogEvents();
		$eventlog = array();
		if (!empty($events)) {
			foreach ($events as $key => $value) {
				$event = array(
					'external_id'	=> $value['application_id'],
					'source'		=> $this->getCallContext()->getApiAgentName(),
					'target'		=> $this->getCallContext()->getCompany(),
					'event'			=> $value['event'],
					'response'		=> $value['response']
				);

				$eventlog[] = $event;
			}
			$this->app_client->insertEventlogRecords($eventlog);
		}

		// page flow will trigger this at the appropriate spot
		if (!empty($results['save_loan_actions'])
			&& isset($this->state->loan_actions))
		{
			$this->application->handleLoanActions($this->state->loan_actions, $this->call_context);
			$this->application->handleTriggers();
			unset($this->state->loan_actions);
		}

		$response = new VendorAPI_Response(
			$this->state,
			empty($this->error) ? VendorAPI_Response::SUCCESS : VendorAPI_Response::ERROR,
			$this->result,
			$this->error
		);

		$this->application->saveApplicationInfoToAppService($this->app_client);

		$this->saveApplication(!empty($results['save_now']), $this->state);

		return $response;
	}
	
	/**
	 * Updates the state object based on the Blackbox winner returned from CFE.
	 * @param VendorAPI_Blackbox_Winner $winner The new winner object
	 * @param VendorAPI_StateObject $state The current state object
	 * @return void
	 */
	private function processWinner(VendorAPI_Blackbox_Winner $winner, VendorAPI_StateObject $state)
	{
		$loan_actions = $winner->getLoanActions();
		if (!empty($loan_actions))
		{
			$state->loan_actions = $loan_actions;
		}
	}

	/**
	 * Return the page data from the CFE Engine.
	 *
	 * @param ECash_CFE_Engine $engine
	 * @param array ecash_sign_docs
	 * @return ArrayObject
	 */
	protected function getPageData(ECash_CFE_Engine $engine, $ecash_sign_docs)
	{
		$page_data = new ArrayObject();
		$engine->executeEvent(
			'getPage',
				array(
				'track_key' => $this->application->getTrackId(),
				'space_key' => $this->application->getSpaceKey($this->driver, $this->driver->getStatProClient()),
				'application_id' => $this->application->application_id,
				'driver' => $this->getDriver(),
				'page_data' => $page_data,
				'ecash_sign_docs' => $ecash_sign_docs,
			)
		);

		return $page_data;
	}

	/**
	 * Expires other applications that the customer may have filled out
	 *
	 * @return nothing
	 */
	protected function expireApplications()
	{
		$expire_status = 'expired::prospect::*root';

		/* Get a list of other applications */
		$apps = new ECash_HistoryProvider(
			$this->driver->getDatabase(),
			array($this->driver->getCompany()),
			TRUE
		);
		$apps->excludeApplication($this->application->application_id);
		$apps_history = $apps->getHistoryBy(array('ssn' => $this->application->ssn));

		if (empty($apps_history))
		{
			return;
		}

		$expirable = $apps_history->getExpirableApplications();

		/* Expire the 'extra' applications */
		foreach ($expirable as $app_id => $app_info)
		{
			$state_obj = $this->factory->createStateObject($this->call_context, $app_id);
			if (!$state_obj->isPart('application'))
			{
				$state_obj->createPart('application');
			}

			$state_obj->application->application_status = $expire_status;
			$this->saveState($state_obj);

			$this->app_client->updateApplicationStatus($app_id, $this->getCallContext()->getApiAgentId(), $expire_status);
		}
	}

	/**
	 * Set Qualify Items
	 *
	 * Sets:
	 * 		fund_date
	 * 		payoff_date
	 * 		fund_amount
	 * 		apr
	 * 		finance_charge
	 * 		total_payments
	 *
	 * Item value will be used for col name translations
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setQualifyItems($data)
	{
		if (!empty($data['fund_amount']))
		{
			$this->application->calculateQualifyInfo(TRUE, $data['fund_amount']);
		}
	}

	/**
	 * Set Esig IP Address
	 *
	 * Sets:
	 * 		ESig IP Address
	 *
	 * Stores this from the customer site for placing on the loan document
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setESigIPAddress($ip_address)
	{
		if (filter_var($ip_address, FILTER_VALIDATE_IP))
		{
			$this->application->setESigIPAddress($ip_address);
		}
	}

	/**
	 * setApplicationObject
	 *
	 * Sets Items in Application object based on key.
	 * Item value will be used for col name translations
	 *
	 * @param array $items
	 * @param array $data
	 * @return void
	 */
	protected function setApplicationObject($items, $data)
	{
		$app_data = array();
		foreach ($items as $from_item => $to_item)
		{
			if (!empty($data[$to_item]))
			{
				$app_data[$from_item] = $data[$to_item];
			}
		}

		$this->application->setApplicationData($app_data);
	}

	/**
	 * Set Relationship Information
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setReferences($data)
	{
		for ($i = 1; isset($data['ref_0'.$i.'_name_full']); $i++)
		{
			$this->application->addPersonalReference(
				$this->getCallContext(),
				$data['ref_0'.$i.'_name_full'],
				$data['ref_0'.$i.'_phone_home'],
				$data['ref_0'.$i.'_relationship']);
		}
	}

	/**
	 * Set Pay Date Information
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setPayDateModel($data)
	{
		$items = array(
			'income_frequency' 	=> 'income_frequency',
			'paydate_model'		=> 'paydate_model',
			'day_of_week'		=> 'day_of_week',
			'last_paydate'		=> 'last_paydate',
			'day_of_month_1'    => 'day_of_month_1',
			'day_of_month_2'	=> 'day_of_month_2',
			'week_1'			=> 'week_1',
			'week_2'			=> 'week_2'
		);

		$this->setApplicationObject($items, $data);

//		$this->application->setApplicationData(array('date_first_payment' => NULL));
		$this->application->calculateQualifyInfo(TRUE);
	}

	/**
	 * Returns whether or not documents have changed.
	 *
	 * [#54109] Agean (and all of commercial) has done the same change
	 * as AMG - no longer needing documents to be resigned if they
	 * have changed.  This is to overcome a problem where SubmitPage
	 * doesn't check to see if the documents have changed until after
	 * running the submitPage flow, but it updates the status to
	 * agreed in the application service, then returns an error
	 * (because documents *have* changed).  We are overriding this to
	 * return false. This code should later be moved into
	 * page_flow.xml and be executed as an action.
	 *
	 * @param array $documents
	 * @return bool FALSE
	 */	
	protected function haveDocumentsChanged(array $documents)
	{
		return FALSE;
	}

	/**
	 * signs documents
	 * @param array $data
	 * @return array
	 */
	protected function signDocuments(array $documents)
	{
		$results = array();
		foreach ($documents as $document)
		{
			try
			{
				$docdata = $this->document->create($document, $this->application, $this->provider, $this->getCallContext());
				if ($docdata instanceof VendorAPI_DocumentData)
				{
					$results[$document] = array();
					$results[$document]['archive_id'] = $docdata->getDocumentId();
					if ($this->document->signDocument($this->application, $docdata, $this->getCallContext()))
					{
						$results[$document]['signed'] = TRUE;
						$results[$document]['archive_id'] = $docdata->getDocumentId();
						$this->application->addDocument($docdata, $this->getCallContext());
						$this->application->expireDocumentHash($docdata, $this->getCallContext());
					}
					else
					{
						$results[$document]['signed'] = FALSE;
						$this->addError("Failed to sign {$document}");
					}
				}
			}
			catch (Exception $e)
			{
				$this->addError($e->getMessage());
			}
		}
		return $results;
	}

	/**
	 * Add an error
	 * @param String $msg
	 * @return void
	 */
	protected function addError($msg)
	{
		if (!is_array($this->error))
		{
			$this->error = array();
		}
		$this->error[] = $msg;
	}

	/**
	 *
	 * @return ECash_VendorAPI_DAO_Application
	 */
	public function getDAOApplication()
	{
		return new ECash_VendorAPI_DAO_Application($this->driver);
	}

	/**
	 * @return DOMDocument
	 */
	protected function getPageflowConfig()
	{
		return $this->driver->getPageflowConfig();
	}

	/**
	 *
	 * @param ECash_CFE_IContext $context
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
	protected function getBlackboxConfig(array $data)
	{
		if (!isset($data['debug']) || !is_array($data['debug']))
			$data['debug'] = array();
		if (isset($data['no_checks']) && $data['no_checks'] == 1)
			$data['debug']['NO_CHECKS'] = TRUE;
		if (isset($data['datax_fraud']) && $data['datax_fraud'] == 1)
			$data['debug']['DATAX_FRAUD'] = TRUE;
		
		$debug = NULL;
		if (isset($data['debug']) && is_array($data['debug']))
			$debug = new VendorAPI_Blackbox_DebugConfig($data['debug']);
		
		$config = new VendorAPI_Blackbox_Config($debug);
		$config->enterprise = $this->driver->getEnterprise();
		$config->company = $this->driver->getCompany();
		$config->campaign = $this->application->getCampaign();
		$config->is_enterprise = TRUE;
		$config->is_react = $this->application->isReact();
		$config->call_context = $this->call_context;
		$config->persistor = new VendorAPI_TemporaryPersistor();

		$config->event_log = new VendorAPI_Blackbox_EventLog(
			$this->state,
			$this->state->application_id,
			$config->campaign,
			VendorAPI_Blackbox_EventLog::ALL
		);

		$this->eventlog = $config->event_log;

		return $config;
	}

	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->factory;

	}
}
