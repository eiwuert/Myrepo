<?php
/**
 * Class ECash_Documents_Handler
 * Used to handle document operations
 * 
 *@example 
 *$handler = new ECash_Documents_Handler();
 *$document = $handler->getByID($archive_id);
 *
 *  */
 class ECash_Documents_Handler 
 {
  	 protected $app;
   	 protected $tokens;
   	 protected $company_id;
	 protected $loan_type_id;
	 protected $have_tokens;
	 protected $prpc;
	 /**
	  * ECash_Documents_Handler
	  *  
	  * @param ECash_Documents_IToken
	  * @param ECash_Application
	  * @param DB_IConnection_1
	  * 
	  */  
  	 public function __construct(ECash_Documents_IToken $tokens = null,  ECash_Application $app = null, DB_IConnection_1 $db = null)
  	 {
  	 		$this->prpc = new ECash_Documents_Condor();
			if(empty($app))
			{
				$this->company_id = ECash::getCompany()->company_id;
				$this->loan_type_id = 0;
			}
			else
			{
				$this->app = $app;
				$this->company_id = $this->app->getCompanyID();
				$this->loan_type_id = $this->app->loan_type_id;
			}
			if(empty($db))
			{
				$this->db = ECash::getMasterDb();
			}
			else
			{
				$this->db = $db;
			}
			if(empty($tokens))
			{
				$this->have_tokens = false;	
			}
			else
			{
				$this->have_tokens = true;
				$this->tokens = $tokens;
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
	 * This retrieves the condor template names
	 * 
	 * @return array
	 */
	public function getCondorList()
	{
		return $this->prpc->Get_Template_Names();
	}

	 /**
	  * getByIDFromCondor
	  * 
	  * @param int $archive_id
	  * @param string $name_other
	  * @param string $override_template - template name to be used if none is specified by condor
	  * 
	  * @return ECash_Documents_Document returns Document based on Archive ID
	  */
	public function getByIDFromCondor($archive_id, $name_other = null, $override_template = null)
	{
		$document = FALSE;
		$condor_document = $this->prpc->Find_By_Archive_Id($archive_id);

		if ($condor_document !== FALSE)
		{
			if (empty($condor_document->template_name))
			{
				$template_name = empty($override_template) ? 'Other' : $override_template;
			}
			else
			{
				$template_name = $condor_document->template_name;
			}

			$doc_list_model = ECash::getFactory()->getModel('DocumentList', $this->db);
			$list_model_loaded = $doc_list_model->loadBy(array(
				'name' => $template_name, 
				'company_id' => $this->company_id
			));

			if ($list_model_loaded)
			{
				$doc_list = ECash::getFactory()->getDocumentClient()->findDocumentByArchiveId($archive_id);
				$tokens = ($this->have_tokens) ? $this->tokens : NULL;

				$document = new ECash_Documents_Document(
					$template_name,
					$doc_list_model->document_list_id,
					$doc_list_model->esig_capable,
					$doc_list_model->send_method,
					null,
					$tokens,
					null,
					$archive_id,
					$this->app,
					$this->db,
					$doc_list,
					$name_other
				);
			}
		}

		return $document;
	}

	 /**
	  * Gets a document object loaded from an archive id
	  * 
	  * @param int $archive_id
	  * @param string $name_other
	  * 
	  * @return ECash_Documents_Document|FALSE - False if unable to load from archive_id
	  */
	 public function getByID($archive_id, $name_other = null)
	 {
	 	$document = FALSE;
		$client_documents = ECash::getFactory()->getDocumentClient()->findDocumentByArchiveId($archive_id);

		if (!empty($client_documents))
		{
			$client_document = $client_documents[0];
			/**
			 * There is a 'document_list' table in the app service but that is only there for hash.
			 * The real/complete document list info still resides in LDB. The document_id that is
			 * returned however is the service one NOT the ldb one, so look it up in ldb by name.
			 */
			$doc_list_model = ECash::getFactory()->getModel('DocumentList', $this->db);
			$list_model_loaded = $doc_list_model->loadBy(array(
				'name' => $client_document->document_list_name,
				'company_id' => $this->company_id
			));

			if ($list_model_loaded)
			{
				$tokens = ($this->have_tokens) ? $this->tokens : NULL;

				$document = new ECash_Documents_Document(
					$doc_list_model->name,
					$doc_list_model->document_list_id,
					$doc_list_model->esig_capable,
					$doc_list_model->send_method,
					null,
					$tokens,
					null,
					$archive_id,
					$this->app,
					$this->db,
					$client_documents,
					$name_other
				);
			}
		}

		return $document;
	 }

	 /**
	  * getSendable
	  * 
	  * @param array $transports
	  * 
	  * @return ECash_Documents_TemplateList list of templates that are sendable
	  */
	 public function getSendable($transports = array())
	 {
	 	$docs = ECash::getFactory()->getModel('DocumentListList', $this->db);
	 	$docs->getSendable($this->company_id, $this->loan_type_id);
		//Removing dependancy on Condor
	 	//$Condor_Templates = $this->prpc->Get_Template_Names();
		$templates = array();
	 	foreach($docs as $doc)
	 	{
	 		$methods = explode(',', $doc->send_method);
	 		$include = false;
	 		foreach($transports as $transport)
	 		{
	 			if(in_array($transport, $methods))
	 				$include = true;
	 		}
	 	//	if(($include || empty($transports)) && in_array($doc->name, $Condor_Templates))
			if(($include || empty($transports)))
	 			$templates[] = new ECash_Documents_Template($doc->name, $doc, $this->app, $this->db);
	 	}
	 	return new ECash_Documents_TemplateList($templates);
	 	
	 }
	 /**
	  * getRecievable
	  * 
	  * @param array $transports
	  * 
	  * @return ECash_Documents_TemplateList list of templates that are recievable
	  */
	 public function getRecievable($transports = array())
	 {
	 	$docs = ECash::getFactory()->getModel('DocumentListList', $this->db);
	 	$docs->getRecievable($this->company_id, $this->loan_type_id);
		//Removing dependancy on Condor	 	
		//$Condor_Templates = $this->prpc->Get_Template_Names();
		//$Condor_Templates[] = 'Other';
		$templates = array();
	 	foreach($docs as $doc)
	 	{
	 		$methods = explode(',', $doc->send_method);
	 		$include = false;
	 		foreach($transports as $transport)
	 		{
	 			if(in_array($transport, $methods))
	 				$include = true;
	 		}
	 	//	if(($include || empty($transports)) && in_array($doc->name, $Condor_Templates))
			if(($include || empty($transports)))
 	 			$templates[] = new ECash_Documents_Template($doc->name, $doc, $this->app, $this->db);
	 	}
	 	return new ECash_Documents_TemplateList($templates);
	 }
	 	 /**
	  * getEsigable
	  * 
	  * @param array $transports
	  * 
	  * @return ECash_Documents_TemplateList list of templates that are esigable
	  */
	 public function getEsigable($transports = array())
	 {
	 	$docs = ECash::getFactory()->getModel('DocumentListList', $this->db);
	 	$docs->getDocs($this->company_id, $this->loan_type_id, array('esig_capable' => 'yes'), 'send');
		//Removing dependancy on Condor 
	 //	$Condor_Templates = $this->prpc->Get_Template_Names();
		$templates = array();
	 	foreach($docs as $doc)
	 	{
	 		$methods = explode(',', $doc->send_method);
	 		$include = false;
	 		foreach($transports as $transport)
	 		{
	 			if(in_array($transport, $methods))
	 				$include = true;
	 		}
	 	//	if(($include || empty($transports)) && in_array($doc->name, $Condor_Templates))
			if(($include || empty($transports)))
	 			$templates[] = new ECash_Documents_Template($doc->name, $doc, $this->app, $this->db);
	 	}
	 	return new ECash_Documents_TemplateList($templates);
	 }
	 /**
	  * getSent
	  * 
	  * @param $application_id
	  * 
	  * @return ECash_Documents_DocumentList returns document list of sent documents
	  */
	 public function getSent($application_id)
	 {
	 	//$doc_model = ECash::getFactory()->getModel('Document', $this->db);
	 	//$documents = $doc_model->loadAllBy(array('application_id' => $application_id, 'document_event_type' => 'sent'));
		$documents = ECash::getFactory()->getDocumentClient()->findAllDocumentsByApplicationId($application_id);
	 	$docs = array();
	 	foreach($documents as $doc)
	 	{
			if($doc->document_event_type == 'sent')
			{
		 		$document = $this->getById($doc->archive_id);
				if($document)
				{
					$document->setECashID($doc->document_id);
					$docs[] = $document;
				}
				else
				{
					//@todo determine what to do if document template doesn't exist in ECash or Condor
				}	 	
			}
	 	}
		return new ECash_Documents_DocumentList($docs);
	 	
	 }
	 /**
	  * getRecieved
	  * 
	  * @param $application_id
	  * 
	  * @return ECash_Documents_DocumentList returns document list of Recieved documents
	  */
	 public function getRecieved($application_id)
	 {
	 	//$doc_model = ECash::getFactory()->getModel('Document', $this->db);
	 	//$documents = $doc_model->loadAllBy(array('application_id' => $application_id, 'document_event_type' => 'received'));
		$documents = ECash::getFactory()->getDocumentClient()->findAllDocumentsByApplicationId($application_id);
	 	$docs = array();
	 	foreach($documents as $doc)
	 	{
			if($doc->document_event_type == 'received')
			{
		 		$document = $this->getById($doc->archive_id);
				if($document)
				{
					$document->setECashID($doc->document_id);
					$docs[] = $document;
				}
				else
				{
					//@todo determine what to do if document template doesn't exist in ECash or Condor
				}
			}	
	 	}
		return new ECash_Documents_DocumentList($docs);
	 	
	 }
	 /**
	  * getSentandRecieved
	  * 
	  * @param $application_id
	  * 
	  * @return ECash_Documents_DocumentList returns document list of Sent and Recieved documents
	  */
	 public function getSentandRecieved($application_id)
	 {
	 	//$doc_model = ECash::getFactory()->getModel('Document', $this->db);
	 	//$documents = $doc_model->loadAllBy(array('application_id' => $application_id));
		$documents = ECash::getFactory()->getDocumentClient()->findAllDocumentsByApplicationId($application_id);		
		$docs = array();
		$used_ids = array();
	 	foreach ($documents as $doc)
	 	{
			if(!in_array($doc->archive_id, $used_ids))
			{
	 			$used_ids[] = $doc->archive_id;
				$document = $this->getById($doc->archive_id);
				if($document)
				{
					$document->setECashID($doc->document_id);
					$docs[] = $document;
				}
				else
				{
					//@todo determine what to do if document template doesn't exist in ECash or Condor
				}
			}	
	 	}

		return new ECash_Documents_DocumentList($docs);
	 	
	 }
	 /**
	  * getAll
	  * 
	  * @return ECash_Documents_TemplateList return Template List of all active ECash documents
	  * 
	  */
	 public function getAll()
	 {
	 	$docs = ECash::getFactory()->getModel('DocumentListList', $this->db);
	 	$docs->getdocs($this->company_id, $this->loan_type_id);
		//Removing dependancy on Condor
	 	//$Condor_Templates = $this->prpc->Get_Template_Names();
	 	//$Condor_Templates[] = 'Other';
	 	$templates = array();
	 	foreach($docs as $doc)
	 	{
	 	//	if(in_array($doc->name, $Condor_Templates))
	 			$templates[] = new ECash_Documents_Template($doc->name, $doc, $this->app, $this->db);
	 	}
	 	return new ECash_Documents_TemplateList($templates);
	 }
	 /**
	  * getTemplatebyNameShort
	  * 
	  * @param string $name_short
	  * 
	  * @return ECash_Documents_Template return Template of ECash document
	  * 
	  */
	 public function getTemplateByNameShort($name_short)
	 {
	 	$doc = ECash::getFactory()->getModel('DocumentList', $this->db);
	 	$doc->getByNameShort($this->company_id, $this->loan_type_id, $name_short);
		//Removing dependancy on Condor 	 
		//$Condor_Templates = $this->prpc->Get_Template_Names();
	 	
	 	if(!is_null($doc->document_list_id))
	 	{
	 	//	if(in_array($doc->name, $Condor_Templates))
	 			return new ECash_Documents_Template($doc->name, $doc, $this->app, $this->db);
	 	//	else
	 	//		return false;
	 	}
	 	else
	 	{
	 		return false;
	 	}
	 	
	 }
	 /**
	  * getTemplatebyName
	  * 
	  * @param string $name
	  * 
	  * @return ECash_Documents_Template return Template of ECash document
	  * 
	  */
	 public function getTemplateByName($name)
	 {
	 	$doc = ECash::getFactory()->getModel('DocumentList', $this->db);
	 	$doc->loadby(array('company_id' => $this->company_id, 'name' => $name, 'active_status' => 'active'));
		//Removing dependancy on Condor 	 
		//$Condor_Templates = $this->prpc->Get_Template_Names();
	 	
	 	if(!is_null($doc->document_list_id))
	 	{
	 	//	if(in_array($doc->name, $Condor_Templates))
	 			return new ECash_Documents_Template($doc->name, $doc, $this->app, $this->db);
	 	//	else
	 	//		return false;
	 	}
	 	else
	 	{
	 		return false;
	 	}
	 	
	 }
	 /**
	  * getTemplatebyId
	  * 
	  * @param int $id
	  * 
	  * @return ECash_Documents_Template return Template of ECash document
	  * 
	  */
	 public function getTemplateById($id)
	 {
	 	$doc = ECash::getFactory()->getModel('DocumentList', $this->db);
	 	$doc->loadby(array('document_list_id' => $id, 'active_status' => 'active'));
		//Removing dependancy on Condor	 
	//	$Condor_Templates = $this->prpc->Get_Template_Names();
	 	
	 	if(!is_null($doc->name))
	 	{
	 //		if(in_array($doc->name, $Condor_Templates))
	 			return new ECash_Documents_Template($doc->name, $doc, $this->app, $this->db);
	 //		else
	 //			return false;
	 	}
	 	else
	 	{
	 		return false;
	 	}
	 	
	 }
	 /** 
	  * create
	  * 
	  * @param ECash_Documents_Template $template
	  * @param boolean $preview
	  * 
	  * @return ECash_Documents_Document returns created document 
	  */
	 public function create(ECash_Documents_Template $template, $preview = false)
	 {
	 	if($this->have_tokens)
	 		return $template->create($this->tokens, $preview);
	 	else
	 		return false;
	 }
	 /**
	  * getTokens
	  * 
	  * @return ECash_Documents_IToken returns token object
	  */
	 public function getTokens()
	 {
	 	return $this->tokens;
	 }
	  /**
	  * setTokens
	  * 
	  * @return ECash_Documents_IToken returns token object
	  */
	 public function setTokens(ECash_Documents_IToken $tokens)
	 {
	 	$this->tokens = $tokens;
	 	$this->have_tokens = true;
	 }
	 /**
	  * getPackages
	  * 
	  * @return array ECash_Documents_TemplatePackage returns array of Package Templates
	  */
	 public function getPackages()
	 {
	 	$document_data = ECash::getFactory()->getData('Document', $this->db);
	 	$package_list = $document_data->get_package_list($this->company_id, $this->loan_type_id);
	 	$packages = array();
		//Removing dependancy on Condor
	//	$Condor_Templates = $this->prpc->Get_Template_Names();
	 	foreach($package_list as $package_name => $package)
	 	{
	 		$templates = array();
	 		foreach($package as $doc)
		 	{
	//	 		if(in_array($doc->child_name, $Condor_Templates))
	//	 		{
					$document_list_model = ECash::getFactory()->getModel('DocumentList', $this->db);
		 			$document_list_model->loadBy(array('document_list_id' => $doc->child_id));
		 			$templates[] = new ECash_Documents_Template($doc->child_name, $document_list_model, $this->app, $this->db);
	//	 		}
		 		$package_id = $doc->document_package_id;
		 		$body_id = $doc->package_body_id;
				$name_short = $doc->name_short;
		 	}
			$package_body_model = ECash::getFactory()->getModel('DocumentList', $this->db);
		 	$package_body_model->loadBy(array('document_list_id' => $body_id));
		 	$packages[$package_name] = new ECash_Documents_TemplatePackage($templates, $package_name, $name_short, $package_id, $package_body_model);
	 		
	 	}
	 	return $packages;
	 	
	 }
	 /**
	 * getPackageByName
	 * 
	 * @param string $name
	 * 
	 * @return ECash_Documents_TemplatePackage returns Package Template
	 */
	 public function getPackageByName($name)
	 {
	 	$packages = $this->getPackages();
	 	
	 	if($packages[$name] instanceof ECash_Documents_TemplatePackage)
	 		return $packages[$name];
	 	else
	 		return false;
	 }
	 /**
	  * createPackage
	  * 
	  * @param Ecash_Documents_TemplatePackage $templatePackage
	  * @param boolean $preview
	  * 
	  * @return ECash_Documents_DocumentPackage return created packaged documents
	  * 
	  */
	 public function createPackage(Ecash_Documents_TemplatePackage $templatePackage, $preview = false)
	 {
	 	if($this->have_tokens)
	 	 	return $templatePackage->create($this->tokens, $preview); 	
	 	else
	 		return false;
	 }
	 public function Set_Generic_Email($sender, $subject, $message)
	 {
	 	if($this->have_tokens)
	 		$this->tokens->Set_Generic_Email($sender, $subject, $message);
		else
			return false;
	 }
	
 }



?>
