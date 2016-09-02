<?php
require_once(SQL_LIB_DIR . "loan_actions.func.php");
require_once(SERVER_CODE_DIR . "base_module.class.php");
require_once(SQL_LIB_DIR . "customer_contact.func.php");

class Funding extends Base_Module
{

	protected $queue;

	public function __construct(Server $server, $request, $mode, Module_Interface $module = NULL)
	{
		parent::__construct($server, $request, $mode, $module);
		$this->module_name = 'funding';
	}

	public function Get_Next_Application()
	{
		$search = new Search($this->server, $this->request);

		$queue_manager = ECash::getFactory()->getQueueManager();
		$queue = $queue_manager->getQueue($this->request->queue);
		$item = $queue->dequeue();

		if ($item != NULL)
		{
			// Agent Tracking
	 		$agent = ECash::getAgent();
			$agent->getTracking()->add($this->request->queue, $item->RelatedId);

			ECash::getApplicationById($item->RelatedId);
	        $engine = ECash::getEngine();
	        $engine->executeEvent('DEQUEUED', array('Queue' => $this->request->queue));
	        
			$search->Show_Applicant($item->RelatedId);

			$data = ECash::getTransport()->Get_Data();
			ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');

/*			if($data->application_id)
			{
				$this->Check_For_Loan_Conditions();
			}
*/
			// If the application is a react and is using an unconfirmed olp_process
			// we want to put them into a different underwriting queue for extra verification
//			$react_processes = array('online_confirmation');
//
//			if($this->mode == 'underwriting' && $data->is_react === 'yes' && in_array($data->app_olp_process, $react_processes))
//			{
//				$data->javascript_on_load = "OpenCustomerManagerWindow('action=cust_intf_get_app_info&mode=underwriting&application_id=$application_id&customer_id={$data->customer_id}', 'Customer Split/Merge', 'ssn');";
//				ECash::getTransport()->Set_Data($data);
//			}


		}
		else
		{
			if ($GLOBALS['queue_result_message'])
			{
				$duh = new stdClass;
				$duh->search_message = $GLOBALS['queue_result_message'];
				ECash::getTransport()->Set_Data($duh);
			}
//			$search->Get_Last_Search($this->module_name, $this->request->mode);
			$search->Get_Last_Search($this->module_name, $this->mode);
		}
	}

