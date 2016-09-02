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
		
//		print "<pre>" . var_export($data, true) . "</pre>";

			if ($data->document->audit->status == 'failed')
			{
				$audit_status = '<div style="color: red; font-weight: none;"><div style="font-weight: bold;">Failed</div> on '.$data->document->audit->date.'</div>';
			}
			else 
			{
				$audit_status = '<div style="color: green; font-weight: none;"><div style="font-weight: bold;">Verified</div> on '.$data->document->audit->date.'</div>';
			}
			
//			$audit_status = '<pre>' . var_export($data->document->audit,true) . '</pre>';
			
			$document_template = ($data->request->document_view_type == "pdf") ? "document_view_fax.html" : "document_view.html";
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/'.$document_template);
			
			$tokens = array(
				'error_block' => (isset($error_block) ? $error_block : ''),
				
				'document_id' => $data->request->document_id,
				
				'date_created' => $data->document->date_created,
				'date_esig' => (is_null($data->document->date_esignature) ? '--' : $data->document->date_esignature),
				
				'audit_status' => $audit_status
			);
			
			$page = $this->Replace_All($html, $tokens);
		
		return $page;
	}
}