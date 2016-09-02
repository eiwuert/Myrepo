<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");
require_once(DIR_LIB . "dropdown.1.generic.php");

//ecash module
class Display_View extends Display_Parent
{
	
	public function Get_Body_Tags()
	{
		return NULL;
	}

	public function Get_Header()
	{
		//the inline "Check_Data" is a hack b/c it's defined in the menu.js script (not needed for search)
		return "<title>CCS Admin </title>
		        <script type=\"text/javascript\" src=\"get_js.php?override=flux_capacitor&flux_capacitor=" . rand(1,100000000) . "\"></script>
				<script>function Check_Data() { return true; }</script>
		        <script type=\"text/javascript\" src=\"get_js.php?override=menu&flux_capacitor=" . rand(1,100000000) . "\"></script>";
	}
				
	public function Get_Module_HTML()
	{
		$data_transport = $this->transport->Get_Data();
		
		return $this->Replace_Tokens($data_transport);
	}
	
	public function Replace_Tokens($data_transport)
	{

		$html = file_get_contents(CLIENT_MODULE_DIR .$this->transport->section_manager->parent_module. "/module/".$this->module_name."/view/".$data_transport['template_used']);
		
		$html = preg_replace('/%%%(.*?)%%%/e', "\$data_transport[strtolower(\\1)]", $html);
		
		return $html;
	}
}

?>