	/**
	 * Updates Status & handles some Funding...
	 *
	 * @todo Fix hard-coded values for testing Agean
	 */
	public function Change_Status($action = null)
	{
		require_once (LIB_DIR . "/Document/AutoEmail.class.php");

		$action = ($action) ? $action : $this->request->submit_button;
		$loan_data = new Loan_Data($this->server);
		$application_id = $this->request->application_id;

		$action_result = NULL;
		$set_data = TRUE;

		switch($action)
		{

			case 'Pre-Fund':
				$action_result = $loan_data->toPreFund($application_id);
				break;

			case 'Decline':
				$action_result = $loan_data->decline($application_id);
				$id = $this->request->loan_actions;
				$loan_action_name_short = Get_Loan_Action_Name_Short($id);
				if ($action_result)
				{
					$document_type = $loan_action_name_short;
					eCash_Document_AutoEmail::Send($this->server, $application_id, $document_type);
				}

				ECash::getTransport()->Set_Levels('close_pop_up');
			break;


			case "Approve":
				// Loan Action
				$action_result = $loan_data->To_Underwriting_Queue($application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "Additional":
				$action_result = $loan_data->To_Addl_Verify_Queue($application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;
			
			case "Hotfile":
				$action_result = $loan_data->To_Hotfile_Queue($application_id);
				ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				break;
				
			case "Fund":
			case "Fund_Check":
			case "Fund_Moneygram":
			case "Fund_Paydown":
			case "Fund_Payout":
				// If they're not marked Do Not Loan, and they don't have other active applications, go ahead.
				$customer = ECash::getCustomerByApplicationId($application_id);
				$dnl = $customer->getDoNotLoan();

				if ($dnl->get())
				{
					$fund = FALSE;
					$_SESSION['error_message'] = 'WARNING! This account is marked DO NOT LOAN.  Your changes will not be submitted.';
				} 
				else if ($this->Check_For_Other_Active_Loans($application_id)) 
				{
					$fund = FALSE;
					$_SESSION['error_message'] = 'WARNING! This account has other active loans.  Your changes will not be submitted.';
				} 
				else if (Has_A_Scheduled_Event($application_id)) 
				{
					$fund = TRUE;
					//$fund = FALSE;
					//$_SESSION['error_message'] = 'WARNING! This account has one or more scheduled events already.  Your changes will not be submitted.';
				} 
				else 
				{
					$results = $this->verifyFunding($application_id);
					switch($results['status'])
					{
						case 'CONFIRMED':
							$fund = TRUE;
							break;
						case 'DECLINED':
							$fund = FALSE;
							break;
						case 'UNAVAILABLE':
							//@TODO: Should build something in here to control the behavior when unavailable
							$fund = FALSE;
							break;
					}
					$this->request->comment = $results['comment'];
				}

				if($fund === TRUE)
				{
					try
					{
						$action_result = $loan_data->Fund($application_id, $this->request->submit_button);
						if($action_result)
						{
							//write the fund date and amount if funding
							$this->Update_Application();
						}

						if (!empty($this->request->investor_group))
						{
							require_once(SQL_LIB_DIR.'tagging.lib.php');
							Remove_Application_Tags($application_id);
							Tag_Application($application_id, $this->request->investor_group);
						}
					}
					catch(Exception $e)
					{
						ECash::getLog()->Write("Exception: Error funding application {$application_id}  " . $e->getMessage());
						ECash::getLog()->Write($e->getTraceAsString());
						$_SESSION['error_message'] = 'WARNING! There was an error funding this account!\n' . $_SESSION['error_message'];
						//throw $e;
					}
					// This loan is getting funded so we should not have any followups. [rlopez][mantis:8058]
					Follow_Up::Expire_Follow_Ups($application_id);

					ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
					ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				}
				else // Do Not Fund is set or they have other active loans
				{
					$data = new stdClass();
					ECash::getLog()->write("Agent tried to fund " . $application_id . " - [Do Not Loan: " . ($do_not_loan ? 'true' : 'false') . "] [Active Account: " . ($has_active_loans ? 'true' : 'false') ."] [Existing Events: " . ($has_scheduled_events ? 'true' : 'false') . "]", LOG_WARNING);
					ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
					ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
				}

				break;

			case "Deny":

				// The rule from CLK is to send the Teletrack letter if the Teletrack
				// box is checked, even if other boxes are checked too. If the Teletrack
				// box is not checked, send the generic letter.

				// Loan Action
				$action_result = $loan_data->Deny($application_id);

				if ($action_result)
				{
					$document_type = 'DENIAL_LETTER_GENERIC';
					foreach ($this->request->loan_actions as $id)
					{
						$loan_action_name_short = strtoupper(Get_Loan_Action_Name_Short($id));
						switch ($loan_action_name_short)
						{
							case 'ON-ALL-TELETRACK FAILURE':
							case 'ON-TELETRACK FAILURE':
								$document_type = 'DENIAL_LETTER_TELETRACK';
							break;
							
							case 'DATAX_PERFORM_FAIL':
							case 'D_ON_DATAX_FAIL':
								$document_type = 'DENIAL_LETTER_DATAX';
							break;
							
							case 'D_ON_CREDIT_BUREAU_FAIL':
								$document_type = 'DENIAL_LETTER_CREDIT_BUREAU';
							break;
							
							case 'D_ON_CL_VERIFY':
								$document_type = 'DENIAL_LETTER_CL_VERIFY';
							break;
							
							case 'D_ON_VERITRAC':
								$document_type = 'DENIAL_LETTER_VERITRAC';
							break;
							
							case 'D_MILITARY':
								$document_type = 'DENIAL_LETTER_MILITARY';
							break;
						}
						

					}

					if(isset($this->request->document_list)) eCash_Document_AutoEmail::Send($this->server, $application_id, $document_type);
				}

				$queue_manager = ECash::getFactory()->getQueueManager();
				$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));

				// This loan is getting denied so we should not have any followups. [rlopez][mantis:8239]
				$fup = new Follow_Up();
				$fup->Expire_Follow_Ups($application_id);

				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "Withdraw":
				// Loan Action
				$action_result = $loan_data->Withdraw($this->request->application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');

				if ($action_result)
				{
					$queue_manager = ECash::getFactory()->getQueueManager();
					$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));

					// (EMAIL) Withdrawn Letter E-Sig=No
					if(isset($this->request->document_list)) eCash_Document_AutoEmail::Send($this->server, $application_id, 'WITHDRAWN_LETTER');
				}
				// This loan is getting withdrawn so we should not have any followups. [rlopez][mantis:8239]
				$fup = new Follow_Up();
				$fup->Expire_Follow_Ups($application_id);
				break;

			case "Reverify":
				// Comment
				$action_result = $loan_data->To_Verify_Queue($application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "Cashline Duplicate":
				$action_result = $loan_data->Cashline_Dup($application_id);
				ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				break;

		}

		if (!$action_result)
		{
			//this fixes the 911 from Feb. 3, 2005
			//thx for finding it George -- JRF

			//nothing happened, but we may have to set the data
			if($set_data)
			{
				//ECash::getTransport()->Add_Levels('overview');
				ECash::getTransport()->Set_Data($_SESSION['current_app']);
			}
			return FALSE;
		}

		$comm_ref = null;
 		if(! empty($this->request->loan_actions))
			$comm_ref  = $this->Add_Loan_Action($set_data);
		if(! empty($this->request->comment))
			$this->Add_Comment($comm_ref);

	}

