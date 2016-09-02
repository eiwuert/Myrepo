<?php

include_once(CLIENT_CODE_DIR . '/sub_menu_handler.class.php');

abstract class Client_View_Parent
{
	protected $transport;
	protected $module_name;
	protected $mode;
	protected $view;
	protected $data;
	protected $display;

	protected $test_data_types = array(
		'text',
		'image'
	);

	// Used by Display_Application to call stuff like Get_Header, Get_Body_Tags, etc.
	// If ever need client-side processing but don't want to send new HTML content,
	// set this to false (example, downloading a file)
	public $send_display_data = true;

	public function __construct(Transport $transport, $module_name)
	{
		$this->mode = $transport->Get_Next_Level();

		$this->transport = $transport;
		$this->module_name = $module_name;
		$this->data = $transport->Get_Data();
		
		require_once(CLIENT_CODE_DIR . "display_".$this->mode.".class.php");
		$this->display  = new Display_View($this->transport, $this->module_name, $this->mode);

		// construct sub menu if this section has subs
		$this->sub_menu_handler = new Sub_Menu_Handler($transport->section_manager, $module_name);
		$this->module_menu_html = $this->sub_menu_handler->Get_Sub_Section_Menu();
	}

	// Variable replacement callback function.  This got a little complicated, complain to Chris.  Fix later.
	protected function Replace($matches)
	{
		// Is it an edit layer?
		if( strpos($matches[0], "_edit%%%") )
		{
			$matches[1] = substr($matches[1], 0, -5);

			if( !empty($this->data->saved_error_data) && array_key_exists($matches[1], (array)$this->data->saved_error_data) )
			{
				$return_value = $this->data->saved_error_data->{$matches[1]};

			}
			elseif(array_key_exists($matches[1], (array)$this->data))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		else // Non edit replacement.
		{
			if(array_key_exists($matches[1], (array)$this->data))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		return $return_value;
	}
	
	protected function Replace_All($source_html, $tokens)
	{
		foreach ($tokens as $token => $value)
		{
			$source_html = str_replace("%%%{$token}%%%", $value, $source_html);
		}
		return $source_html;
	}

	/**
	 * returns a string of options for a <select> element
	 * 
	 * @param array $types
	 * @return string
	 */
	protected function Get_Data_Type_Options($types, $sel = NULL)
	{
		// Create the data type select options
		$options = "";
		foreach ($types as $type)
		{
			$options .= "<option value=\"{$type}\"";
			if ($sel == $type) {
				$options .= " selected=\"selected\"";
			}
			$options .= ">{$type}</option>\n";
		}
		return $options;
	}

	protected function Get_Fieldname_Class($fieldname, $validation)
	{
		if (isset($validation[$fieldname]) && ($validation[$fieldname]))
		{
			return 'boldred';
		}
		return 'bold';		
	}
	
	protected function Generate_Error_Block_Html($error)
	{
		$html = file_get_contents(CLIENT_VIEW_DIR . "error_block.html");
		
		$tokens = array(
			'error_list' => $error
		);
		
		$html = $this->Replace_All($html, $tokens);
		
		return $html;
	}

	protected function Generate_Success_Block_Html($success)
	{
		$html = file_get_contents(CLIENT_VIEW_DIR . "success_block.html");
		
		$tokens = array(
			'success_list' => $success
		);
		
		$html = $this->Replace_All($html, $tokens);
		
		return $html;
	}

	protected function Generate_Notice_Block_Html($notice)
	{
		$html = file_get_contents(CLIENT_VIEW_DIR . "notice_block.html");
		
		$tokens = array(
			'notice_list' => $notice
		);
		
		$html = $this->Replace_All($html, $tokens);
		
		return $html;
	}
	
	public function Get_Header()
	{
		return $this->display->Get_Header();
	}

	public function Get_Body_Tags()
	{
		return $this->display->Get_Body_Tags();
	}

	public function Get_Module_HTML()
	{
		return $this->display->Get_Module_HTML();
	}
	
	public function Replace_Tokens($data)
	{
		return $this->display->Replace_Tokens($data);
	}
	
	public function Get_Error_Block()
	{
		return $this->display->Get_Error_Block();
	}
	
	public function Get_Success_Block()
	{
		return $this->display->Get_Message_Block($this->transport->Get_Success(), 'success');
	}
	
	public function Get_Notice_Block()
	{
		return $this->display->Get_Message_Block($this->transport->Get_Notices(), 'notice');
	}

	public function Get_Menu_HTML()
	{
		return $this->module_menu_html;
	}

	public function Include_Template()
	{
		if (method_exists($this->display, "Include_Template"))
		{
			return $this->display->Include_Template();
		}

		return true;
	}
}

?>