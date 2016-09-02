<?php
/**
 * Class ECash_Documents_Document
 * Representation of a Condor Document
 * 
 * 
 * 
 */
class ECash_Documents_Document
{
	protected $db;
	protected $app;
	protected $name;
	protected $transport_types;
	protected $is_signable;
	protected $contents;
	protected $archive_id;
	protected $ecash_id;
	protected $tokens;
	protected $doclist_model;
	protected $content_type;
	protected $name_other;
	protected $prpc;
	
	public function __construct($name, $document_list_id, $esig_capable, $send_method, $contents, $tokens, $content_type, $archive_id = null, ECash_Application $app = null,  DB_IConnection_1 $db = null, $doclist_model = null, $name_other = null)
	{
		$this->name = $name;
		$this->app = $app;
		$this->db = $db;
		$this->tokens = $tokens;
		$this->is_signable  = $esig_capable;
		$this->ecash_id = $document_list_id;
		$this->transport_types = array();
		$this->archive_id = $archive_id;
		$this->contents = $contents;
		$this->name_other = $name_other;
		if(!empty($doclist_model))
			$this->doclist_model = $doclist_model;
		$types = explode(',', $send_method);
		$this->transport_types = array();
		$this->content_type = $content_type;
		$this->prpc = new ECash_Documents_Condor();
		
		foreach($types as $type)
		{
			switch($type)
			{
				case 'email':
					$this->transport_types['email'] = new ECash_Documents_Email();
				break;
				case 'fax':
					$this->transport_types['fax'] = new ECash_Documents_Fax();
				break;
				default:
		//			throw new exception('Unknown Transport Type');		
			}
			
		}
	
	}
	/**
	 * This sets the prpc used in this class
	 * 
	 * @param object
	 */
	public function setPrpc($prpc)
	{
		$this->prpc = $prpc;
	}
	/**
	* save
	* 
	* @param string $transport_method
	* @param string $event
	* @param string $sent_to
	* @param int $agent_id
	* @param bool $signed
	* 
	* @return bool returns whether record is saved or not
	*/
	public function save($transport_method, $event, $sent_to, $agent_id = null, $signed = false)
	{
		if(empty($this->archive_id))
		{
			ECash::getLog('documents')->write("Missing archive_id: " . var_export($this, TRUE));
			return false;
		}

		$document_model = ECash::getFactory()->getModel('Document', $this->db);
		$document_model->date_modified = date("Y-m-d H:i:s");
		$document_model->date_created = date("Y-m-d H:i:s");
		$document_model->company_id = $this->app->getCompanyId();
		$document_model->application_id = $this->app->getId();
		$document_model->document_list_id = $this->ecash_id;
		$document_model->document_event_type = $event;
		$document_model->name_other = $this->name_other;
		//@todo: make this not suck
		$agent = ECash::getAgent();
		$document_model->agent_id = !empty($agent_id) ? $agent_id : (!empty($agent) ? $agent->getAgentId() : 1);
		$document_model->sent_to = $sent_to;
		$document_model->document_method = $transport_method;
		$document_model->transport_method = 'condor';
		$document_model->archive_id = $this->archive_id;
		if($signed)
			$document_model->signature_status = 'signed';
		return $document_model->save() == 1;
	}
	/**
	* setModel
	* 
	* @param ECash_Models_DocumentList $doclist
	* 
	*/
	public function setModelList(ECash_Models_DocumentList $doclist)
	{
		$this->doclist_model = $doclist->toArray();
	}
	/**
	* getModel
	* 
	* @return ECash_Models_DocumentList 
	* 
	*/
	public function getModelList()
	{
		return $this->doclist_model;
	}
	/**
	* recieved
	* 
	* @param string $transport_method
	* @param int $agent_id
	* @param bool $signed
	* 
	* @return bool returns whether record is saved or not
	*/	
	public function recieved($transport_method, $agent_id = null, $signed = false)
	{
		
		if(!empty($this->app))
		{
			$this->prpc->Set_Application_Id($this->archive_id, $this->app->application_id);
			$this->Check_Status_Trigger();
			$engine = $this->app->getEngine();
			$engine->executeEvent('DOCUMENT_RECEIVED', array($this->ecash_id));
			return $this->save($transport_method, 'received', null, $agent_id, $signed);
		}
		else
		{
			return false;
		}
	}
	/**
	 * Checks if a status change should occur based on database values
	 * 
	 */
	 protected function Check_Status_Trigger()
	 {
	 	$model = ECash::getFactory()->getModel('DocumentProcess', $this->db);

	 	if($model->loadBy(array('document_list_id' => $this->ecash_id, 'current_application_status_id' => $this->app->application_status_id)))
	 	{
	 		$this->app->application_status_id = $model->application_status_id;
	 		$this->app->save();
	 	}
	 	
	 }
	/**
	* getContents
	* 
	* @return String returns document html 
	* 
	*/	
	public function getContents()
	{
		if($this->contents == null)
		{
			$document = $this->prpc->Find_By_Archive_Id($this->archive_id);
			if($document)
			{
				$this->contents = $document->data;
				$this->content_type = $document->content_type;	
			}
		}
		return $this->contents;
	}
	/**
	* getContentType
	* 
	* @return String returns document content type 
	* 
	*/	
	public function getContentType()
	{
		if($this->content_type == null)
		{
			$document = $this->prpc->Find_By_Archive_Id($this->archive_id);
			if($document)
			{
				$this->contents = $document->data;
				$this->content_type = $document->content_type;	
			}
		}
		return $this->content_type;
	}
	/**
	 * send
	 * 
	 * @param ECash_Documents_ITransport $transporttype
	 * @param int $agent_id
	 * 
	 * @return bool returns whether document was sent and saved or not
	 */
	public function send(ECash_Documents_ITransport $transporttype, $agent_id = null)
	{
		//Check for Document Body
		$body_name = $this->checkForBody();
		
		if($transporttype->send($this, $body_name))
		{
			switch($transporttype->getType())
			{
				case 'email':
					$sent_to = $transporttype->getEmail();
				break;
				case 'fax':
					$sent_to = $transporttype->getPhoneNumber();
				break;
				default:
					throw new exception('Unknown Transport Type'); 
			}

			return $this->save($transporttype->getType(), 'sent', $sent_to, $agent_id);
		}
		else
		{
			return false;
		}
	}
	/**
	* checkForBody
	*
	* Checks to determine if document had a body document
	*
	* @return string $body_name
	*/
	private function checkForBody()
	{
		$body_list = ECash::getFactory()->getModel('DocumentListBody', $this->db);
		$document = ECash::getFactory()->getModel('DocumentList', $this->db);

		if($body_list->loadBy(array('document_list_id' => $this->ecash_id)))
		{
			if($document->loadBy(array('document_list_id' => $body_list->document_list_body_id)))
				return $document->name;		
			else
				return null;		
		}
		else
		{
			return null;
		}

	}
	/**
	* isSignable
	* 
	* @return bool  
	* 
	*/
	public function isSignable()
	{
		return $this->is_signable;
	}
	/**
	* getTransportTypes
	* 
	* @return array of ECash_Documents_ITransport 
	* 
	*/
	public function getTransportTypes()
	{
		return $this->transport_types;
	}
	/**
	* getArchiveID
	* 
	* @return int 
	* 
	*/
	public function getArchiveID()
	{
		return $this->archive_id;
	}
	/**
	* getECashID
	* 
	* @return int 
	* 
	*/
	public function getEcashID()
	{
		return $this->ecash_id;
	}
	/**
	* setECashID
	* 
	* @param int $id 
	* 
	*/
	public function setECashID($id)
	{
		$this->ecash_id = $id;
	}
	/**
	* getName
	* 
	* @return string
	* 
	*/
	public function getName()
	{
		return $this->name;
	}
	/**
	* getTokens
	* 
	* @return stdclass 
	* 
	*/
	public function getTokens()
	{
		if(!empty($this->tokens))
		{
			return $this->tokens->getTokens();
		}	
		return null;
	}
	/**
	* getApp
	* 
	* @return ECash_Application 
	* 
	*/
	public function getApp()
	{
		return $this->app;
	}	
	
}


?>
