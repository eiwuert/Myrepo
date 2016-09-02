<?php

/**
 * Observer class on the do_not_loan_flag model for updating the application service
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class ECash_WebServices_Models_Observers_DoNotLoanFlag
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
			$this->insertDoNotLoanFlag($event->model);
		}
		elseif ($event->type == DB_Models_ObservableWritableModel_1::EVENT_UPDATE
			|| $event->type == DB_Models_ObservableWritableModel_1::EVENT_DELETE)
		{
			if ($event->type == DB_Models_ObservableWritableModel_1::EVENT_DELETE 
				|| (isset($event->altered_data['active_status']) 
					&& !strcasecmp($event->altered_data['active_status'],'inactive')))
			{
				$this->deleteDoNotLoanFlag($event->model);
			}
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
	 * Insert a do not loan flag
	 *
	 * @param ECash_Models_DoNotLoanFlag $model
	 */
	protected function insertDoNotLoanFlag($model)
	{
		$model_category = ECash::getFactory()->getReferenceModel('DoNotLoanFlagCategory');
		$model_category->loadByKey($model->category_id);
		$this->getAppClient()->insertDoNotLoanFlag(
			$model->company_id,
			$model->ssn,
			$model_category->name,
			$model->other_reason,
			$model->explanation
		);
	}

	/**
	 * Delete a do not loan flag
	 *
	 * @param ECash_Models_DoNotLoanFlag $model
	 */
	protected function deleteDoNotLoanFlag($model)
	{
		$this->getAppClient()->deleteDoNotLoanFlag($model->company_id, $model->ssn);
	}
}

?>
