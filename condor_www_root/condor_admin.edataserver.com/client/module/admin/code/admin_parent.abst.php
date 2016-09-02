<?php 

require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

abstract class Admin_Parent extends Display_Parent implements Display_Module
{
	protected $module_name;
	protected $transport;
	protected $mode;
	protected $view;
	protected $display_level;
	protected $display_sequence;


	// Used by Display_Application to call the display methods (i.e Get_Module_HTML)
	// Set to false to not send a new HTML content page, but do alternative processing.
	public $send_display_data = true;

	public function __construct(Transport $transport, $module_name)
	{
		
		$this->transport = $transport;
		$this->module_name = $module_name;
		$this->display_level = 2;
		$this->display_sequence = 1;
		
	}

	public function Set_Mode($mode)
	{
		$this->mode = $mode;
	}

	public function Set_View($view)
	{
		$this->view = $view;
	}

	public function Get_Hotkeys()
	{
		return "<script type=\"text/javascript\" src=\"get_js.php?override=admin_hotkeys&flux_capacitor=" . rand(1,1000000000) . "\"></script>";
	}

	public function Include_Template() { return true; }

	public function Get_Body_Tags()
	{
		$onload = "onLoad=\"javascript:get_last_settings();\"";
		return $onload;
	}

	public function Get_Menu_HTML()
	{
		$button_size = 115;
		$button_count = 0;

		foreach ($this->transport->user_acl_sub_names as $key => $value)
		{

				$temp_html .= '<td class="nav_item" onmouseover="this.className=\'nav_item_over\'" onmouseout="this.className=\'nav_item\'" onclick="getUrl(\'/?mode='.$value.'\');">'.$key.'</td>';
		}
		
		$html = "
		<table class=\"top_nav\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
          <tbody>
            <tr> 
            {$temp_html}
              <td>&nbsp;</td>
            </tr>
          </tbody>
        </table>		
		";

		return $html;
	}

	protected function Replace($matches)
	{
		$return_value = NULL;

		if(array_key_exists($matches[1], (array)$this->data))
		{
			$return_value = $this->data->{$matches[1]};
		}

		return $return_value;
	}
	
	
}