<?php

/**
 * Observer class on the do_not_loan_flag_override model for updating the application service
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class ECash_WebServices_Models_Observers_DoNotLoanFlagOverride
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
		if ($event->type == DB_Models_ObservableWritableModel_1::EVENT_INSERT)
		{
			$this->overrideDoNotLoanFlag($event->model);
		}
		elseif ($event->type == DB_Models_ObservableWritableModel_1::EVENT_DELETE)
		{
			$this->deleteDoNotLoanFlagOverride($event->model);
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
	 * Insert a do not loan override
	 *
	 * @param ECash_Models_DoNotLoanFlagOverride $model
	 */
	protected function overrideDoNotLoanFlag($model)
	{
		$this->getAppClient()->overrideDoNotLoanFlag($model->company_id, $model->ssn);
	}

	/**
	 * Delete a do not loan override
	 *
	 * @param ECash_Models_DoNotLoanFlagOverride $model
	 */
	protected function deleteDoNotLoanFlagOverride($model)
	{
		$this->getAppClient()->deleteDoNotLoanFlagOverride($model->company_id, $model->ssn);
	}
}

?>
