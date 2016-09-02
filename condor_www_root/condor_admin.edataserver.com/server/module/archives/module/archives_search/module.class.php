<?php

require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once(SERVER_CODE_DIR . "search_server.class.php");

class Module implements Module_Interface
{
	private $server;
	private $request;
	private $transport;
	private $action;
	private $data;
	private $form_validation;
	private $document_query;
	private $search_server;

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
		
		// initialize sub-server
		$this->search_server = new Search_Server($this->server, $request, $module_name);
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		
		$this->data = $this->search_server->Main();
		
		$this->transport->Set_Data($this->data);
		
		//print "<pre>" . var_export($this->data, true) . "</pre>";

		return TRUE;
	}
	

	
}
