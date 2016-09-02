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

		if($data->success)
		{
			$html_path = CLIENT_MODULE_DIR."/{$this->transport->section_manager->parent_module}/module/$this->module_name/view";
			
			$table_rows = '';
			$count		= 0;
			$edit_link	= '';
			$can_edit	= FALSE;
			ini_set('arg_separator.output', '&amp;'); // used by http_build_query
			
			// Define each row/token
			foreach($data->token_list as $token)
			{
				$html = file_get_contents("$html_path/templates_tokens_list_row.html");
				
				// If they have edit access, show the edit link
				if($data->edit_access)
				{
					$http_query = http_build_query(
						array(
							'module' => 'templates_tokens_edit',
							'action' => 'edit',
							'token' => $token->token
						)
					);
					$edit_link = "<a href=\"?$http_query\"><img src=\"/image/edit_whbg.gif\" alt=\"Edit\" border=\"0\" title=\"Edit\"/></a>";
				}

				if ($token->test_data_type === 'image')
				{
					$token->test_data = "<a target=\"_blank\" href=\"{$token->test_data}\">{$token->test_data}</a>";
				}
				
				$tokens = array(
					'row_class'			=> ($count++ % 2) ? 'even' : 'odd',
					'edit_token'		=> $edit_link,
					'token'				=> $token->token,
					'description'		=> $token->description,
					'test_data'			=> $token->test_data,
					'test_data_type'	=> $token->test_data_type,
					'date_created'		=> $token->date_created
				);
				
				$table_rows .= $this->Replace_All($html, $tokens);
			}
			
			// Create final page
			$html = file_get_contents("$html_path/templates_tokens_list.html");
			$html = $this->Replace_All($html, array('table_rows' => $table_rows));
		}
		else
		{
			$html = $this->Generate_Error_Block_Html("There are no tokens defined for this company.");
		}
		
		return $html;
	}
}