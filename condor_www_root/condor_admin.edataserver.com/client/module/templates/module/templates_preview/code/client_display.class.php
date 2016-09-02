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

		return 'haha.lol.';
	}
	
	public final function Include_Template()
	{
		return false;
	}
}