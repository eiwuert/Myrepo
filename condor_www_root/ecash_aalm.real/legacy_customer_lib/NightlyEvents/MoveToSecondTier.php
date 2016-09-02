<?php

	class ECash_NightlyEvent_MoveToSecondTier extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'second_tier_schedule';
		protected $timer_name = 'Move_Unpaid_To_Second_Tier';
		protected $process_log_name = 'move_unpaid_to_second_tier';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Move_Hotfiles_To_Pending_Expired()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->moveUnpaidToSecondTier($this->server, $this->start_date);
		}
/**
 * moveUnpaidToSecondTier Moves applications that have not received a completed payment for a certain 
 * amount of days (defined as a business rule), and have no scheduled transactions into 2nd tier collections status.
 * 
 *
 * @param Server $server
 * @param String $run_date
 */
		function moveUnpaidToSecondTier(Server $server, $run_date)
		{
			
			$db = ECash_Config::getMasterDbConnection();
			$company_id = $server->company_id;
			$biz_rules = new ECash_Business_Rules($db);
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($server->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
			
			
			//Get the amount of days to wait before sending something into a 2nd tier collections status.
			$days = ($rules['second_tier']) ? $rules['second_tier'] : 10;
			
			$log = $server->log;
		
			//Get applications that have failed (not had a completion) for X amount of days,
			//Have no scheduled events,
			//and are currently in a collections status.
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT 
					application_id,
		            application_status_id,
		            IFNULL(lc.first_failure_after_complete,lc.first_failure) as recent_failure,
		            last_complete
				FROM 
					(SELECT ap.application_id,
				       		ap.company_id,
				       		ap.application_status_id,
		                    MAX(IF(tr.transaction_status in ('complete', 'pending' ),es.date_effective,null)) as last_complete,
		                    MAX(IF(tr.transaction_status = 'failed',es.date_effective,null)) as last_failure,
		                    MIN(IF(tr.transaction_status = 'failed',es.date_effective,null)) AS first_failure,
		                    MIN(tr2.date_effective) as first_failure_after_complete,
		                    COUNT(DISTINCT es2.event_schedule_id) as num_scheduled
			       	FROM application ap
			       	JOIN application_status_flat asf ON asf.application_status_id = ap.application_status_id
		           	JOIN event_schedule AS es ON es.application_id = ap.application_id
				   	JOIN event_amount AS ea ON ea.event_schedule_id = es.event_schedule_id
				   	JOIN transaction_register tr ON tr.event_schedule_id = es.event_schedule_id
		           	LEFT JOIN transaction_register tr2 ON (tr.application_id = tr2.application_id AND tr2.transaction_status = 'failed' 
		                                                  AND tr.transaction_status = 'complete'  AND tr2.date_effective > tr.date_effective)
		           	LEFT JOIN event_schedule es2 ON (ap.application_id = es2.application_id AND es2.amount_principal + es2.amount_non_principal < 0
		                                            AND es2.event_status = 'scheduled')
		           
		           WHERE ea.amount < 0
		           AND ap.company_id = {$company_id}
				   AND (asf.level1 = 'collections'
				   		OR
				   		asf.level2 = 'collections')
		           
				   GROUP BY ap.application_id
		            HAVING num_scheduled < 1)
		
				       AS lc
		
				  HAVING datediff(NOW(),recent_failure)>{$days} AND (recent_failure > last_complete OR last_complete is null)

				";

			$result = $db->Query($sql);
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$log->Write("[App: {$row->application_id}] Moving to Second Tier Collections");
				Update_Status(null, $row->application_id, array('pending','external_collections','*root'),NULL,NULL,FALSE); // the false is for no-queues
				
			}
		}


	}

?>