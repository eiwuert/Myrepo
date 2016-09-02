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
		}		
		

	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		$this->data->request = $this->request;
		
		$this->data->action_preview = (isset($this->request->preview_x) || isset($this->request->preview_y));
		$this->data->action_submit  = (isset($this->request->submit_x)  || isset($this->request->submit_y));

		switch($this->request->action)
		{
			case 'delete':
				// unconfirmed delete.
				$this->data->template = $this->template_query->Fetch_Single($this->request->template_id);
				break;
				
			case 'delete_confirm':
				// confirmed delete.
				if($this->template_query->Deactivate_Template($this->request->template_id))
				{
					// Remove shared templates with this same ID.
					$this->template_query->Remove_Shared_By_ID($this->request->template_id);
					$this->data->success = true;
				}
				break;
				
			case 'submit_rtf':
				$errors = $this->Validate_RTF_Request();
				if(count($errors))
				{
					$this->data->success = false;
				}
				else 
				{
					$template_data = $this->template_query->Fetch_Most_Recent($this->request->template_id);
					$this->data->request->template_name = $template_data->name;
					$this->data->request->template_type = $template_data->type;
					$this->template_query->Update_Template(
						$this->data->request->template_id,
						$this->data->request->template_subject,
						$this->data->file_content,
						'text/rtf'
					);
				}
				break;

			case 'submit':
				$template_data = $this->template_query->Fetch_Most_Recent($this->request->template_id);
				$this->data->request->template_name = $template_data->name;
				$this->data->request->template_type = $template_data->type;

				// strip out possible Firefox injection
				$this->request->template_data = $this->form_validation->Strip_Firefox_Highlights($this->request->template_data);
				
				$validation = $this->Validate_Request();
				
				if (sizeof($validation))
				{
					// errors
					$this->data->success = false;
					$this->data->validation = $validation;
				}
				else 
				{
					if ($this->data->action_submit)
					{
						// no errors... update it
						$new_template_id = $this->template_query->Update_Template(
							$this->request->template_id,
							$this->request->template_subject,
							stripslashes($this->request->template_data)
						);
						$this->server->active_id['template_id'] = $new_template_id;
						$this->data->success = true;
					}
					else if ($this->data->action_preview)
					{
						$this->server->template_obj       = new stdClass();
						$this->server->template_obj->data = stripslashes($this->request->template_data);
						$this->data->request              = $this->request;
						$this->data->success              = true;
					}
				}
				break;

			case 'edit':
			default:
				if (!$this->request->template_id)
				{
					$this->data->success = false;
					break;
				}
				$this->transport->action = 'edit';
				// Populate 'request' -- the front-end looks here for it.
				// Yes. It's a hack. Two week timeline.  Two weeks.
				$template_data = $this->template_query->Fetch_Most_Recent($this->request->template_id);
				$this->server->template_obj = $template_data;
				
				if (is_null($template_data))
				{
					$this->data->success = false;
				}
				else
				{				
					$this->data->request->template_name    = $template_data->name;
					$this->data->request->template_subject = $template_data->subject;
					$this->data->request->template_data    = stripslashes($template_data->data);
					$this->data->request->template_type    = $template_data->type;
					$this->data->request->content_type     = $template_data->content_type;
					$this->data->success = true;
				}
				break;
			}
		
		$this->data->token_list = $this->template_query->Fetch_Tokens();
		$this->transport->Set_Data($this->data);

		return TRUE;
	}

	private function Validate_RTF_Request()
	{
		if (!isset($this->data->request->template_id) || !is_numeric($this->data->request->template_id))
		{
			$this->data->errors[] = "Template Id not set.";
			$errors['template_id'] = 'INVALID';
		}

		if (!$this->template_query->Check_Id_Exists($this->data->request->template_id))
		{
			$this->data->errors[] = "Invalid template id.";
			$errors['template_id'] = "INVALID";
		}

		if (!isset($this->data->request->template_subject) || empty($this->data->request->template_subject))
		{
			$this->data->errors[] = "Invalid template subject.";
			$errors['template_subject'] = "INVALID";
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

		return $errors;
	}

	private function Normalize_Request()
	{
		$this->form_validation->Normalize_Request_Fields(
			array(
				'template_subject',
				'template_data'
			),
			$this->request
		);
	}
	
	private function Validate_Request()
	{
		$errors = $this->form_validation->Validate_Request_Fields(
			array(
				'template_subject' => 'REQUIRED',
				'template_data'    => 'REQUIRED'
			),
			$this->request
		);
		
		if (!$errors)
		{
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