<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';

class Profiles
{
	private $transport;
	private $request;
	private $last_agent_id;
	private $last_action;
	private $agent_login_id;
	private $acl;


	/**
	 *
	 */
	public function __construct($agent_login_id, Transport $transport, $request, $acl)
	{
		$this->agent_login_id = $agent_login_id;
		$this->transport = $transport;
		$this->request = $request;
		$this->acl = $acl;

		if (isset($this->request->action))
		{
			$this->last_action = $this->request->action;
		}

		if( isset($this->request->agent) && is_numeric($this->request->agent) )
		{
			$this->last_agent_id = $this->request->agent;
		}
	}



	/**
	 *
	 */
	public function Add_Profile()
	{
		$this->acl->Add_Agent($this->request->agent_login, $this->request->name_first, $this->request->name_last, $this->request->password1);

		return $this->_Fetch_Data();
	}



	/**
	 *
	 */
	public function Modify_Profile()
	{
		$this->acl->Update_Agent($this->request->agent, $this->request->agent_login,
		$this->request->name_first, $this->request->name_last,
		$this->request->password2);
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

		$data['agent'] = $this->acl->Get_Agents();

		if( isset($this->last_agent_id) )
		{
			$data['last_agent_id'] = $this->last_agent_id;
		}

		if( isset($this->last_action) )
		{
			$data['last_action'] = $this->last_action;
		}

		$this->transport->Set_Data($data);

		return TRUE;
	}
}

?>
