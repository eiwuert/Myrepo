<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(LIB_DIR. "admin_resources.php");


//ecash module
class Display_View extends Admin_Parent
{
	private $agents;
	private $sections;
	private $companies;
	private $section_acl;
	private $last_agent_id;
	private $groups;
	private $master_tree;
	private $group_sections;
	private $unsorted_master_tree;
	private $agent_groups;
	private $ecash_admin_resources;


	public function __construct(Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = $transport->Get_Data();
		$this->agents = $returned_data['agents'];
		$this->groups = $returned_data['groups'];
		$this->companies = $returned_data['companies'];
		$this->group_sections = $returned_data['group_sections'];
		$this->agent_groups = $returned_data['agent_groups'];
		$this->unsorted_master_tree = $returned_data['unsorted_master_tree'];

		$this->ecash_admin_resources = new Admin_Resources($this->unsorted_master_tree, $this->display_level, $this->display_sequence);

		// get the last agent id if it exists
		if (isset($returned_data['last_agent_id']))
		{
			$this->last_agent_id = $returned_data['last_agent_id'];
		}
		else
		{
			$this->last_agent_id = 0;
		}
	}



	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();
		$fields->agent_count = count($this->agents);

		$fields->agents = "[";

		// get agent info
		$temp_agent = '';
		reset($this->agents);
		foreach ($this->agents as $agent)
		{
			$fields->agents .= "\n{agent_id:'" . $agent->agent_id . "', agent_login:'" . $agent->login
			. "', name_first:'" . $agent->name_first . "', name_last:'" . $agent->name_last . "', groups:[";

			reset($this->agent_groups);
			foreach($this->agent_groups as $key)
			{
				if ($agent->agent_id == $key->agent_id)
				{
					$fields->agents .= "\n{id:'" . $key->group_id . "'},";
				}
			}

			$fields->agents .= "]},";
		}
		$fields->agents .= "];";

		// get companies
		$fields->companies = "[";
		reset($this->companies);
		foreach($this->companies as $value)
		{
			$fields->companies .= "{id:'" . $value->company_id . "', desc:'" . $value->name . "'},";
		}
		$fields->companies .= "];";

		// get company groups
		$fields->groups = "[";
		reset($this->groups);
		foreach ($this->groups as $group)
		{
			$fields->groups .= "\n{group_id:'" . $group->group_id . "', group_name:'" . $group->name
			. "', company_id:'" . $group->company_id . "', sections:[";

			reset($this->group_sections);
			foreach($this->group_sections as $group_section)
			{
				if ($group_section->group_id == $group->group_id)
				{
					$fields->groups .= "\n\t\t\t\t\t\t\t\t{id:'". $group_section->section_id . "'}, ";
				}
			}

			$fields->groups .= "]},\n";
		}
		$fields->groups .= "]";

		//  sort the master keys
		$sorted_master_keys = $this->ecash_admin_resources->Get_Sorted_Master_Tree();

		// set the master checkbox tree
		$fields->master_tree = '[';
		$temp_head = -1;
		reset($sorted_master_keys);
		foreach ($sorted_master_keys as $element => $key)
		{
			reset($this->unsorted_master_tree);
			foreach ($this->unsorted_master_tree as $element)
			{
				if ($key == $element->section_id)
				{
					// start tree
					if ($element->sequence_no == $this->display_sequence && $element->level == $this->display_level)
					{
						$temp_head = $element->section_id;
						$fields->master_tree .= "{id:'". $element->section_id ."', section_desc:'" . $element->description . "', sections:[";
					}
					else if ($element->section_parent_id == $temp_head) // chldren
					{
						$fields->master_tree .= "\n\t\t\t\t\t\t\t{id:'" . $element->section_id . "', section_desc:'" . $element->description . "'},";
					}
					else if ($element->section_parent_id != $temp_head) // new node
					{
						$temp_head = $element->section_id;
						$fields->master_tree .= "]},\n\t\t{id:'" . $element->section_id . "', section_desc:'" . $element->description . "', sections:[";
					}
				}
			}
		}
		$fields->master_tree .= "]}]";

		$fields->last_agent_id = $this->last_agent_id;

		$js = new Form('js/admin_privs.js');

		return $js->As_String($fields);
	}

	public function Get_Module_HTML()
	{
		switch ( $this->transport->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new stdClass();
			$fields->agent_count = count($this->agents);
			reset($this->agents);
			foreach ( $this->agents as $agent )
			{
				$fields->exisiting_agent_list .= "<option value='" . $agent->agent_id . "'>" . $agent->login . "</option>";
			}

			// company list
			$company_id = -1;
			$first_id = true;
			$fields->add_company_list = "";
			reset($this->companies);
			foreach($this->companies as $value)
			{
				$fields->add_company_list .= "<option value='" . $value->company_id . "'>" . $value->name . "</option>";

				if ($first_id)
				{
					$company_id = $value->company_id;
					$first_id = false;
				}
			}

			// company groups
			$fields->add_company_group_list = "";
			reset($this->groups);
			foreach($this->groups as $elements)
			{
				if ($company_id == $elements->company_id)
				{
					$fields->add_company_group_list .= "<option value='" . $elements->group_id . "'>" . $elements->name . "</option>";
				}
			}

			$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/admin_privs.html");

			return $form->As_String($fields);
		}
	}
}

?>
