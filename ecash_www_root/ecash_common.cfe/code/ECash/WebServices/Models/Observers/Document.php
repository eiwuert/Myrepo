<?php

/**
 * Observer class on the application model for updating documents
 *
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 */
class ECash_WebServices_Models_Observers_Document
{
	/**
	 * @var Delegate_1
	 */
	protected $delegate;

	/**
	 * Application service client for communication with the application service
	 *
	 * @var ECash_WebService_DocumentClient
	 */
	protected $doc_client;

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
		if ($event->type == DB_Models_ObservableWritableModel_1::EVENT_BEFORE_INSERT)
		{
			/**
			 * If document_id is set in the model the service will first try to pull 
			 * the document to update it. If it is unable to get the record 
			 * (it will be since this is an insert) it will do nothing and 
			 * the document will not be inserted.
			 */
			$event->model->document_id = NULL;
			$result = $this->saveDocument($event->model);
			$event->model->document_id = $result->item->document_id;
		}
		elseif ($event->type == DB_Models_ObservableWritableModel_1::EVENT_UPDATE)
		{
			$this->saveDocument($event->model);
		}
	}

	/**
	 * Get a document client
	 *
	 * @return ECash_WebService_AppClient
	 */
	protected function getDocumentClient()
	{
		if (!isset($this->doc_client))
		{
			$this->doc_client = ECash::getFactory()->getDocumentClient();
		}

		return $this->doc_client;
	}

	/**
	 * Saves row updates the model to the document service
	 *
	 * @param ECash_Model_Document $model
	 * @return bool;
	 */
	protected function saveDocument($model)
	{
		$result = FALSE;
		$doc_list_model = ECash::getFactory()->getReferenceModel('DocumentListRef');
		$doc_list_model->loadBy(array('document_list_id' => $model->document_list_id));

		if (isset($doc_list_model->name))
		{
			$result = $this->getDocumentClient()->saveDocument(
				$model->application_id,
				$model->company_id,
				$model->agent_id,
				$model->archive_id,
				$model->document_id,
				$model->document_id_ext,
				$doc_list_model->name_short,
				$model->document_event_type,
				$model->name_other,
				$model->document_method,
				$model->transport_method,
				$model->signature_status,
				$model->sent_to
			);
		}

		if (is_numeric($result)) {
			$rtn = new stdClass();
			$item = new stdClass();
			$item->document_id = $result;
			$rtn->item = $item;
		}

		return $rtn;
	}
}

?>
