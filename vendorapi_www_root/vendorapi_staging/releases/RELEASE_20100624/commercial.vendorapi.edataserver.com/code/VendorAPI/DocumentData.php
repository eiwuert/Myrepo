<?php
/**
 * Really just a data container for some sort of
 * doucment object
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_DocumentData
{
	protected $template;
	protected $unique_id;
	protected $subject;
	protected $attachments;
	protected $printed;
	protected $signed;
	protected $latest_dispatch;
	protected $application_id;
	protected $undefined_tokens;
	protected $document_id;

	protected $part_id;
	protected $data;
	protected $uri;
	protected $content_type;

	protected $attached_data;
	protected $document_content;
	protected $is_preview;
	protected $document_list_id;

	public function __construct()
	{
		$this->attachments = array();
	}

	public function getTemplate()
	{
		return $this->template;
	}

	public function getUniqueId()
	{
		return $this->unique_id;
	}

	public function getSubject()
	{
		return $this->subject;
	}

	public function getAttachments()
	{
		return $this->attachments;
	}

	public function addAttachment(VendorAPI_DocumentData $doc)
	{
		$this->attachments[] = $doc;
	}

	public function getPrinted()
	{
		return $this->printed;
	}

	public function getSigned()
	{
		return $this->signed;
	}

	public function getLatestDispatch()
	{
		return $this->latest_dispatch;
	}

	public function getApplicationId()
	{
		return $this->application_id;
	}

	public function getUndefinedTokens()
	{
		return $this->undefined_tokens;
	}

	public function getPartId()
	{
		return $this->part_id;
	}

	public function getContents()
	{
		return $this->data;
	}

	public function getUri()
	{
		return $this->uri;
	}

	public function getContentType()
	{
		return $this->content_type;
	}

	public function setTemplate($template)
	{
		$this->template = $template;
	}

	public function setUniqueId($unique_id)
	{
		$this->unique_id = $unique_id;
	}

	public function setSubject($subject)
	{
		$this->subject = $subject;
	}

	public function setPrinted($printed)
	{
		$this->printed = $printed;
	}

	public function setSigned($signed)
	{
		$this->signed = $signed;
	}

	public function setLatestDispatch($dispatch)
	{
		$this->latest_dispatch = $dispatch;
	}

	public function setApplicationId($id)
	{
		$this->application_id = $id;
	}

	/**
	 * Set the array of undefined tokens
	 * @param Array $tokens
	 * @return void
	 */
	public function setUndefinedTokens($tokens)
	{
		$this->undefined_tokens = $tokens;
	}

	/**
	 * Set the part id?
	 * @param Integer $id
	 * @return void
	 */
	public function setPartId($id)
	{
		$this->part_id = $id;
	}

	/**
	 * Set the content of this document
	 * @param Strng $content
	 * @return void
	 */
	public function setContent($content)
	{
		$this->data = $content;
	}

	/**
	 * The content type of this document
	 * @param String $type
	 * @return void
	 */
	public function setContentType($type)
	{
		$this->content_type = $type;
	}

	/**
	 * Some sort of attached data?
	 * @param $attached_data
	 * @return void
	 */
	public function setAttachedData($attached_data)
	{
		$this->attached_data = $attached_data;
	}

	public function setUri($uri)
	{
		$this->uri = $uri;
	}

	/**
	 * Sets the document id
	 * @param Integer $id
	 * @return Integer
	 */
	public function setDocumentId($id)
	{
		$this->document_id = $id;
	}

	/**
	 * Returns the document id
	 * @return Integer
	 */
	public function getDocumentId()
	{
		return $this->document_id;
	}

	/**
	 * Returns the document contents
	 *
	 * @return string
	 */
	public function getDocumentContent()
	{
		return $this->document_content;
	}

	/**
	 * Sets the contents of a document
	 *
	 * @param string $data
	 * @return void
	 */
	public function setDocumentContent($data)
	{
		$this->document_content = $data;
	}

	/**
	 * Hash used to compare documents
	 * @return string
	 */
	public function getHash()
	{
		return sha1($this->document_content);
	}

	/**
	 * Set/Return whether this document is a preview
	 * document or not
	 *
	 * @param Boolean $bool
	 * @return Boolean
	 */
	public function isPreview($bool = NULL)
	{
		if (is_bool($bool))
		{
			$this->is_preview = $bool;
		}
		return $this->is_preview;
	}

	/**
	 * Returns the docuemnt list id
	 *
	 * @return Integer
	 */
	public function getDocumentListId()
	{
		return $this->document_list_id;
	}

	/**
	 * Set the document list id for thsi document
	 *
	 * @param Integer $id
	 * @return void
	 */
	public function setDocumentListId($id)
	{
		$this->document_list_id = $id;
	}

	/**
	 * Take an object (like the one returned from condor!) and populate
	 * this document object with that data.
	 * @param Object $object
	 * @return void
	 */
	public function populateFromObject($object)
	{
		if (is_object($object))
		{
			$this->setPartId($object->part_id);
			$this->setContent($object->data);
			$this->setUri($object->uri);
			$this->setContentType($object->content_type);
			$this->setAttachedData($object->attached_data);
			$this->setTemplate($object->template_name);
			$this->setUniqueId($object->unique_id);
			$this->setSubject($object->subject);
			$this->setPrinted($object->Printed);
			$this->setSigned($object->Signed);
			$this->setLatestDispatch($object->latest_dispatch);
			$this->setApplicationId($object->application_id);
			$this->setUndefinedTokens($object->undefined_tokens);
			if (empty($object->document_id))
			{
				$this->isPreview(TRUE);
			}
			else
			{
				$this->setDocumentId($object->document_id);
				$this->isPreview(FALSE);
			}
			if (!empty($object->data))
			{
				$this->setDocumentContent($object->data);
			}
			if (!empty($object->attached_documents))
			{
				foreach ($object->attached_documents as $doc_data)
				{
					$doc = new VendorAPI_DocumentData();
					$doc->populateFromObject($doc_data);
					$this->addAttachment($doc);
				}
			}
		}
		else
		{
			throw new InvalidArgumentException('Must pass an object.');
		}
	}
}