<?php

 class ECash_Application_Documents extends ECash_Application_Component
 {
   protected $app;
   protected $tokens;
   
   
  	 public function __construct(ECash_Documents_IToken $tokens, DB_IConnection_1 $db, ECash_Application $app)
  	 {
			$this->application_id = $app->application_id;
			$this->db = $db;
			$this->app = $app;
			$this->tokens = $tokens;
	 } 
	 
	 public function getByID($ArchiveID)
	 {
	 	
	 } 	
	 
	 public function getSendable($flags, ECash_Documents_ITransport $transport = null)
	 {
	 	
	 }
	 public function getRecievable($flags, ECash_Documents_ITransport $transport = null)
	 {
	 	
	 }
	 public function getSent()
	 {
	 	
	 	
	 }
	 public function getRecieved()
	 {
	 	
	 }
	 public function getAll()
	 {
	 	
	 	
	 }
	 public function create(ECash_Documents_Template $template, $preview = false)
	 {
	 	
	 }
	 public function getTokens()
	 {
	 	
	 }
	 public function getPackages()
	 {
	 	
	 	
	 }
	 public function createPackage(Ecash_Documents_TemplatePackage $templatePackage, $preview = false)
	 {
	 	
	 }
	 
    	
    	
    	
    	
 }





?>