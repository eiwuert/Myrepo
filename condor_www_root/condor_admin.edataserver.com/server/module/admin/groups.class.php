<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';

class Groups
{
	private $transport;
	private $request;
	private $last_group_id;
	private $last_action;
	private $agent_login_id;
	private $acl;
	private $server;
	private $errors;


	/**
	 *
	 */
	public function __construct($server, $request)
	{
		$this->server = $server;
		$this->agent_login_id = $server->agent_id;
		$this->transport = $server->transport;
		$this->request = $request;
		$this->acl = $server->acl;

		if (isset($request->action))
		{
			$this->last_action = $request->action;
		}

		if (isset($this->request->groups) && is_numeric($this->request->groups))
		{
			$this->last_group_id = $this->request->groups;
		}
	}



	/**
	 *
	 */
	public function Add_Groups()
	{
		if ($this->Has_Company_Access($this->request->company))
		{
			$section_ids = $this->_Get_Group_IDs($this->request);
			$this->acl->Add_Group($this->request->group_name, $this->request->company, $section_ids);
		}

		return $this->_Fetch_Data();
	}




	/**
	*
	*/
	private function Has_Company_Access($company_id)
	{
		if($this->transport->login == GOD_USER)
			return TRUE;
		
		$allowed_companies = array();
		$result = FALSE;
		$companies = $this->acl->Get_Companies(TRUE);

		foreach($companies as $company)
		{
			if( $this->acl->Acl_Access_Ok("admin", $company_id)
			&& $this->acl->Acl_Access_Ok("privs", $company_id))
			{
				$allowed_companies[] = $company;
			}
		}

		foreach($allowed_companies as $key => $value)
		{
			if ($value->company_id == $this->request->company)
			{
				$result = TRUE;
				break;
			}
		}

		return $result;
	}





	/**
	 *
	 */
	private function _Get_Group_IDs($request)
	{
		$section_ids = Array();
		foreach($request as $key => $value)
		{
			if (substr($key, 0, 8) == 'section_')
			{
				$section_ids[count($section_ids)] = substr($key, 8, strlen($key) - 7);
			}
		}
		return $section_ids;
	}




	/**
	 *
	 */
	public function Delete_Groups()
	{
		if ($this->Has_Company_Access($this->request->company) &&
			!$this->acl->Agents_In_Group($this->request->groups))
		{
			$this->acl->Remove_Group($this->request->groups);
		}
		else
		{
			$this->errors[] = 'Group still has Profiles assigned to it.\nPlease reassign these Profiles before deleting the group.';
		}

		return $this->_Fetch_Data();
	}




	/**
	 *
	 */
	public function Modify_Groups()
	{
		if ($this->Has_Company_Access($this->request->company))
		{
			$section_ids = $this->_Get_Group_IDs($this->request);
			$this->acl->Update_Group($this->request->groups, $this->request->group_name, $this->request->company, $section_ids);
		}

		return $this->_Fetch_Data();
	}



	/**
	 *
	 */
	public function Display()
	{
		return $this->_Fetch_Data();
	}




	/**
	 *
	 */
	private function _Fetch_Data()
	{
		//$data['groups'] = $this->acl->Get_Groups();
		$data['group_sections'] = $this->acl->Get_Groups_Sections($this->agent_login_id, 2);
		$data['master_tree'] = $this->acl->Get_Sections();

		$companies = $this->acl->Get_Companies(TRUE);
		$groups = $this->acl->Get_Groups();
		$data['groups'] = array();
		$data['companies'] = array();


		foreach($companies as $company)
		{

			if($this->transport->login == GOD_USER)
			{
				$data['companies'][] = $company;
				
				foreach($groups as $group)
				{
					if ($group->company_id == $company->company_id)
					{
						$data['groups'][] = $group;
					}
				}
			}
			elseif( $this->acl->Acl_Access_Ok("admin", $company->company_id)
			&& $this->acl->Acl_Access_Ok("privs", $company->company_id) )
			{
				$data['companies'][] = $company;

				// sort the groups so only the ones the user has access to are
				// returned.
				foreach($groups as $group)
				{
					if ($group->company_id == $company->company_id)
					{
						$data['groups'][] = $group;
					}
				}
			}

		}

		if( isset($this->last_group_id) )
		{
			$data['last_group_id'] = $this->last_group_id;
		}

		if( isset($this->last_action) )
		{
			$data['last_action'] = $this->last_action;
		}
		
		if(!empty($this->errors))
		{
			$data['errors'] = $this->errors;
		}

		$this->transport->Set_Data($data);

		return TRUE;
	}
}

?>
