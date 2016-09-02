<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';

class Privs
{
	private $transport;
	private $request;
	private $last_agent_id;
	private $agent_login_id;
	private $acl;
	private $server;

	/**
	 *
	 */
	public function __construct($server, $request)
	{
		$this->server = $server;
		$this->agent_login_id = $server->agent_id; //agent_login_id;
		$this->transport = $server->transport;
		$this->request = $request;
		$this->acl = $server->acl;

		if( isset($this->request->agent_list) && is_numeric($this->request->agent_list) )
		{
			$this->last_agent_id = $this->request->agent_list;
		}
	}



	/**
	 *
	 */
	public function Add_Privs()
	{
		if( $this->acl->Acl_Access_Ok("admin", $this->request->all_companies_list)
		&& $this->acl->Acl_Access_Ok("privs", $this->request->all_companies_list) 
		|| $this->transport->login == GOD_USER)
		{
			$this->acl->Add_Agent_To_Group($this->request->agent_list, $this->request->group_list);
		}

		return $this->_Fetch_Data();
	}



	/**
	 *
	 */
	public function Delete_Privs()
	{
		if( $this->acl->Acl_Access_Ok("admin", $this->request->agent_company_list)
		&& $this->acl->Acl_Access_Ok("privs", $this->request->agent_company_list) 
		|| $this->transport->login == GOD_USER)
		{
			$this->acl->Delete_Agent_From_Group($this->request->agent_list, $this->request->agent_group_list);
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
		$data['agents'] = $this->acl->Get_Agents();
		$data['groups'] = $this->acl->Get_Groups();
		$companies = $this->acl->Get_Companies(TRUE);
		$data['companies'] = array();
		foreach($companies as $company)
		{
			if( $this->acl->Acl_Access_Ok("admin", $company->company_id)
			&& $this->acl->Acl_Access_Ok("privs", $company->company_id) 
			|| $this->transport->login == GOD_USER)
			{
				$data['companies'][] = $company;
			}
		}
		$data['group_sections'] = $this->acl->Get_Groups_Sections($this->agent_login_id, TRUE);
		$data['agent_groups'] = $this->acl->Get_Agents_Groups();
		$data['unsorted_master_tree'] = $this->acl->Get_Sections();

		if(isset($this->last_agent_id))
		{
			$data['last_agent_id'] = $this->last_agent_id;
		}

		$this->transport->Set_Data($data);

		return TRUE;
	}
}

?>
