<?php

// require_once("collections_queue.class.php");
require_once(SERVER_MODULE_DIR . "collections/quick_checks.class.php");
require_once(SERVER_CODE_DIR . "email_queue.class.php");
require_once SERVER_MODULE_DIR.'collections/EcldUnmatchedReturnFile.php';
require_once SERVER_MODULE_DIR.'collections/EcldUnmatchedReturn.php';
		

class Collections extends Base_Module
{
	private $tq;
	private $cq;
	private $collection_query;
	public function __construct(Server $server, $request, $mode, Module_Interface $module = NULL)
	{ 
		parent::__construct($server, $request, $mode, $module);
		$this->server = $server;
		$this->transport = ECash::getTransport();
		$this->mode= $mode;
		$this->module = $module;
		$this->module_name = 'collections';
		$this->request = $request;
		$this->quick_check = new Quick_Checks( $this->server );
	}

	public function Receive_Quick_Checks()
	{
		$obj = new stdClass();
		if(! empty($_FILES))
		{
			if( is_uploaded_file($_FILES['quick_checks_file']['tmp_name']))
			{
				try
				{
					$success = $this->quick_check->Process_Return_File($_FILES['quick_checks_file']['tmp_name']);
				}
				catch( Exception $e )
				{
					$obj->display_upload_status = "processing failed";
				}

				if( ! empty($success) && $success === true )
					$obj->display_upload_status = "success";
			}
			else
			{
				$obj->display_upload_status = "upload failed";
			}
		}

		ECash::getTransport()->Set_Data($obj);
		ECash::getTransport()->Add_Levels('collections');
		ECash::getTransport()->Add_Levels('quick_checks','receive');
	}

	public function Search_Quick_Checks($from_date, $to_date)
	{
		$from = $from_date->from_date_year . $from_date->from_date_month . $from_date->from_date_day;
		$to   = $to_date->to_date_year     . $to_date->to_date_month     . $to_date->to_date_day;
		$obj  = new stdClass();
		$pending = $this->quick_check->Get_Pending_Count();
		$obj->pending_count = $pending['count'];
		$obj->pending_total = $pending['total'];
		$data               = $this->quick_check->Get_Batch_Info( $from, $to );
		$obj->data_stuff    = array();

		foreach( $data as $row )
		{
			$new_row = new stdClass();
			$new_row->date_created          = $row['date'];
			$new_row->type                  = $row['type'];
			$new_row->record_count          = number_format((int)$row['count']);
			$new_row->total		            = "\\$" . number_format($row['total'], 2);
			// pdf batches were grouped to show as 1 line per date
			// so must use a date instead of a specific id
			if( strtolower($row['type']) === 'pdf' )
			{
				$m = substr($row['date'], 0, 2);
				$d = substr($row['date'], 3, 2);
				$y = substr($row['date'], 6, 4);
				$new_row->quick_checks_batch_id = $y . $m . $d;
			}
			else
			{
				$new_row->quick_checks_batch_id = $row['id'];
			}
			$new_row->status                = ucfirst($row['status']);
			$obj->data_stuff[] = $new_row;
		}
		ECash::getTransport()->Set_Data($obj);

		ECash::getTransport()->Add_Levels('quick_checks','quick_checks');
	}
	
	/**
	 * Sets up the transport object to display the unmatched returns screen 
	 * containing the files created between $from_date and $to_date.
	 *
	 * @param string $from_date
	 * @param string $to_date
	 */
	public function Search_Unmatched_Returns($from_date, $to_date)
	{
		$from = $from_date->from_date_year . $from_date->from_date_month . $from_date->from_date_day;
		$to   = $to_date->to_date_year     . $to_date->to_date_month     . $to_date->to_date_day;
		
		$data = new stdClass;
		$data->pending_count = eCash_EcldUnmatchedReturn::countAllUnrecievedMatches($this->server->company_id);
		$data->unmatched_returns = array();

		foreach(eCash_EcldUnmatchedReturnFile::listMatchingFiles($from, $to, $this->server->company_id) as $file )
		{
			$new_row = new stdClass();
			$new_row->date_created = date('m/d/Y', strtotime($file['date_created']));
			$new_row->file_name = $file['file_name'];
			$new_row->file_id = $file['ecld_unmatched_return_file_id'];
			$new_row->count = $file['cnt'];
			
			$data->unmatched_returns[] = $new_row;
		}
		ECash::getTransport()->Set_Data($data);

		ECash::getTransport()->Add_Levels('unmatched_returns');
	}
	
	/**
	 * Builds a new unmatched returns file.
	 */
	public function Build_Unmatched_File()
	{
		eCash_EcldUnmatchedReturnFile::createNewFile($this->server->company_id);
	}
	
