<?php
/**
 * Class ECash_Application_Documents
 * Used to integrate documents into the application object
 * 
 * @example 
  
 	$app = ECash::getFactory()->getApplication('900016064',1);
 	//sets generic email tokens for email queues
  	$app->getDocuments()->Set_Generic_Email('testing','test','tests');
 	$doc_app = $app->getDocuments();
 
 
 * * get all sendable docs that can be emailed and send them
 
 	$templates = $doc_app->getSendable(array('email'));
 	foreach($templates as $template)
	{
		if($doc = $doc_app->create($templates->current()))
		{
			echo "\n" . $template->getName() . ' exists';
		//	echo $doc->getContents();	
			$transports = $doc->getTransportTypes();
			$transports['email']->setEmail('richard.bunce@sellingsource.com');
		//	$transports['fax']->setPhoneNumber('7024929871');
		//	$transports['fax']->setCoverSheet(eCash_Config::getInstance()->DOCUMENT_DEFAULT_FAX_COVERSHEET);
			if($doc->send($transports['email'], 4))
			{
				echo "\nsent\n";	
				
			}
			else
			{
				echo "\nnot sent\n";
				
			}
			
			
		}
		else
		{
			echo "\n*" . $template->getName() . ' does not exists';
		}
	}

 * * get a Template By Name and send it

	$template = $doc_app->getTemplateByName('Loan Document');
	
		if($doc = $doc_app->create($template))
		{
			echo "\n" . $template->getName() . ' exists';
		//	echo $doc->getContents();	
			$transports = $doc->getTransportTypes();
			$transports['email']->setEmail('richard.bunce@sellingsource.com');
		//	$transports['fax']->setPhoneNumber('7024929871');
		//	$transports['fax']->setCoverSheet(eCash_Config::getInstance()->DOCUMENT_DEFAULT_FAX_COVERSHEET);
			if($doc->send($transports['email'], 4))
			{
				echo "\nsent\n";	
				
			}
			else
			{
				echo "\nnot sent\n";
				
			}
			
			
		}
		else
		{
			echo "\n*" . $template->getName() . ' does not exists';
		}

* * gets all packages and sends them

		$packages = $doc_app->getPackages();
	foreach($packages as $package)
	{
		echo "\nPackage:" . $package->getName() . "\n";
		foreach($package as $template)
		{
			echo $template->getName() . "\n";
			
		}
		$doc_list = $doc_app->createPackage($package);
		$transports = $doc_list->getTransportTypes();
		$transports['email']->setEmail('richard.bunce@sellingsource.com');
		if($doc_list->send($transports['email']))
		{
			echo "\nsent\n";	
			
		}
		else
		{
			echo "\nnot sent\n";
			
		}
		foreach($doc_list as $doc)
		{
			echo "\n " . $doc->getName() ."\n";
		//	echo $doc->getContents(); 
	
		}
			
	}

* * gets a package by name and sends it

		$package = $doc_app->getPackageByName('Reactivation Packet');
	
		echo "\nPackage:" . $package->getName() . "\n";
		foreach($package as $template)
		{
			echo $template->getName() . "\n";
			
		}
		$doc_list = $doc_app->createPackage($package);
		$transports = $doc_list->getTransportTypes();
		$transports['email']->setEmail('richard.bunce@sellingsource.com');
		if($doc_list->send($transports['email']))
		{
			echo "\nsent\n";	
			
		}
		else
		{
			echo "\nnot sent\n";
			
		}


* * retrieves all sent docs on application and list all the instances they were sent
		
	$documents = $doc_app->getSent();
	foreach($documents as $doc)
	{
		echo "\n" . $doc->getName() . " has been sent\n";
		foreach($doc->getModelList() as $row)
		{
			echo "Sent: {$row->date_created}\n";
			echo "Sent To: {$row->sent_to}\n";
			echo "By: {$row->document_method}\n";
			echo "Agent ID: {$row->agent_id}\n";
			echo "Archive ID: {$row->archive_id}\n";
		}
		
	}			

 * 
 */
 class ECash_Application_Documents extends ECash_Application_Component
 {
     protected $handler;
	 
	 /**
	  * ECash_Application_Documents
	  * 
	  * @param ECash_Documents_IToken
	  * @param DB_IConnection_1
	  * @param ECash_Application
	  * 
	  */
  	 public function __construct(ECash_Documents_IToken $tokens, DB_IConnection_1 $db, ECash_Application $app)
  	 {
		 parent::__construct($db, $app);
		 $this->handler = new ECash_Documents_Handler($tokens, $app, $db);
	 } 
	 /**
	  * getByID
	  * 
	  * @param int $ArchiveID
	  * 
	  * @return ECash_Documents_Document returns Document based on Archive ID
	  */
	 public function getByID($ArchiveID, $other_name = null)
	 {
		return $this->handler->getByID($ArchiveID, $other_name);		
	 } 
	 /**
	  * getByIDFromCondor
	  * 
	  * @param int $ArchiveID
	  * 
	  * @return ECash_Documents_Document returns Document based on Archive ID From Condor
	  */
	public function getByIDFromCondor($ArchiveID, $other_name = null, $override_template = null)
	{
		return $this->handler->getByIDFromCondor($ArchiveID, $other_name, $override_template);
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
		return $this->handler->getSendable($transports);	 	
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
		return $this->handler->getRecievable($transports);
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
		return $this->handler->getEsigable($transports);
	 }
	 /**
	  * getSent
	  * 
	  * @return ECash_Documents_DocumentList returns document list of sent documents
	  */
	 public function getSent()
	 {
	 	return $this->handler->getSent($this->application->getId());
	 }
	 /**
	  * getSent
	  * 
	  * @return ECash_Documents_DocumentList returns document list of sent documents
	  */
	 public function getRecieved()
	 {
		return $this->handler->getRecieved($this->application->getId());
	 }
	 /**
	  * getSentandRecieved
	  * 
	  * @return ECash_Documents_DocumentList returns document list of Sent and Recieved documents
	  */
	 public function getSentandRecieved()
	 {
	 	return $this->handler->getSentandRecieved($this->application->getId());
	 }
	 /**
	  * getAll
	  * 
	  * @return ECash_Documents_TemplateList return Template List of all active ECash documents
	  * 
	  */
	 public function getAll()
	 {
		return $this->handler->getAll();
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
		return $this->handler->getTemplateByName($name);	 	
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
		return $this->handler->getTemplateByNameShort($name_short);
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
		return $this->handler->getTemplateById($id);
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
	 	return $this->handler->create($template, $preview);
	 }
	 /**
	  * getTokens
	  * 
	  * @return ECash_Documents_IToken returns token object
	  */
	 public function getTokens()
	 {
	 	return $this->handler->getTokens();
	 }
	 /**
	  * setPrpc
	  * 
	  * @param object 
	  */
	 public function setPrpc($prpc)
	 {
	 	$this->handler->setPrpc($prpc);
	 }
	 /**
	  * setTokens
	  * 
	  * @param ECash_Documents_IToken 
	  */
	 public function setTokens(ECash_Documents_IToken $tokens)
	 {
	 	$this->handler->setTokens($tokens);
	 }
	 /**
	  * getPackages
	  * 
	  * @return array ECash_Documents_TemplatePackage returns array of Package Templates
	  */
	 public function getPackages()
	 {
		return $this->handler->getPackages();	 	
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
		return $this->handler->getPackageByName($name);
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
	 	return $this->handler->createPackage($templatePackage, $preview);
	 	
	 }
	 public function Set_Generic_Email($sender, $subject, $message)
	 {
	 	$this->handler->Set_Generic_Email($sender, $subject, $message);
	 }
	
 }







?>
