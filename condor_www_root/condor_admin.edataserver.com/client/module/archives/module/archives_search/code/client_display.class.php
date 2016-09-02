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
//		include_once CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/code/add_site.php';
		
		$data = (object)$this->transport->Get_Data();
		
		if ($data->action_submit && !$data->success)
		{
			$error_block = $this->Generate_Error_Block_Html("There were errors with the information you entered.<br>".implode("<br>", $data->errors));
		}
		else if ($data->action_submit)
		{
			$result_block = '';
			
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/result_row.html');
			$count = 0;
			foreach ($data->documents as $document)
			{
				$receive_from = '-';
				if($document->type == 'INCOMING')
				{
					$receive_from = !empty($document->receive_from) ? $document->receive_from : '<i style="color: gray">Not Available</i>';
				}
				$document_view_type = is_numeric($document->receive_from)? "pdf" : "text";

				$tokens = array(
					'row_class' => ($count % 2) ? 'even' : 'odd',
					'archive_id' => $document->document_id,
					'application_id' => !is_null($document->application_id) ? $document->application_id : '<i style="color: gray">Unassigned</i>',
					'received_from' => $receive_from,
					'date_created' => $document->date_created,
					'subject' => strlen($document->subject) > 0 ? $document->subject : '<i style="color: gray">None</i>',
					'document_type' => $document->type,
					'document_view_type' => $document_view_type,
				);
				$result_block .= $this->Replace_All($html, $tokens);
				$count++;
			}				
		}
		
		$mode_class = $this->Get_Fieldname_Class('search_mode', $data->validation);
		
		$id_select  = "<select name=\"search_mode\" class=\"$mode_class\">\n";
		$id_select .= "\t<option value=\"app_id\"".($data->request->search_mode == 'app_id' ? 'selected="selected"' : '').">Application ID:</option>\n";
		$id_select .= "\t<option value=\"archive_id\"".($data->request->search_mode == 'archive_id' ? 'selected="selected"' : '').">Archive ID:</option>\n";
		$id_select .= "</select>";
		

		$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/document_search.html');
		$lines = array();
		for($i = 0,$page = 1;$i < $data->total_documents;$i+=$data->max_documents,$page++)
		{
			if($i != $data->offset_document)
			{
				$page_string .= '<a href="javascript: ChangeOffset('.$i.');">';
			}
			$page_string .= $page;
			if($i != $data->offset_document)
			{
				$page_string.= '</a>';
			}
			$num = 5 - strlen($page);
			if($num > 0)
			{
				$page_string .= str_repeat('&nbsp;',$num);
			}
			$page_string .= "\n";
			if($page % 20 ==0)
			{
				$lines[] = $page_string;
				$page_string = '';
			}
		}
		/**
		 * Add the last line to the array of lines
		 */
		$num = 120 - strlen(strip_tags(str_replace('&nbsp;',' ',$page_string)));
		if($num > 0)
		{
			$page_string .= str_repeat('&nbsp;',$num);
		}
        $lines[] = $page_string;
        $page_string = join('<br />',$lines);
		
        $top_doc = $data->offset_document + count($data->documents);
		$bot_doc = $top_doc > 0 ? ($data->offset_document + 1) : 0;
		$tokens = array(
			'error_block' => (isset($error_block) ? $error_block : ''),
			
			'id_select' => (isset($id_select) ? $id_select : ''),
			
			's_id' => $data->request->id,
			'date_start' => $data->request->date_start,
			'date_end' => $data->request->date_end,
			
			'date_start_css' => $this->Get_Fieldname_Class('date_start', $data->validation),
			'date_end_css' => $this->Get_Fieldname_Class('date_end', $data->validation),
			
			'num_results' => $data->total_documents,
			'docs_per_page' => $data->max_documents,
			'view_range' => "$bot_doc - $top_doc",
			'current_offset' => $data->offset_document,
			'result_block' => (isset($result_block) ? $result_block : ''),
			'pages' => $page_string,
		);
		
		$page = $this->Replace_All($html, $tokens);
		
		return $page;
	}
}
