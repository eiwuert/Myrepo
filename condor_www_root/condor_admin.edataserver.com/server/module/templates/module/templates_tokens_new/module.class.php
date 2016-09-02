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
		$this->server		= $server;
		$this->transport	= $server->transport;
		$this->action		= ($request->action) ? $request->action : NULL;
		$this->request		= $request;

		// set mode
		$this->mode = ($this->request->mode) ? $this->request->mode : 'default';

		// add initial module levels
		$this->transport->Set_Levels('application', $module_name, $this->mode);
		$this->template_query = new Condor_Template_Query($this->server);
		$this->form_validation = new Form_Validation($this->server);
		
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

		// If the user is being passed from the edit_template page, prepopulate the token name
		if (isset($request->token_name))
		{
			$this->data->token_name = "%%%{$request->token_name}%%%";
		}
	}
	
	public function Main()
	{
		$this->transport->action = $this->request->action;
		
		// Normalize the data
		$this->form_validation->Normalize_Request_Fields(
			array(
				'token_name',
				'token_description'
			),
			$this->request
		);
		
		$this->data->token_list = $this->template_query->Fetch_Tokens();
		
		if($this->request->action == 'submit')
		{
			$this->data->token_name			= isset($this->request->token_name) ? $this->request->token_name : '';
			$this->data->token_description	= isset($this->request->token_description) ? $this->request->token_description : '';
			$this->data->test_data			= isset($this->request->test_data) ? $this->request->test_data : '';
			$this->data->test_data_type		= isset($this->request->test_data_type) ? $this->request->test_data_type : '';
			$this->data->confirm			= $this->request->confirm ? true : false;
			
			$validation = $this->Form_Validation();
			if (!empty($validation))
			{
				$this->data->validation = $validation;
				$this->data->success = false;
			}
			elseif ($this->request->confirm)
			{
				$this->template_query->Create_Token(
					$this->request->token_name,
					$this->request->token_description,
					$this->request->test_data,
					$this->request->test_data_type
				);
				$this->data->success = true;
			}
		}

		$this->transport->Set_Data($this->data);

		return true;
	}
	
	/**
	 * Validates the form fields.
	 *
	 * @return array
	 */
	private function Form_Validation()
	{
		$errors = $this->form_validation->Validate_Request_Fields(
			array(
				'token_name'		=> 'REQUIRED',
				'token_description' => 'REQUIRED'
			),
			$this->request
		);
		
		if (strlen($this->request->token_name))
		{
			if ($this->template_query->Check_Token_Exists($this->request->token_name))
			{
				$this->data->errors[] = 'Token name already exists. See if you can use that token instead or change your token name.';
				$errors['token_name'] = 'INVALID';
			}

			// removing the requirement that the user add the percent characters
			if (!preg_match('/^%%%\w+%%%$/', $this->request->token_name))
			{
				$temp = preg_replace('/%/', '', $this->request->token_name);
				$this->request->token_name = "%%%{$temp}%%%";
			}
			
			if (empty($errors))
			{
				$new_token = strtolower(str_replace('%', '', $this->request->token_name));
				$this->data->similar_tokens = $this->form_validation->Find_Similar_Tokens($new_token);
			}
			
			if (empty($this->data->similar_tokens))
			{
				$this->data->unique = true;
			}
		}
		
		return $errors;
	}
}
