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
		$html_path = CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" . $this->module_name;
		$html = file_get_contents("$html_path/view/statistics_sent_failed.html");
		
		$data = $this->transport->Get_Data();
		
		if(!$data->success)
		{
			$error_block = $this->Generate_Error_Block_Html("There were errors with the information you entered.<br>".implode("<br>", $data->errors));
		}
		
		$rows = file_get_contents("$html_path/view/statistics_sent_failed_rows.html");
		
		$table_rows = '';
		$count = 0;
		
		// Construct the failed send list
		foreach($data->failed as $failed_send)
		{
			$sub_tokens = array(
				"row_css" => ($count++ % 2) ? 'even' : 'odd',
				"view_link" => "?module=document_view&amp;document_id=$failed_send->document_id",
				"archive_id" => $failed_send->document_id,
				"date_sent" => date("m/d/Y g:i:s A", strtotime($failed_send->date_created)),
				"method" => $failed_send->method,
				"status" => strtoupper($failed_send->status)
			);
			
			$table_rows .= $this->Replace_All($rows, $sub_tokens);
		}
		
		// Generate tokens
		$tokens = array(
			"table_rows" => $table_rows,
			"date_start" => $data->date_start,
			"date_end" => $data->date_end,
			"error_block" => isset($error_block) ? $error_block : "",
			"date_start_css" => $this->Get_Fieldname_Class('date_start', $data->validation),
			"date_end_css" => $this->Get_Fieldname_Class('date_end', $data->validation),
			"num_results" => $data->num_results
		);
		
		$html = $this->Replace_All($html, $tokens);
		
		return $html;
	}
}
