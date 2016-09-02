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
		
		$this->data->action_submit  = isset($this->request->action);
		$this->data->request->attachment_uri = $this->server->files->attachment['name'];
		
		$this->data->template = $this->template_query->Fetch_Most_Recent($this->request->template_id);
					
		switch($this->request->action)
		{
			case 'submit':
				$this->Normalize_Request();
				$validation = $this->Validate_Request();
				
				if(!empty($validation))
				{
					// errors
					$this->data->success = false;
					$this->data->validation = $validation;
				}
				else 
				{
					switch($this->request->upload_type)
					{
						case 'file':
							// store file
							$bin = file_get_contents($this->server->files->attachment['tmp_name']);
							$uri = $this->data->request->attachment_uri;
							$content_type = $this->server->files->attachment['type'];
							break;
						case 'url':
							// Get the Content-Type
							$headers = get_headers($this->request->url, 1);
							$headers = array_change_key_case($headers);
							$content_type = $headers['content-type'];
							
							// Get the file
							$curl = curl_init($this->request->url);
							curl_setopt($curl, CURLOPT_HEADER, false); // Keep the header
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Output as a string
							$bin = curl_exec($curl);
							
							// We'll use the file's name for the URI.
							$uri = basename($this->request->url);
							break;
						default: break;
					}
					
					$this->template_query->Attach_File(
						$this->data->template->template_id,
						$content_type,
						$bin,
						$uri
					);
					
					$this->data->success = true;
				}
				break;

			default:
				break;
		}
		
		$this->transport->Set_Data($this->data);

		return TRUE;
	}

	private function Normalize_Request()
	{
		// Nothing to normalize
	}
	
	private function Validate_Request()
	{
		$errors = array();
		
		if ($this->request->upload_type == 'file' && $this->server->files->attachment['size'] <= 0)
		{
			$errors['attachment'] = 'REQUIRED';
		}
		elseif($this->request->upload_type == 'url')
		{
			$errors = $this->form_validation->Validate_Request_Fields(
				array(
					'url' => 'REQUIRED'
				),
				$this->request
			);
		}
		
		return $errors;
	}
}

?>