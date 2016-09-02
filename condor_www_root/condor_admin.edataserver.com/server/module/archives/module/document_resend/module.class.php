<?php

require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once(LIB_DIR . "condor_api.php");

class Module implements Module_Interface
{
	private $server;
	private $request;
	private $transport;
	private $action;
	private $data;
	private $form_validation;
	private $document_query;
	private $condor;

	public function __construct(Server $server, $request, $module_name)
	{
		$this->server = $server;
		$this->transport = $server->transport;
		$this->action = ($request->action) ? $request->action : 'doc_view';
		$this->request = $request;

		// set mode
		$this->mode = ($this->request->mode) ? $this->request->mode : 'default';

		// add initial module levels
		$this->transport->Add_Levels($module_name, $this->mode);
		
		$this->document_query = new Condor_Document_Query($this->server);
		$this->condor = Condor_API::Get_API_Object($this->server);
		$this->form_validation = new Form_Validation($this->server);
		
		if ($this->server->active_id['document_id'] && !$this->request->document_id)
		{
			$this->request->document_id = $this->server->active_id['document_id'];
			$this->document_id = $this->request->document_id;
		} 
		elseif($this->request->document_id)
		{
			$this->server->active_id['document_id'] = $this->request->document_id;
			$this->document_id = $this->request->document_id;
		}
		else 
		{
			$this->transport->Set_Levels('application', 'archives_search', 'default');
			$this->action = null;
			return true;
		}
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		$this->data = new stdClass();
		$this->data->request = $this->request;
		$this->data->errors = array();

		switch ($this->action)
		{
			case 'submit':
				$this->Normalize_Request();
				$this->data->validation = $this->Validate_Request();
				
				if (sizeof($this->data->validation))
				{
					$this->data->success = false;
				}
				else 
				{
					$recipients = array(
						'email_primary_name'	=> $this->request->recipient,
						'email_primary'			=> $this->request->recipient,
						'fax_number'			=> $this->request->recipient
					);
					
					$id = $this->condor->Send($this->request->document_id, $recipients, strtoupper($this->request->send_method));
					
					if($id !== FALSE)
					{
						$this->data->success = true;
					}
					else
					{
						$this->data->errors[] = 'There were errors while sending, however the message may have been sent anyway.<br>Check the Dispatch History.';
					}
				}
				break;
			
			default :
				break;
		}
			
		$this->transport->Set_Data($this->data);

		return TRUE;
	}

	private function Normalize_Request()
	{
		$this->form_validation->Normalize_Request_Fields(
			array(
				'recipient',
				'send_method'),
			$this->request
		);

		if ($this->request->send_method == 'fax')
		{
			$this->request->recipient = str_replace(array("-",")","("), "", $this->request->recipient);
		}
	}
	
	private function Validate_Request()
	{
		$validation = $this->form_validation->Validate_Request_Fields(
			array(
				'send_method' => array(true, 'string'),
				'recipient' => array(true, ($this->request->send_method == 'fax') ? 'phone' : 'email'),
			),
			$this->request
		);
		
		if(isset($validation['recipient']))
		{
			switch($this->request->send_method)
			{
				case 'fax':
					$this->data->errors[] = 'Fax number is invalid.';
					break;
				case 'email':
					$this->data->errors[] = 'Email address is invalid.';
					break;
			}
		}

		return $validation;
	}
}

?>