	/**
	 * Downloads the specified unmatched returns file.
	 *
	 * @param int $file_id
	 */
	public function Download_Unmatched_File($file_id)
	{
		$file = eCash_EcldUnmatchedReturnFile::loadFile($file_id, $this->server->company_id);
		
		if ($file instanceof eCash_EcldUnmatchedReturnFile)
		{
			$file_size = strlen($file->getFileContent());
			header( "Accept-Ranges: bytes\n");
			header( "Content-Length: {$file_size}\n");
			header( "Content-Disposition: attachment; filename={$file->getFileName()}\n");
			header( "Content-Type: application/vnd.ms-excel\n\n");
			
			echo $file->getFileContent();
			exit;
		}
		else 
		{
			throw new Exception("Could not find file id {$file_id} for company in the unmatched returns table.");
		}
	}

	public function View_Batch($quick_checks_batch_id)
	{
		$obj = new stdClass();
		$obj->data_rows = array();
		$data = $this->quick_check->Get_PDF_Batch_Info($quick_checks_batch_id);
		foreach( $data as $row )
		{
			$new_row = new stdClass();
			$new_row->status                   = $row['status'];
			$new_row->number_in_batch          = $row['count'];
			$new_row->total					   = $row['total'];
			$new_row->quick_checks_subbatch_id = $row['ecld_file_id'];
			$obj->data_rows[] = $new_row;
		}

		ECash::getTransport()->Set_Data($obj);

		ECash::getTransport()->Set_Levels('popup', 'quick_check_view_download');
	}

	public function Resend($quick_checks_batch_id)
	{
		$obj = new stdClass();
		$obj->quick_checks_batch_id = $quick_checks_batch_id;
		ECash::getTransport()->Set_Data($obj);
		$this->quick_check->Send_Deposit_File($quick_checks_batch_id);
		ECash::getTransport()->Add_Levels('quick_checks');
	}

	public function Download_Subbatch( $quick_checks_subbatch_id )
	{
		$this->quick_check->Download_PDF( (int)$quick_checks_subbatch_id );
	}

	public function Process_Quick_Checks($collection_type)
	{
		// Remove the IPC file to clear old messages out of the message queue.
		Remove_Progress_Facility('qc');
		switch( strtolower($collection_type) )
		{
			case 'electronic':
				$exec_string = CLI_EXE_PATH."php -f ".BASE_DIR."cronjobs/ecash_engine.php " . $this->server->company . " main qc_batch >>/virtualhosts/log/applog/" . APPLOG_SUBDIRECTORY . "/quickchecks/send_batch.log &";
				exec($exec_string);
				ECash::getLog()->Write("Launched QC Process: $exec_string");
				break;
			case 'pdf':
			default:
				$this->quick_check->Process_PDF_Checks();
				break;
		}
	}


	public function Add_Collections_Dispositions()
	{

		require_once(SQL_LIB_DIR . "loan_actions.func.php");

		$loan_data = new Loan_Data($this->server);
		ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($this->request->application_id));

		ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				
		// Grabbing the last Status_History ID
		$app_history_array = Get_Application_History($this->request->application_id);
		$app_history = $app_history_array[count($app_history_array)-1];
		$status_id = $app_history->status_history_id;		
		
		foreach ($this->request->loan_action as $key => $value)
		{
			if($value != 0)
			$loan_history_id = Insert_Loan_Action($this->request->application_id,$value,$status_id, $this->server->agent_id); //mantis:6807 - added $agent_id
		}
			
