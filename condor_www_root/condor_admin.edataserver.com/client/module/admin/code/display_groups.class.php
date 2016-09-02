<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(LIB_DIR. "admin_resources.php");

//ecash module
class Display_View extends Admin_Parent
{
	private $companies;
	private $unsorted_master_tree;
	private $sorted_master_tree;
	private $sorted_master_keys;
	private $groups;
	private $group_sections;
	private $last_group_id;
	private $last_action;
	private $ecash_admin_resources;
	private $errors;



	/**
	 *
	 */
	public function __construct(Transport $transport, $module_name)
	{
		
		parent::__construct($transport, $module_name);
		$returned_data = $transport->Get_Data();
		$this->companies = $returned_data['companies'];
		$this->groups = $returned_data['groups'];
		$this->group_sections = $returned_data['group_sections'];
		$this->unsorted_master_tree = $returned_data['master_tree'];
		$this->errors = $returned_data['errors'];


		$this->ecash_admin_resources = new Admin_Resources($this->unsorted_master_tree, $this->display_level, $this->display_sequence);

		// get the last agent id if it exists
		if (isset($returned_data['last_group_id']))
		{
			$this->last_group_id = $returned_data['last_group_id'];
		}
		else
		{
			$this->last_group_id = 0;
		}

		// get the last acl action if it exists
		if (isset($returned_data['last_action']))
		{
			$this->last_action = "'" . $returned_data['last_action'] . "'";
		}
		else
		{
			$this->last_action = "'" . 'add_groups' . "'";
		}
	}



	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();
		$fields->group_count = count($this->groups);

		$fields->groups = "[";
		foreach ($this->groups as $group)
		{
			$fields->groups .= "\n{group_id:'" . $group->group_id . "', group_name:'" . $group->name
			. "', company_id:'" . $group->company_id
			. "', is_used:'" . $group->is_used
			. "', sections:[";

			reset($this->group_sections);
			foreach($this->group_sections as $key)
			{
				if ($key->group_id == $group->group_id)
				{
					$fields->groups .= "\n\t\t\t\t\t\t\t\t\t{id:'" . $key->section_id . "'}, ";
				}
			}

			$fields->groups .= "]},\n";
		}
		$fields->groups .= "]";

		$this->sorted_master_keys = $this->ecash_admin_resources->Get_Sorted_Master_Tree();

		$fields->sorted_master_keys = '[';

		$a = 0;
		foreach($this->sorted_master_keys as $key => $value)
		{
			$fields->sorted_master_keys .= "{value:'" . $value . "'},";
		}
		$fields->sorted_master_keys .= "]";


		$fields->all_group_sections = '[';
		reset($this->group_sections);
		foreach($this->group_sections as $key)
		{
			$fields->all_group_sections .= "{ group_id:'" . $key->group_id . "', section_id:'" . $key->section_id ."'},";
		}
		$fields->all_group_sections .= "]";


		$this->sorted_master_tree = $this->Format_Sorted_Master_Tree();

		$fields->sorted_master_tree = $this->sorted_master_tree;
		$fields->last_group_id = $this->last_group_id;
		$fields->last_action = $this->last_action;

		$js = new Form('js/admin_groups.js');

		return $js->As_String($fields);
	}



	/**
	 *
	 */
	private function Format_Sorted_Master_Tree()
	{
		/*$master = '[';
		$temp_head = -1;

		foreach ($this->sorted_master_keys as $key => $value)
		{
			reset($this->unsorted_master_tree);
			foreach ($this->unsorted_master_tree as $element)
			{
				if ($value == $element->section_id )
				{
					$master .= "{section_id:'".$element->section_id."', section_desc:'".$element->description."', section_parent_id:'".$element->section_parent_id."'}\n";
				}
			}

		}

//echo '<pre>'.$master; exit;
		return $master;*/
	}

	/**
	 *
	 */
	public function Get_Module_HTML()
	{
		switch ( $this->transport->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new stdClass();
			$fields->group_count = count($this->groups);

			foreach ( $this->groups as $group )
			{
				$fields->group_select_list .= "<option value='{$group->group_id}'>{$group->name}</option>";
			}

			// set all companies list
			foreach ( $this->companies as $comp )
			{
				$fields->all_companies_list .= "<option value='" . $comp->company_id . "'>" . $comp->name . "</option>";
			}

			// set the master checkbox tree
			$fields->master_tree = '';
			$temp_head = -1;
			
			foreach ($this->sorted_master_keys as $element => $key)
			{
				foreach ($this->unsorted_master_tree as $element)
				{
					if ($key == $element->section_id && $element->level >= $this->display_level)
					{
						// start tree
						if ($element->sequence_no == $this->display_sequence && $element->level == $this->display_level)
						{
							$temp_head = $element->section_id;
							$fields->master_tree .= "\n<input type='checkbox' name='section_".$element->section_id."' id='".$element->section_id
							."' onClick=\"check_it(".$element->section_id.");\">
							<input type='hidden' id='section_".$element->section_id."_parent' value='".$element->section_parent_id."'><b>".$element->description.'</b><br/>';
						}
						else if ($element->section_parent_id == $temp_head) // children
						{
							$level = $element->level;
							$spacing = ($level > 2) ? str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level) : "";
							
							$fields->master_tree .= $spacing."\n<input type='checkbox' name='section_".$element->section_id."' id='".$element->section_id
							."' onClick=\"check_it(".$element->section_id.");\">
							<input type='hidden' id='section_".$element->section_id."_parent' value='".$element->section_parent_id."'>".$bold.$element->description.$bold_e."<br/>\n";
						}
						else if ($element->section_parent_id != $temp_head) // new node
						{
							$level = $element->level;
							$spacing = ($level > 2) ? str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level) : "";
							$bold = ($level > 2) ? "" : "<b>";
							$bold_e = ($level > 2) ? "" : "</b>";
							
							$fields->master_tree .= $spacing."\n<input type='checkbox' name='section_".$element->section_id."' id='". $element->section_id
							."' onClick=\"check_it(".$element->section_id.");\">
							<input type='hidden' id='section_".$element->section_id."_parent' value='".$element->section_parent_id."'>".$bold.$element->description.$bold_e."<br/>\n";
							
						}
					}
				}
			}
			
			if(!empty($this->errors))
			{
				$error = $this->errors[0];
				$fields->error_block = <<<ERROR_BLOCK
<script type="text/javascript">window.alert('$error');</script>
ERROR_BLOCK;
			}

			$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/admin_groups.html");
			return $form->As_String($fields);
		}
	}
}

?>
