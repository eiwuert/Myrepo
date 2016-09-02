<?php

require_once(SERVER_CODE_DIR.'module_interface.iface.php');
require_once(SERVER_MODULE_DIR.'/admin/profiles.class.php');
require_once(SERVER_MODULE_DIR.'/admin/privs.class.php');
require_once(SERVER_MODULE_DIR.'/admin/groups.class.php');
require_once(SERVER_MODULE_DIR.'/admin/blank.class.php');

class Module implements Module_Interface
{
	private $action;
	private $profile_object;
	private $group_object;
	private $priv_object;
	private $server;
	private $request;


	public function __construct(Server $server, $request, $module_name)
	{
		$this->server = $server;
		$this->request = $request;

		$this->blank_object = new Blank();
		$this->profile_object = new Profiles($server->agent_id, $server->transport, $request, $server->acl);
		$this->group_object = new Groups($server, $request);
		$this->priv_object = new Privs($server, $request);

		if (isset($request->mode) && $request->mode != 'admin')
		{
			if ($request->mode == 'groups') // groups
			{
				$mode = "groups";
				if(isset($request->action))
				{
					$this->action = $request->action;
				}
				else
				{
					$this->action = 'display_groups';
				}
			}
			else if ($request->mode == 'profiles') // profiles
			{
				$mode = "profiles";
				if(isset($request->action))
				{
					$this->action = $request->action;
				}
				else
				{
					$this->action = 'display_profiles';
				}
			}
			else if ($request->mode == 'privs')// privs
			{
				$mode = "privs";
				if(isset($request->action))
				{
					$this->action = $request->action;
				}
				else
				{
					$this->action = 'display_privs';
				}
			}
			else
			{
				$mode = "blank";
				$this->action = 'display_blank';
			}
		}
		else
		{
			$mode = "blank";
			$this->action = 'display_blank';
		}

		// left off here
		if(isset($request->action))
		{
			$this->action = $request->action;

			if (strpos($request->action, 'group') === false)
			{
				if (strpos($request->action, 'priv') === false)
				{
					$mode = 'profiles';
				}
				else
				{
					$mode = 'privs';
				}
			}
			else
			{
				$mode = 'groups';
			}
		}

		$server->transport->Add_Levels($module_name, $mode);
	}



	/**
	 *
	 */
	public function Main()
	{
		switch($this->action)
		{
			// profiles
			case 'add_profile':
			$this->profile_object->Add_Profile();
			break;

			case 'modify_profile':
			$this->profile_object->Modify_Profile();
			break;

			// groups
			case 'add_groups':
			$this->group_object->Add_Groups();
			$this->group_object->Display();
			break;

			case 'modify_groups':
			$this->group_object->Modify_Groups();
			break;

			case 'delete_groups':
			$this->group_object->Delete_Groups();
			break;

			// privss
			case 'add_privs':
			$this->priv_object->Add_Privs();
			break;

			case 'delete_privs':
			$this->priv_object->Delete_Privs();
			break;

			case 'display_privs':
			$this->priv_object->Display();
			break;

			case 'display_groups':
			$this->group_object->Display();
			break;

			case 'display_profiles':
			$this->profile_object->Display();
			break;

			case 'display_blank':
			$this->blank_object->Display();
			break;

		} return TRUE;
	}
}

?>
