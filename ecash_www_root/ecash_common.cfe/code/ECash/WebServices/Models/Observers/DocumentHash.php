<?php

/**
 * Observer class on the application model for updating documents
 *
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 */
class ECash_WebService_Models_Observers_DocumentHash
{
	/**
	 * @var Delegate_1
	 */
	protected $delegate;

	/**
	 * Application service client for communication with the application service
	 *
	 * @var ECash_WebService_DocumentHashClient
	 */
	protected $dochash_client;

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
		if (($event->type == DB_Models_ObservableWritableModel_1::EVENT_UPDATE)
				|| ($event->type == DB_Models_ObservableWritableModel_1::EVENT_INSERT))
		{
			$this->saveDocumentHash($event->model);
		}
	}

	/**
	 * Get a document client
	 *
	 * @return ECash_WebService_AppClient
	 */
	protected function getDocumentHashClient()
	{
		if (!isset($this->dochash_client))
		{
			$this->dochash_client = ECash::getFactory()->getDocumentHashClient();
		}

		return $this->dochash_client;
	}

	/**
	 * Saves ro updates the model to the application service
	 *
	 * @param ECash_Model_DocumentHash $model
	 */
	protected function saveDocument($model)
	{
		$doc_name = ECash::getFactory()->getReferenceModel('DocumentList');
		$doc_name->loadByKey($model->document_list_id)->name;

		$dto = array(
			'document_hash_id'		=> $model->document_hash_id,
			'document_list_id'		=> $doc_name,
			'application_id'		=> $model->application_id,
			'company_id'			=> $model->company_id,
			'hash'					=> $model->hash,
			'active_status'			=> $model->active_status
		);
		$this->dochash_client->save($dto);
	}

}

?>
