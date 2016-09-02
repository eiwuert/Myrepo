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
		
		switch($data->template->content_type)
		{
			case 'text/rtf':
				$page = $this->View_RTF_Template($data);
				break;
			default:
				$page = $this->View_HTML_Template($data);
				break;
		}

		return $page;
	}
	private function View_RTF_Template($data)
	{
		$html_path = CLIENT_MODULE_DIR . "/" . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view";
		$html = file_get_contents("$html_path/templates_view_rtf.html");
		$tokens = Array(
			'download_url' => http_build_query(
				Array(
					'template_id' => $data->template->template_id
				)
			)
		);
		$page = $this->Replace_All($html, $tokens);
		return $page;
	}
	private function View_HTML_Template($data)
	{
		$html_path = CLIENT_MODULE_DIR . "/" . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view";
		
		if($this->transport->action == 'delete_attachment')
		{
			$html = file_get_contents("$html_path/confirm_delete.html");
			
			foreach($data->template->attachments as $attachment)
			{
				if($attachment->template_attachment_id == $data->template_attachment_id)
				{
					$attachment_name = $attachment->name;
					$template_attachment_id = $attachment->template_attachment_id;
				}
			}
			
			$tokens = array(
				'template_attachment_id' => $template_attachment_id,
				'attachment_name' => $attachment_name,
				'template_name' => $data->template->name
			);
			
			$page = $this->Replace_All($html, $tokens);
		}
		elseif($this->transport->action == 'delete_confirm')
		{
			if($this->data->success)
			{
				$page = $this->Generate_Success_Block_Html("Attachment successfully removed.");
			}
			else
			{
				$page = $this->Generate_Error_Block_Html("Unknown error removing attachment.");
			}
		}
		elseif ($data->success)
		{
			$attachment_block = '';
			$count = 0;

			foreach ($data->template->attachments as $attachment)
			{
				$html = file_get_contents(CLIENT_MODULE_DIR . "/" . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view/templates_attachment_row.html");
				
				// We don't want to display the delete attachment button if
				// we're showing a historical template
				if($this->transport->action == 'show_history')
				{
					$delete_link = '&nbsp;';
				}
				else
				{
					$delete_link = "<a href=\"?module=templates_view&action=delete_attachment&attachment_id=$attachment->template_attachment_id\"><img src=\"image/delete_whbg.gif\" border=\"0\" /></a>";
				}
			
				$tokens = array(
					'row_class' => (($count++) & 1) ? 'odd' : 'even',
					'delete_link' => $delete_link,
					'attachment_name' => $attachment->name,
					'attachment_type' => $attachment->type
				);
				
				$attachment_block .= $this->Replace_All($html, $tokens);
			}
			
			// Don't allow them to add attachments when viewing history
			if($this->transport->action == 'show_history')
			{
				$add_attachment = '&nbsp;';
			}
			else
			{
				$add_attachment = '<a href="?module=templates_attach_file">Attach File</a> | <a href="?module=templates_attach_template">Attach Template</a>';
			}
						
			$html = file_get_contents(CLIENT_MODULE_DIR . "/" . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view/templates_view.html");
			
			// Set the template type displayed text
			switch($data->template->type)
			{
				case 'FAX_COVER':
					$template_type = 'Cover Sheet';
					break;
				case 'DOCUMENT':
				default:
					$template_type = 'Document';
			}
			
			$tokens = array(
				'template_name' => $data->template->name,
				'template_subject' => $data->template->subject,
				'template_data' => htmlentities($data->template->data),
				'template_type' => $template_type,
				'attachment_block' => $attachment_block,
				'add_attachments' => $add_attachment
			);
			
			$page = $this->Replace_All($html, $tokens);
		}
		else
		{
			$page = $this->Generate_Error_Block_Html("Unable to load specified template.");
		}
		return $page;
	}
}