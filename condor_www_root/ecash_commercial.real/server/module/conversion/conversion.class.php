<?php

require_once(SERVER_CODE_DIR . "base_module.class.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");

/*  The Conversion class covers the basic functions for all conversion methods.
 *  The sub-classes of Other_Conversion and Cashline_Conversion cover the 
 *  Get_Queue_Count and Get_Next_App methods for us since they use different tables.
 */

class Conversion extends Base_Module
{
	public  $ld;
	public  $db;
	private $conversion_mode;

	const DEQUEUE_TIMEOUT = 10;

	public function __construct(Server $server, $request, $mode)
	{
		parent::__construct($server, $request, $mode);
		$this->ld = new Loan_Data($this->server);
		$this->db = ECash_Config::getMasterDbConnection();
		$this->conversion_mode = eCash_Config::getInstance()->CONVERSION_MODE;
	}

	public function Get_Next_App($status)
	{
		// For safety reasons, wipe this first.
		unset($_SESSION['conversion_status_chain']);

		$data = $this->Get_Next_Application($status);
		
		if (empty(ECash::getTransport()))
		{
			return $data->application_id;;
		}
		
		if( is_object($data) && count((array)$data) )
		{
			ECash::getTransport()->Set_Data($data);
			if($data->conversion_mode == 'cashline') 
			{
				ECash::getTransport()->Add_Levels('overview','conversion_info','view','general_info','view');
			}
			else 
			{
				ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
			}
		}
		else
		{
			$data = new stdClass();
			$data->queue_count = $this->Get_Conversion_Queue_Count();
			$_SESSION['queue_count'] = $data->queue_count;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('overview','queue_empty');
		}
	}
	
	public function Get_Next_Return($status = 'other')
	{
		switch ($status)
		{
			case 'collections_contact':
				$queue_name = 'Cashline Return Collections Contact';
				break;
			case 'collections_new':
				$queue_name = 'Cashline Return Collections New';
				break;
			case 'past_due':
				$queue_name = 'Cashline Return Past Due';
				break;
			default:
				$queue_name = 'Cashline Return';
		}
		$application_id = queue_pull($queue_name, 'ASC');
		
		if ($application_id > 0)
		{
			$data = $this->ld->Fetch_Loan_All($application_id);
			$data->queue_count = $this->Get_Queue_Count();
			$data->conversion_mode = 'cashline';
			$_SESSION['queue_count'] = $data->queue_count;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('overview','schedule','view');
		}
		else
		{
			$data = new stdClass();
			$data->queue_count = $this->Get_Queue_Count();
			$data->conversion_mode = 'cashline';
			$_SESSION['queue_count'] = $data->queue_count;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('overview','queue_empty');
		}
	}

	public function Get_Conversion_Queue_Count()
	{
		return $this->Get_Queue_Count();
	}

