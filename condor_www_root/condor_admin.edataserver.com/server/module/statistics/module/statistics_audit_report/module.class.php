<?php

require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once(SERVER_CODE_DIR . "audit_report_query.class.php");

class Module implements Module_Interface
{
	private $server;
	private $request;
	private $transport;
	private $action;
	private $data;
	private $audit_report_query;

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
		
		$this->audit_report_query = new Audit_Report_Query($this->server);
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		$this->data->action = $this->request->action;

		switch($this->request->action)
		{
			case 'detail':
				if (is_numeric($this->request->execution_id))
				{
					$this->data->document_audits = $this->audit_report_query->Fetch_Failed_Document_Audits($this->request->execution_id);
				}
				break;
			case 'list':
			default:
				$this->data->reports = $this->audit_report_query->Fetch_Report_List();
				break;
		}
		
		$this->transport->Set_Data($this->data);
		
		//echo "<pre>" . var_export($this->data,true) . "</pre>";

		return TRUE;
	}
}
