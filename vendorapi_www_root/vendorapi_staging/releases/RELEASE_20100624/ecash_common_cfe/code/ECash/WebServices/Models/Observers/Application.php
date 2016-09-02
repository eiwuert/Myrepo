<?php

/**
 * Observer class on the application model for updating the application service
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class ECash_WebServices_Models_Observers_Application
{
	/**
	 * @var Delegate_1
	 */
	protected $delegate;

	/**
	 * Application service client for communication with the application service
	 *
	 * @see function getAppClient
	 * @var ECash_WebService_AppClient
	 */
	protected $app_client;

	/**
	 * Constructor for the ApplicationModelObserver object
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->delegate = Delegate_1::fromMethod($this, 'onEvent');
	}

	/**
	 * Attach to an observable model
	 *
	 * @param IObservable_1 $model
	 * @return void
	 */
	public function attach(IObservable_1 $model)
	{
		$model->attachObserver($this->delegate);
	}

	/**
	 * Fired when a change occurs on the model we're watching
	 *
	 * @param stdClass $event
	 * @return void
	 */
	public function onEvent($event)
	{
		if ($event->type == DB_Models_ObservableWritableModel_1::EVENT_UPDATE)
		{
			$this->getAppClient()->enableBuffer(true);
			$this->updateApplicationStatus($event->model, $event->altered_data);
			$this->updateApplicant($event->model, $event->altered_data);
			$this->updateContactInfo($event->model, $event->altered_data);
			$this->updateEmploymentInfo($event->model, $event->altered_data);
			$this->updatePaydateInfo($event->model, $event->altered_data);
			$this->updateBankInfo($event->model, $event->altered_data);
			$this->updateApplicationInfo($event->model, $event->altered_data);
			$this->getAppClient()->flush();
		}
		
		if ($event->type == DB_Models_ObservableWritableModel_1::EVENT_AFTER_INSERT)
		{
			$this->updateCustomerId($event->model);
			$this->associateApplicantAccount($event->model);
		}
	}

	/**
	 * Get a new application service client
	 *
	 * @return ECash_WebService_AppClient
	 */
	protected function getAppClient()
	{
		if (!isset($this->app_client))
		{
			$this->app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		}

		return $this->app_client;
	}

	/**
	 * Grab the current flat status and send it to the app service
	 *
	 * @param ECash_Models_Applications $app
	 * @param array $altered_column_data
	 * @return void
	 */
	protected function updateApplicationStatus(ECash_Models_Application $app, array $altered_column_data)
	{
		if (!empty($altered_column_data['application_status_id']))
		{
			$application_id = $app->application_id;
			$application_status_id = $altered_column_data['application_status_id'];
			
			$ref_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			$status = $ref_list->toName($application_status_id);

			$app_client = $this->getAppClient();
			$app_client->updateApplicationStatus(
				$application_id, 
				ECash::getAgent()->getAgentId(), 
				$status
			);
		}
	}

	/**
	 * Updates app service applicant information from altered columns
	 *
	 * @param ECash_Models_Applications $app
	 * @param array $altered_column_data
	 * @return bool - Whether an update was performed or not
	 */
	protected function updateApplicant(ECash_Models_Application $app, array $altered_column_data)
	{
		if (!empty($altered_column_data))
		{
			$application_id = $app->application_id;
			$app_client = $this->getAppClient();
			$retval = $app_client->updateApplicant($application_id, $altered_column_data);

			return $retval;
		}

		return FALSE;
	}
	/**
	 * Updates app service bank information from altered columns
	 *
	 * @param ECash_Models_Applications $app
	 * @param array $altered_column_data
	 * @return bool - Whether an update was performed or not
	 */
	protected function updateBankInfo(ECash_Models_Application $app, array $altered_column_data)
	{
		if (!empty($altered_column_data))
		{
			$application_id = $app->application_id;
			$app_service_client = $this->getAppClient();
			$retval = $app_service_client->updateBankInfo($application_id, $altered_column_data);

			return $retval;
		}

		return FALSE;
	}

	/**
	 * Updates app service contact information from altered columns
	 *
	 * @param ECash_Models_Applications $app
	 * @param array $altered_column_data
	 * @return bool - Whether an update was performed or not
	 */
	protected function updateContactInfo(ECash_Models_Application $app, array $altered_column_data)
	{
		if (!empty($altered_column_data))
		{
			$application_id = $app->application_id;
			$app_client = $this->getAppClient();
			$retval = $app_client->updateContactInfo($application_id, $altered_column_data);

			return $retval;
		}

		return FALSE;
	}
	
	/**
	 * Updates app service contact information from altered columns
	 *
	 * @param ECash_Models_Applications $app
	 * @param array $altered_column_data
	 * @return bool - Whether an update was performed or not
	 */
	protected function updateEmploymentInfo(ECash_Models_Application $app, array $altered_column_data)
	{
		if (!empty($altered_column_data))
		{
			$application_id = $app->application_id;
			$app_client = $this->getAppClient();
			$retval = $app_client->updateEmploymentInfo($application_id, $altered_column_data);

			return $retval;
		}

		return FALSE;
	}

	/**
	 * Updates app service application information from altered columns
	 *
	 * @param ECash_Models_Applications $app
	 * @param array $altered_column_data
	 * @return bool - Whether an update was performed or not
	 */
	protected function updateApplicationInfo(ECash_Models_Application $app, array $altered_column_data)
	{
		if (!empty($altered_column_data))
		{
			$application_id = $app->application_id;
			$app_client = $this->getAppClient();
			$retval = $app_client->updateApplication($application_id, $altered_column_data);

			return $retval;
		}

		return FALSE;
	}	
	/**
	 * Updates app service paydate information from altered columns
	 *
	 * @param ECash_Models_Applications $app
	 * @param array $altered_column_data
	 * @return bool - Whether an update was performed or not
	 */
	protected function updatePaydateInfo(ECash_Models_Application $app, array $altered_column_data)
	{
		if (!empty($altered_column_data))
		{
			$application_id = $app->application_id;
			$app_client = $this->getAppClient();
			$retval = $app_client->updatePaydateInfo($application_id, $altered_column_data);

			return $retval;
		}

		return FALSE;
	}
	
	protected function associateApplicantAccount($model)
	{
		$customer_id = $model->customer_id;
		$my_application_id = $model->application_id;
	
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$accounts = $this->getAppClient()->getApplicationIdsForCustomer($customer_id);
		
		if(empty($accounts))
		{
			return FALSE;
		}
		else
		{
			/**
			 * Iterate through the accounts and find the first match that 
			 * isn't our application_id, grab it's login/password, then
			 * associate it.
			 */
			do
			{
				$application_id = array_shift($accounts);
	
				if($info = $this->getAppClient()->getApplicantAccountInfo($application_id))
					break;
			}
			while(count($accounts) > 0);
			
			if(!empty($info))
			{
				return $this->getAppClient()->associateApplicantAccount($my_application_id, $info->login, $info->password);
			}
			else
			{
				return FALSE;
			}
		}
	}
	
	protected function updateCustomerId($model)
	{
		$args = array('customer_id' => $model->customer_id);
		$result = $this->getAppClient()->updateApplication($model->application_id, $args);
	}
}

?>
