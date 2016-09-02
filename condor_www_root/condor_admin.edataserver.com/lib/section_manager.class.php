<?php
// ACL
require_once(LIB_DIR . "acl.3.php");

class Section_Manager
{
	public $section_tree;
	public $transport;
	public $acl_by_section_id;
	public $acl_by_section_parent_id;
	public $acl_by_level;
	public $child_sections;
	
	public $acl_unsorted;
	public $acl_sorted;
	public $current_level;	
	
	function __construct(&$transport, $module_name)
	{
		$this->transport = $transport;
		$this->section_tree = $this->Build_Section_Tree($module_name);
	}
	
	public function Disable_Section($module_name)
	{
		$this->transport->acl_unsorted[$this->transport->company_id][$module_name] = NULL;
		$this->section_tree = $this->Build_Section_Tree($this->module_name);
	}
	
	private function Build_Section_Tree($module_name)
	{
		$section_tree = array();
		$this->acl_by_section_id = array();
		$this->acl_by_section_parent_id = array();
		$this->acl_by_level = array();
		$this->child_sections = array();		
		
		$this->module_name = $module_name;
		$this->acl_unsorted = $this->transport->acl_unsorted[$this->transport->company_id];
		$this->acl_sorted = $this->transport->acl_sorted;
		$this->current_level = $this->acl_unsorted[$this->module_name]->level;

		// create acl vars by different keys
		foreach ($this->acl_unsorted as $acl_prep) 
		{
			$this->acl_by_section_id[$acl_prep->section_id] = $acl_prep;	
			$this->acl_by_section_parent_id[$acl_prep->section_parent_id][] = $acl_prep;	
			$this->acl_by_level[$acl_prep->level][] = $acl_prep;	
		}
		
		// search for parent module  this->parent_module
		$this->parent_section = $this->Get_Parent_Section();
		$this->parent_module = $this->parent_section->name;
		
		
		// gather all sections related to the parent 
		// structure the array to be array[ level ] [ sequence ] [ section_id ]
		$this->Get_Child_Sections($this->parent_section->section_id);
		
		
		if (count($this->child_sections))
		{
			
			foreach ($this->child_sections as $child_section)
			{
				
				$section_tree[$child_section->level][$child_section->section_parent_id][$child_section->sequence_no][$child_section->section_id] = $child_section;
			}
		}

		return $section_tree;
	}
	
	private function Get_Child_Sections($section_parent_id)
	{
		reset($this->acl_by_section_id);
		foreach($this->acl_by_section_id as $section)
		{
			
			if ($section->section_parent_id == $section_parent_id)
			{
				$this->child_sections[$section->section_id] = $section;
				$this->Get_Child_Sections($section->section_id);
			}
		}
	}
	
	
	public function Get_Parent_Section()
	{

		if ($this->current_level>0)
		{
			$limit = 20;
			$tries = 0;
			$current_section_name = $this->module_name;
			
			while(!$found_it && ($tries <= $limit))
			{
				if ($this->acl_by_section_id[$this->acl_unsorted[$current_section_name]->section_parent_id]->level == '0')
				{
					return $this->acl_by_section_id[$this->acl_unsorted[$current_section_name]->section_parent_id];
				}
				else 
				{
					$current_section_name = $this->acl_by_section_id[$this->acl_unsorted[$current_section_name]->section_parent_id]->name;	
				}
				++$tries;
			}

		}
		else
		{
			return $this->acl_unsorted[$this->module_name];
		}
	}
	
	public function Reset($module_name)
	{
		$this->section_tree = $this->Build_Section_Tree($module_name);
	}
}

