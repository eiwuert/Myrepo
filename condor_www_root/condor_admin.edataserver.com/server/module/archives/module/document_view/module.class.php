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
		
		//print $server->transport->section_manager;
		//print "<pre>" . var_export($server->transport->acl_unsorted, true) . "</pre>";
		
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		$this->data = new stdClass();
		$this->data->request = $this->request;
		$this->data->errors = array();

		switch ($this->action)
		{
			case 'doc_view':
				if (is_numeric($this->request->document_id))
				{
					$this->data->document = $this->document_query->Fetch_Document_All($this->request->document_id);
					if ($this->data->document->type == 'INCOMING')
					{
						$this->transport->section_manager->Disable_Section('document_resend');
					}
				}
				break;
			case 'doc_view_data':
				if (is_numeric($this->request->document_id))
				{
					$this->data->document_obj = $this->condor->Find_By_Archive_Id($this->request->document_id);
					
					if(!empty($this->data->document_obj->attached_data))
					{
						$attachment_list = $this->data->document_obj->attached_data;
						foreach($attachment_list as $attachment)
						{
							if($attachment->content_type != 'text/html')
							{
								$this->data->document_obj->data = str_replace(
									$attachment->uri,
									"data:$attachment->content_type;base64,".base64_encode($attachment->data),
									$this->data->document_obj->data
								);
							}
						}
					}
				}
				else
				{
					$this->data->document_obj = null;
				}
				
				// serve data for iframe
				$this->transport->Set_Levels('inline', 'doc_view', $this->mode);
			default : break;
		}

		$this->transport->Set_Data($this->data);

		//print "<pre>" . var_export($this->data, true) . "</pre>";

		return TRUE;
	}	
}
