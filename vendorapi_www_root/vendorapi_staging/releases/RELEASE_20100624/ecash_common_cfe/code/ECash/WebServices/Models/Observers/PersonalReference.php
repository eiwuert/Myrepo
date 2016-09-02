<?php

/**
 * Observer class on the application model for updating the webservice application info
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class ECash_WebServices_Models_Observers_PersonalReference
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
			$this->updatePersonalReference($event->model);
		}
		if ($event->type == DB_Models_ObservableWritableModel_1::EVENT_INSERT)
		{
			$this->insertPersonalReference($event->model);
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
	 * Update personal reference in the application service
	 *
	 * @param ECash_Models_PersonalReference $model
	 */
	protected function updatePersonalReference($model)
	{
		ECash::getLog()->Write("Updating Personal Reference with id: " .$model->service_personal_reference_id);
		$this->getAppClient()->updatePersonalReference(
			$model->application_id,
			$model->service_personal_reference_id,
			$model->company_id,
			$model->name_full,
			$model->phone_home,
			$model->relationship,
			$model->contact_pref,
			$model->reference_verified
		);
	}

	/**
	 * Inserts personal reference to the application service
	 *
	 * @param ECash_Models_PersonalReference $model
	 */
	protected function insertPersonalReference($model)
	{
		ECash::getLog()->Write("Inserting Personal Reference");
		$this->getAppClient()->addPersonalReference(
			$model->application_id,
			$model->company_id,
			$model->name_full,
			$model->phone_home,
			$model->relationship,
			$model->contact_pref,
			$model->reference_verified
		);
	}
}

?>
