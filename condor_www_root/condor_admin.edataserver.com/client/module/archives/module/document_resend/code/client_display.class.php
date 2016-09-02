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
		
		
		if (!$data->success && $data->request->action == 'submit')
		{
			$error_block = $this->Generate_Error_Block_Html("You entered invalid data.<br>" . implode("<br>",$data->errors));
		}
		
		if ($data->success)
		{
			$page = $this->Generate_Success_Block_Html("Document queued for delivery.");
		}
		else 
		{

			$method_select = 
		 '<select name="send_method">
		    <option value="email" '.($data->request->send_method=='email' ? 'selected="selected"' : '').'>Email</option>
			<option value="fax" '.($data->request->send_method=='fax' ? 'selected="selected"' : '').'>Fax</option>
		  </select>';
	
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/document_resend.html');
			
			$tokens = array(
				'error_block' => (isset($error_block) ? $error_block : ''),
				
				'date_created' => date(DISPLAY_DATETIME_FORMAT, $data->document->date_created),
				'date_esig' => '--',
				
				'method_select' => $method_select,
				'method_css' => $this->Get_Fieldname_Class('send_method', $data->validation),
				
				'recipient' => $data->request->recipient,
				'recipient_css' => $this->Get_Fieldname_Class('recipient', $data->validation)
			);
			
			$page = $this->Replace_All($html, $tokens);
		}
		
		return $page;
	}
}