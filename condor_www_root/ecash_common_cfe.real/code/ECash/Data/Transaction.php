<?php

class ECash_Data_Transaction extends ECash_Data_DataRetriever
{
	const INFO_TOTAL = 'total';
	const INFO_COUNT = 'count';
	const INFO_PERIOD = 'period';
	const INFO_DATE_EFFECTIVE = 'date_effective';

	public function getFailedTransactionCount($application_id)
	{
		$query = "SELECT count(*)
						FROM transaction
						WHERE application_id = ?
						AND status = '".ECash_Models_Transaction::STATUS_FAILED."'";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return $st->fetchColumn();
	}

	public function hasPendingQuickChecks($application_id)
	{
		$query = "
				SELECT count(*)
				FROM transaction
				WHERE status = '".ECash_Models_Transaction::STATUS_PENDING."'
				AND application_id = ?
				AND transaction_type_id in (SELECT transaction_type_id
			    FROM transaction_type
			    WHERE name_short = '".ECash_Models_Reference_TransactionType::CLEARING_QUICKCHECK."')";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return (($st->fetchColumn() > 0) ? TRUE : FALSE);
	}

	public function getBalance($application_id)
	{
		$query = "
				SELECT sum(amount)
				FROM transaction
				WHERE status = '".ECash_Models_Transaction::STATUS_COMPLETE."'
				AND application_id = ?";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return $st->fetchColumn();
	}

	public function hasCompletedQuickChecks($application_id)
	{
		$query = "
				SELECT count(*)
				FROM transaction
				WHERE status = '".ECash_Models_Transaction::STATUS_COMPLETE."'
				AND application_id = ?
				AND transaction_type_id in (SELECT transaction_type_id
				FROM transaction_type
				WHERE name_short = '".ECash_Models_Reference_TransactionType::CLEARING_QUICKCHECK."')";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return $st->fetchColumn();
	}

	public function getPendingPaymentInfo($application_id)
	{
		$query = "
	       select
				sum(tr.amount) as ".self::INFO_TOTAL.",
				count(transaction_id) as ".self::INFO_COUNT.",
			    MAX(tt.pending_period) as ".self::INFO_PERIOD.",
		MAX(tr.date_effective) as ".self::INFO_DATE_EFFECTIVE."
	       from transaction as tr
		       join transaction_type  as tt on (tt.transaction_type_id = tr.transaction_type_id)
	       where
	       tr.status = '".ECash_Models_Transaction::STATUS_PENDING."'
		       and tr.application_id = ?
			   ";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return $st->fetch(PDO::FETCH_ASSOC);
	}

