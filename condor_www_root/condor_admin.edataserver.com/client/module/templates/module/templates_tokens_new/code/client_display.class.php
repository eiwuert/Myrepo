<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

class Client_Display extends Client_View_Parent implements Display_Module
{
	public function __construct(Transport $transport, $module_name)
	{
		$this->transport	= $transport;
		$this->module_name	= $module_name;
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
		
		$html_path = CLIENT_MODULE_DIR."/{$this->transport->section_manager->parent_module}/module/$this->module_name/view";
		
		$error_block = '';
		
		if (!empty($data->similar_tokens) && !$data->confirm)
		{
			$html = file_get_contents("$html_path/similar_tokens_row.html");
			$count = 0;
			
			foreach ($this->data->similar_tokens as $sim_token)
			{
				$tokens = array(
					'row_css'				=> ($count++ % 2) ? 'even' : 'odd',
					'name'					=> $sim_token->token,
					'description'			=> $sim_token->description,
					'test_data'				=> $sim_token->test_data,
					'test_data_type'		=> $sim_token->test_data_type,
					'date'					=> $sim_token->date_created
				);
				
				$similar_tokens .= $this->Replace_All($html, $tokens);
			}

			if (!preg_match('/^%%%\w+%%%$/', $data->token_name))
			{
				$temp = preg_replace('/%/', '', $data->token_name);
				$data->token_name = "%%%{$temp}%%%";
			}

			$tokens = array(
				'original_token'		=> $data->token_name,
				'token_description'		=> $data->token_description,
				'test_data'				=> $data->test_data,
				'test_data_type'		=> $data->test_data_type,
				'similar_tokens'		=> $similar_tokens
			);
			
			$page = file_get_contents("$html_path/similar_tokens.html");
			$page = $this->Replace_All($page, $tokens);
		}
		elseif ($data->unique && !$data->confirm)
		{
			$tokens = array(
				'token_name'		=> $data->token_name,
				'description'		=> $data->token_description,
				'test_data'			=> $data->test_data,
				'test_data_type'	=> $data->test_data_type
			);
			
			$page = file_get_contents("$html_path/verify_token.html");
			$page = $this->Replace_All($page, $tokens);
		}
		else
		{
			if (isset($data->success) && !$data->success)
			{
				$error_block = $this->Generate_Error_Block_Html("You entered invalid data or did not supply a required field.<br />" .
					implode('<br />', $data->errors));
			}
			elseif (isset($data->success) && $data->success)
			{
				return $this->Generate_Success_Block_Html("Token added successfully.");
			}
			
			$available_tokens = '';
			
			// Generate the help for available tokens
			if (!empty($data->token_list))
			{
				$count = 0;
				
				foreach ($data->token_list as $token)
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
		
			$tokens = array(
				'error_block'			=> $error_block,
				'token_rows'			=> $available_tokens,
				'token_name'			=> isset($data->token_name) ? $data->token_name : '',
				'token_description'		=> isset($data->token_description) ? $data->token_description : '',
				'test_data'				=> isset($data->test_data) ? $data->test_data : 'Empty',
				'test_data_type_list'	=> parent::Get_Data_Type_Options($this->test_data_types),
				'css_token_name'		=> $this->Get_Fieldname_Class('token_name', $data->validation),
				'css_token_description' => $this->Get_Fieldname_Class('token_description', $data->validation)
			);
			
			$page = file_get_contents("$html_path/templates_tokens_new.html");
			$page = $this->Replace_All($page, $tokens);
		}
		
		return $page;
	}
}

?>