	public function Update_Application()
	{
		require_once(SERVER_CODE_DIR . "edit.class.php");
		$edit = new Edit($this->server, $this->request);
		$edit->Save_Application(TRUE);
	}

	public function Send_Docs()
	{
		if(!empty($this->request->document_list))
		{
			// HACKED -- MarcC (7/13/05)
			if (!is_array($this->request->document_list))
			{
				$docs = eCash_Document::Get_Document_List($this->server,"all", "AND active_status = 'active'");
				foreach ($docs as $doc)
				{
					if ($doc->description == $this->request->document_list)
					{
						$this->request->document_list =
						array($doc->document_list_id => $doc->description);
						break;
					}
				}
			}

         ECash::getTransport()->Set_Data($_SESSION['current_app']);
		ECash::getTransport()->Set_Levels('close_pop_up');
		}
	}

	public function Check_For_Other_Active_Loans($application_id, $ssn = null)
	{
		// I'm sure there's a more elegant approach to this, but this should
		// work and be fairly easy to maintain

		$db = ECash_Config::getMasterDbConnection();
		
		// If there's no SSN supplied, lets find it.
		if(($ssn === null) || ($ssn == ''))
		{
			$sql = "
				SELECT ssn FROM application WHERE application_id = {$application_id}";
			$st = $db->query($sql);
			$ssn = $st->fetch(PDO::FETCH_OBJ)->ssn;
		}

		// What about Funding Failed?
		$sql = "
		SELECT  a.application_id,
        		a.application_status_id,
        		(CASE
        			WHEN asf.level1 = 'external_collections' AND asf.level0 = 'recovered'
	                THEN 'Recovered'
        			WHEN asf.level2 = 'collections' OR asf.level1 = 'external_collections'
	                THEN 'Collections'
	                WHEN asf.level0 = 'funding_failed'
	                THEN 'Funding Failed'
	                WHEN asf.level0 = 'paid'
	                THEN 'Paid'
        	        WHEN asf.level2 = 'customer'
    	            THEN 'Customer'
	                WHEN asf.level1 = 'applicant'
    	            THEN 'Applicant'
    	             WHEN asf.level2 = 'applicant'
    	            THEN 'Applicant'
        	        WHEN asf.level1 = 'prospect'
            	    THEN 'Prospect'
	                WHEN asf.level1 = 'cashline'
    	            THEN 'Cashline'
        	        ELSE asf.level0
				END) as Status
		FROM    application a,
        		application_status_flat asf
		WHERE   asf.application_status_id = a.application_status_id
		AND     company_id = ".ECash::getCompany()->company_id."
		AND     a.ssn = '{$ssn}'
		AND     a.application_id != {$application_id}";

		$st = $db->query($sql);

		// If there's no other loans, it's obviously false
		if(! $st->rowCount() > 1)
		{
			return false;
		}

		// If any other apps are found, if they're not prospect or applicant,
		// then throw an error and we won't allow funding.
		$status_filter = array('Prospect','Applicant','Paid','Funding Failed','Recovered');
		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			// Only allow the Prospect and Applicant trees, along with the Paid Customer
			// and Funding Failed leaf statuses.
			if(!in_array($row->Status,$status_filter))
			{
				return true;
			}
		}

