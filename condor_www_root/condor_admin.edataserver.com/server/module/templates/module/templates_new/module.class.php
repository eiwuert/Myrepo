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
		$this->form_validation = new Form_Validation($this->server);
		
		$this->data = new stdClass();
		$this->data->errors = array();
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		$this->Normalize_Request();
	
		$this->data->action_preview = (isset($this->request->preview_x) || isset($this->request->preview_y));
		$this->data->action_submit  = (isset($this->request->submit_x)  || isset($this->request->submit_y));

		switch($this->request->sub_action)
		{
			case 'preview_pdf':
				$this->data->preview_pdf = true;
				break;
		}

		switch($this->request->action)
		{
			case 'submit':
				$validation = $this->Validate_Request();
				$this->transport->action = 'new_html_template';
				
				if (sizeof($validation))
				{
					// errors
					$this->data->success = false;
					$this->data->validation = $validation;
					$this->data->request = $this->request;
				}
				else 
				{
					if ($this->data->action_submit)
					{
						/*
							Remove any shared template references with the same name. We have to
							assume that if it has the same name, that it is a replacement.
							
							TODO: Add a warning/confirmation that this will replace an existing
							shared template.
						*/
						$this->template_query->Remove_Shared($this->request->template_name);

						// strip out possible Firefox injection
						$this->request->template_data = $this->form_validation->Strip_Firefox_Highlights($this->request->template_data);
						
						$t_id = $this->template_query->Create_Template(
							$this->request->template_name,
							$this->request->template_subject,
							$this->request->template_data,
							$this->request->template_type
						);
						$this->data->success = (is_numeric($t_id) && $t_id > 0);
					}
					else if ($this->data->action_preview)
					{
						$this->server->template_obj = new stdClass();
						$this->server->template_obj->data = $this->request->template_data;
						$this->data->request = $this->request;
						$this->data->success = true;
					}
				}
				break;

			case 'submit_new_rtf':
				$validation = $this->Validate_RTF_Request();
				if(count($validation))
				{
					$this->data->success = FALSE;	
				}
				else 
				{
					$this->data->success = TRUE;
					$this->template_query->Create_Template(
						$this->request->template_name,
						$this->request->template_subject,
						$this->data->file_content,
						'DOCUMENT',
						'text/rtf'
					);
				}
				break;

			default:
				break;
		}
		
		$this->data->token_list = $this->template_query->Fetch_Tokens();
		$this->transport->Set_Data($this->data);

		return TRUE;
	}
	
	private function Normalize_Request()
	{
		$this->form_validation->Normalize_Request_Fields(
			array(
				'template_name',
				'template_subject',
				'template_data'
			),
			$this->request
		);
	}

	private function Validate_RTF_Request()
	{
		$errors = $this->form_validation->Validate_Request_Fields(
			array(
				'template_name' => 'REQUIRED',
				'template_subject' => 'REQUIRED'
			),
			$this->request
		);
		
		if (!sizeof($errors))
		{
			if ($this->template_query->Check_Name_Exists($this->request->template_name))
			{
				// TODO: Move this string to the client side and only provide a reference in the transport object.
				$this->data->errors[] = "This template name is already in use.  Please specify a unique name.";
				$errors['template_name'] = 'INVALID';
			}

			if (!file_exists($_FILES['template_file']['tmp_name']))
			{
				$this->data->errors[] = "The file failed to upload correctly.";
				$errors['template_file'] = 'INVALID';
			}

			$file_content = file_get_contents($_FILES['template_file']['tmp_name']);

			if (empty($file_content))
			{
				$this->data->errors[] = "The file had no data.";
				$errors['template_file'] = 'INVALID';
			}

			$fl = substr($file_content,0,strpos($file_content,"\n"));

			if (!preg_match('/\\{\\\rtf[\d]{1}\\\ansi/',$fl))
			{
				$this->data->errors[] = "That file does not appear to be a valid RTF document.";
				$errors['template_file'] = 'INVALID';	
			}
			else 
			{
				$this->data->file_content = $file_content;
			}
			
		}
		return $errors;
	}

	private function Validate_Request()
	{
		$errors = $this->form_validation->Validate_Request_Fields(
			array(
				'template_name'		=> 'REQUIRED',
				'template_subject'	=> 'REQUIRED',
				'template_data'		=> 'REQUIRED'
			),
			$this->request
		);
		
		if (!sizeof($errors))
		{
			if ($this->template_query->Check_Name_Exists($this->request->template_name))
			{
				// TODO: Move this string to the client side and only provide a reference in the transport object.
				$this->data->errors[] = "This template name is already in use.  Please specify a unique name.";
				$errors['template_name'] = 'INVALID';
			}
			
			$invalid_tokens = $this->template_query->Validate_Tokens($this->request->template_data);
			
			if (!empty($invalid_tokens))
			{
				$this->data->can_create_token = $this->server->acl->Acl_Access_Ok('templates_tokens_new', $this->server->company_id);
				$this->data->similar_tokens = array();

				foreach ($invalid_tokens as $token)
				{
					$this->data->errors[] = $token;
					$this->data->similar_tokens[$token] = $this->form_validation->Find_Similar_Tokens($token);
				}

				$errors['template_data'] = 'INVALID';
			}
		}
		
		return $errors;
	}
}

?>