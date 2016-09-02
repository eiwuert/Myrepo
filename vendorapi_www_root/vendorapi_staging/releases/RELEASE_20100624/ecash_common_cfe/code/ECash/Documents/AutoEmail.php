<?php
/**
 * ECash_Documents_AutoEmail
 * 
 * Defines auto email class used to queue and send queued documents
 * 
 */
class ECash_Documents_AutoEmail
{
	/**
	 * Sends a document
	 *
	 * @param ECash_Application $app
	 * @param ECash_Documents_Template $package_template
	 */
	static public function SendDocument(ECash_Application $app, ECash_Documents_Template $template)
	{
        $docs = $app->getDocuments();
        if($template)
        {
            $doc = $docs->create($template);
            if($doc)
            {
                $transport_types = $doc->getTransportTypes();
                $send_method = "email";
                $transport = $transport_types[$send_method];
                $cust_email = $app->email;
                $transport->setEmail($cust_email);
                
                if(!$doc->send($transport, ECash::getAgent()->getAgentId()))
                {
                    ECash::getLog('documents')->write("Send Result: " . 'Document Failed to Send' );
                }
                else
                {
                    ECash::getLog('documents')->write("Send Result: " . 'Document Sent' );
                }
                
            }
            else
            {
                ECash::getLog('documents')->write("Send Result: " . 'Document Failed Creation' );
            }
        }
        else
        {
            ECash::getLog('documents')->write("Send Result: " . 'Document Template Failed Creation' );
        }
	}
	/**
	 * Sends a packaged document
	 *
	 * @param ECash_Application $app
	 * @param ECash_Documents_TemplatePackage $package_template
	 */
	static public function SendPackage(ECash_Application $app, ECash_Documents_TemplatePackage $package_template)
	{
			$docs = $app->getDocuments();
			if($package_template)
			{
				$package = $docs->createPackage($package_template);
				if($package)
				{
					
					$transport_types = $package->getTransportTypes();
					$send_method = "email";
					$transport = $transport_types[$send_method];
					$cust_email = $app->email;
					$transport->setEmail($cust_email);
					
					if(!$package->send($transport, ECash::getAgent()->getAgentId()))
					{
						ECash::getLog('documents')->write("Send Result: " . 'Package Failed to Send' );
					}
					else
					{
						ECash::getLog('documents')->write("Send Result: " . 'Package Sent' );
					}
					
				}
				else
				{
					ECash::getLog('documents')->write("Send Result: " . 'Package Failed Creation' );
				}
			}
			else
			{
				ECash::getLog('documents')->write("Send Result: " . 'Package Template Failed Creation' );
			}
	}
	/**
	 * Determines if a name given is a package or a document and sends it
	 *
	 * @param int $application_id
	 * @param string $doc_id
	 * @param int $transaction_register_id
	 */
	static public function Send($application_id, $doc_id, $transaction_id = NULL)
	{
		$doc = "";
		$app = ECash::getApplicationById($application_id);
		$loan_type_model = ECash::getFactory()->getModel('LoanType');
		$loan_type_model->loadBy(array('loan_type_id' => $app->loan_type_id));
		require_once CUSTOMER_LIB . "/autoemail_list.php";
		$doc = Get_AutoEmail_Doc(ECash::getServer(), $doc_id, $loan_type_model->name_short);
	
		if ($doc) 
		{
			$docs = $app->getDocuments();
			//check if document exists first, if so send document, else if check if it is a package, if so send package
			if($template = $docs->getTemplateByNameShort($doc))
			{
				ECash::getLog('documents')->write("Sending document $doc to application_id $application_id", LOG_WARNING);
				ECash_Documents_AutoEmail::SendDocument($app, $template);
			}
			elseif($template = $docs->getPackageByName($doc))
			{
				ECash::getLog('documents')->write("Sending Package $doc to application_id $application_id", LOG_WARNING);
				ECash_Documents_AutoEmail::SendPackage($app, $template);
			}
			else
			{
				ECash::getLog('documents')->write("Sending $doc to application_id $application_id, does not exist as a document or package");
			}	
		}
		else
		{
			ECash::getLog('documents')->write("Sending $doc_id to application_id $application_id, no name returned from customer Get_AutoEmail_Doc");
		}
	}
	
