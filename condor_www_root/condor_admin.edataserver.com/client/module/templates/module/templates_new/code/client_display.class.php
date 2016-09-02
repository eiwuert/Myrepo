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
		$error_block = '';

		switch($this->transport->action)
		{
			case 'new_html_template':
				list($html, $tokens) = $this->Create_New_HTML_Template($data);
				break;
			case 'new_rtf_template':
				list($html, $tokens) = $this->Create_New_RTF_Template($data);
				break;
			case 'submit_new_rtf':
				list($html, $tokens) = $this->Upload_RTF_Template($data);
				break;
			default:
				list($html, $tokens) = $this->Pick_Template_Type($data);
				break;	
		}

		$page = $this->Replace_All($html, $tokens);
		return $page;
	}

	private function Upload_RTF_Template($data)
	{
		if($data->success === FALSE)
		{
			return $this->Create_New_RTF_Template($data,TRUE);
		}
		return Array('Template created!',Array(''));
	}

	private function Create_New_RTF_Template($data,$error_block=FALSE)
	{
		$form_action = http_build_query(
			Array(
				'module' => 'templates_new',
				'action' => 'submit_upload',
			)
		);

		if ($error_block === TRUE)
		{
			$error_block = $this->Generate_Error_Block_Html("You entered invalid data or did not supply a required field.<br />".implode('<br />',
					$data->errors));
		}
		else 
		{
			$error_block = '';
		}

		$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . '/module/' . $this->module_name . 
			'/view/templates_new_rtf.html');

		$tokens = Array(
			'form_action' => $form_action,
			'error_block' => $error_block
		);

		return array($html,$tokens);
	}

	private function Pick_Template_Type($data)
	{
		$html_link = http_build_query(
			array(
				'module' => 'templates_new',
				'action' => 'new_html_template',
			)
		);

		$rtf_link = http_build_query(
			array(
				'module' => 'templates_new',
				'action' => 'new_rtf_template')
		);

		$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . '/module/' . $this->module_name . '/view/templates_new.html');

		$tokens = Array(
			'html_link' => $html_link,
			'rtf_link' => $rtf_link,
		);

		return Array($html, $tokens);
	}

	private function Create_New_HTML_Template($data)
	{
		if ((!isset($data->success) || !$data->success) || $data->action_preview)
		{
			include_once (EDITOR_CODE_DIR . '/include_CuteEditor.php');

			// create the wysiwyg instance
			$editor = new CuteEditor();
			$editor->ID = 'template_data';
			$editor->Text = $data->request->template_data;
			
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

					if ($this->data->similar_tokens)
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
			
			if (isset($data->success) && $data->success && $data->action_preview)
			{
				if ($data->preview_pdf)
				{
					$html_pdf = '<a href="javascript: Submit_Preview_Type(\'preview_html\');">HTML</a> | PDF';
					$iframe_url = "?module=templates_preview&preview_pdf=1";
				}
				else
				{
					$html_pdf = 'HTML | <a href="javascript: Submit_Preview_Type(\'preview_pdf\');">PDF</a>';
					$iframe_url = "?module=templates_preview";
				}
				
				$preview_window = '<strong>Preview:</strong><br />'.$html_pdf.'<br /><iframe width="800" height="300" src="'.$iframe_url.'"></iframe><br /> <br />';
			}
			
			// Generate the help for available tokens
			if (!empty($data->token_list))
			{
				$count = 0;
				
				foreach($data->token_list as $token)
				{
					$row_class = ($count++ % 2) ? 'odd' : 'even';
					$available_tokens .= <<<HTML
	<tr class="$row_class">
		<td>$token->token</td>
		<td>$token->description</td>
		<td>$token->test_data</td>
		<td>$token->test_data_type</td>
	</tr>

HTML;
				}
			}
			
			$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . '/module/' . $this->module_name .'/view/templates_new_html.html');
			
			$tokens = array(
				'token_count'			=> count($data->token_list),
				'error_block'			=> $error_block,
				'preview_window'		=> (isset($preview_window) ? $preview_window : ''),
				'template_name'			=> $data->request->template_name,
				'css_template_name'		=> $this->Get_Fieldname_Class('template_name', $data->validation),
				'css_template_data'		=> $this->Get_Fieldname_Class('template_data', $data->validation),
				'css_template_type'		=> $this->Get_Fieldname_Class('template_type', $data->validation),
				'template_subject'		=> $data->request->template_subject,
				'css_template_subject'	=> $this->Get_Fieldname_Class('template_subject', $data->validation),
				'token_rows'			=> $available_tokens,
				'wysiwyg'				=> $editor->GetString()
			);

			$editor = null;
		}
		elseif (isset($data->success) && $data->success)
		{
			$html = file_get_contents(CLIENT_VIEW_DIR . "success_block.html");
			
			$tokens = array(
				'success_list' => "Template created."
			);
		}
		
		return array($html, $tokens);
	}
}

?>