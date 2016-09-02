<?php

require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once(SERVER_CODE_DIR . "condor_template_query.class.php");

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
		$this->form_validation = new Form_Validation($this->server);
		
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
			$this->transport->Set_Levels('application', 'templates_list', 'default');
			$this->action = null;
			return true;
		}
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		$this->data->request = $this->request;
		
		$this->data->action_submit  = (isset($this->request->submit_x)  || isset($this->request->submit_y));
		
		$this->data->template = $this->template_query->Fetch_Most_Recent($this->request->template_id);
		
		switch($this->request->action)
		{
			case 'submit':
				$this->Normalize_Request();
				$validation = $this->Validate_Request();
				
				if (sizeof($validation))
				{
					// errors
					$this->data->success = false;
					$this->data->validation = $validation;
					$this->data->templates = $this->template_query->Fetch_All();
				}
				else 
				{
					// success
					$this->template_query->Attach_Template($this->data->template->template_id, $this->request->template_att_id);
					$this->data->success = true;					
				}
				break;
			
			default:
				break;
		}
		$this->data->templates = $this->template_query->Fetch_All();
		$this->transport->Set_Data($this->data);

		return TRUE;
	}

	private function Normalize_Request()
	{
		$this->form_validation->Normalize_Request_Fields(
			array(
				'template_att_id'
			),
			$this->request
		);
	}
	
	private function Validate_Request()
	{
		$errors = $this->form_validation->Validate_Request_Fields(
			array(
				'template_att_id' => 'REQUIRED'
			),
			$this->request
		);
		
		if (!sizeof($errors))
		{
			if ($this->request->template_id == $this->request->template_att_id)
			{
				$this->data->errors[] = 'Attempt to attach template to itself.';
				$errors['template_att_id'] = 'INVALID';
			}
			else if (!$this->template_query->Check_ID_Valid($this->data->template->template_id))
			{
				$this->data->errors[] = 'Parent template does not exist!';
				$errors['template_att_id'] = 'INVALID';
			}
			else if (!$this->template_query->Check_ID_Valid($this->request->template_att_id))
			{
				$this->data->errors[] = 'Child template does not exist!';
				$errors['template_att_id'] = 'INVALID';
			}
			else if ($this->template_query->Check_Template_Attachment_Exists($this->data->template->template_id, $this->request->template_att_id, 'TEMPLATE'))
			{
				$this->data->errors[] = 'Templates are not able to be attached multiple times.';
				$errors['template_att_id'] = 'INVALID';
			}
		}
			
		return $errors;
	}
}

?>