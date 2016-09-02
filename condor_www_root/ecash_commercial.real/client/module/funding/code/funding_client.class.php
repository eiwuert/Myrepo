<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");

class Funding_Client extends Client_View_Parent implements Display_Module
{

	private static $submenu_list = array(
		"verification",
		"underwriting",
		"tiffing"
		);

	public function Get_Hotkeys()
	{
		//skip hotkeys for those who don't want them
		if(eCash_Config::getInstance()->USE_HOTKEYS === FALSE)
			return '';

		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}

		$allow_cashline = in_array('cashline', $this->data->allowed_submenus) ? 'true' : 'false';
		$flux = rand(1,100000000);
        include_once(WWW_DIR . "include_js.php");
		$hotkey_js = include_js(Array('funding_hotkeys')) . "
	<script type=\"text/javascript\">
		//for hotkeys
		var allow_cashline = {$allow_cashline};
		var co_abbrev = \"{ECash::getTransport()->company}\";
		var agent_id = \"{ECash::getTransport()->agent_id}\";
	</script>\n";
		return $hotkey_js;

	}

	public function Get_Menu_HTML()
	{
		if (method_exists($this->display, "Get_Menu_HTML"))
		{
			return $this->display->Get_Menu_HTML();
		}

		$button_count = 0;

		$this->data->company = ECash::getTransport()->company;
		$this->data->agent_id = ECash::getTransport()->agent_id;

		// Create Submenu Buttons
		foreach( self::$submenu_list as $menu_item)
		{
			$menu_item_name = $menu_item."_button";
			$file = $menu_item . "_block.html";
			if( is_array($this->data->allowed_submenus) && in_array($menu_item, $this->data->allowed_submenus) )
			{
				// if we can access customized funding buttons for this user, use them!
				if(file_exists(CUSTOMER_LIB . $this->module_name . "/view/" .$file))
				{
					$this->data->{$menu_item_name} = file_get_contents(CUSTOMER_LIB . $this->module_name . "/view/" .$file);
				}
				else
				{
					$this->data->{$menu_item_name} = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/" . $file);
				}
				$button_count++;
				$this->data->{$menu_item_name} = Display_Utility::Token_Replace($this->data->{$menu_item_name}, (array)$this->data);
			}
			else
			{
				$this->data->{$menu_item_name} = "";
			}
		}

		$this->data->search_box_form = file_get_contents(CLIENT_VIEW_DIR . "search_box.html");

		// Display Next App button
		if($this->view == 'overview' && ($this->mode == 'underwriting' || $this->mode == 'verification'))
		{
			$this->data->next_app_button = file_get_contents(CLIENT_VIEW_DIR . "next_app_block_react.html");
	        include_once(WWW_DIR . "include_js.php");
        	$this->data->JAVASCRIPT_disable_link = include_js(Array('disable_link'));

			$queue_count = "";

			$reactivations = in_array("react", $this->data->allowed_submenus);
			$this->data->react_permission_text = ($reactivations ? "React" : "New") . "&nbsp;";

			if ( isset($this->data->queue_count) )
			{
				$this->data->queue_count = number_format($this->data->queue_count);
				$queue_count = $this->data->queue_count;
			}
			$this->data->next_app_dest = "/?action=get_next_application&flux_capacitor=" . rand(1,10000000);
			$this->data->queue_count = $queue_count;
			$this->data->next_app_button = Display_Utility::Token_Replace($this->data->next_app_button, (array)$this->data);
		}
		else
		{
			$this->data->next_app_button = '';
		}


		// Create the Queue Buttons
//		self::Create_Queue_Buttons();
				
		$this->Create_Queue_Buttons();

		$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/funding_menu.html");
			
        include_once(WWW_DIR . "include_js.php");
        $this->data->JAVASCRIPT_disable_link = include_js(Array('disable_link'));

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}

}

?>
