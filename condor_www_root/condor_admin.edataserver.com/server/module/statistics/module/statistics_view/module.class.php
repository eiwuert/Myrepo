<?php

require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once(SERVER_CODE_DIR . "condor_statistics_query.class.php");

class Module implements Module_Interface
{
	private $server;
	private $request;
	private $transport;
	private $action;
	private $data;
	
	private $statistics_query;

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

		if($this->transport->action == 'Submit')
		{
			$this->data->date = date('m/d/Y', strtotime($this->request->date));
		}
		else
		{
			$this->data->date = date('m/d/Y');
		}
		
		$this->transport->Set_Data($this->data);

		return TRUE;
	}
}
