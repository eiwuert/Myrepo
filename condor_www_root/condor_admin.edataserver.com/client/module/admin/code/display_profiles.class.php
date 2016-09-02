<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{
	private $agents;
	private $last_agent_id;
	private $last_action;


	public function __construct(Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = $transport->Get_Data();
		$this->agents = $returned_data['agent'];

		// get the last agent id if it exists
		if (isset($returned_data['last_agent_id']))
		{
			$this->last_agent_id = $returned_data['last_agent_id'];
		}
		else
		{
			$this->last_agent_id = 0;
		}

		// get the last acl action if it exists
		if (isset($returned_data['last_action']))
		{
			$this->last_action = "'" . $returned_data['last_action'] . "'";
		}
		else
		{
			$this->last_action = "'" . 'add_profile' . "'";
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

		foreach ($this->agents as $user)
		{
			$fields->agents .= "\n{id:'{$user->agent_id}', agent_login:'{$user->login}', name_first:'{$user->name_first}', name_last:'{$user->name_last}'},";
		}

		$fields->agents .= "];";

		$fields->last_agent_id = $this->last_agent_id;
		$fields->last_action = $this->last_action;

		$js = new Form('js/admin_profiles.js');

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

			foreach ( $this->agents as $user )
			{
				$fields->user_select_list .= "<option value={$user->agent_id}>{$user->login}</option>";
			}

			$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/admin_profiles.html");

			return $form->As_String($fields);
		}
	}
}

?>
