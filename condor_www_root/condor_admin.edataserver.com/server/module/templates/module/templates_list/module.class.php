<?php

require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once(SERVER_CODE_DIR . "condor_template_query.class.php");

class Module implements Module_Interface
{
	private $server;
	private $request;
	private $transport;
	private $action;
	private $data;

	public function __construct(Server $server, $request, $module_name)
	{
		$this->server = $server;
		$this->transport = $server->transport;
		$this->action = ($request->action) ? $request->action : NULL;
		$this->request = $request;

		// set mode
		$this->mode = ($this->request->mode) ? $this->request->mode : 'default';

		// add initial module levels
		$this->transport->Add_Levels($module_name, $this->mode);
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		
		$condor_template_query = new Condor_Template_Query($this->server);
		
		// View a shared template (this only allows them to view it, not modify it)
		if('view_shared' == $this->request->action)
		{
			$this->data = $condor_template_query->Fetch_Single($this->request->template_id);
			$this->server->template_obj = $this->data;
		}
		else
		{
			if($this->request->action == 'make_default' && isset($this->request->template_name))
			{
				$condor_template_query->Set_Default_Cover_Sheet(
					$this->server->company_id,
					$this->request->template_name
				);
			}
			
			$this->data = array();
			$this->data['template_rows'] = $condor_template_query->Fetch_All('DOCUMENT');
			$this->data['cover_template_rows'] = $condor_template_query->Fetch_All('FAX_COVER');
			$this->data['shared_template_rows'] = $condor_template_query->Fetch_Shared();
			$this->data['edit_access'] = $this->server->acl->Acl_Access_Ok('templates_edit', $this->server->company_id);
			$this->data['view_access'] = $this->server->acl->Acl_Access_Ok('templates_view', $this->server->company_id);
		}
		
		$this->transport->Set_Data($this->data);

		return TRUE;
	}
}
