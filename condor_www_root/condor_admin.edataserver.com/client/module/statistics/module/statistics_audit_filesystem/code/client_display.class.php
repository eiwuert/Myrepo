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
		
		$html_path = CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view";
		$html_frame = file_get_contents("$html_path/frame.html");
		
		if ($data->action == 'detail')
		{
			$html_detail = file_get_contents("$html_path/report_detail.html");
			$html_row = file_get_contents("$html_path/report_detail_row.html");
			$row_data = '';
			$count = 0;
			
			foreach ($data->filesystem_audits as $audit)
			{

				$tokens = array(
					'row_class' => ($count & 1) ? 'odd' : 'even',
					'date_audit' => date(DISPLAY_DATETIME_FORMAT, $audit->date_audit),
					'path' => $audit->file_path,
					'size' => $audit->file_size,
					'hash' => $audit->file_hash,
					'status' => $audit->status
				);
				
				$row_data .= $this->Replace_All($html_row, $tokens);
				$count++;
			}
			
			$tokens = array(
				'row_data' => strlen($row_data) ? $row_data : '<tr><td style="color: gray; text-align: center" colspan="5"><i>No Data</i></td></tr>'
			);
			
			$page = $this->Replace_All($html_detail, $tokens);
		}
		else
		{
			$html_list = file_get_contents("$html_path/report_list.html");
			$html_row = file_get_contents("$html_path/report_list_row.html");
			$row_data = '';
			$count = 0;

			foreach ($data->reports as $report)
			{
				$tokens = array(
					'report_date' => date(DISPLAY_DATE_FORMAT, $report->date_audit),
					'report_linked' => $report->count_linked ? $report->count_linked : 0,
					'report_unlinked' => $report->count_unlinked ? $report->count_unlinked : 0,
					'report_modified' => $report->count_modified ? $report->count_modified : 0,
					'row_class' => ($count & 1) ? 'odd' : 'even',
					'execution_id' => $report->execution_id
				);
				
				$row_data .= $this->Replace_All($html_row, $tokens);
				$count++;
			}
			
			$tokens = array(
				'row_data' => strlen($row_data) ? $row_data : '<tr><td style="color: gray; text-align: center" colspan="5"><i>No Data</i></td></tr>'
			);

			$page = $this->Replace_All($html_list, $tokens);
		}
		
		$tokens = array(
			'content' => $page
		);
		
		$page = $this->Replace_All($html_frame, $tokens);
		
		return $page;
	}
}
