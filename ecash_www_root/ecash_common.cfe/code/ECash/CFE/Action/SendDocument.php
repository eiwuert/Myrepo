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

		public function getReferenceData($param_name, $company_id, $loan_type_id) {
			$retval = array();
			switch($param_name) {
				case "document":
					$model = ECash::getFactory()->getModel('DocumentListList');

					$model->getReferenceData($company_id, $loan_type_id);
					foreach($model as $document)
					{
						$retval[] = array($document->name_short, $document->name_short, $document->company_id);
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
			
			$app = ECash::getApplicationById($application_id);
			$template = $app->getDocuments()->getTemplateByNameShort($params['document']);
	
			if($doc = $app->getDocuments()->create($template))
			{
				$transports = $doc->getTransportTypes();
				$transports['email']->setEmail($app->email);

				$doc->send($transports['email'], ECash::getAgent()->getAgentId());
				
			}

		}
		
		public function isEcashOnly()
		{
			return true;
		}
	}
	
?>