	public function Generate_Schedule($application_id = null)
	{
		if (empty($application_id))
		{
			$application_id = $_SESSION['current_app']->application_id;
		}

		switch($this->request->action_type)
		{
		case 'fetch':
			$data = $this->ld->Fetch_Loan_All($application_id, false);
			$data->collectionsAgents = Get_Collections_Agents($this->server->company_id);
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'generate_schedule');
			break;
		case 'save':
			// Check for Modifications before posting modifications
			if(Check_For_Transaction_Modifications($this->db, $this->server->log, $application_id))
			{
				ECash::getLog()->write("[Agent:{$_SESSION['Server_state']['agent_id']}] Modification to " . $application_id . " have been made since last data retrieval!  Not submitting changes.");
				$_SESSION['error_message']='WARNING!  Changes have been made since this account information was retrieved.  Your changes will not be submitted.';
			}
			else
			{
				$tr_info = Get_Transactional_Data($application_id, $this->db);
				ECash::getLog()->Write("[App_id: $application_id] Creating an edited schedule.");
				$status_chain = Create_Edited_Schedule($application_id, $this->request, $tr_info);
				$_SESSION['conversion_status_chain'] = $status_chain;

				// If they checkmarked the watch flag, enable the watch flag.  This will not actually
				// enable the watch status as far as ACH stuff is concerned, it merely puts them into
				// an unaffiliated state.  Once they become affiliated, they will actually start to
				// fall under the special rules for 'watched' accounts.
				if($_REQUEST['watch_flag'] == "on" || $this->request->account_status == 20)
				{
					Set_Watch_Status_Flag($application_id, 'yes');
					if ($this->request->account_status == "3")
					{
						$_SESSION['conversion_status_chain'] = array('queued', 'contact', 'collections', 'customer', '*root');
					}
				}
				$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			}

			if (!empty(ECash::getTransport())) 
			{
				ECash::getTransport()->Set_Levels('close_pop_up');
			}
			break;
		}
	}

	public function Import_Account()
	{
		$application_id = $_SESSION['current_app']->application_id;

		// Change the status
		if(isset($_SESSION['conversion_status_chain']))
		{
			$status_chain = $_SESSION['conversion_status_chain'];
			if(Update_Status($this->server, $application_id, $status_chain))
			{
				Verify_Import($application_id);
			}
			unset($_SESSION['conversion_status_chain']);
		}
		else
		{
			$schedule = Fetch_Schedule($application_id);
			if (count($schedule) == 0) 
			{
				$_SESSION['error_message'] = 'WARNING!  Transactions have not been generated for this account yet.  No changes will be made.';
			}
		}

		ECash::getTransport()->Set_Data($this->ld->Fetch_Loan_All($application_id));
		if($this->conversion_mode === 'CASHLINE')
		{
			ECash::getTransport()->Add_Levels('overview','conversion_info','view','general_info','view');
		}
		else
		{
			ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
		}
	}

	public function Reset_Account()
	{
		$application_id = $_SESSION['current_app']->application_id;

		if(Check_For_Transaction_Modifications($this->db, $this->server->log, $application_id))
		{
			ECash::getLog()->write("[Agent:{$_SESSION['Server_state']['agent_id']}] Modification to " . $application_id . " have been made since last data retrieval!  Not submitting changes.", LOG_WARNING);
			$_SESSION['error_message'] = 'WARNING!  Changes have been made since this account information was retrieved.  Your changes will not be submitted.';
		}
		else
		{
			eCash_AgentAffiliation::expireAllApplicationAffiliations($application_id);
			Remove_Unregistered_Events_From_Schedule($_SESSION['current_app']->application_id);
			$this->Remove_All_Events();
			Update_Status($this->server, $application_id, array('dequeued','cashline','*root'));
		}

		

		ECash::getTransport()->Set_Data($this->ld->Fetch_Loan_All($application_id, true));
		if($this->conversion_mode === 'CASHLINE')
		{
			ECash::getTransport()->Add_Levels('overview','conversion_info','view','general_info','view');
		}
		else
		{
			ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
		}
	}

	public function Remove_All_Events($application_id = NULL)
	{
		if (empty($application_id))
		{
			$application_id = $_SESSION['current_app']->application_id;
		}

		$this->db->exec("DELETE FROM transaction_history WHERE application_id = $application_id");
		$this->db->exec("DELETE FROM transaction_ledger WHERE application_id = $application_id");
		$this->db->exec("DELETE FROM transaction_register WHERE application_id = $application_id");
		$this->db->exec("DELETE FROM event_amount WHERE application_id = $application_id");
		$this->db->exec("DELETE FROM event_schedule WHERE application_id = $application_id");
	}
	

	public function Send_To_Managers()
	{
		$application_id = $_SESSION['current_app']->application_id;
		
		Update_Status($this->server, $application_id, "manager_queued::cashline::*root");
		ECash::getTransport()->Add_Levels("search");
	}


	public function Get_Manager_Agent_Ids()
	{
		$query = "
			SELECT acl.access_group_id
			FROM acl, section s
			WHERE s.name = 'conversion_manager'
			AND s.section_id = acl.section_id
			AND acl.company_id = {$this->server->company_id}";

		$results = $this->db->query($query);
		$agids = array();
		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			$agids[] = $row->access_group_id;
		}

		if (empty($agids)) return array();

		$sel2_query = "
			SELECT DISTINCT agent_id
			FROM agent_access_group
			WHERE access_group_id in (".implode(",", $agids).")";

		$agent_ids = array();
		$results = $this->db->query($sel2_query);
		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			$agent_ids[] = $row->agent_id;
		}

		return $agent_ids;
	}
	
}
?>
