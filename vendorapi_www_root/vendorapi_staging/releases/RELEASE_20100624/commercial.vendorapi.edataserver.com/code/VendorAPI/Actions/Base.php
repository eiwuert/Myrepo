<?php

/**
 * Base class for Vendor Actions.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class VendorAPI_Actions_Base implements VendorAPI_IAction
{
	/**
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * @var VendorAPI_CallContext
	 */
	protected $call_context;

	/**
	 * @param VendorAPI_IDriver $driver
	 */
	public function __construct(VendorAPI_IDriver $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * Sets the call context.
	 *
	 * @param VendorAPI_CallContext $call_context
	 */
	public function setCallContext(VendorAPI_CallContext $call_context)
	{
		$this->call_context = $call_context;
	}

	/**
	 * Gets the call context.
	 *
	 * @return VendorAPI_CallContext
	 */
	protected function getCallContext()
	{
		return $this->call_context;
	}

	/**
	 * Set the driver that is driving this train
	 * @param VendorAPI_IDriver $driver
	 * @return void
	 */
	public function setDriver(VendorAPI_IDriver $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * Return the driver for this action
	 * @return VendorAPI_IDriver
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * Save the state object to the local sqlite
	 * databases.
	 *
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	protected function saveState(VendorAPI_StateObject $state)
	{
		$this->saveStateModel($state);
		$this->saveMysqlModel($state);
	}

	/**
	 * Save the state object to sqlite
	 *
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	private function saveStateModel(VendorAPI_StateObject $state)
	{
		/**
		 * Most likely redundant, but I want to ensure these values are always
		 * set in the request timer, and while Post is covered, other actions like
		 * Fail or Withdraw are not implementing these.
		 */
		if(isset($state->application_id))
		{
			$this->getDriver()->getTimer()->setApplicationId($state->application_id);
		}
		if(isset($state->external_id))
		{
			$this->getDriver()->getTimer()->setExternalId($state->external_id);
		}

		$this->getDriver()->getTimer()->start(__CLASS__ . "::" . __METHOD__ . ":: VendorAPI_StateObjectModel::save()");
		$model = new VendorAPI_StateObjectModel($this->driver->getCompany());
		$model->save($state);
		$this->getDriver()->getTimer()->stop(__CLASS__ . "::" . __METHOD__ . ":: VendorAPI_StateObjectModel::save()");
	}

	/**
	 * Save the state object to the local sqlite
	 * databases.
	 *
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	private function saveMysqlModel(VendorAPI_StateObject $state)
	{
		/**
		 * Most likely redundant, but I want to ensure these values are always
		 * set in the request timer, and while Post is covered, other actions like
		 * Fail or Withdraw are not implementing these.
		 */
		if(isset($state->application_id))
		{
			$this->getDriver()->getTimer()->setApplicationId($state->application_id);
		}
		if(isset($state->external_id))
		{
			$this->getDriver()->getTimer()->setExternalId($state->external_id);
		}

		$this->getDriver()->getTimer()->start(__CLASS__ . "::" . __METHOD__ . ":: VendorAPI_StateObjectMysqlModel::save()");
		$mysql_model = new VendorAPI_StateObjectMysqlModel($this->driver->getStateObjectDB());

		$pk_id = $mysql_model->stateObjectExists($state->application_id);
		$mysql_model->application_id = $state->application_id;
		$mysql_model->date_modified = time();
		$mysql_model->state_object = $state;

		if ($pk_id)
		{
			//Need to set the id without having it 'updated'
			$mysql_model->setVendorStateObjectId($pk_id);

			$mysql_model->update();
		}
		else
		{
			$mysql_model->insert();
		}
		$this->getDriver()->getTimer()->stop(__CLASS__ . "::" . __METHOD__ . ":: VendorAPI_StateObjectMysqlModel::save()");
	}

	/**
	 * Hits the given $stat, $track_key, and $space_key
	 *
	 * @param string $stat
	 * @param string $track_key
	 * @param string $space_key
	 */
	protected function hitStat($stat, $track_key, $space_key)
	{
		$stat_client = $this->driver->getStatProClient();
		$stat_client->hitStat($stat, $track_key, $space_key);
	}

	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{

	}

	/**
	 * Creates and returns a new VendorAPI_StateObject instance.
	 *
	 * @return VendorAPI_StateObject
	 */
	protected function getStateObjectByApplicationID($application_id)
	{
		$this->getDriver()->getTimer()->setApplicationId($application_id);
		$mysql_model = new VendorAPI_StateObjectMysqlModel($this->driver->getStateObjectDB());

		$this->getDriver()->getTimer()->start('VendorAPI_StateObjectMysqlModel::loadBy');
		$loaded_from_mysql = $mysql_model->loadByApplicationId($application_id);
		$this->getDriver()->getTimer()->stop('load_from_mysql' . __CLASS__ . "::" . __METHOD__);

		$state_object = NULL;
		if ($loaded_from_mysql)
		{
			$this->getDriver()->getLog()->write('Loaded state object from mysql');
			$state_object = $mysql_model->state_object;
		}
		if (empty($state_object))
		{
			//create a new state
			$state_object = $this->getApplicationFactory()->createStateObject($this->getCallContext(), $application_id);
			$state_object->application_id = $application_id;
		}

		return $state_object;
	}

	/**
	 * Creates and returns a new VendorAPI_StateObject instance.
	 *
	 * @return VendorAPI_StateObject
	 */
	protected function getStateObject($serialized)
	{
		if (!empty($serialized))
		{
			return $this->unserializeStateObject($serialized);
		}

		return new VendorAPI_StateObject();
	}

	/**
	 * Attempts to unserialize the state object if not null.
	 */
	protected function unserializeStateObject($serialized)
	{
		if ($serialized)
		{
			$state = @unserialize($serialized);
			if (!$state instanceof VendorAPI_StateObject)
			{
				throw new Exception('Invalid state object');
			}
			return $state;
		}
		return NULL;
	}

	/**
	 * Returns an array of validation errors from the validator.
	 *
	 * Each element in the returned array is an array with the keys 'field' and 'message'. The 'field' key will
	 * contain the field that failed validation and 'message' will contain a brief message describing
	 * the validation error.
	 *
	 * @param VendorAPI_Actions_Validators_Base $validator
	 * @return array
	 */
	protected function getValidationErrors(VendorAPI_Actions_Validators_Base $validator)
	{
		$errors = $validator->getErrors();

		$validation_errors = array();

		foreach ($errors as $err_obj)
		{
			$validation_errors[] = array('field' => $err_obj->field, 'message' => $err_obj->message);
		}

		return $validation_errors;
	}

	// @TODO Move this to an appropriate spot.

	/**
	 * Sets and returns an array of data containing the client $data as well as information obtained
	 * from the API call (Qualify info, etc).
	 *
	 * @param array $data
	 * @return array
	 */
	protected function setExtraApplicationData(array $data)
	{
		if (isset($data['dob'])) $data['dob'] = date('Y-m-d', strtotime($data['dob']));
		if (isset($data['is_react'])) $data['is_react'] = ($data['is_react']) ? 'yes' : 'no';

		$data['company_id'] = $this->driver->getCompanyID();

		$data['legal_id_type'] = 'dl';
		$data['application_type'] = 'paperless';

		$data['enterprise_site_id'] = $this->driver->getEnterpriseSiteId();
		$data = $this->transformCampaignInfoSiteIds($data);
		return $data;
	}

	protected function transformCampaignInfoSiteIds(array $data)
	{
		if (isset($data['campaign_info']) && is_array($data['campaign_info']))
		{
			$modified_campaign_info = array();
			foreach ($data['campaign_info'] as $campaign_info)
			{
				if (!empty($campaign_info['promo_id']))
				{
					$campaign_info['campaign_name'] = $data['campaign'];
					$modified_campaign_info[] = $campaign_info;
				}
			}
			$data['campaign_info'] = $modified_campaign_info;
		}

		return $data;
	}

	/**
	 * Runs the given $data through qualify and modifies the given $state object
	 * as appropriate.
	 *
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	protected function setQualifyInfoInState($data, VendorAPI_StateObject $state)
	{
		if (!$state->isPart('application'))
		{
			$state->createPart('application');
		}

		$state->application->date_fund_estimated = date('Y-m-d', $this->qualify->getFundDateEstimate());
		$state->application->date_first_payment = date('Y-m-d', $this->qualify->getFirstPaymentDate());
		$state->application->fund_qualified = $this->qualify->getLoanAmount();
		$state->application->apr = $this->qualify->getAPR();
		$state->application->finance_charge = $this->qualify->getFinanceCharge();
		$state->application->payment_total = $this->qualify->getTotalPayment();
	}

	/**
	 * Returns an array of tables in the form of
	 * table_name => is_multirow These are used by
	 * saveDataInState to only save the data
	 * we need.
	 *
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return array
	 */
	protected function getTables(array $data, VendorAPI_StateObject $state)
	{
		// The key is the table name, the value
		// is whether this table is a multipart or not
		$tables = array(
			'application'  => FALSE,
			'campaign_info' => TRUE,
			'personal_reference' => TRUE,
		);
		if (isset($data['is_react']) &&
			strcasecmp($data['is_react'], 'yes') == 0  &&
			!$state->isPart('react_affiliation'))
		{
			$tables['react_affiliation'] = FALSE;
		}
		if ($data['is_title_loan'])
		{
			$tables['vehicle'] = FALSE;
		}
		if (is_array($data['application_contact']))
		{
			$tables['application_contact'] = TRUE;
		}
		return $tables;
	}

	/**
	 * Saves application data into the state object.
	 *
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return void
	 * @todo rename this function to be a bit more specific
	 */
	public function saveDataInState(array $data, VendorAPI_StateObject $state)
	{

		$tables = $this->getTables($data, $state);
		if (!is_array($tables) || !count($tables))
		{
			return FALSE;
		}
		if (isset($data['call_center']) && $data['call_center'])
		{
			$state->call_center = TRUE;
		}

		$data['income_direct_deposit'] = $data['income_direct_deposit'] ? 'yes' : 'no';

		$state->application_id = $data['application_id'];
		foreach ($tables as $table => $is_multi)
		{
			$model = $this->driver->getDataModelByTable($table);
			$columns = $model->getColumns();
			if (!$state->isPart($table) || ($is_multi && !$state->isMultiPart($table)))
			{
				$state->createPart($table, $is_multi);
			}
			if ($is_multi && is_array($data[$table]))
			{
				foreach($data[$table] as $info)
				{
					$array_data = array();
					foreach ($columns as $column)
					{
						if (!empty($data[$column]))
						{
							$array_data[$column] = $data[$column];
						}
					}
					foreach ($info as $k => $v)
					{
						if (!empty($v))
						{
							$array_data[$k] = $v;
						}
					}
					$state->{$table}[] = $array_data;
				}
			}
			elseif (!$is_multi)
			{

				$method = 'insertTable'.str_replace(' ','', ucwords(str_replace('_', ' ', $table)));
				if (method_exists($this, $method))
				{
					$this->$method($state, $columns, $data);
				}
				else
				{
					foreach ($columns as $column)
					{
						if (!empty($data[$column]))
						{
							$state->$table->$column = $data[$column];
						}
					}
				}
			}
		}
	}

	protected function insertTableReactAffiliation($state, $columns, $data)
	{
		$state->react_affiliation->company_id = $this->driver->getCompanyID();
		$state->react_affiliation->application_id = $data['react_application_id'];
		$state->react_affiliation->react_application_id = $data['application_id'];
		$state->react_affiliation->agent_id = $this->getCallContext()->getApiAgentId();
	}

	/**
	 * Saves an application
	 * @param boolean $save_now
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	protected function saveApplication($save_now, VendorAPI_StateObject $state)
	{
		$this->getDriver()->getTimer()->start('saveApplication');
		if ($save_now)
		{
			try
			{
				$dao = new ECash_VendorAPI_DAO_Application($this->driver);
				$dao->save($state);
				$this->saveMysqlModel($state);
				$this->getDriver()->getTimer()->stop('saveApplication');
				return;
			}
			catch (Exception $e)
			{
			}
		}

		$this->saveState($state);
		$this->getDriver()->getTimer()->stop('saveApplication');
	}

	/**
	 * @throws Exception
	 * @param VendorAPI_StateObject $state
	 * @param VendorAPI_IApplication $application
	 * @param WebServices_Client_AppClient $app_client
 	 * @param array $data
 	 * @param VendorAPI_IModelPersistor $persistor
 	 * @return boolean
	 */
	protected function saveToAppService(VendorAPI_StateObject $state, VendorAPI_IApplication $application, WebServices_Client_AppClient $app_client, $data, VendorAPI_IModelPersistor $persistor)
	{
		$send_fields = $this->getASInsertFieldsFromApp($application, $state, $data);

		$application_info = $app_client->insert($send_fields);
		if(!empty($application_info->applicationId))
		{
			return $this->processAppServiceInsertResult($application_info, $state, $application, $data, $persistor);
		}
		else
		{
			throw new Exception("Insert into Application Service has Failed");
		}
	}

	protected function processAppServiceInsertResult($application_info, VendorAPI_StateObject $state, VendorAPI_IApplication $application, $data, $persistor)
	{
		$application->updateApplicationId($application_info->applicationId);
		if ($application_info->isNewCustomer)
		{
			$application->createCustomer($application_info->customerId, $application_info->username, Crypt_3::Encrypt($application_info->password));
		}
		$application->setCustomer($application_info->customerId);

		$state->application_id = $application_info->applicationId;
		$state->application_contact_ids = $application_info->contactInfoIds;

		$data['application_id'] = $application_info->applicationId;
		$inquiry = new ECash_VendorAPI_BureauInquiry();
		$inquiry->sendInquiryToAppService($state, $data, $this->driver->getInquiryClient(), $persistor);

		/**
		 * Set the external_id and the application_id on the request timers
		 * in case they haven't been already.
		 */
		if(isset($state->external_id))
		{
			$this->getDriver()->getTimer()->setExternalId($state->external_id);
		}
		$this->getDriver()->getTimer()->setApplicationId($state->application_id);

		return $application_info->applicationId;
	}

	/**
	 * @throws Exception
	 * @param VendorAPI_StateObject $state
	 * @param VendorAPI_IApplication $application
	 * @param WebServices_Client_AppClient $app_client
 	 * @param VendorAPI_ResponseBuilder $response_builder
 	 * @return boolean
	 */
	protected function saveUnpurchasedLeadToAppService(VendorAPI_StateObject $state, VendorAPI_IApplication $application, WebServices_Client_AppClient $app_client)
	{
		$application_id = $app_client->insertUnpurchasedApp($this->getCallContext()->getCompanyId());
		if(!empty($application_id))
		{
			$application->updateApplicationId($application_id);

			$state->application_id = $application_id;
			return $application_id;
		}
		else
		{
			throw new Exception("Insert into Application Service has Failed");
		}
	}

	/**
	 * Gets fields for the application service from the application object
	 *
	 * @param VendorAPI_IApplication $app
	 * @return array
	 */
	protected function getASInsertFieldsFromApp(VendorAPI_IApplication $app, VendorAPI_StateObject $state, $data)
	{
		$send_array = array();
		$app_columns = $app->getModelColumns();
		//creates the send array based on the columns that exist in the application

		foreach ($app_columns as $k => $v)
		{
			if ($app->{$v} != NULL)
			{
				if (in_array($v, $app->getTimestampColumns()) && !is_numeric($app->{$v}))
				{
					$send_array[$v] = strtotime($app->{$v});
				}
				else
				{
					$send_array[$v] = $app->{$v};
				}
			}
			else
			{
			//	$send_array[$v] = new SoapVar(NULL, XSD_STRING);
			}
		}


		try {
			$prefs = $app->getData();
			$prs = $prefs['personal_reference'];
		}
		catch (Exception $e)
		{
			$prs = array();
		}

		$i = 0;

		/* get the personal references */
		$personal_references = array();
		foreach ($prs as $key => $v)
		{
			if ($v['company_id'] != $this->getCallContext()->getCompanyId())
			{
				continue;
			}
			
			$reference = array();

			$reference['name_full'] = $v['name_full'];
			$reference['phone_home'] = $v['phone_home'];
			$reference['relationship'] = $v['relationship'];
			/* mock current implementation of 'do not contact' and 'unverified' */
			$reference['ok_to_contact'] = 'do not contact';
			$reference['verified'] = 'unverified';
			$reference['application_id'] = $v['application_id'];
			$reference['modifying_agent_id'] = $this->getCallContext()->getApiAgentId();
			$reference['company_id'] = $v['company_id'];

			$personal_references[] = $reference;
		}

		/* get the campaign info */
		try {
			$camps = $app->getCampaignInfo(FALSE);
		}
		catch (Exception $e) {
			$camps = array();
		}

		$campaign_info = array();
		foreach ($camps as $key => $value) {
			$site = $value->site_id->getModel()->getColumnData();
			$camp = array(
				'application_id'	=> $value->application_id,
				'campaign_name'		=> $value->campaign_name,
				'friendly_name'		=> $value->campaign_name,
				'promo_id'			=> $value->promo_id,
				'promo_sub_code'	=> $value->promo_sub_code,
				'reservation_id'	=> $value->reservation_id,
				'site'				=> $site['name'],
				'license_key'		=> $site['license_key'],
			);

			$campaign_info[] = $camp;
		}

		/* get the eventlog */
		$eventlog = array();
		if ($state->isPart('eventlog'))
		{
			$eventlog_entries = $state->getData('eventlog');

			foreach ($eventlog_entries as $key => $value) {
				foreach ($value as $k => $v) {
					$event = array(
						'external_id'	=> $v['application_id'],
						'source'		=> $this->getCallContext()->getApiAgentName(),
						'target'		=> $this->getCallContext()->getCompany(),
						'event'			=> $v['event'],
						'response'		=> $v['response']
					);

					$eventlog[] = $event;
				}
			}
		}
                //add react affiliation if it exists
		if(!empty($data['react_application_id']))
		{
			if(empty($data['agent_id']))
			{
				$agent_id = $this->getCallContext()->getApiAgentId();
			}
			else
			{
				$agent_id = $data['agent_id'];
			}
			 $react_affiliation = new stdclass();
			 $react_affiliation->company_id = $this->driver->getCompanyID();
			 $react_affiliation->application_id = $data['react_application_id'];
			 $react_affiliation->react_application_id = $data['application_id'];
			 $react_affiliation->agent_id = $data['agent_id'];
			 $send_array['react_affiliation'] = $react_affiliation;
		 }
		$send_array['campaign_info'] = $campaign_info;
		$send_array['eventlog'] = $eventlog;
		$send_array['personal_references'] = $personal_references;
		$send_array['external_id'] = $state->external_id;
		$send_array['track_key'] = $app->track_id;
		$send_array['date_created'] = time();
		$send_array['date_modified'] = time();
		$send_array['customer_id'] = 0;

		//hack because of convereting a timestamp over soap to java seems to be adding a day
		if (is_numeric($send_array['dob']))
		{

			$send_array['dob'] = Date('Y-m-d', $send_array['dob']);
		}

		unset($send_array['application_id']);

		if ($send_array['is_react'] == 'yes')
		{
			$send_array['is_react'] = TRUE;
		}
		else
		{
			$send_array['is_react'] = FALSE;
		}

		if ($send_array['is_watched'] == 'yes')
		{
			$send_array['is_watched'] = TRUE;
		}
		else
		{
			$send_array['is_watched'] = FALSE;
		}

		if ($send_array['income_direct_deposit'] == 'yes')
		{
			$send_array['income_direct_deposit'] = 1;
		}
		else
		{
			$send_array['income_direct_deposit'] = 0;
		}

		$send_array['application_status'] = $app->getApplicationStatus();
		$send_array['application_source'] = $this->getCallContext()->getApiAgentName();
//throw new Exception(print_r($send_array, true));
		return $send_array;
	}
}