		return false;
	}

	public function Search_Dequeue()
	{
		$app = ECash::getTransport()->Get_Data();

		$queue_name = $this->mode . (($app->is_react === 'yes' || $app->is_react === TRUE) ? '_react' : '');
		$queue_manager = ECash::getFactory()->getQueueManager();
		if($queue_manager->hasQueue($queue_name))
		{
			$queue = $queue_manager->getQueue($queue_name);
			$queue_item = new ECash_Queues_BasicQueueItem($app->application_id);
	
			// are we in this mode's queue?
			if ($queue->contains($queue_item))
			{
				// run the pull thing -- this hits the pulled stat, if applicable
				//pull_from_automated_queue($this->server, $queue_name, $app->application_id);
				/**
				 * @todo: special stats treatment here?
				 */
				 //Logic from old queues and new queues has changed actually removing from the queue would be bad
				//$queue->remove($queue_item);
				$queue->dequeue($queue_item->RelatedId);
				
				// update our session lock with the new modified date
				Update_Application_Lock(ECash_Config::getMasterDbConnection(), $this->server->log, $app->application_id);
			}
		}
		return;
	}

	public function Check_For_Loan_Conditions()
	{
		require_once(SQL_LIB_DIR . "scheduling.func.php");

		$data = ECash::getTransport()->Get_Data();

		if(isset($data->application_id))
		{
			$application_id = $data->application_id;
		}
		else
		{
			throw new Exception ("No application_id in transport object!");
		}

		$balance = Fetch_Balance_Information($application_id);

		$new_data = new stdClass();
		if($data->do_not_loan)
		{
			$new_data->fund_warning = "Application is marked DO NOT LOAN!";
		}
		else if($this->Check_For_Other_Active_Loans($application_id))
		{
			$new_data->fund_warning = "Found other active loans for this company! &nbsp;&nbsp; Please review the Application History.";
		}
		//Only adding the fund warning if it has a pending principal amount AND it has a fund amount/fund event as well
		else if (is_object($balance) && $balance->principal_pending != 0 && $data->schedule_status->initial_principal)
		{
			$new_data->fund_warning = "Application has a principal balance!";
		}

		ECash::getTransport()->Set_Data($new_data);
	}
	
	/**
	 * Executes funding verification for the supplied application_id
	 *
	 * @param integer $application_id
	 */
	public function verifyFunding($application_id)
	{
		$app = ECash::getFactory()->getModel('Application');
		$app->loadBy(array('application_id' => $application_id));
		//Get verification_type business rule
		$business_rules = new ECash_BusinessRulesCache(ECash_Config::getMasterDbConnection());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rule_set = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		
		/**
		 * If the funding_verification rule is set, use it and return
		 * the result.  If it isn't, just reutrn a successful value.
		 */
		if(isset($rule_set['funding_verification']))
		{
			$type = $rule_set['funding_verification'];
			
			//Instantiate verification class
			$class_file = strtolower($type) . ".class.php";
	 
			require_once(LIB_DIR . $class_file);
			$verifier = new $type($app);
			//Call verification with necessary parameters
			$results = $verifier->runVerification();
			
			//Return the verification outcome
			return $results;
		}
		else
		{
			/**
			 * These are the generic values to send back if there is no verifier.
			 * 
			 * The 'Confirmed and Approved' comment is the generic response which 
			 * Impact is used to seeing so it's being set here as it was the old
			 * default.  [GForge #15144]
			 */
			return array('status'  => 'CONFIRMED',
						 'comment' => 'Confirmed and Approved');
		}
	}
}

?>
