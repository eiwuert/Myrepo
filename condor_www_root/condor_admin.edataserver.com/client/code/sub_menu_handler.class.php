<?

class Sub_Menu_Handler
{

	public $sub_menu_html;
	private $current_parent_id;
	
	function __construct(&$section_manager, $module_name)
	{
		$this->section_manager = $section_manager;
		$this->module_name = $module_name;
	}
	

	public function Get_Sub_Section_Menu()
	{
		if ($this->section_manager->current_level)
		{
			$level = $this->section_manager->acl_unsorted[$this->module_name]->level;
			while($level >= 1)
			{
				if ($level == $this->section_manager->acl_unsorted[$this->module_name]->level)
				{

					$current_section_id = $this->section_manager->acl_unsorted[$this->module_name]->section_id;
					$current_section_parent_id = $this->section_manager->acl_unsorted[$this->module_name]->section_parent_id;

					$this->Generate_Sub_Menu($this->section_manager->section_tree[$level][$current_section_parent_id], $level, $current_section_id);
					$this->current_parent_id = $this->section_manager->acl_unsorted[$this->module_name]->section_parent_id;
				}
				else 
				{
					$current_section_id = $this->section_manager->acl_unsorted[$this->section_manager->acl_by_section_id[$current_section_parent_id]->name]->section_id;
					$current_section_parent_id = $this->section_manager->acl_unsorted[$this->section_manager->acl_by_section_id[$current_section_parent_id]->name]->section_parent_id;

					$this->Generate_Sub_Menu($this->section_manager->section_tree[$level][$current_section_parent_id], $level, $current_section_id);
					
					$this->current_parent_id = $this->section_manager->acl_unsorted[$this->section_manager->acl_by_section_id[$current_section_id]->name]->section_parent_id;
				}
				--$level;
			}
		}
		else 
		{
			// generate only the default level one sub menu
			$section_id = $this->section_manager->acl_unsorted[$this->module_name]->section_id;
			$this->Generate_Sub_Menu($this->section_manager->section_tree[1][$section_id], 1, $section_id);
		}	

		return $this->sub_menu_html;	
	}	
	
	
	private function Generate_Sub_Menu($section_array, $level, $current_section_id)
	{
		$style_base = ($level == 1) ? 'nav' : 'subnav';
		
		ksort($section_array);
		foreach($section_array as $sequence => $section)
		{
			list($section_id, $section_data) = each($section);

			/// hack for default section for the sections that rely on a subsection as a default
			$name = ($this->section_manager->acl_by_section_id[$section_data->default_section_id]->name) ? $this->section_manager->acl_by_section_id[$section_data->default_section_id]->name : $section_data->name;

			if (($section_data->name == $this->module_name) || ($this->current_parent_id == $section_data->section_id)) 
			{
				$temp_html .= '<td class="'.$style_base.'_item_selected" onclick="getUrl(\'?module='.$name.'\');">'.$section_data->description.'</td>';
              
			}
			else 
			{
				$temp_html .= '<td class="'.$style_base.'_item" onmouseover="this.className=\''.$style_base.'_item_over\'" onmouseout="this.className=\''.$style_base.'_item\'" onclick="getUrl(\'/?module='.$name.'\');">'.$section_data->description.'</td>';
			}
		}
		
		$html = "
		<table class=\"top_{$style_base}\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
          <tbody>
            <tr> 
            {$temp_html}
              <td>&nbsp;</td>
            </tr>
          </tbody>
        </table>		
		";
		
		// since we're starting from the current level up
		// this function will construct the menu backwards
		$this->sub_menu_html = ($this->sub_menu_html) ? $html . $this->sub_menu_html : $html;
			
	}
}


