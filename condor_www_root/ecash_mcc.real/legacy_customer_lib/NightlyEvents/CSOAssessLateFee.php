<?php

	class ECash_NightlyEvent_CSOAssessLateFee extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'assess_late_fee'; 
		protected $timer_name = 'CSO_Assess_Late_Fee';
		protected $process_log_name = 'cso_assess_late_fee';
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
			
			$this->assessLateFees($this->server, $this->start_date);
		}

		
		private function assessLateFees(Server $server, $run_date)
		{
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			
			//get the rule_set_ids to include in the query
			//get the CSO loan type rule sets to use
			$business_rules = new ECash_BusinessRulesCache(ECash_Config::getMasterDbConnection());
		 	$rule_set_ids = $business_rules->Get_Rule_Set_Ids_By_Parm_Value('loan_type_model','CSO');
		 	$app_rule_set_ids = implode(',',$rule_set_ids);
		 	
		 	//get the default status ID
		 	$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		 	$default_status_id = $status_list->toId('default::collections::customer::*root');
		 	
		 	//get the late fee transaction type ID
		 	$tx_type_list = ECash::getFactory()->getReferenceList('TransactionType');
			$late_fee_type_id = $tx_type_list->toId('cso_assess_fee_late');

		 	
		 	//This is to get applications that we are potentially assessing a late fee for.
		 	
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT 
			    app.application_id,
			    app.rule_set_id,
			    tr.transaction_register_id AS late_fee,
			    sh.date_created 
			FROM 
			    application app
			JOIN
			    status_history sh ON (sh.application_status_id = {$default_status_id} AND sh.application_id = app.application_id)
			JOIN
			    application_status_flat asf ON app.application_status_id = asf.application_status_id
			LEFT JOIN 
			    transaction_register tr ON (tr.application_id = app.application_id AND tr.transaction_type_id = {$late_fee_type_id})
			WHERE
			    (
			        asf.level1 = 'collections'
			     OR
			        asf.level2 = 'collections'
			    )
			AND
			    app.company_id = {$this->company_id}
			AND
				app.rule_set_id IN ({$app_rule_set_ids})
			HAVING late_fee IS NULL;
			";
			$result = $this->db->Query($query);
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$application_id = $row->application_id;
				$renewal_class = ECash::getFactory()->getRenewalClassByApplicationID($application_id);
				//get the defaulting payment
				$failure = $renewal_class->getDefaultingFailure($application_id);
				//get the amount of days to wait
				$rule_set = $business_rules->Get_Rule_Set_Tree($row->rule_set_id);
				$number = $rule_set['cso_assess_fee_late']['waiting_period'];
				$type = $rule_set['cso_assess_fee_late']['waiting_period_type'];

				//determine if its been that many days
				$assessment_date = ($type == 'calendar') ? $pdc->Get_Calendar_Days_Forward($failure['default_date'],$number) : $pdc->Get_Business_Days_Forward($failure['default_date'],$number);

				if (strtotime($assessment_date) <= strtotime($run_date)) 
				{
					
					//if so go to town!
					$fee = $renewal_class->getCSOFeeAmount('cso_assess_fee_late',$application_id,null,null,$failure['default_amount']);
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', $fee);
					$e = Schedule_Event::MakeEvent($run_date, $run_date, $amounts, 'cso_assess_fee_late','Late Fee Assessed');
					$this->log->Write("Assessing {$fee} late fee on {$application_id}");
					Post_Event($application_id, $e);	
				}
			}
		}
		
	}

?>