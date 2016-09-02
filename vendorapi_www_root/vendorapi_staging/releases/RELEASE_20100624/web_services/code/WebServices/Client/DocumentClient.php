<?php
/**
 * @copyright Copyright 2010 The Selling Source, Inc.
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @created Jan 27, 2010
 */
abstract class WebServices_Client_DocumentClient extends WebServices_Client
{
	/**
	 * Saves documents and hashes
	 * 
	 * @param unknown_type $docs
	 * @param unknown_type $hashes
	 * @return unknown_type
	 */
	public function saveDocumentsAndHashes($docs, $hashes)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->saveDocumentsAndHashes($docs, $hashes);

		return $retval;
	}

	/**
	 * Retrieves the document list id using the document name
	 * 
	 * @param String $name
	 * @return Integer | FALSE
	 */
	public function findDocumentListId($name)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findDocumentListId($name);

		return $retval;
	}

	/**
	 * Retrieves a document by id
	 * 
	 * @param integer $document_id
	 * @return mixed | FALSE
	 */
	public function findDocumentById($document_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findDocumentById($document_id);
		if (isset($retval->item))
		{
			$retval = $retval->item;
		}

		return $retval;
	}

	/**
	 * Retrieves a document by document name
	 * 
	 * @param string $document_name
	 * @return mixed | FALSE
	 */
	public function findDocumentByName($document_name)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findDocumentByName($document_name);

		return $retval;
	}

	/**
	 * Finds a document for the passed archive id
	 * 
	 * @param int $archiveId
	 * @return stdClass
	 */
	public function findDocumentByArchiveId($archiveId)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findDocumentByArchiveId($archiveId);
		$retval = $this->resultToObjectArray($retval);

		return $retval;
	}

	/**
	 * Retrieves all documents for an application
	 * 
	 * @param integer $application_id
	 * @return array
	 */
	public function findAllDocumentsByApplicationId($application_id)
	{
		$retval = array();
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findAllDocumentsByApplicationId($application_id);
		$retval = $this->resultToObjectArray($retval);

		return $retval;
	}



	/**
	 * Saves a document
	 * 
	 * @param mixed $args
	 * @return integer | FALSE
	 */
	public function saveDocuments($args)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->saveDocuments($args);

		return $retval;
	}

	/**
	 * Method to save a single document to the application service
	 * 
	 * @param int $application_id
	 * @param int $company_id
	 * @param int $agent_id
	 * @param int $archive_id
	 * @param int $document_id
	 * @param string $document_id_ext
	 * @param int $document_list_name
	 * @param string $document_event_type
	 * @param string_type $document_method
	 * @param string $name_other
	 * @param string $transport_method
	 * @param int $signature_status
	 * @param string $sent_to
	 * @param string $sent_from
	 * @return bool
	 */
	public function saveDocument(
		$application_id,
		$company_id,
		$agent_id,
		$archive_id,
		$document_id,
		$document_id_ext,
		$document_list_name,
		$document_event_type,
		$name_other,
		$document_method,
		$transport_method,
		$signature_status,
		$sent_to,
		$sent_from = null
	)
	{
		$dto = array(
			'document_id'			=> $document_id,
			'document_list_name'	=> $document_list_name,
			'application_id'		=> $application_id,
			'company_id'			=> $company_id,
			'document_event_type'	=> $document_event_type,
			'name_other'			=> $name_other,
			'document_id_ext'		=> $document_id_ext,
			'agent_id'				=> $agent_id,
			'signature_status'		=> $signature_status,
			'sent_to'				=> $sent_to,
			'sent_from'				=> $sent_from,//@todo This column is what?
			'document_method'		=> $document_method,
			'transport_method'		=> $transport_method,
			'archive_id'			=> $archive_id,
			'document_model_legacy'	=> 1
		);

		return $this->saveDocuments(array($dto));
	}

	/**
	 * Removes a document from an application
	 * 
	 * @param integer $document_id
	 * @return Boolean
	 */
	public function deleteDocument($document_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->deleteDocument($document_id);

		return $retval;
	}

	/**
	 * Finds a document by its hash id
	 * 
	 * @param int $document_hash_id
	 * @return stdClass
	 */
	public function findDocumentHashById($document_hash_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findDocumentHashById($document_hash_id);

		return $retval;
	}

	/**
	 * Saves a document hash
	 * 
	 * @param mixed $args
	 * @return Integer | FALSE
	 */
	public function saveDocumentHash($args)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->saveDocumentHash($args);

		return $retval;
	}

	/**
	 * Retrieves the document
	 * 
	 * @param Integer $application_id
	 * @param Integer $document_list_id
	 * @return Integer | FALSE
	 */
	public function findDocumentHash($application_id, $document_list_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findDocumentHash($application_id, $document_list_id);

		return $retval;
	}

	/**
	 * Finds all document hashes for an application id
	 * 
	 * @param int $application_id
	 * @return stdClass
	 */
	public function findAllDocumentHashesByApplicationId($application_id)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->findAllDocumentHashesByApplicationId($application_id);

		return $retval;
	}

	/**
	 * Handles any exceptions that need to be thrown for unit tests and extensibility
	 *
	 * @param string $message
	 * @param int $code
	 * @throws Exception built with $message and $code
	 * @return void
	 */
	protected function throwException($message, $code)
	{
		throw new Exception($message, $code);
	}

	/**
	 * Performs the call to the underlying service, clearing all buffered calls
	 *
	 * @return mixed
	 */
	public function flush()
	{
		return $this->getService()->flush();
	}
}

?>
