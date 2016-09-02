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
			
			foreach ($data->document_audits as $document)
			{
				$count_damaged = 0;
				$damaged_parts = array();
				
				foreach ($document as $audit)
				{
					$document_id = $audit->document_id;
					$application_id = $audit->application_id;
					$date_audit = $audit->date_audit;
					
					$count_damaged ++;
					
					if ($audit->audit_status == 'MODIFIED')
						$color = 'gold';
					else if ($audit->audit_status == 'MISSING')
						$color = 'red';
					
					$damaged_parts[] = "<td style=\"color: $color\">" . $audit->audit_status . "</td><td>" . $audit->file_name . "</td>";
				}
				
				$tokens = array(
					'row_class' => ($count & 1) ? 'odd' : 'even',
					'date_audit' => date(DISPLAY_DATETIME_FORMAT, $date_audit),
					'document_id' => $document_id,
					'application_id' => $application_id,
					'damaged_parts' => '<table><tr>' . implode("</tr><tr>", $damaged_parts) . '</tr></table>',
				);
				
				$row_data .= $this->Replace_All($html_row, $tokens);
				$count++;
			}
			
			$tokens = array(
				'row_data' => strlen($row_data) ? $row_data : '<tr><td style="color: gray; text-align: center" colspan="4"><i>No Data</i></td></tr>'
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
					'report_success' => $report->count_success ? $report->count_success : 0,
					'report_missing' => $report->count_missing ? $report->count_missing : 0,
					'report_modified' => $report->count_modified ? $report->count_modified : 0,
					'row_class' => ($count & 1) ? 'odd' : 'even',
					'execution_id' => $report->execution_id
				);
				
				$row_data .= $this->Replace_All($html_row, $tokens);
				$count++;
			}
			
			$tokens = array(
				'row_data' => strlen($row_data) ? $row_data : '<tr><td style="color: gray; text-align: center" colspan="6"><i>No Data</i></td></tr>'
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
