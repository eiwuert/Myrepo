<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");

class Collections_Client extends Client_View_Parent implements Display_Module
{
	private $unaffiliated_queue_count;
	private $affiliated_queue_count;
	private $button_left;
	private $button_size;
	private $button_count;
	private $button_start;
	public $data;
	private static $submenu_list = array(
		"internal",
		"external",
		"corrections",
		"quick_checks"
		);

	public function __construct(ECash_Transport $transport, $module_name)
	{
		
		$this->mode = ECash::getTransport()->Get_Next_Level();
		$this->view = ECash::getTransport()->Get_Next_Level();
		$this->transport = ECash::getTransport();
		$this->module_name = $module_name;
		$this->data = ECash::getTransport()->Get_Data();
		//print "<pre>"; print $this->mode . "\n" . $this->view . "\n"; print_r($transport); print "</pre>"; exit;
		
		
		$file_to_get = "display_". $this->view . ".class.php";

		if (file_exists(CLIENT_CODE_DIR . "/{$file_to_get}")) 
		{
			require_once(CLIENT_CODE_DIR . "/{$file_to_get}");
		}
		else
		{
			require_once (dirname(__FILE__) . "/{$file_to_get}");
		}
		
		$this->display  = new Display_View(ECash::getTransport(), $this->module_name, $this->mode);	
//		parent::__construct($transport, $module_name);

		$this->button_count = 0;


		$this->data->agent_id = ECash::getTransport()->agent_id;
			
		if (isset($this->data->unaffiliated_queue_count))
		{
			$this->unaffiliated_queue_count = $this->data->unaffiliated_queue_count;
		}
		else
		{
			$this->unaffiliated_queue_count = '';
		}
			
		if (isset($this->data->affiliated_queue_count))
		{
			$this->affiliated_queue_count = $this->data->affiliated_queue_count;
		}
		else
		{
			$this->affiliated_queue_count = '';
		}

		if (isset($this->data->display_upload_status))
		{
			$this->data->display_upload_status = $this->data->display_upload_status;
		}
		else
		{
			$this->data->display_upload_status = '';
		}
	}

	public function Get_Hotkeys()
	{
		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}

		include_once(WWW_DIR . "include_js.php");
		return include_js(Array('fraud_hotkeys'));
	}

	public function Get_Menu_HTML()
	{
		$this->data->company = ECash::getTransport()->company;
		$this->data->agent_id = ECash::getTransport()->agent_id;
		// Create Submenu Buttons
		foreach( self::$submenu_list as $menu_item)
		{
			$menu_item_name = $menu_item."_button";
			$file = $menu_item . "_block.html";
			if( is_array($this->data->allowed_submenus) && in_array($menu_item, $this->data->allowed_submenus) )
			{
				$this->data->{$menu_item_name} = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/" . $file);
				$this->data->{$menu_item_name} = Display_Utility::Token_Replace($this->data->{$menu_item_name}, (array)$this->data);
			}
			else
			{
				$this->data->{$menu_item_name} = "";
			}
		}

		// Create the Queue Buttons
		self::Create_Queue_Buttons();
		
		/**
		 * HACK: This code needs to go away.
		 */
		if (($this->mode == 'internal' || $this->mode = 'external') && in_array('collections_email_queue', $this->data->allowed_submenus))
		{
			global $server;
			$eq = new Incoming_Email_Queue($server, $this->data);
			$count = $eq->Fetch_Queue_Count('collections_email_queue');

			$new_button =
					<<<END_HTML
						<a id="AppQueueBarEmail" class="menu" href="/?module=collections&mode={$this->mode}&action=get_next_email">
							<div class="menu_label_nextapp {$this->mode}">
								Email: &nbsp;<span class="queue_count">{$count}</span>
							</div>
						</a>
END_HTML;
			$this->data->queue_buttons .= $new_button;
		}
		/**
		 * ENDHACK
		 */
		
		

		// Conditionally display the Search Box
		if (in_array($this->mode, array('internal', 'external')))
		{
			$this->data->search_box_form = file_get_contents(CLIENT_VIEW_DIR . "search_box.html");
		}
		else
		{
			$this->data->search_box_form = '';
		}

		//Conditionally display the Cashline ID
		if(!empty($this->data->archive_cashline_id))
		{
			$this->data->archive_cashline_id_tag = "Cashline ID: " . $this->data->archive_cashline_id;
		}
		else
		{
			$this->data->archive_cashline_id_tag = NULL;
		}

		$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/menu.html");

        include_once(WWW_DIR . "include_js.php");
        $this->data->JAVASCRIPT = include_js(Array('disable_link'));
		return Display_Utility::Token_Replace($html, (array)$this->data);
	}
}

?>
