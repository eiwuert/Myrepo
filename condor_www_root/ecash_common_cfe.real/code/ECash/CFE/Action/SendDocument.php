<?php
	class ECash_CFE_Action_SendDocument extends ECash_CFE_Base_BaseAction
	{
		public function getType()
		{
			return "SendDocument";
		}
		
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('document', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
			);
		}

		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "document":
					$model = ECash::getFactory()->getModel('DocumentListList');
					$model->loadReferenceData();
					foreach($model as $document)
					{
						$retval[] = array($document->name_short, $document->name, $document->company_id);
					}
					break;
			}
			return $retval;
		}

		public function execute(ECash_CFE_IContext $c)
		{
			
			// evaluate any expression parameters
			$params = $this->evalParameters($c);
						
			//Get Company and application Ids
			$company_id = $c->getAttribute('company_id');
			$application_id=$c->getAttribute('application_id');
			
			//get session if applicable
			$session_id =  isset($_REQUEST['ssid']) ? $_REQUEST['ssid'] : null;
			
			
			//Get Server Object
			$server = Server_Factory::get_server_class(null,$session_id);
			$server->Set_Company($company_id);
			
			//Get document object
			$document = ECash::getFactory()->getReferenceModel('DocumentList');
			$document->loadBy(array('company_id' => $company_id,
								  	'name_short' => $params['document']));
																		
			//get document_id
			$doc = array($document->document_list_id);
			
			//Send the document!
			$result = eCash_Document::singleton($server,NULL)->Send_Document($application_id, $doc, 'email', NULL, NULL);
			
		}
		
		public function isEcashOnly()
		{
			return true;
		}
	}
	
?>
