<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once (EDITOR_CODE_DIR . '/include_CuteEditor.php');

class Client_Display extends Client_View_Parent implements Display_Module
{

	public function __construct(Transport $transport, $module_name)
	{
		$this->transport	 = $transport;
		$this->module_name	 = $module_name;

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
		$error_block = '';

		switch ($data->request->action)
		{
			case 'submit_rtf':
				$page = $this->Submit_RTF($data);
				break;

			default:
				switch($data->request->content_type)
				{
					case 'text/rtf':
						$page = $this->Edit_RTF_Template($data);
						break;
					default:
						$page = $this->Edit_HTML_Template($data);
						break;
				}
				break;
		}
		// Generate the help for available tokens
		return $page;
	}

	private function Submit_RTF($data)
	{
		if ($data->success === FALSE)
		{
			return $this->Edit_RTF_Template($data);
		}

		$html_path = CLIENT_MODULE_DIR . "/" . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view";
		$html = file_get_contents("$html_path/templates_rtf_updated.html");
		$tokens = Array(
			'template_name' => $data->request->template_name
		);

		$page = $this->Replace_All($html,$tokens);
		return $page;
	}

	private function Edit_RTF_Template($data)
	{
		if ($data->success === FALSE)
		{
			$error_block = $this->Generate_Error_Block_Html("You entered invalid data or did not supply a required field.<br />".implode('<br />',
					$data->errors));
		}
		else 
		{
			$error_block = '';
		}

		$html_path = CLIENT_MODULE_DIR . "/" . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view";
		$html = file_get_contents("$html_path/templates_edit_rtf.html");
		$download_url = http_build_query(Array(
					'template_id' => $data->request->template_id
				));
		$tokens = Array(
			'download_url'		=> $download_url,
			'template_name'		=> $data->request->template_name,
			'template_subject'	=> $data->request->template_subject,
			'template_id'		=> $data->template_id,
			'error_block'		=> $error_block
			);
		$page = $this->Replace_All($html, $tokens);
		return $page;
	}

	private function Edit_HTML_Template($data)
	{
		// create the wysiwyg instance
		$editor = new CuteEditor();
		$editor->ID = 'template_data';

		if (!empty($data->token_list))
		{
			$count = 0;
			
			foreach($data->token_list as $token)
			{
				$row_class = ($count++ % 2) ? 'odd' : 'even';
				$available_tokens .= <<<HTML
<tr class="{$row_class}">
	<td>$token->token</td>
	<td>$token->description</td>
	<td>$token->test_data</td>
	<td>$token->test_data_type</td>
</tr>
HTML;
			}
		}
		
		// Set the template type displayed text
		switch ($data->request->template_type)
		{
			case 'FAX_COVER':
				$template_type = 'Cover Sheet';
				break;
			
			case 'DOCUMENT':
			default:
				$template_type = 'Document';
		}
		
		if ($this->transport->action == 'edit')
		{
			if (!$data->success)
			{
				$page = $this->Generate_Error_Block_Html("Unable to load template. Template is invalid or inactive.");				
			}
			else
			{
				$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . '/module/' .
					$this->module_name . '/view/templates_edit.html');
	
				$editor->Text = $data->request->template_data;
				
				$tokens = array(
					'token_count'			=> count($data->token_list),
					'error_block'			=> $error_block,
					'preview_window'		=> '',
					'template_name'			=> $data->request->template_name,
					'css_template_data'		=> $this->Get_Fieldname_Class('template_data', $data->validation),
					'template_type'			=> $template_type,
					'template_subject'		=> $data->request->template_subject,
					'css_template_subject'	=> $this->Get_Fieldname_Class('template_subject', $data->validation),
					'token_rows'			=> $available_tokens,
					'wysiwyg'				=> $editor->GetString()
				);

				$page = $this->Replace_All($html, $tokens);				
			}
		}
		else if ($this->transport->action == 'submit')
		{
			if (!isset($data->success) || !$data->success || $data->action_preview)
			{
				if (isset($data->success) && !$data->success)
				{
					$link = $data->can_create_token;
					$msg = "You entered invalid data or did not supply a required field.<br />Please check the following token name(s).<br />";
					
					if ($link)
					{
						$msg .= "<br />Click the token to create it.<br />";
					}
					$msg .= "<ol>";

					foreach ($data->errors as $error)
					{
						$msg .= "<li style=\"margin-bottom:10px;\">";
						// if the user has sufficient access, allow a link to create a new token
						if ($link)
						{
							$msg .= "<a href=\"?module=templates_tokens_new&token_name=".preg_replace('/%/', '', $error)."\" target=\"_blank\">{$error}</a>";

						}
						else
						{
							$msg .= $error;
						}

						if (!empty($this->data->similar_tokens[$error]))
						{
							$msg .= "<ul><em>Did you mean?</em>";
							foreach ($this->data->similar_tokens[$error] as $s_token)
							{
								$msg .= "<li>{$s_token->token}</li>";
							}
							$msg .= "</ul>";
						}

						$msg .= "</li>";
					}

					$msg .= "</ol>";
					$error_block = $this->Generate_Error_Block_Html($msg);
				}
				
				if ($data->action_preview)
				{
					$preview_window = '<b>Preview:<br><iframe width="100%" height="300" src="?module=templates_preview"></iframe><br /> <br />';
				}
				
				$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . '/module/' . $this->module_name .
					'/view/templates_edit.html');

				$editor->Text = $data->request->template_data;

				$tokens = array(
					'token_count'			=> count($data->token_list),
					'error_block'			=> $error_block,
					'preview_window'		=> (isset($preview_window) ? $preview_window : ""),
					'template_name'			=> $data->request->template_name,
					'css_template_data'		=> $this->Get_Fieldname_Class('template_data', $data->validation),
					'template_type'			=> $template_type,
					'template_subject'		=> $data->request->template_subject,
					'css_template_subject'	=> $this->Get_Fieldname_Class('template_subject', $data->validation),
					'token_rows'			=> $available_tokens,
					'wysiwyg'				=> $editor->GetString()
				);
				
				$page = $this->Replace_All($html, $tokens);						
			}
			else if (isset($data->success) && $data->success)
			{
				$page = $this->Generate_Success_Block_Html("Template updated.");
			}
		}
		else if ($this->transport->action == 'delete')
		{
			$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . '/module/' . $this->module_name .
				"/view/confirm_delete.html");
			
			$tokens = array(
				'template_name' => $data->template->name
			);
						
			$page = $this->Replace_All($html, $tokens);
		}
		else if ($this->transport->action == 'delete_confirm')
		{
			if (isset($data->success) && $data->success)
			{
				$page = $this->Generate_Success_Block_Html("Template successfully deactivated.");
		
			}
			else
			{
				$page = $this->Generate_Error_Block_Html("Unknown error deactivating template.");

			}
		}
		
		$editor = null;
		return $page;
	}
}

?>
