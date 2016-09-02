<?php

	//	$manager->Define_Task('Status_Bankruptcy_Move_To_Collections', 'move_bankruptcy_to_collections', $sbmtc_timer, 'bankruptcy_move', array($server));

	class ECash_NightlyEvent_BankruptcyMoveToCollections extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_bankruptcy_to_collections';
		protected $timer_name = 'Bankruptcy_Move_To_Collections';
		protected $process_log_name = 'bankruptcy_move';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Status_Bankruptcy_Move_To_Collections()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Status_Bankruptcy_Move_To_Collections($this->server);
		}

		private function Status_Bankruptcy_Move_To_Collections($server)
		{
			$log = get_log("scheduling");

	//		require_once(LIB_DIR.'AgentAffiliation.php');
			require_once(SQL_LIB_DIR ."scheduling.func.php");
			require_once(SQL_LIB_DIR ."util.func.php");
			require_once(CUSTOMER_LIB . "bankruptcy_to_collections_dfa.php");

			$biz_rules = new ECash_BusinessRulesCache($this->db);

			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

			$interval = ($rules['bankruptcy_to_collections_interval']) ? $rules['bankruptcy_to_collections_interval'] : 30;

			try
			{
				$query = '
				-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						application_id, date_modified
					FROM
						application a,
						application_status_flat asf
					WHERE
						a.application_status_id = asf.application_status_id
					AND	(
								level1 = 'bankruptcy'
							AND level2 = 'collections'
							AND level3 = 'customer'
							AND level4 = '*root'
							AND level0 <> 'verified'
						)
					AND     a.company_id = '{$this->company_id}'
					AND     a.date_application_status_set < date_sub(current_timestamp, interval {$interval} day)
					FOR UPDATE ";

				$result = $this->db->query($query);

				while($row = $result->Fetch(PDO::FETCH_OBJ))
				{
					// Prep data for the DFA
					$data     = Get_Transactional_Data($row->application_id);
					$parameters = new stdClass();
					$parameters->application_id = $row->application_id;

					$parameters->log      = $log;
					$parameters->info     = $data->info;
					$parameters->rules    = Prepare_Rules($data->rules, $data->info);
					$parameters->schedule = Fetch_Schedule($row->application_id);
					$parameters->status   = Analyze_Schedule($parameters->schedule);
					$parameters->verified = Analyze_Schedule($parameters->schedule, true);

					// Set up the DFA and run it.
					if (!isset($dfas['btc'])) {
						$dfa = new BToCDFA($server);
						$dfa->SetLog($log);
						$dfas['btc'] = $dfa;
					} else {
						$dfa = $dfas['btc'];
					}
					$dfa->run($parameters);

					// Do we still need this?
					$application = ECash::getApplicationById($row->application_id);
					$affiliations = $application->getAffiliations();
					$affiliations->expireAll();
					
				}
			}
			catch(Exception $e)
			{
				$log->Write("Movement of apps from bankruptcy to :contact::queued status failed. " .
				"Transaction will be rolled back.", LOG_ERR);
				throw $e;
			}

			return true;
		}


	}

?>