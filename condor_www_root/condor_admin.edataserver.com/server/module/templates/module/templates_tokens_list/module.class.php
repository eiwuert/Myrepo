<?php

require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once(SERVER_CODE_DIR . "form_validation.class.php");

class Module implements Module_Interface
{
	private $server;
	private $request;
	private $transport;
	private $action;
	private $data;
	private $template_query;
	private $form_validation;

	public function __construct(Server $server, $request, $module_name)
	{
		$this->server = $server;
		$this->transport = $server->transport;
		$this->action = ($request->action) ? $request->action : NULL;
		$this->request = $request;

		// set mode
		$this->mode = ($this->request->mode) ? $this->request->mode : 'default';

		// add initial module levels
		$this->transport->Set_Levels('application', $module_name, $this->mode);

		$this->template_query = new Condor_Template_Query($this->server);
		
		$this->data = new stdClass();
		$this->data->errors = array();
		
		if ($this->server->active_id['template_id'] && !$this->request->template_id)
		{
			$this->request->template_id = $this->server->active_id['template_id'];
			$this->template_id = $this->request->template_id;
		} 
		elseif ($this->request->template_id)
		{
			$this->server->active_id['template_id'] = $this->request->template_id;
			$this->template_id = $this->request->template_id;
		}
	}
	
	public function Main()
	{
		$this->transport->action = $this->request->action;
		
		$this->data->token_list = $this->template_query->Fetch_Tokens();
		
		if ($this->server->acl->Acl_Access_Ok('templates_tokens_edit', $this->server->company_id))
		{
			$this->data->edit_access = true;
		}
		else
		{
			$this->data->edit_access = false;
		}

		if (!empty($this->data->token_list))
		{
			$this->data->success = true;
		}
		else
		{
			$this->data->success = false;
		}
			
		$this->transport->Set_Data($this->data);

		return true;
	}
}