		return  $loan_history_id;
	}
	
	public function Change_Status()
	{
		$loan_data = new Loan_Data($this->server);
		$app_id = $this->request->application_id;
		$action_result = NULL;
		$set_data = TRUE;


		switch($this->request->submit_button)
		{
			case 'Second_Tier':
				$action_result = $loan_data->To_Second_Tier($app_id);
				Add_Comment(ECash::getTransport()->company_id, $app_id, ECash::getTransport()->agent_id, "Sent to Collections 2nd Tier");
				break;
			case 'Bankruptcy_Notification' :
				$action_result = $loan_data->Bankruptcy($app_id, false);
				if ($action_result)
				{
					Remove_Unregistered_Events_From_Schedule($app_id);
				}
				$this->request->comment = "Bankruptcy notified";
				break;
			case 'Bankruptcy_Verified' :
				$action_result = $loan_data->Bankruptcy($app_id, true);
				$this->request->comment = "Bankruptcy Verified";
				break;
			case 'Not_Bankruptcy':
				if ($this->tq->Has_Fatal_ACH_Codes($app_id))
				{
					$action_result = $loan_data->Quickcheck($app_id);
				}
				else
				{
					$action_result = $loan_data->Not_Bankruptcy($app_id);
					if ($action_result)
					{
						Schedule_Full_Pull($app_id, $this->db);
					}
				}
				break;
			case 'Dequeue':
				$action_result = $loan_data->Permanently_Dequeue($app_id);
				$this->Add_Loan_Action();
				if(! empty($this->request->comment))
					$this->Add_Comment($set_data, $comm_ref);
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;
			case 'Skip_Trace' :
				$action_result = $loan_data->To_Skip_Trace($app_id);
				$this->request->comment = "Added Skip Trace";
				break;
			case 'Remove_Skip_Trace':
				$action_result = $loan_data->From_Skip_Trace($app_id);
				$this->request->comment = "Removed Skip Trace";
				break;
		}

// There are no docs for bankruptcy?!?
//		if ($action_result)
//		{
//			require_once(SERVER_CODE_DIR ."documents.class.php");
//			$documents = new Documents($this->server, $this->request);
//			$documents->Send_Docs();
//		}

		ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($app_id));
		ECash::getTransport()->Set_Levels('application','collections','internal','overview','loan_actions','view','general_info','view');
		
		$this->setupQueues($this->module);
		return ($action_result);
	}



	// This needs to be consolidated
	public function Add_Comment($comment_reference = null)
	{
		// Requiring the file here because the functions are only used here.
		require_once(dirname(__FILE__)."/../../../sql/lib/comment.func.php");

		if(trim($this->request->comment) != '')
		{
			// Set the type of comment
			//   REQUIRED
			switch( $this->request->action )
			{
				case 'add_follow_up':
				$this->request->comment_type = 'followup';
				break;
				case 'change_status':
				switch( $this->request->submit_button )
				{
					default:
					$this->request->comment_type = 'standard';
					break;
				}
				break;
				case 'add_comment':
				default:
				$this->request->comment_type = 'standard';
				break;
			}


			Add_Comment($this->server->company_id, $this->request->application_id, $this->server->agent_id,
				    $this->request->comment, $this->request->comment_type, $this->server->system_id);
		}

		$loan_data = new Loan_Data($this->server);
		ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($this->request->application_id));
		ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
	}
	
	public function Active_And_Regenerate_Schedule() 
	{
		Update_Status($this->server, $this->request->application_id, array('active', 'servicing', 'customer', '*root' ));
		Complete_Schedule($this->request->application_id);
		$loan_data = new Loan_Data($this->server);
		$data = $loan_data->Fetch_Loan_All($this->request->application_id);
		ECash::getTransport()->Set_Data($data);
	}

	public function Deceased_Notification($application_id)
	{
		$status_map = Fetch_Status_Map(FALSE);

		$deceased_status = Search_Status_Map('unverified::deceased::collections::customer::*root', $status_map);

		// Set it as a deceased unverified status
		Update_Status(NULL, $application_id, $deceased_status, NULL, NULL, FALSE);

		$loan_data = new Loan_Data($this->server);
		$data = $loan_data->Fetch_Loan_All($application_id);
		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');	
	}

	public function Deceased_Verification($application_id)
	{
		$status_map = Fetch_Status_Map(FALSE);

		$deceased_status = Search_Status_Map('verified::deceased::collections::customer::*root', $status_map);

		// Set it as a deceased unverified status
		Update_Status(NULL, $application_id, $deceased_status, NULL, NULL, FALSE);

		$loan_data = new Loan_Data($this->server);
		$data = $loan_data->Fetch_Loan_All($application_id);
		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');	
	}


	/**
	 * Recalls a loan from 2nd Tier Collections
	 * - This may ONLY be Pending or Sent, not recovered
	 * - Currently nothing is done to notify the 2nd Tier collections company
	 *   nor are any adjustments being made.
	 *
	 * 	- The customer is moved to the 'Contact Collections' status and the 
	 *    General Collections Queue, and Complete_Schedule() is run to rebuild
	 *    the customer's schedule.
	 * 
	 * @param integer $application_id
	 */
	public function Recall_Loan($application_id)
	{
		$status = Fetch_Application_Status($application_id);
		$approved_statuses = array( 'sent::external_collections::*root',
									'pending::external_collections::*root');
		
		if(in_array($status['status_chain'], $approved_statuses))
		{
			$agent_id = ECash::getAgent()->getAgentId();
			$company_id = ECash::getCompany()->company_id;
			$comment = "Recalled customer from 2nd Tier Collections";
			Add_Comment($company_id, $application_id, $agent_id, $comment);
			
			Update_Status(NULL, $application_id, 'queued::contact::collections::customer::*root');
			Complete_Schedule($application_id);
		}
		else
		{		
			$_SESSION['error_message'] = "This status of this application is not valid for this function!";
		}
		
		$loan_data = new Loan_Data($this->server);
		$data = $loan_data->Fetch_Loan_All($this->request->application_id);
		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');	

	}

	
}

?>
