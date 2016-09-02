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
	protected $model;
	
	public function __construct($name,ECash_Models_ObservableWritableModel $model, ECash_Application $app = null, DB_IConnection_1 $db = null)
	{
		$this->name = $name;
		$this->app = $app;
		$this->db = $db;
		$this->is_signable  = $model->esig_capable;
		$this->ecash_id = $model->document_list_id;
		$this->model = $model;
		$this->transport_types = array();
		$types = explode(',', $model->send_method);
		$this->prpc = new ECash_Documents_Condor();
		
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
	 * This sets the prpc used in this class
	 * 
	 * @param object
	 */
	public function setPrpc($prpc)
	{
		$this->prpc = $prpc;
	}
	/**
	 * getModel
	 * 
	 * @return ECash_Documents_Template returns documentlist model that represents the template
	 */
	public function getModel()
	{
		return $this->model;
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
		$document = $this->prpc->Create($this->name, $tokens->getTokens(), !$preview, $this->app->getID(), $this->app->getTrackId(), null);		

		if($document === FALSE)
		{
			ECash::getLog('documents')->write("Problem sending, condor says: " . var_export($document, TRUE));
			return false;
		}
		else
		{
			$doc = ECash::getFactory()->getModel('DocumentList', $this->db);
			$doc->loadby(array('name' => $this->name, 'company_id' => $this->app->getCompanyId()));		
			if(!empty($doc->document_list_id))
			{
				//This is done because Condor returns two different structures if preview or not
				if($preview)
				{
					$contents = $document->data;
					$archive_id = null;
					$content_type = $document->content_type;
				}
				else
				{
					$contents = $document['document']->data;
					$archive_id = $document['archive_id'];
					$content_type = $document['document']->content_type;
					
				}	
				
				return new ECash_Documents_Document($this->name, $doc->document_list_id, $doc->esig_capable, $doc->send_method, $contents, $tokens, $content_type, $archive_id, $this->app, $this->db);
			}
			else
			{
				ECash::getLog('documents')->write("Problem with doc list: " . var_export($doc, TRUE));
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
		return $this->prpc->Get_Template_Tokens($this->name);
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
