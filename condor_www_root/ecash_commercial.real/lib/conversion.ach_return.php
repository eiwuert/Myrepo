<?php
require_once(SQL_LIB_DIR . "app_stat_id_from_chain.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");

/**
 * This class handles returns processing for cashline returns.
 */
class CashlineReturnsProcessor
{
	/**
	 * Contains a log to write all messages to.
	 *
	 * @var Applog
	 */
	private $log;
	
	/**
	 * The object to use for queries
	 *
	 * @var DB_Database_1
	 */
	private $db;
	

	/**
	 * Creates a new returns processor
	 *
	 * @param Applog $l
	 * @param DB_Database_1 $s
	 */
	public function __construct($l, $s) 
	{
		$this->log = $l;
		$this->db = $s;
	}
	
	/**
	 * Fetches an application id based on a cashline id and a company id. 
	 * Returns false if an id cannot be found.
	 *
	 * @param int $cashline_id
	 * @param int $company_id
	 * @return int
	 * @todo Move this function out of here. It belongs with the application code.
	 */
	protected function fetchApplicationIDFromCashlineID($cashline_id, $company_id)
	{
		$query = "
			SELECT
				a.application_id
			FROM
				application a
			WHERE
				a.company_id = {$company_id} AND
				a.archive_cashline_id = {$cashline_id}
		";
		
		$result = $this->db->query($query);
		
		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			return $row['application_id'];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Returns an array of transaction ids of pending transactions that match 
	 * the return amount for a given application.
	 *
	 * @param int $application_id
	 * @param float $return_amount
	 * @return array
	 * @todo When the transaction code gets a little more objectified we need 
	 *       to get rid of the query in here and use retrieval functions from 
	 *       the transaction code.
	 */
	protected function getMatchingPendingTransactions($application_id, $return_amount)
	{
		$query = "
			SELECT 
				tr.transaction_register_id,
				tr.amount
			FROM
				application app
				JOIN transaction_register tr USING (application_id)
			WHERE
				app.application_id = {$application_id} AND
				tr.transaction_status = 'pending'
		";
			
		$result = $this->db->query($query);
		$found_transactions = false;
		
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($return_amount == $row['amount']) {
				return $row['transaction_register_id'];
			}
			$found_transactions = true;
		}
		
		if ($found_transactions)
		{
			$this->log->Write("[application:{$application_id}] Cashline Return script could not find matching pending transaction.");
		}
		else 
		{
			$this->log->Write("[application:{$application_id}] No pending transactions have been found for this application.");
		}
		
		return FALSE;
	}
	
	/**
	 * Marks the given pending transactions for an application as failed using 
	 * the return code and ach report id.
	 *
	 * @param int $application_id
	 * @param array $transaction_register_id
	 * @param string $return_code
	 * @param int $report_id
	 * @todo When the transaction code gets a little more objectified we need 
	 *       to move either the whole function out or gut it and use more 
	 *       standard functions.
	 */
	protected function markPendingTransactionReturned($application_id, $transaction_register_id, $return_code, $report_id)
	{
		$this->log->Write("[Cashline Return] Setting transaction {$transaction_register_id}: Pending -> Failed (App {$app_id})");
		$upd_query = "
			UPDATE transaction_register
			SET transaction_status = 'failed'
			WHERE
				transaction_register_id = ({$transaction_register_id}) AND
				application_id = {$application_id}";
		$this->db->exec($upd_query);

		// Now update the ACH row.
		$upd_query = "
			UPDATE ach
			SET 		
				ach_status = 'returned',
				ach_report_id = {$report_id},
				ach_return_code_id = (
					SELECT ach_return_code_id 
					FROM ach_return_code 
					WHERE name_short = '{$return_code}'
				)
			WHERE
				ach_id = (
					SELECT ach_id 
					from transaction_register 
					where transaction_register_id = ($transaction_register_id)
				)
			";
		$this->db->exec($upd_query);
	}
	
	
	/**
	 * Attempts to process a return based on only cashline id. 
	 * 
	 * First (only in rc) it will attempt to match the ach to any pending 
	 * transactions. If that fails (or this is being run on a LIVE 
	 * environment) then a set of transactions will be created to represent 
	 * the cashline return and update the balance accordingly.
	 *
	 * @param int $cashline_id
	 * @param int $company_id
	 * @param float $return_amount
	 * @param string $return_code
	 * @param int $report_id
	 * @param string $date
	 */
	public function reportACHReturn($cashline_id, $company_id, $return_amount, $return_code, $report_id, $date)
	{
		$application_id = $this->fetchApplicationIDFromCashlineID($cashline_id, $company_id);
		
		if ($application_id === false)
		{
			throw new Exception("[cashline:{$cashline_id}] Could not find a matching cashline id.");
		}
		
		if (EXECUTION_MODE == 'RC')	{
			
			$transaction_register_id = $this->getMatchingPendingTransactions(
				$application_id, $return_amount
			);

			if ($transaction_register_id !== false)
			{
				$this->markPendingTransactionReturned(
					$application_id, $transaction_register_id, $return_code, 
					$report_id
				);
				return $application_id;
			}
		}
		
		postCashlineReturn($application_id, -$return_amount, $this->isReturnCodeFatal($return_code));
		$this->queueCashlineReturn($application_id);
		return $application_id;
	}
	
	private function checkForFailedTransactions($application_id, $date)
	{
		$query = "
			SELECT 
				COUNT(*) cnt
			FROM 
				transaction_history 
			WHERE 
				application_id = {$application_id} AND 
				status_after = 'failed' AND 
				DATE(date_created) = '{$date}'
		";
		
		$result = $this->db->query($query);
		
		$row = $result->fetch(PDO::FETCH_ASSOC);
		
		return ($row['cnt'] > 0);
	}

	protected function queueCashlineReturn($application_id)
	{
		queue_push("Cashline Return", $application_id, time(), time());
	}
	
	protected function isReturnCodeFatal($return_code)
	{
		$return_codes = Fetch_ACH_Return_Code_Map();
		
		foreach ($return_codes as $code)
		{
			if ($code['return_code'] == $return_code)
			{
				return $code['is_fatal'] == 'yes';
			}
		}
	}
}

?>
