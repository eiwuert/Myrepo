<?php

	//	$manager->Define_Task('Move_Arrangements_To_MyQueue', 'move_arrangements_to_myqueue', $myqueue_timer, 'move_arrangements_to_myqueue', array($server, $today));

	class ECash_NightlyEvent_QueueLostArrangements extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = null; //ADD YOSELF A BUSINESS RULE!!!!!!!!!!!!!!!!
		protected $timer_name = 'Queue_Lost_Arrangements';
		protected $process_log_name = 'queue_lost_arrangements';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Move_Arrangements_To_MyQueue()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->QueueLostArrangements($this->today);
		}

		
		/**
		 * Queues arrangements that have no pending events, are in made arrangements status, and do not exist/aren't pending in any queues
		 * 
		 */
		private function QueueLostArrangements($run_date)
		{
			//get applications that qualify
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			
			//get the rule_set_ids to include in the query
			//get the CSO loan type rule sets to use
			$business_rules = new ECash_BusinessRulesCache(ECash_Config::getMasterDbConnection());

		 	
		 	//get the default status ID
		 	$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		 	$arrangements_status_id = $status_list->toId('current::arrangements::collections::customer::*root');
			
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT 
				    count(es.event_schedule_id) as 'events',
				    count(tr.transaction_register_id) as 'transactions',
				    count(ntsqe.queue_entry_id) as 'time_sensitive_queues',
				    count(naqe.queue_entry_id) as 'my_queues',
				    app.application_id
				FROM 
				    application app
				LEFT JOIN
				    event_schedule es ON es.application_id = app.application_id AND es.event_status = 'scheduled'
				LEFT JOIN
				    transaction_register tr ON tr.application_id = app.application_id AND tr.transaction_status = 'pending'
				LEFT JOIN 
				    n_agent_queue_entry naqe ON (naqe.related_id = app.application_id AND (naqe.date_expire > NOW() OR naqe.date_expire = NULL))
				LEFT JOIN
				    n_time_sensitive_queue_entry ntsqe ON (ntsqe.related_id = app.application_id AND (ntsqe.date_expire > NOW() OR ntsqe.date_expire IS NULL))
				WHERE
				    app.application_status_id = {$arrangements_status_id}
				GROUP BY app.application_id
				HAVING events = 0 AND transactions = 0 AND my_queues = 0 AND time_sensitive_queues = 0
				";
			$result = $this->db->Query($query);

			$qm = ECash::getFactory()->getQueueManager();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$application_id = $row->application_id;
				$application = ECash::getApplicationById($application_id);
				
				$affiliations = $application->getAffiliations();
				$currentAffiliation = $affiliations->getCurrentAffiliation('collections', 'owner');
				if(!empty($currentAffiliation))
				{
					$agent = $currentAffiliation->getAgent();
				}

				//add to myqueue/collections queue depending on after partial rule
				$rule_set = $business_rules->Get_Rule_Set_Tree($application->rule_set_id);

				//Let's get our values!
				$days_forward = (isset($rule_set['partial_payment']['notification_after_partial'])) ? $rule_set['partial_payment']['notification_after_partial'] : 1;
		
				$action = (isset($rule_set['partial_payment']['action_after_partial'])) ? $rule_set['partial_payment']['action_after_partial'] : 'My Queue';
								
				$inactivity_expiration = (is_array($rule_set['agent_queue_inactive_expire']) && isset($rule_set['agent_queue_inactive_expire']['agent_queue_inactive_expire'])) ? $rule_set['agent_queue_inactive_expire']['agent_queue_inactive_expire'] : 7;

				
				$date_available = $pdc->Get_Calendar_Days_Forward($run_date, $days_forward);

				$date_inactive_expiration = $inactivity_expiration >= 1 ? strtotime($pdc->Get_Calendar_Days_Forward($date_available,$inactivity_expiration)) : null;			
				
			
				//update account to collections contact status
				Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, false);
							
				//remove from queues, just in case.  We don't want any CFE rules screwing things up for us!
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
				
				if(!empty($agent))
				{
					switch($action)
					{
						case 'My Queue':
							$agent->getQueue()->insertApplication($application, 'collections', $date_inactive_expiration, strtotime($date_available));
							//if adding to myqueue add to collections queue with a delay of X days determined by myqueue inactivity rule
							if ($date_inactive_expiration) 
							{
								$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($application_id);
								$queue_item->DateAvailable = $date_inactive_expiration;
								$qm->moveToQueue($queue_item, 'collections_general');
							}
							$this->log->Write("{$application_id} is a lost arrangement! Inserting into Agent {$agent->getAgentId()}'s {$action} ON {$date_available}");
						break;
						case 'Collections General':
						default:
							$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($application_id);
							$queue_item->DateAvailable = strtotime($date_available);
							$qm->moveToQueue($queue_item, 'collections_general');
							$this->log->Write("{$application_id} is a lost arrangement! Inserting into {$action} ON {$date_available}");
						break;
					}
				}
				else 
				{  //It's not in any queues, it has no controlling agent, its totally lost! I don't care where you want it to go. I'm gonna throw it in the collections queue!
					$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($application_id);
					$queue_item->DateAvailable = strtotime($date_available);
					$qm->moveToQueue($queue_item, 'collections_general');
					$this->log->Write("{$application_id} is a lost arrangement! No controlling agent!! Inserting into My Queue ON {$date_available}");
				}
			}
			//Shoop Da Woop!
		}

	}

?>