	/**
	 * Adds a document to the document queue for later sending.
	 *
	 * @param int $application_id
	 * @param string $doc_id
	 * @param int $transaction_register_id
	 */
	static public function Queue_For_Send($application_id, $doc_id, $transaction_register_id = NULL) {
		$app = ECash::getApplicationById($application_id);
		$document_queue_model = ECash::getFactory()->getModel('DocumentQueue');
		$document_queue_model->date_created = time();
		$document_queue_model->company_id = $app->company_id;
		$document_queue_model->application_id = $application_id;
		$document_queue_model->document_name = $doc_id;
		$document_queue_model->transaction_register_id = $transaction_register_id; 
		$document_queue_model->save();
		ECash::getLog('documents')->write("Queued to send: " . $application_id ." : ".$doc_id );
	}
	
	/**
	 * Sends all or a number of queued documents.
	 * 
	 * To send all documents that are queued call this function with only 1 
	 * parameter. To send a certain number of documents, pass that number as 
	 * the second parameter. Returns true if the documents sent.
	 *
	 * @param Server $server
	 * @param int $number_to_send
	 * @return bool
	 */
	static public function Send_Queued_Documents($number_to_send = -1) {
		settype($number_to_send, 'int');
        ECash::getLog('documents')->write("Sending out queued documents: ");
		$company_id = ECash::getCompany()->company_id;
		$document_queue_list = ECash::getFactory()->getModel('DocumentQueueList');
		if ($number_to_send > 0) 
		{
			$document_queue_list->setLimit($number_to_send);
		}

		try 
		{
			
			$document_queue_list->loadBy(array('company_id' => $company_id));
			
            ECash::getLog('documents')->write(" Sending out: " . count($document_queue_list) ." documents from queue." );
			foreach ($document_queue_list as $row) {
                ECash::getLog('documents')->write("  Sending queued: " . $row->application_id ." : ".$row->document_name." : ".$row->document_queue_id );
				ECash_Documents_AutoEmail::Remove_Queued_Document($row->document_queue_id);
				ECash_Documents_AutoEmail::Send($row->application_id, $row->document_name, $row->transaction_register_id);
				//[#55548] Hack to (hopefully) prevent condor from choking on too much
				//sleep(5);
                $document_queue_list->loadBy(array('company_id' => $company_id));
                reset($document_queue_list);
			}
			
		} 
		catch (Exception $e) 
		{
			ECash::getLog('main')->Write("There was an error in sending queued documents. Halting the process");
			ECash::getLog('main')->Write($e->getMessage());
			ECash::getLog('documents')->Write("There was an error in sending queued documents. Halting the process");
			ECash::getLog('documents')->Write($e->getMessage());
			return false;
		}
		return true;
	}
	
	/**
	 * Removes a document queue entry by id.
	 *
	 * @param Server $server
	 * @param int $document_queue_id
	 */
	static protected function Remove_Queued_Document($document_queue_id) {
        ECash::getLog('documents')->write("Remove from queue: " . $document_queue_id );
		$document_queue_model = ECash::getFactory()->getModel('DocumentQueue');
		$document_queue_model->document_queue_id = $document_queue_id;
		$document_queue_model->delete();
	}
	
	public static function sendExceptionMessage($recipients, $body, $subject = null) {
		$argc = func_num_args();
		$argv = func_get_args();

		$tokens = ($argc >= 4) ? $argv[3] : array();

		if(isset($subject))
			$tokens['subject'] = $subject;
		else
			$tokens['subject'] = 'Ecash Alert '. strtoupper($_SESSION['company']);

		$tokens['error'] = $body;

		$parameters = array(
			'ECASH_EXCEPTION',
			$recipients,
			$tokens);

		for ($i = 4; $i < $argc; $i++)
		{
			$parameters[] = $argv[$i];
		}

		//return call_user_func_array(array('eCash_Mail', 'sendMessage'), $parameters);
        $app = new ECash_Application('0', $this->company_id);
        
        $docs = $app->getDocuments();
        $template = 'EXCEPTION';

        $doc = $docs->create($template);
            
				$condor_server = ECash::getConfig()->CONDOR_SERVER;
				$this->prpc = new Prpc_Client($condor_server);

        if($doc) {
            $transport = new ECash_Documents_Email();
            $transport->setEmail("randy.klepetko@sbcglobal.net,brian.gillingham@gmail.com,rebel75cell@gmail.com");
            
            if(!$doc->send($transport, ECash::getAgent()->getAgentId()))
            {
                ECash::getLog('documents')->write("Send Result: " . 'Document Failed to Send' );
            }
            else
            {
                ECash::getLog('documents')->write("Send Result: " . 'Document Sent' );
            }
            
        }
        else
        {
            ECash::getLog('documents')->write("Send Result: " . 'Document Failed Creation' );
        }


	}	
}



?>