	public function getBalanceDetails($application_id)
	{
		$query = "
		SELECT
		    SUM(IF(eat.name_short = 'principal' AND tr.status = 'complete', ea.amount, 0)) principal_balance,
		    SUM(IF(eat.name_short = 'service_charge' AND tr.status = 'complete', ea.amount, 0)) service_charge_balance,
		SUM(IF(eat.name_short = 'fee' AND tr.status = 'complete', ea.amount, 0)) fee_balance,
		    SUM(IF(eat.name_short = 'irrecoverable' AND tr.status = 'complete', ea.amount, 0)) irrecoverable_balance,
		    SUM(IF(eat.name_short <> 'irrecoverable' AND tr.status = 'complete', ea.amount, 0)) total_balance,
		SUM(IF(eat.name_short = 'principal' AND tr.status IN ('complete', 'pending'), ea.amount, 0)) principal_pending,
		    SUM(IF(eat.name_short = 'service_charge' AND tr.status IN ('complete', 'pending'), ea.amount, 0)) service_charge_pending,
		    SUM(IF(eat.name_short = 'fee' AND tr.status IN ('complete', 'pending'), ea.amount, 0)) fee_pending,
		SUM(IF(eat.name_short <> 'irrecoverable' AND tr.status IN ('complete', 'pending'), ea.amount, 0)) total_pending,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'principal', ea.amount, 0)) principal_not_reatt,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'service_charge', ea.amount, 0)) service_charge_not_reatt,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'fee', ea.amount, 0)) fee_not_reatt,
			SUM(IF(ea.num_reattempt = 0, ea.amount, 0)) total_not_reatt,
			MAX(IF(tr.status = 'failed' AND eat.name_short = 'principal', ea.num_reattempt, 0)) principal_num_reattempts,
			MAX(IF(tr.status = 'failed' AND eat.name_short = 'service_charge', ea.num_reattempt, 0)) service_charge_num_reattempts,
			MAX(IF(tr.status = 'failed' AND eat.name_short = 'fee', ea.num_reattempt, 0)) fee_num_reattempts
		FROM event_amount ea
		JOIN event_amount_type eat USING (event_amount_type_id)
		JOIN transaction tr USING(transaction_id)
		WHERE
			ea.application_id = ?
		GROUP BY ea.application_id ";

		$result = $this->db->queryPrepared($query, array($application_id));
	return $result->fetch(PDO::FETCH_OBJ);
	}

	public function hasPaydown($application_id)
	{
		$query = "
			SELECT COUNT(*) AS total,
				(
					SELECT COUNT(*)
					FROM api_payment
					WHERE application_id = :application_id
					AND active_status = 'active'
				) AS api_total
			FROM event_schedule es
			JOIN event_type et USING (event_type_id)
			WHERE es.application_id = :application_id
				AND et.name_short IN ('paydown', 'payout')
				AND es.event_status = 'scheduled'";

		$row = DB_Util_1::querySingleRow(
			$this->db,
			$query,
			array(
				'application_id' => $application_id
			),
			PDO::FETCH_OBJ
		);

		return ($row !== FALSE && ($row->total > 0 || $row->api_total > 0));
	}

	public function getPaymentsDue($application_id, $count)
	{
		$query = "
			SELECT
				es.date_effective                                  AS date_due,
				SUM(es.amount_principal + es.amount_non_principal) AS amount_due,
				SUM(es.amount_non_principal)                       AS service_charge_amount_due,
				SUM(es.amount_principal)                           AS principal_amount_due
			FROM event_schedule es, event_type et
			WHERE
				es.application_id = ?
				AND et.event_type_id = es.event_type_id
				AND es.date_effective >= CURDATE()
				AND (et.name_short = 'payment_service_chg'
					OR  et.name_short = 'repayment_principal'
					OR  et.name_short = 'paydown'
					OR  et.name_short = 'payout')
			GROUP BY date_effective
			ORDER BY date_effective ASC
			LIMIT $count";

		$result = DB_Util_1::queryPrepared(
			$this->db,
			$query,
			array(
				$application_id
			)
		);

		return $result->fetchAll(PDO::FETCH_OBJ);
	}

	public function getLastPayment($application_id)
	{
		$query = "
			SELECT
				es.date_effective AS date_due,
				ABS(SUM(es.amount_principal + es.amount_non_principal)) AS amount_due
			FROM event_schedule es, event_type et
			WHERE
				es.application_id = ?
				AND et.event_type_id = es.event_type_id
				AND es.date_effective <= CURDATE()
				AND (et.name_short IN ('payment_service_chg',
					'repayment_principal',
					'payout'))
			GROUP BY date_effective
			ORDER BY date_effective DESC
			LIMIT 1";

		$result = DB_Util_1::queryPrepared(
			$this->db,
			$query,
			array(
				$application_id,
			)
		);

		return $result->fetch(PDO::FETCH_OBJ);
	}

	/**
	 * Gets the transaction_history (and agent first/last name) for a single transaction
	 *
	 * @param int $transaction_id
	 * @return array of rows as objects
	 */
	public function getHistory($transaction_id)
	{
		$query = "SELECT
					h.*,
					a.name_last, a.name_first
				FROM transaction_history h
				JOIN agent a on a.agent_id = h.agent_id
				WHERE h.transaction_id = ?
				ORDER BY h.date_created DESC";
		
		$result = DB_Util_1::queryPrepared(
			$this->db,
			$query,
			array(
				$transaction_id,
			)
		);
		
		return $result->fetchAll(PDO::FETCH_OBJ);
	}

}

?>