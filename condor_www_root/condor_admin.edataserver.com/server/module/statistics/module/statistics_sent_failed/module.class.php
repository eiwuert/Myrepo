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
		
		// Add statistics query class
		$this->statistics_query = new Condor_Statistics_Query($this->server);
		$this->form_validation = new Form_Validation($server);
	}

	public function Main()
	{
		$this->transport->action = $this->request->action;
		
		// Initial values
		$this->data->num_results = 0;
		$this->data->date_start = date("m/d/Y");
		$this->data->date_end = date("m/d/Y");
		$this->data->success = false;
		
		if (strlen($this->request->date_start) || strlen($this->request->date_end))
		{
			$this->form_validation->Normalize_Request_Fields(
				array("date_start", "date_end"),
				$this->request
			);
			
			$this->data->validation = $this->form_validation->Validate_Request_Fields(
				array(
					'date_start' => array(true, 'date_mm/dd/yyyy'),
					'date_end' => array(true, 'date_mm/dd/yyyy')
				),
				$this->request
			);
			
			$this->data->date_start = date("m/d/Y", strtotime($this->request->date_start));
			$this->data->date_end = date("m/d/Y", strtotime($this->request->date_end));
			
			// No validation errors?
			if (empty($this->data->validation))
			{
				// Start date before end date?
				if (strtotime($this->request->date_start) > strtotime($this->request->date_end))
				{
					$this->data->validation['date_start'] = 'INVALID';
					$this->data->validation['date_end'] = 'INVALID';
					$this->data->errors[] = "Start date must precede the end date.";
				}
				else
				{
					$failed_sends = $this->statistics_query->Get_Failed_Sends(
						$this->request->date_start,
						$this->request->date_end
					);
					
					$this->data->failed = $failed_sends;
					$this->data->num_results = count($failed_sends);
					$this->data->success = true;
				}
			}
		}
		else
		{
			// Initial load of the page, doesn't perform any searching
			$this->data->success = true;
		}
		
		$this->transport->Set_Data($this->data);

		return true;
	}
}

?>
