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
		$data = (array)$this->transport->Get_Data();
		
		if('view_shared' == $this->transport->action)
		{
			$html = file_get_contents(
				CLIENT_MODULE_DIR.
				$this->transport->section_manager->parent_module.
				"/module/".
				$this->module_name.
				"/view/templates_list_view_shared.html"
			);
			
			switch($data['type'])
			{
				case 'FAX_COVER':
					$template_type = 'Cover Sheet'; break;
				case 'DOCUMENT':
				default:
					$template_type = 'Document'; break;
			}
			
			$tokens = array(
				'template_name' => $data['name'],
				'template_subject' => $data['subject'],
				'template_data' => htmlentities($data['data']),
				'template_type' => $template_type
			);
		}
		else
		{
			$html = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view/templates_list.html");
			
			$tokens = array();
			$tokens['cover_template_rows'] = $this->Template_Rows(
				$data['cover_template_rows'],
				$data['edit_access'],
				$data['view_access']
			);
			$tokens['template_rows'] = $this->Template_Rows(
				$data['template_rows'],
				$data['edit_access'],
				$data['view_access']
			);
			$tokens['shared_template_rows'] = $this->Template_Rows(
				$data['shared_template_rows'],
				$data['edit_access'],
				$data['view_access']
			);
		}
		
		$page = $this->Replace_All($html, $tokens);
		
		return $page;	
	}
	
	/**
	 * Generates the template rows from the given $data and returns the rows.
	 *
	 * @param array $data
	 */
	private function Template_Rows($data, $edit_access = false, $view_access = false)
	{
		$count = 0;
		$template_rows = '';
		$template_row = file_get_contents(CLIENT_MODULE_DIR . $this->transport->section_manager->parent_module . "/module/" . $this->module_name . "/view/templates_list_row.html");
		
		foreach($data as $template)
		{
			if($edit_access && 'SHARED' != $template->type)
			{
				$delete_link = http_build_query(
					array(
						'module' => 'templates_edit',
						'action' => 'delete',
						'template_id' => $template->template_id
					)
				);
				if(strtolower($template->content_type) == 'text/rtf')
				{
					$action = 'edit_rtf';
				}
				else 
				{
					$action = 'edit';
				}
				$edit_link = http_build_query(
					array(
						'module' => 'templates_edit',
						'action' => $action,
						'template_id' => $template->template_id
					)
				);
				
				$delete_edit_links = "<a href='?$delete_link'><img src='image/delete_whbg.gif' width='15' height='15' border='0' title='Deactivate' /></a><a href='?$edit_link'><img src='image/edit_whbg.gif' width='15' height='15' border='0' title='Edit' /></a>";
			}
			
			if($view_access)
			{
				if('SHARED' == $template->type)
				{
					$view_link = http_build_query(
						array(
							'module' => 'templates_list',
							'action' => 'view_shared',
							'template_id' => $template->template_id
						)
					);
					$template_link = "<a href='?$view_link'>$template->name</a>";
				}
				else
				{
					$view_link = http_build_query(
						array(
							'module' => 'templates_view',
							'action' => '',
							'template_id' => $template->template_id
						)
					);
					$template_link = "<a href='?$view_link'>$template->name</a>";
				}
			}
			
			$default = '';
			if($template->default_cover)
			{
				$default = ' - <i>(Default)</i>';
			}
			
			$default_button = '';
			if($template->type == 'FAX_COVER' && !$template->default_cover && $edit_access)
			{
				$link = http_build_query(
					array(
						'module' => 'templates_list',
						'action' => 'make_default',
						'template_name' => $template->name
					)
				);
				$default_button = '<a href="?'.$link.'">Make Default</a>';
			}
			
			$tokens = array(
				'delete_edit_links' => isset($delete_edit_links) ? $delete_edit_links : '&nbsp;',
				'template_name' => (isset($template_link) ? $template_link : $template->name).$default,
				'row_class' => $count++ % 2 ? 'even' : 'odd',
				'date_created' => $template->date_created,
				'created_by' => $template->creator_name_last . ", " . $template->creator_name_first,
				'last_modified' => $template->date_modified,
				'modified_by' => $template->modifier_name_last . ", " . $template->modifier_name_first,
				'default_button' => $default_button,
				'content_type' => $template->content_type
			);
						
			$template_rows .= $this->Replace_All($template_row, $tokens);
		}
		
		return $template_rows;
	}
}
