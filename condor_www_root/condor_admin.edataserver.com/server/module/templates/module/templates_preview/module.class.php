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
		$this->transport->Set_Levels('inline', $module_name, $this->mode);

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
	}
	
	public function Main()
	{
		$this->transport->action = $this->request->action;
		
		if (isset($this->request->preview_pdf))
		{
			$this->data->preview_pdf = true;
		}

		if (!is_null($this->server->template_obj))
		{
			$this->data->template = $this->server->template_obj;
		}
		else
		{
			$this->data->template = $this->template_query->Fetch_Most_Recent($this->request->template_id);
			$this->server->template_obj = $this->data->template;
		}
		if($this->data->template->content_type == 'text/rtf')
		{
			$this->data->no_preview = TRUE;
		}

		if (!is_null($this->data->template))
		{
			if(!empty($this->data->template->attachments))
			{
				$attachment_list = $this->data->template->attachments;
				foreach($attachment_list as $attachment)
				{
					if($attachment->content_type != 'text/html')
					{
						$this->data->template->data = str_replace(
							$attachment->name,
							"data:$attachment->content_type;base64,".base64_encode($attachment->data),
							$this->data->template->data
						);
					}
				}
			}
			$this->data->success = true;
		}
		else
		{
			$this->data->success = false;
		}
			
		$this->transport->Set_Data($this->data);

		return TRUE;
	}
}
