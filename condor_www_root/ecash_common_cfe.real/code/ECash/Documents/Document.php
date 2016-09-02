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
	
	
	public function __construct($name, $document_list_id, $esig_capable, $send_method, $contents, $tokens, $archive_id = null, ECash_Application $app = null,  DB_IConnection_1 $db = null, ECash_Models_DocumentList $doclist_model = null)
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
		$this->doclist_model = $doclist_model;
		$types = explode(',', $send_method);
		$this->transport_types = array();
		
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
			return false;

		$document_model = ECash::getFactory()->getModel('Document', $this->db);
		$document_model->date_modified = date("Y-m-d H:i:s");
		$document_model->date_created = date("Y-m-d H:i:s");
		$document_model->company_id = $this->app->getCompanyId();
		$document_model->application_id = $this->app->getId();
		$document_model->document_list_id = $this->ecash_id;
		$document_model->document_method_legacy = 'condor';
		$document_model->document_event_type = $event;
		$document_model->name_other = $this->app->getModel()->name_first . ' '. $this->app->getModel()->name_last;
		//@todo: make this not suck
		$agent = ECash::getAgent();
		$document_model->agent_id = !empty($agent_id) ? $agent_id : (!empty($agent) ? $agent->getAgentId() : 1);
		$document_model->sent_to = $sent_to;
		$document_model->document_method = $transport_method;
		$document_model->transport_method = 'condor';
		$document_model->archive_id = $this->archive_id;
		if($signed)
			$document_model->signature_status = 'signed';
		$document_model->save();
		return true;
	}
	/**
	* setModel
	* 
	* @param ECash_Models_DocumentList $doclist
	* 
	*/
	public function setModelList(ECash_Models_DocumentList $doclist)
	{
		$this->doclist_model = $doclist;
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
		return $this->save($transport_method, 'recieved', null, $agent_id, $signed);
	}
	/**
	* getContents
	* 
	* @return String returns document html 
	* 
	*/	
	public function getContents()
	{
		return $this->contents;
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
		if($transporttype->send($this))
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
		return $this->tokens;
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