<?php
/* This cron does the following:
 * 1) Checks for apps which are in past due status
 * 2) Check grace period rule, let that be n, if n days have passed since it went into past due status, or they've reached their next paydate
 *    sent them to default.
 */

	class ECash_NightlyEvent_CSODefaultedLoans extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = null;
//		protected $business_rule_name = 'cso_defaulted_loans'; //Replace with proper biz rule
		protected $timer_name = 'CSO_Defaulted_Loans';
		protected $process_log_name = 'CSO_Defaulted_Loans';
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
			
			$this->CSO_Defaulted_Loans($this->server, $this->start_date);
		}

		/* Returns an array of CSO rule set IDs */
		private function getCSORuleSetIDs()
		{
			$query = "
			-- eCash 3.5 : File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				SELECT 
					rs.rule_set_id
				FROM
					rule_set_component_parm_value rscpv
				JOIN
					rule_component_parm rcp ON rscpv.rule_component_parm_id = rcp.rule_component_parm_id
				JOIN
					rule_set rs ON rscpv.rule_set_id = rs.rule_set_id
				JOIN 
					rule_component rc ON rcp.rule_component_id = rc.rule_component_id
				WHERE 
					rcp.parm_name = 'loan_type_model'
				AND
					rscpv.parm_value = 'CSO'
			";
		
			$result = $this->db->Query($query);
			$results = array();

			while($row = $result->fetch(PDO::FETCH_ASSOC))
			{
				$rule_sets[] = $row['rule_set_id'];	
			}

			return $rule_sets;
		}
		/**
		 * Check for active apps with failed transactions in the past X days and no arrangements.
		 *
		 * @param Server $server
		 * @param string $run_date
		 */
		private function CSO_Defaulted_Loans(Server $server, $run_date)
		{
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_Business_Rules(ECash_Config::getMasterDbConnection());
			
			// The only loan type this applies to
			$loan_type = 'cso_loan';
	
			$rule_set_ids = '(' . implode(',', $this->getCSORuleSetIDs()) . ')';
		
			// Get all apps in past due status
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 
						DISTINCT app.application_id AS application_id,
						income_direct_deposit       AS income_direct_deposit,
						lt.name_short               AS loan_type,
						(
							SELECT
								sh.date_created
							FROM
								status_history sh
							WHERE
								sh.application_id = app.application_id
							AND
								sh.application_status_id = (select application_status_id FROM application_status WHERE name_short='past_due')
							ORDER BY
								sh.status_history_id DESC
							LIMIT 1
						)                           AS past_due_date
					FROM 
						application app
					JOIN
						loan_type lt ON (lt.loan_type_id = app.loan_type_id)
					WHERE 
						app.application_status_id = (select application_status_id from application_status where name_short='past_due')
					AND
						app.rule_set_id IN {$rule_set_ids}
			";

			$result = $this->db->Query($query);
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				// I need to do this for every application as the model may change, and we want this to work
				// while being ignorant of different rule sets

				$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($server->company, $row->loan_type);
				$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
				$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
				$period = (isset($rules['lender_default'])) ? $rules['lender_default'] : 3; //default to 3 days

				$default_date = $pdc->Get_Business_Days_Forward(date('m/d/Y', strtotime($row->past_due_date)), $period);
				$renewal_class = ECash::getFactory()->getRenewalClassByApplicationID($row->application_id);
				if (time() >= strtotime($default_date))
				{
					$renewal_class->defaultLoan($row->application_id);
					continue;
				}
				
	        	$tr_data = Get_Transactional_Data($row->application_id, $this->db);
		        $tr_data->info->direct_deposit = ($tr_data->info->direct_deposit == 1) ? true : false;

				// We also need to check if we've passed their next paydate
				$paydates = $pdc->Calculate_Pay_Dates($tr_data->info->paydate_model, 
												 	  $tr_data->info->model, 
													  (($row->income_direct_deposit == "yes") ? true : false), 
													  1,
													  date('m/d/Y', strtotime($row->past_due_date)),
													  TRUE);

				// If they've passed the next paydate, default the loan
				if (strtotime(time()) >= strtotime($paydates[0]))
				{
					$renewal_class->defaultLoan($row->application_id);
					continue;
				}
			}
		}
	}

?>
