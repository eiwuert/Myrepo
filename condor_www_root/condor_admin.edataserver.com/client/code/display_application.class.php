<?

require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Application implements Display
{
	public function Do_Display(Transport $transport)
	{
		/*
		* Somwhere in here we need to figure out what modules this person
		* has access to and generate the correct module menu to display,
		* then pass it on to the module we create here.
		*/

		//set login cookie if need be
		if (!empty($_REQUEST['login']))
		{
			//this guy passed authentication, so let's save his company in a cookie
			$cookie_exp = time() + 60 * 60 * 24 * 30; //thirty days
			setcookie('default_company', $_REQUEST['abbrev'], $cookie_exp);
		}

		// Gets the module name, build it using the directory-specific Client_Module,
		// and retrieve all content.
		
		$module_name = $transport->Get_Next_Level();

		if (isset($module_name))
		{
			$use_module = ($transport->section_manager->parent_module) ? $transport->section_manager->parent_module : $module_name;
			require_once(CLIENT_MODULE_DIR . $use_module ."/code/client_module.class.php");
			$mod = Client_Module::Get_Display_Module($transport, $module_name);

			// Make sure the module actually wants to keep processing
			if (!$mod->send_display_data) 
			{
				return;
			}
	
			$header           = $mod->Get_Header();
			$body_tags        = $mod->Get_Body_Tags();
			$hotkeys          = $mod->Get_Hotkeys();
			$module_menu_html = $mod->Get_Menu_HTML();
			$module_html      = $mod->Get_Module_HTML();
			$error_block      = $mod->Get_Error_Block();
			$notice_block     = $mod->Get_Notice_Block();
			$success_block    = $mod->Get_Success_Block();

		}
		if (!isset($mod) || $mod->Include_Template())
		{
			include(CLIENT_VIEW_DIR . "template.html");
		}
	}
}

?>
