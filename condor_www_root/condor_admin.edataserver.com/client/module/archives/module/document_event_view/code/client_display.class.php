<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

define(NOT_AVAILABLE, '<i style="color: gray">Not Available</i>');

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
		
		if(isset($data->document))
		{
			
			$events_block = '';
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/event_view_row.html');
			$count = 0;


			foreach ($data->document->events as $event_obj)
			{
				$tokens = array(
					'row_class' => ($count++ % 2) ? 'even' : 'odd',
					'event_type' => $event_obj->event_type,
					'event_user' => $event_obj->user_name_last . ", " . $event_obj->user_name_first,
					'event_date' => $event_obj->date_created,
					'ip_address' => (!empty($event_obj->ip_address)) ? $event_obj->ip_address : NOT_AVAILABLE
				);
				
				$event_block .= $this->Replace_All($html, $tokens);
			}
			
			$dispatch_block = '';
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/dispatch_view_row.html');
			$count = 0;
			ini_set('arg_separator.output', '&amp;'); // used by http_build_query()
			
			foreach($data->document->dispatch as $dispatch)
			{
				switch($dispatch->status)
				{
					case 'FAIL': $status_style = 'color: red'; break;
					case 'INFO': $status_style = 'color: blue'; break;
					case 'COMPLETED':
					case 'SENT': $status_style = 'color: green'; break;
					case 'RETRY': $status_style = 'color: orange'; break;
					default: $status_style = 'color: black'; break;
				}
				
				$url = http_build_query(
					array(
						'module' => 'document_event_view',
						'action' => 'view_history',
						'dispatch_id' => $dispatch->document_dispatch_id,
						'transport' =>$dispatch->transport,
						'recipient' => $dispatch->recipient,
						'sender' => $dispatch->sender
					)
				);
				
				$tokens = array(
					'row_class' => ($count++ % 2) ? 'even' : 'odd',
					'date_created' => $dispatch->date_created,
					'last_modified' => !empty($dispatch->last_modified) ?
						$dispatch->last_modified : NOT_AVAILABLE,
					'recipient' => $dispatch->recipient,
					'sender' => !empty($dispatch->sender) ?
						$dispatch->sender : NOT_AVAILABLE,
					'type' => $dispatch->transport,
					'status_style' => $status_style,
					'status' => !empty($dispatch->status) ? 
						$dispatch->status : NOT_AVAILABLE,
					'history' => "<a href=\"?$url\">View History</a>"
				);
				
				$dispatch_block .= $this->Replace_All($html, $tokens);
			}
			
			if(empty($dispatch_block))
			{
				$dispatch_block = '<tr><td style="color: gray; text-align: center" colspan="6"><i>No Data</i></td></tr>';
			}

			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/event_view.html');
			
			$tokens = array(
				'error_block' => (isset($error_block) ? $error_block : ''),
				'events_block' => (isset($event_block) ? $event_block : ''),
				
				'dispatch_block' => (isset($dispatch_block) ? $dispatch_block : ''),
				
				'date_created' => $data->document->date_created,
				'date_esig' => '--',
			);
			
			$page = $this->Replace_All($html, $tokens);
			
		}
		elseif(isset($data->dispatch_history))
		{
			$history_block = '';
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/dispatch_history_row.html');
			$count = 0;
			
			foreach($data->dispatch_history as $element)
			{
				$row_class = ($count++ % 2) ? 'even' : 'odd';
				
				switch($element->status_type)
				{
					case 'INFO': $style = 'color: blue'; break;
					case 'COMPLETED':
					case 'SENT': $style = 'color: green'; break;
					case 'FAIL': $style = 'color: red'; break;
					case 'RETRY': $style = 'color: orange'; break;
					default: $style = 'color: black'; break;
				}
				
				$tokens = array(
					'row_class' => $row_class,
					'date_created' => $element->date_created,
					'recipient' => $data->recipient,
					'sender' => $data->sender,
					'type' => $data->transport,
					'status' => strtoupper($element->status_name),
					'status_style' => $style,
					'status_type' => !empty($element->status_type) ?
						$element->status_type : NOT_AVAILABLE
				);
				
				$history_block .= $this->Replace_All($html, $tokens);
			}
			
			if(empty($history_block))
			{
				$history_block = '<tr><td colspan="6" style="text-align: center; color: gray"><i>No Data</i></td></tr>';
			}
			
			$html = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/dispatch_view.html');
			
			$tokens = array(
				'dispatch_rows' => $history_block
			);
			
			$page = $this->Replace_All($html, $tokens);
		}
		
		return $page;
	}
}
