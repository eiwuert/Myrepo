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
		$this->transport->Add_Levels($module_name, $this->mode);
		
		$this->template_query = new Condor_Template_Query($this->server);
		
		$this->data = new stdClass();
		$this->data->errors = array();
		
		if ($this->server->active_id['template_id'] && !$this->request->template_id)
		{
			$this->request->template_id = $this->server->active_id['template_id'];
			$this->template_id = $this->request->template_id;
		} 
		elseif($this->request->template_id)
		{
			$this->server->active_id['template_id'] = $this->request->template_id;
			$this->template_id = $this->request->template_id;
		}
		else 
		{
			$this->transport->Set_Levels('application', 'templates_view', 'default');
			$this->action = null;
		}				
	}
	
	public function Main()
	{
		$this->transport->action = $this->request->action;
		
		if (!isset($this->request->template_id) || !is_numeric($this->request->template_id))
		{
			$this->data->success = false;
		}
		elseif($this->action == "show_history")
		{
			$this->data->template = $this->template_query->Fetch_Single($this->request->template_id);
			$this->server->template_obj = $this->data->template;

			if (!is_null($this->data->template))
			{
				$this->data->success = true;
			}
			else
			{
				$this->data->success = false;
			}
		}
		elseif($this->action == "delete_confirm")
		{
			$new_template_id = $this->template_query->Remove_Attachment(
				$this->request->template_id,
				$this->request->template_attachment_id
			);
			$this->server->active_id['template_id'] = $new_template_id;
			$this->data->success = true;
		}
		elseif($this->action == "delete_attachment")
		{
			$this->data->template = $this->template_query->Fetch_Single($this->request->template_id);
			$this->server->template_obj = $this->data->template;
			
			$this->data->template_attachment_id = $this->request->attachment_id;
			
			if (!is_null($this->data->template))
			{
				$this->data->success = true;
			}
			else
			{
				$this->data->success = false;
			}
		}
		else
		{
			$this->data->template = $this->template_query->Fetch_Most_Recent($this->request->template_id);
			$this->server->template_obj = $this->data->template;

			if (!is_null($this->data->template))
			{
				$this->data->success = true;
			}
			else
			{
				$this->data->success = false;
			}
		}
		
		$this->transport->Set_Data($this->data);

		return TRUE;
	}
}
