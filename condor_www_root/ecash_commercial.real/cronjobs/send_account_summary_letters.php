<?php

require_once(SQL_LIB_DIR . 'fetch_status_map.func.php');

/**
 * Send account summary letters based on fund date and debit date.
 *
 * The times in which we are supposed to send the Account Summary letters are as follows:
 * 1) The morning after an account is funded.
 * 2) The 2nd morning after an account is debited using an automatic debiting method (currently this is only ACH).
 *
 * @package Cronjob
 */
class SendAccountSummaryLetters
{
	/**
	 * Convience access to DB object
	 * @var object
	 */
	private $db;

	/**
	 * Holidays
	 * @var array
	 */
	private $holidays;

	/**
	 * Pay date calc object
	 * @var object
	 */
	private $pay_date_calc;

	/**
	 * Date to run cron job against.
	 * @var string Y-m-d
	 */
	private $date;
	
	/**
	 * Server Object
	 * @var server
	 */
	private $server;

	/**
	 * Application Status Id for active::servicing::customer::*root
	 * @var integer
	 */
	private $active_status;

	/**
	 * @param server $server
	 * @param object $db DB_Database_1
	 * @param object $log AppLog
	 * @param object $pay_date_calc Pay_Date_Calc_3
	 * @param string $date optional Date to run cron as; null = today
	 */
	public function __construct($server, DB_Database_1 $db, $log, $pay_date_calc, $date=null)
	{
		$this->server = $server;
		$this->db = $db;
		$this->log = $log;
		$this->pay_date_calc = $pay_date_calc;

		if ($date === null)
		{
			$date = date('Y-m-d');
		}
		$this->date = $date;
	}

	/**
	 * Initialize the paydate calculater from the Cronjob server object.
	 *
	 * @param object $server Connection to the rest of the system; provided by the cron job handler.
	 */
	public static function initializeCronJob($server)
	{
		require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
		require_once(LIB_DIR . 'common_functions.php');

		$db = ECash_Config::getMasterDbConnection();
		$log = $server->log;
		$holidays = Fetch_Holiday_List();
		$pay_date_calc = new Pay_Date_Calc_3($holidays);

		return new self($server, $db, $log, $pay_date_calc);
	}

	/**
	 * Process the cron job. Get accounts based on the date and then send them the documents.
	 */
	public function processCronJob($company_id)
	{
		$status_map = Fetch_Status_Map();
		$this->active_status = Search_Status_Map('active::servicing::customer::*root', $status_map);
		

		$fundDate = $this->pay_date_calc->Get_Calendar_Days_Backward($this->date, 1);
		$debitDate = $this->pay_date_calc->Get_Calendar_Days_Backward($this->date, 2);

		$this->logMessage('Getting funded (' . $fundDate . ') and debited (' . $debitDate . ') accounts');

		$accounts = $this->getFundedAccounts($fundDate, $company_id);
		$accounts = array_merge($accounts, $this->getDebitAccounts($debitDate, $company_id));

		$this->logMessage(count($accounts) . ' number of accounts to email');

		foreach ($accounts as $account_id)
		{
			if (empty($account_id) || $this->isLastPayment($account_id, $company_id))
			{
				continue;
			}

			$this->logMessage('Sending Account Summary Letter for account ' . $account_id);

			eCash_Document_AutoEmail::Send($this->server, $account_id, 'ACCOUNT_SUMMARY');
		}
	}

	/**
	 * Find accounts that were funded on a specific date.
	 *
	 * @param string $date Date of form Y-m-d
	 * @return array application ids
	 */
	private function getFundedAccounts($date, $company_id)
	{
		$accounts = array();

		// Following query pulls up all accounts that were funded on date
		$query = "
			SELECT DISTINCT
				tr.application_id
			FROM
				transaction_register AS tr
			JOIN transaction_type tt ON (tr.transaction_type_id = tt.transaction_type_id)
			JOIN application a ON (tr.application_id = a.application_id)
			WHERE
				tt.name_short = 'loan_disbursement'
				AND tr.company_id = {$company_id}
				AND DATE(tr.date_created) = '{$date}'
				AND a.application_status_id = {$this->active_status}";
		$results = $this->db->query($query);
		while ($row = $results->fetch(PDO::FETCH_ASSOC))
		{
			$accounts[] = $row['application_id'];
		}

		return $accounts;
	}

	/**
	 * Find accounts that were debitted two days ago.
	 *
	 * @param string $date Date of form Y-m-d
	 * @return array application ids
	 */
	private function getDebitAccounts($date, $company_id)
	{
		$accounts = array();

		// Only pull in debit accounts if it is for a business day.
		if (!$this->pay_date_calc->isBusinessDay(strtotime($date)))
		{
			$this->logMessage('Debited Accounts: Not a business day. Skipping.');
			return $accounts;
		}

		// The following query pulls up all accounts that had a debit on date
		$query = "
			SELECT DISTINCT
				tr.application_id
			FROM
				transaction_register AS tr
			JOIN transaction_type tt ON (tr.transaction_type_id = tt.transaction_type_id)
			JOIN application a ON (tr.application_id = a.application_id)
			WHERE
				tt.clearing_type = 'ach'
				AND tr.amount < 0.0
				AND tr.company_id = {$company_id}
				AND DATE(tr.date_created) = '{$date}'
				AND a.application_status_id = {$this->active_status}";
		$results = $this->db->query($query);
		while ($row = $results->fetch(PDO::FETCH_ASSOC))
		{
			$accounts[] = $row['application_id'];
		}

		return $accounts;
	}

	/**
	 * Check if this is the customer's last payment
	 *
	 * @param integer $application_id
	 * @return bool true if the customer only has one more scheduled payment
	 */
	private function isLastPayment($application_id, $company_id)
	{
		$sql = "
			SELECT
				COUNT(*) AS num_scheduled_payments
			FROM
				event_schedule AS es
			JOIN event_type AS et USING (event_type_id)
			WHERE
				application_id='$application_id'
				AND es.company_id = {$company_id}
				AND et.name_short = 'payment_service_chg'
				AND es.event_status = 'scheduled'";
		$result = $this->db->query($sql);
		$row = $result->fetch(PDO::FETCH_ASSOC);

		return ($row['num_scheduled_payments'] <= 0);
	}

	/**
	 * Convience method to output information to both the applog and output log.
	 *
	 * @todo this should be moved into the cronjob engine
	 *
	 * @param string $message
	 */
	private function logMessage($message)
	{
		$this->log->Write($message);
		echo $message . "\n";
	}
}

/**
 * MAIN processing code
 * @todo This should be automatically called by the cron job handler.
 */
function Main()
{
	global $server;
	$company_id = $server->company_id;

	require_once(LIB_DIR . '/Document/AutoEmail.class.php');
	require_once(LIB_DIR . '/Document/Document.class.php');

	$account_summary_letters = SendAccountSummaryLetters::initializeCronJob($server);
	$account_summary_letters->processCronJob($company_id);
}

?>
