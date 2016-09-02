<?php
/**
 * ECash_Documents_Template
 * represents a document template without having to create an actual document
 * 
 */
class ECash_Documents_Template
{
	protected $name;
	protected $app;
	protected $db;
	protected $transport_types;
	protected $ecash_id;
	protected $is_signable;
	protected $prpc;
	
	public function __construct($name, $document_list_id, $esig_capable, $send_method, ECash_Application $app = null, DB_IConnection_1 $db = null)
	{
		$this->name = $name;
		$this->app = $app;
		$this->db = $db;
		$this->is_signable  = $esig_capable;
		$this->ecash_id = $document_list_id;
		$this->transport_types = array();
		$types = explode(',', $send_method);
		
		foreach($types as $type)
		{
			switch($type)
			{
				case 'email':
					$this->transport_types[] = new ECash_Documents_Email();
				break;
				case 'fax':
					$this->transport_types[] = new ECash_Documents_Fax();
				break;
				default:
				//	throw new exception('Unknown Transport Type');		
			}
			
		}
	}
	/**
	 * create
	 * 
	 * @param ECash_Documents_IToken $tokens
	 * @param $preview 
	 * 
	 * @return ECash_Documents_Document
	 */
	public function create(ECash_Documents_IToken $tokens, $preview = false)
	{
		//@todo: make prpc call to get document
		$document = ECash_Documents_Condor::Prpc()->Create($this->name, $tokens->getTokens(), !$preview, $this->app->getID(), $this->app->getTrackId(), null);
		if($document === FALSE)
		{
			return false;
		}
		else
		{
			$doc = ECash::getFactory()->getReferenceModel('DocumentList', $this->db);
			$doc->loadby(array('name' => $this->name, 'company_id' => $this->app->getCompanyId()));		
			if(!empty($doc->document_list_id))
			{
				//This is done because Condor returns two different structures if preview or not
				if($preview)
				{
					$contents = $document->data;
					$archive_id = null;
				}
				else
				{
					$contents = $document['document']->data;
					$archive_id = $document['archive_id'];
				}	
				
				return new ECash_Documents_Document($this->name, $doc->document_list_id, $doc->esig_capable, $doc->send_method, $contents, $tokens->getTokens(), $archive_id, $this->app, $this->db);
			}
			else
			{
				return false;
			}
		}
	}
	/**
	* getTemplateTokens
	* 
	* @return stdclass 
	* 
	*/
	public function getTemplateTokens()
	{
		return ECash_Documents_Condor::Prpc()->Get_Template_Tokens($this->name);
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
	* @return array 
	* 
	*/
	public function getTransportTypes()
	{
		return $this->transport_types;
	}
	/**
	* getEcashID
	* 
	* @return int 
	* 
	*/
	public function getEcashID()
	{
		return $this->ecash_id;
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
	
	
}


?>