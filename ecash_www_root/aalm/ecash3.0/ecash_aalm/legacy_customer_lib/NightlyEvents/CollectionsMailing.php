<?php
/**
 * CollectionsMailing.php
 *
 * 6.1.6.2 - cron to check whether <good call disposition> exists since insertion into collections new queue, and that time in
 * collections new queue is = 5 calendar days, if so, mail customer letter #2 (Notes: cannot do >= 5 days in collections new queue,
 * otherwise we'd mail multiple times)
 *
 * This cron fulfills spec point 6.1.6.2 of the AALM Collections Specification
 * part of gForge ticket #13633 Upload dated '2008-12-22 10:50:54'
 */

class ECash_NightlyEvent_CollectionsMailing extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'collections_mailing';
	protected $timer_name = 'Collections_Mailing';
	protected $process_log_name = 'collections_mailing';
	protected $use_transaction = FALSE;

	public function __construct()
	{
		$this->classname = __CLASS__;

		parent::__construct();
	}

	/**
	 * A wrapper for the function Resolve_Past_Due_To_Active()
	 * originally located in ecash3.0/cronjobs/nightly.php
	 * and relocated into this class.
	 */
	public function run()
	{
		// Sets up the Applog, any other pre-requisites in the parent
		parent::run();

		$this->Collections_Mailing($this->start_date, $this->end_date);
	}
	
	private function Collections_Mailing($start_date, $end_date)
	{
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($this->db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		// Get the collections new queue
		$qm    = ECash::getFactory()->getQueueManager();
		$tq    = $qm->getQueue('collections_new');
		$tn    = $tq->getQueueEntryTableName();
		$queue = $tq->getModel();

		$days = (isset($rules['collections']['collections_new_mail_delay']))
			? $rules['collections']['collections_new_mail_delay']
			: 5;
		
		$this->createTempTableFromAppService($this->getCollectionsStatuses());

		$query = "
			SELECT
				app.application_id
			FROM
				" . $this->getTempTableName() . " app
				INNER JOIN {$tn} nqe
					ON nqe.related_id = app.application_id
			WHERE
				queue_id = " . $queue->queue_id . "
				AND DATEDIFF(NOW(), nqe.date_queued) = {$days}
				AND (NOW() < nqe.date_expire OR nqe.date_expire IS NULL)
		";
		
		$results = $this->db->query($query);


        while ($row = $results->fetch(PDO::FETCH_OBJ))
        {
			// We need to check whether each of these applications has a "contact" call disposition in the 
			// last 5 days
			$need_mail = TRUE;

			$lah = ECash::getFactory()->getModel('LoanActionHistoryList');
			
			if ($lah->loadBy(array('application_id' => $row->application_id)))
			{
				foreach ($lah as $lah_item)
				{
					$la = ECash::getFactory()->getModel('LoanActions');

					if ($la->loadBy(array('loan_action_id' => $lah_item->loan_action_id)))
					{
						// Check their calls
						$contact_dispositions = array(
							'talked_to_customer',
							'promise_to_pay_cc',
							'promise_to_pay_moneygram',
							'customer_bankruptcy',
							'customer_cccs',
							'manager_callback',
							'setup_callback',
							'talked_no_promise',
							'manual_notes'
						);

						// They've been contacted
						if (in_array($la->name_short, $contact_dispositions))
						{
							$need_mail = FALSE;
							break;
						}
					}
				}
			}

			// If they've had a call disposition, do nothing
			if ($need_mail == FALSE)
				continue;

			try
			{
				// Send Return Letter 2 - 'Second Attempt Letter' 6.1.6.2
				//ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT');
				ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'PAYMENT_FAILED');
                $this->log->Write("Sent 2nd Letter to {$row->application_id} 6.1.6.2 [#13633]");
			}
			catch (Exception $e)
			{
				$this->log->Write("Failed sending 2nd Letter to {$row->application_id}");
			}
		}

		return TRUE;
	}
	
	/**
	 * Creates a temporary table containing the application ID's returned by the application service for the specified
	 * statuses.
	 * 
	 * @param array $statuses an array of statuses
	 */
	private function createTempTableFromAppService(array $statuses)
	{
		$db = ECash::getAppSvcDB();
		$app_ids = array();
		
		foreach ($statuses as $status)
		{
			$as_query = "CALL sp_fetch_application_ids_by_application_status ('".$status."')";
			$result = $db->query($as_query);
			$app_ids = array_merge($app_ids, $result->fetchAll());
		}
		
		ECash_DB_Util::generateTempTableFromArray($this->db, $this->getTempTableName(), $app_ids,
				$this->getTempTableSpec(), $this->getApplicationIdColumn());
	}

	/**
	 * Returns the name of the temp table.
	 * 
	 * @return string
	 */
	private function getTempTableName()
	{
		return 'temp_collectionsMailing_application';
	}
	
	/**
	 * Returns an array that contains the spec for the temp table with the key as the column name and the value as the
	 * MySQL specification.
	 * 
	 * @return array
	 */
	private function getTempTableSpec()
	{
		return array($this->getApplicationIdColumn() => 'INT UNSIGNED');
	}
	
	/**
	 * Returns the name of the application ID column.
	 * 
	 * @return string
	 */
	private function getApplicationIdColumn()
	{
		return 'application_id';
	}
	
	/**
	 * Returns an array of collection statuses.
	 * 
	 * @return array an array of collection statuses
	 */
	private function getCollectionsStatuses()
	{
		return array(
			'new::collections::customer::*root'
		);
	}
}


?>
