<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

class Client_Display extends Client_View_Parent implements Display_Module
{

	public function __construct(Transport $transport, $module_name)
	{
		$this->transport = $transport;
		$this->module_name = $module_name;
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
		
		if (!$data->action_submit || ($data->action_submit && !$data->success)) 
		{
			if ($data->action_submit && !$data->success)
			{
				$error_block = $this->Generate_Error_Block_Html("Please specify a valid file to upload.");
			}
			
			$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view/attach_file.html");
			
			$tokens = array(
				'error_block' => (isset($error_block) ? $error_block : ''),
				'attachment_uri' => isset($data->request->attachment_uri) ? $data->request->attachment_uri : '',
				'attachment_url' => isset($data->request->url) ? $data->request->url : ''
			);
			
			$page = $this->Replace_All($html, $tokens);
		}
		else if ($data->action_submit && $data->success)
		{
			$page = $this->Generate_Success_Block_Html("File attached successfully.");
		}
		
		return $page;
	}
}
