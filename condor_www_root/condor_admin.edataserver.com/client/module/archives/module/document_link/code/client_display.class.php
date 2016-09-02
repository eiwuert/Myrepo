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
//		include_once CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/code/add_site.php';
		
		$data = (object)$this->transport->Get_Data();
		
		if (!is_null($data->document->application_id) && (($data->request->action=='submit' && !$data->success) || $data->request->action != 'submit'))
		{
			$warning_block = $this->Generate_Notice_Block_Html("This document is already associated with an application.  Linking to a different application will overwrite the previous link!  The document is currently linked to Application ID <b>{$data->document->application_id}</b>");
		}
		
		if ($data->request->action != 'submit' || ($data->request->action == 'submit' && !$data->success))
		{
			
			if ($data->request->action == 'submit')
			{
				$error_block = $this->Generate_Error_Block_Html("There were problems with the data you submitted. <br>" . implode("<br>", $data->errors));
			}
	
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/document_link.html');
			
			$tokens = array(
				'error_block' => (isset($error_block) ? $error_block : ''),
				'warning_block' => (isset($warning_block) ? $warning_block : ''),
				
				'application_id' => (isset($data->request->app_id) ? $data->request->app_id : (!is_null($data->document->application_id) ? $data->document->application_id : '')),
				'application_id_css' => $this->Get_Fieldname_Class('app_id', $data->validation),
			);
			
			$page = $this->Replace_All($html, $tokens);
		}
		else if ($data->request->action == 'submit' && $data->success)
		{
			$page = $this->Generate_Success_Block_Html("Document ID {$data->document->document_id} is now linked to {$data->document->application_id}");
		}
		
		return $page;
	}
}