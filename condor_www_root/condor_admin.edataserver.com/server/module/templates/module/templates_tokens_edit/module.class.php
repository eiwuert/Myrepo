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
		$this->form_validation = new Form_Validation($this->server);
		
		$this->data = new stdClass();
		$this->data->errors = array();
		
		if ($this->server->active_id['token'] && !$this->request->token)
		{
			$this->request->token = $this->server->active_id['token'];
		} 
		elseif ($this->request->token)
		{
			$this->server->active_id['token'] = $this->request->token;
		}
		else 
		{
			$this->transport->Set_Levels('application', 'templates_tokens_list', 'default');
			$this->action = null;
		}	
	}
	
	public function Main()
	{
		$this->transport->action = $this->request->action;
		$save_token = $this->request->token;

		if (!preg_match('/^%%%\w+%%%$/', $save_token))
		{
			$temp = preg_replace('/%/', '', $save_token);
			$save_token = "%%%{$temp}%%%";
		}

		switch($this->request->action)
		{
			case 'submit':
				$validation = $this->Form_Validation();
				
				if (empty($validation))
				{
					$this->template_query->Modify_Token(
						$save_token,
						$this->request->token_description,
						$this->request->test_data,
						$this->request->test_data_type,
						$this->request->token_data_id
					);
					
					$this->data->success = true;
				}
				else
				{
					$this->data->token_name			= $save_token;
					$this->data->token_description	= $this->request->token_description;
					$this->data->test_data			= $this->request->test_data;
					$this->data->test_data_type		= $this->request->test_data_type;
					$this->data->token_data_id		= $this->request->token_data_id;
					$this->data->validation			= $validation;
					$this->data->success			= false;
				}
				
				break;

			case 'edit':
			default:
				$token = $this->template_query->Fetch_Single_Token($this->request->token);
				
				$this->data->token_name				= $token->token;
				$this->data->token_description		= $token->description;
				$this->data->test_data				= $token->test_data;
				$this->data->token_data_type		= $token->test_data_type;
				$this->data->test_data_type_list	= $this->data_type_list;
				$this->data->token_data_id			= $token->token_data_id;
				
				break;
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
				'token_description' => 'REQUIRED',
				'test_data'			=> 'REQUIRED'
			),
			$this->request
		);
		
		return $errors;
	}
}

?>
