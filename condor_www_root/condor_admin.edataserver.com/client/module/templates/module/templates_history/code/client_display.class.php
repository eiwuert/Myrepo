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
		$data = (array) $this->transport->Get_Data();
		
		$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" .
			$this->module_name . "/view/templates_history.html");
		
		$history_row = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" .
			$this->module_name . "/view/templates_history_row.html");

		$history_rows = '';
		
		for ($i = 0; $i < sizeof($data['template_history']); $i++)
		{
			$template		= $data['template_history'][$i];
			$template_name	= $template->name;
			$make_current	= "<a href=\"\" onclick=\"return confirmMakingCurrent($template->template_id);\">Make current</a>";
			$view			= "?module=templates_view&action=show_history&template_id=$template->template_id";

			$tokens = array(
				"view_link"		=> strtolower($template->status) === 'active' ? "" : "<a href=\"$view\">View</a>",
				"row_class"		=> ($i % 2 ? 'even' : 'odd'),
				"date_created"	=> $template->date_created,
				"created_by"	=> $template->creator_name_last . ", " . $template->creator_name_first,
				"last_modified" => $template->date_modified,
				"modified_by"	=> $template->modifier_name_last . ", " . $template->modifier_name_first,
				"status"		=> strtolower($template->status),
				"version"		=> "v." .(sizeof($data['template_history']) - $i),
				"revert"		=> strtolower($template->status) === 'active' ? "Current" : $make_current
			);
						
			$history_rows .= $this->Replace_All($history_row, $tokens);
		}				
		
		$tokens = array(
			'template_rows' => $history_rows,
			'template_name' => $template_name
		);
		
		$page = $this->Replace_All($html, $tokens);
		
		return $page;	
	}
}

?>
