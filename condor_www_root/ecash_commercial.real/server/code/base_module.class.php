<?

class Base_Module
{
	protected $server;
	protected $log;
	protected $transport;
	protected $mode;
	protected $request;
	protected $queue_config;
	
	public function __construct(Server $server, $request, $mode, Module_Interface $module_interface = null)
	{
		$this->server = $server;
		$this->log = ECash::getLog();
		$this->mode = $mode;
		$this->request = ECash::getRequest();
		$this->setupQueues($module_interface);
	}

	// Attempt to bring this back to the module level
	public function Get_Next_Application()
	{
		/**
		 * @todo: Figure out why fraud_queue and high_risk_queue need special behavior
		 *
		 */
		//		if($this->mode == 'fraud_queue' || $this->mode == 'high_risk_queue')
		//			$application_id = pull_from_automated_queue($this->server, $this->request->queue, NULL, FALSE);

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
			$application_id = $item->RelatedId;

			$search->Show_Applicant($item->RelatedId);
		
			$data = ECash::getTransport()->Get_Data();
			
			if($this->request->queue == 'account_summary')
			{

				$payments = Fetch_API_Payments($application_id);
				if (count($payments)) 
				{
					$payment = array_shift($payments);

					switch ($payment->event_type) 
					{
						case 'payout':
							$_SESSION['api_payment'] = $payment;
							$data = new stdClass();
							$data->javascript_on_load = 'VerifyPayout();';
							ECash::getTransport()->Set_Data($data);
							break;
						case 'paydown':
							$_SESSION['api_payment'] = $payment;
							$data = new stdClass();
							$data->javascript_on_load = "if(confirm('Would you like to add a paydown to this application?')) OpenTransactionPopup('paydown', 'Add Paydown', 'customer_service');";
							ECash::getTransport()->Set_Data($data);
							break;
					}
				}
				
			}
			
			if(!empty($data->fraud_rules) || !empty($data->risk_rules)) //show idv pane if they're high risk/fraud
			{
				ECash::getTransport()->Add_Levels('overview','idv','view','general_info','view');
			}
			else if (isset($this->module_name) && ($this->module_name == 'collections'))
			{
				ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
			}
			else
			{
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
			}
			

		}
		else
		{
			if ($GLOBALS['queue_result_message'])
			{
				$duh = new stdClass;
				$duh->search_message = $GLOBALS['queue_result_message'];
				ECash::getTransport()->Set_Data($duh);
			}
			$search->Get_Last_Search($this->module_name, $this->request->mode);
		}
	}

	// Originally from Funding/Loan_Servicing
	public function Add_Loan_Action($set_data = TRUE)
	{
		if ($set_data)
		{
			$loan_data = new Loan_Data($this->server);
			ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($this->request->application_id, !$set_data));

			if($this->mode != 'verification')
			{
				ECash::getTransport()->Add_Levels('overview','application','view','general_info','view');
			}
			else
			{
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
			}

			// Grabbing the last Status_History ID
			$application = ECash::getApplicationByID($this->request->application_id);
			$agent_id = ECash::getAgent()->getAgentId();
			$status_id = $application->application_status_id;

			// Insert each of the loan actions
			if($this->request->loan_actions)
			{
				$loan_action_list = ECash::getFactory()->getReferenceList('LoanActions');
				$stats = new ECash_Stats();

				if(is_array($this->request->loan_actions))
				{

					for($i=0; $i<count($this->request->loan_actions); $i++)
					{
						$loan_item = $this->request->loan_actions[$i];

						$lah = ECash::getFactory()->getModel('LoanActionHistory');
						$lah->loan_action_id = $loan_item;
						$lah->application_id = $application->application_id;
						$lah->application_status_id = $application->application_status_id;
						$lah->agent_id = $agent_id;
						$lah->date_created = date('Y-m-d H:i:s');
						$lah->save();
						
						$loan_history_id = $lah->loan_action_history_id;

						$stats->hitStatLoanAction($application,$loan_action_list->toName($loan_item));
					}
				}
				else
				{
					$loan_item = $this->request->loan_actions;
					$lah = ECash::getFactory()->getModel('LoanActionHistory');
					$lah->loan_action_id = $loan_item;
					$lah->application_id = $application->application_id;
					$lah->application_status_id = $application->application_status_id;
					$lah->agent_id = $agent_id;
					$lah->date_created = date('Y-m-d H:i:s');
					$lah->save();
					
					$loan_history_id = $lah->loan_action_history_id;

					$stats->hitStatLoanAction($application,$loan_action_list->toName($loan_item));
				}
	
				// TO DO - make a nice wrapper for the following
				// Select Other for Loan Disposition so lets email it
				if(isset($this->request->comment))
				{
					$header = array(
					'sender_name' => 'Selling Source',
					'subject'     => '[eCash 3.5] - Other Loan Action',
					'site_name'   => 'sellingsource.com',
					'message'     => "\r\n<br>Mode: " . EXECUTION_MODE . " \r\n" .
						"<br>New Other Action: '{$this->request->comment}'\r\n" .
						"<br>Agent: {$this->server->agent_id} \r\n" .
						"<br>Application: {$this->request->application_id} \r\n" .
						"<br> App Status: {$status_id} \r\n" .
						"<br>Section(Button): {$this->request->submit_button} \r\n" .
						"<br>Company: {$this->server->company}");
					if (EXECUTION_MODE == 'LIVE')
					{
						$recipients = array(
						array(
							'email_primary_name' => 'Natalie',
							'email_primary' => 'ndempsey@fc500.com'),
						array(
							'email_primary_name' => 'Crystal',
							'email_primary' => 'crystal@fc500.com'));
					}
					else
					{
						$recipients = array(
						array(
							'email_primary_name' => 'Programmer',
							'email_primary' => 'raymond.lopez@sellingsource.com'));
					}

					require_once(LIB_DIR . '/Mail.class.php');
					foreach ($recipients as $recipient)
					{
						$tokens = array_merge($recipient, $header);
						$recipient_email = $tokens['email_primary'];
						// Disabled for now - BR
						//eCash_Mail::sendMessage('ECASH_COMMENT', $recipient_email, $tokens);
					}
				}
			}
		}

		return  $loan_history_id;
	}

	public function Add_Comment($comment_reference = null)
	{
		// Requiring the file here because the functions are only used here.
		require_once(SERVER_CODE_DIR . "comment.class.php");

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
						case 'Deny':
							$this->request->comment_type = 'deny';
							break;
						case 'Withdraw':
							$this->request->comment_type = 'withdraw';
							break;
						case 'Reverify':
							$this->request->comment_type = 'reverify';
							break;
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

			/* GF #21266
			 * Commented this line out, otherwise every single comment added via a loan action was going to
			 * be set to 'row.' 
			 */
			//$this->request->comment_type = is_null($comment_reference) ? $this->request->comment_type : "row";

			$comments = ECash::getApplicationById($this->request->application_id)->getComments();
			/* GF #21266
             * The add() method was not being passed the $comment_reference id. I suspect Bunce did it.
			 */
			$comments->add($this->request->comment, ECash::getAgent()->AgentId, $this->request->comment_type, ECash_Application_Comments::SOURCE_LOAN_AGENT, $comment_reference);
		}

		$loan_data = new Loan_Data($this->server);
		ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($this->request->application_id));

		if($this->mode == 'underwriting')
		{
			ECash::getTransport()->Add_Levels('overview','application_info','view','general_info','view');
		}
		else
		{
			ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
		}
	}

	public function setupQueues($module_interface = null)
	{
		$acl = ECash::getACL();
		$queue_manager = ECash::getFactory()->getQueueManager();
		$module = ECash::getModule()->Get_Active_Module();
		$section_names = $acl->Get_Acl_Access($module);
		$allowed_submenus = $acl->Get_Acl_Names($section_names);
		$available_queues = array();
		$queues = $queue_manager->getQueuesBySectionId($acl->Get_Section_Id(ECash::getCompany()->company_id, $module, $this->mode));
				
		foreach($queues as $queue_name => $queue)
		{
			$section_model = ECash::getFactory()->getReferenceModel('Section');
			$section_model->loadBy(array('section_id' => $queue->getModel()->section_id));
			if ($acl->Acl_Access_Ok($section_model->name, ECash::getCompany()->company_id))
			{
				$mode_section_id = $acl->Get_Section_Id(ECash::getCompany()->company_id, $module, $this->mode);
				$qp = array();
				$qp['name_short'] = $queue->Model->name_short;
				$qp['display_name'] = $queue->Model->display_name; 
				$qp['count'] = $queue->count();
				list($module, $mode) = $acl->getModuleAndMode($queue->Model->section_id);
				$qp['link_module'] = $module;
				$qp['link_mode'] = $mode;
				$available_queues[$queue_name] = $qp;
			}
		}
		ECash::getTransport()->available_queues = $available_queues;

		// Email Queue Count moved to the module code so that it can get the real numbers in the queues
		
		$eq = new Incoming_Email_Queue($this->server, $this->request);
		
		if ($module_interface !== NULL)
		{
			$module_interface->Register_Action_Handler($eq, 'handle_actions');	
		}
		
		if(is_object(ECash::getAgent()))
		{
			ECash::getTransport()->my_queue_count = ECash::getAgent()->getQueue()->count();
		}		
		
//		ECash::getTransport()->email_queue_counts = $counts;
	}
}

?>
