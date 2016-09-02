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
				$error_block = $this->Generate_Error_Block_Html(implode("<br>",$data->errors));
			}
			
			$select_block = '';
			foreach ($data->templates as $template_obj)
			{
				if ($template_obj->template_id != $data->template->template_id)
					$select_block .= '<option value="'.$template_obj->template_id.'">'.$template_obj->name.': '.$template_obj->subject.'</option>'."\n";
			}
			
			$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view/attach_template.html");
			
			$tokens = array(
				'error_block' => (isset($error_block) ? $error_block : ''),
				'template_select_block' => $select_block
			);
			
			$page = $this->Replace_All($html, $tokens);
		}
		else if ($data->action_submit && $data->success)
		{
			$page = $this->Generate_Success_Block_Html("Template attached successfully.");
		}
		
		return $page;
	}
}
