<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

class Client_Display extends Client_View_Parent implements Display_Module
{
	public function __construct(Transport $transport, $module_name)
	{
		$this->transport	= $transport;
		$this->module_name	= $module_name;
		parent::__construct($transport, $module_name);
	}

	public function Get_Hotkeys()
	{
		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}

		return TRUE;
	}
	
	public function Get_Module_HTML()
	{
		$data = (object)$this->transport->Get_Data();

		$html_path = CLIENT_MODULE_DIR."/{$this->transport->section_manager->parent_module}/module/$this->module_name/view";
		
		if($this->data->success && $this->transport->action == 'submit')
		{
			$page = $this->Generate_Success_Block_Html('Token updated successfully.');
		}
		else
		{
			if($this->transport->action == 'submit' && !$this->data->success)
			{
				$error_block = $this->Generate_Error_Block_Html("You must provide a description.");
			}

			$tokens = array(
				'error_block'			=> isset($error_block) ? $error_block : '',
				'token_name'			=> $data->token_name,
				'test_data'				=> $data->test_data,
				'test_data_type_list'	=> parent::Get_Data_Type_Options($this->test_data_types, $data->token_data_type),
				'css_token_description' => $this->Get_Fieldname_Class('token_description', $this->data->validation),
				'token_description'		=> $data->token_description,
				'token_data_id'			=> $data->token_data_id
			);
			
			$page = file_get_contents("$html_path/templates_tokens_edit.html");
			$page = $this->Replace_All($page, $tokens);
		}
		
		return $page;
	}
}

?>