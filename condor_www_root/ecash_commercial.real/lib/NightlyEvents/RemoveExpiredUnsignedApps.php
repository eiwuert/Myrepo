<?php
/* RemoveExpiredUnsignedApps.php
 *
 * Added to cfe codebase in case anyone else needs this functionality in the future.
 * Made to fulfill ticket GForge #21589 - Expire Applications in 30 Days from Unsigned Application queue
 * 
 * This currently just checks for expired apps in the unsigned apps queue, and withdraws them
 */
require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');


class ECash_NightlyEvent_RemoveExpiredUnsignedApps extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'delinquent_full_pull';
	protected $timer_name = 'Remove_Expired_Unsigned_Apps';
	protected $process_log_name = 'remove_expired_unsigned_apps';
	protected $use_transaction = FALSE;

	public function __construct()
	{
		$this->classname = __CLASS__;

		parent::__construct();
	}

	public function run()
	{
		// Sets up the Applog, any other pre-requisites in the parent
		parent::run();

		$this->Remove_Expired_Unsigned_Apps($this->start_date, $this->end_date);
	}

	private function Remove_Expired_Unsigned_Apps($start_date, $end_date)
	{
		$db = ECash_Config::getMasterDbConnection();
        $holidays  = Fetch_Holiday_List();
        $pdc       = new Pay_Date_Calc_3($holidays);
        $biz_rules = new ECash_BusinessRulesCache($db);

        $loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
        $rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
        $rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		$days = ((!empty($rules['autowithdraw_rules']['autowithdraw_days']))) ? $rules['autowithdraw_rules']['autowithdraw_days'] : 30;

		// Catches all unsigned apps except 'Agree'
		$query = "
			SELECT
				application_id
			FROM
				application app
			JOIN
				application_status_flat asf ON asf.application_status_id = app.application_status_id
			WHERE
				app.company_id = {$this->company_id}
			AND
					asf.level1 = 'prospect'
			AND
			(
					asf.level0 != 'agree'
				AND
					DATEDIFF(CURDATE(),
						(
							SELECT
								date_created
							FROM
								status_history ish
							JOIN
								application_status_flat iasf
							WHERE
								ish.application_id = app.application_id
							AND
								iasf.level0 = 'pending'
							AND
								iasf.level1 = 'prospect'
							ORDER BY
								date_created DESC
							LIMIT 1
						)
					) > {$days}
			)
			OR
			(
					asf.level0 = 'agree'
				AND
					DATEDIFF(CURDATE(), app.date_application_status_set) > {$days}
			)
		";

		$result = $db->Query($query);

		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			// LOG IT.
			$this->log->Write("Application {$row->application_id}: Withdrawing expired unsigned app."); 

			// Withdraw it.
			Update_Status(NULL, $row->application_id, 'withdrawn::applicant::*root', null, null, true);

			// REMOVE IT FROM QUEUES.
			$qm = ECash::getFactory()->getQueueManager();
			$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($row->application_id));
		}

		return TRUE;
	}
}

?>
