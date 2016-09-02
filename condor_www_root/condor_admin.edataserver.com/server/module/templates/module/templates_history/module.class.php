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
	
	private $template_name;

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
		
		// Grab the name
		if ($this->server->template_obj->name && !$this->request->template_name)
		{
			$this->request->template_name = $this->server->template_obj->name;
			$this->template_name = $this->request->template_name;
		} 
		elseif($this->request->template_name)
		{
			$this->server->template_obj->name = $this->request->template_name;
			$this->template_name = $this->request->template_name;
		}
		else 
		{
			$this->transport->Set_Levels('application', 'templates_history', 'default');
			$this->action = null;
			return true;
		}
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		$condor_template_query = new Condor_Template_Query($this->server);

		switch ($this->request->action)
		{
			case 'make_current':
				// Retrieve the template to make active
				$previous = $condor_template_query->Fetch_Single($this->request->template_id);

				$new_template_id = $condor_template_query->Update_Template(
					$previous->template_id,
					$previous->subject,
					stripslashes($previous->data)
				);
				
				$this->server->active_id['template_id'] = $new_template_id;
				$this->data->success = true;
				break;
			default:
				break;
		}

		$this->data = array();
		$this->data['template_history'] = $condor_template_query->Fetch_History($this->template_name);
		$this->transport->Set_Data($this->data);

		return true;
	}
}

?>
