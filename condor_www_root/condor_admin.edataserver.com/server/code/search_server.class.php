<?php

	class Search_Server
	{
		private $data;
		private $server;
		private $mysqli;
		private $form_validation;
		private $document_query;
		private $module_name;
		private $engine_mode;
		private $search_always;
		private $restrict_unlinked;
		private $request;
		
		public function __construct(Server $server, $request, $module_name, $engine_mode = 'ALL', $search_always = false, $restrict_unlinked = false)
		{
			$this->server = $server;
			$this->mysqli = $server->MySQLi();
			$this->module_name = $module_name;
			$this->form_validation = new Form_Validation($this->server);
			$this->data = new stdClass();
			$this->document_query = new Condor_Document_Query($this->server);
			$this->request = $request;
			$this->engine_mode = $engine_mode;
			$this->search_always = $search_always;
			$this->restrict_unlinked = $restrict_unlinked;
		}
		
		public function Main()
		{
			$this->data->request = $this->request;
			$this->data->errors = array();
			$this->data->documents = array();
					
			$this->data->action_submit = isset($this->request->submit);
			
			if ($this->request->action == 'submit' || $this->search_always == true)
			{
				$this->Normalize_Request();
				$this->data->validation = $this->Validate_Request();
				if (!sizeof($this->data->validation))
				{
					$this->data->success = true;
					
					$date_start = (strlen($this->request->date_start)) ? date("Y-m-d 00:00:00", strtotime($this->request->date_start)) : null;
					$date_end = (strlen($this->request->date_end)) ? date("Y-m-d 23:59:59", strtotime($this->request->date_end)) : null;
					$mode_app_id = ($this->request->search_mode == 'app_id' && is_numeric($this->request->id));
					$mode_archive_id = ($this->request->search_mode == 'archive_id' && is_numeric($this->request->id));
					$mode_sender = ($this->request->search_mode == 'sender' && is_string($this->request->sender));

					$offset = (isset($this->request->offset) && is_numeric($this->request->offset));
					$max = (isset($this->request->max) && is_numeric($this->request->max));
					
					$this->data->max_documents = ($max) ? $this->request->max : 100;
					$this->data->offset_document = ($offset) ? $this->request->offset : 0;
					$this->server->log->Write("Max Doc: {$this->data->max_documents}");
					$this->server->log->Write("Offset: {$this->data->offset_document}");
					list($this->data->total_documents,$this->data->documents) = $this->document_query->Fetch_Documents(
						$this->engine_mode,
						$date_start,
						$date_end,
						($mode_app_id) ? $this->request->id : null,
						($mode_archive_id) ? $this->request->id : null,
						($mode_sender) ? $this->request->sender : null,
						$this->restrict_unlinked,
						$this->data->offset_document,
						$this->data->max_documents
					);
					
				}
				else 
				{
					$this->data->success = false;
				}
			}
						
			return $this->data;
		}
		
		private function Normalize_Request()
		{
			$this->form_validation->Normalize_Request_Fields(
				array(
					'search_mode',
					'id',
					'sender',
					'date_start',
					'date_end'),
				$this->request
			);
			
			// Let's try and normalize their phone number,
			// though it still may not pass validation
			$replacement_chars = array('-', '(', ')', '.');
			$this->request->sender = str_replace(
				$replacement_chars,
				'',
				$this->request->sender
			);
		}
		
		private function Validate_Request()
		{
			$validation = $this->form_validation->Validate_Request_Fields(
				array(
					'search_mode' => array(true, 'string'),
					'id' =>          array(false, 'number'),
					'sender' =>      array(false, 'phone'),
					'date_start' =>  array(false, 'date_mm/dd/yyyy'),
					'date_end' =>    array(false, 'date_mm/dd/yyyy')
				),
				$this->request
			);
			
			if(isset($this->request->id) && empty($this->request->date_start) &&
				empty($this->request->date_end) && empty($this->request->id))
			{
				$validation['date_start'] = 'INVALID';
				$validation['date_end'] = 'INVALID';
				$this->data->errors[] = 'You must enter an ID and/or a date range.';
			}
			elseif(!isset($this->request->id) &&
				isset($this->request->sender) && empty($this->request->date_start) &&
				empty($this->request->date_end) && empty($this->request->sender))
			{
				$validation['sender'] = 'INVALID';
				$validation['date_start'] = 'INVALID';
				$validation['date_end'] = 'INVALID';
				$this->data->errors[] = 'You must enter a phone number and/or a date range.';
			}
			else
			{
				if(isset($validation['sender']))
				{
					$this->data->errors[] = 'The phone number must be a full 10 digits, including area code.';
				}
				
				if(isset($validation['date_start']) || isset($validation['date_end']))
				{
					$this->data->errors[] = 'Date must have the following format: mm/dd/yyyy.';
				}
			}
			
			if(!sizeof($validation) && (strlen($this->request->date_start) ||
				strlen($this->request->date_end)))
			{
				$start_date_array = explode('/', $this->request->date_start);
				$end_date_array = explode('/', $this->request->date_end);
				
				if(!checkdate($start_date_array[0], $start_date_array[1], $start_date_array[2]))
				{
					$validation['date_start'] = 'INVALID';
					$this->data->errors[] = 'Start Date is invalid.';
				}
				
				if(!checkdate($end_date_array[0], $end_date_array[1], $end_date_array[2]))
				{
					$validation['date_end'] = 'INVALID';
					$this->data->errors[] = 'End Date is invalid.';
				}
				
				// If the start date or end date is specified without the other
				if(strlen($this->request->date_start) && !strlen($this->request->date_end) ||
					!strlen($this->request->date_start) && strlen($this->request->date_end))
				{
					$validation['date_start'] = 'INVALID';
					$validation['date_end'] = 'INVALID';
					$this->data->errors[] = 'Start Date and End Date must both be entered.';
				}
				// If the start date is after the end date
				elseif(strtotime($this->request->date_start) > strtotime($this->request->date_end))
				{
					$validation['date_start'] = 'INVALID';
					$validation['date_end'] = 'INVALID';
					$this->data->errors[] = 'Start date must preceed end date.';
				}
			}
	
			return $validation;
		}		
	}

?>