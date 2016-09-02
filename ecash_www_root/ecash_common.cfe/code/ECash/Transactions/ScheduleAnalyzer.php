<?php

  /**
   * Wrapper for ECash_Transactions_Schedule.  Uses IteratorAggregate
   * interface to analyze schedule.
   */
class ECash_Transactions_ScheduleAnalyzer extends Object_1
{
	/**
	 * @var ECash_Transactions_Schedule
	 */
	protected $schedule;

	/**
	 * @var ECash_Transactions_Transaction
	 */
	protected $stopping_location;
	
	/**
	 * @var int
	 */
	protected $ach_fee_count = 0;

	/**
	 * @var array ECash_Transactions_Transaction
	 */ 
	protected $outstanding_ach = array();

	/**
	 * @var int
	 */
	protected $max_reattempts = 0;

	/**
	 * @var boolean
	 */
	protected $has_arrangements = FALSE;
	
	/**
	 * @var boolean
	 */
	protected $has_failed_arrangements = FALSE;

	/**
	 * @var boolean
	 */
	protected $has_debt_consolidation_payments = FALSE;
	
	/**
	 * @var boolean
	 */
	protected $has_manual_renewals = FALSE;
	
	/**
	 * @var boolean
	 */
	protected $has_scheduled_reattempts = FALSE;

	/**
	 * @var float
	 */
	protected $initial_principal = 0.0;
	
	/**
	 * @var array
	 */
	protected $quickchecks = array();

	/**
	 * @var int
	 */
	protected $num_fatal_failures = 0;

	/**
	 * @var int
	 */
	protected $num_registered_events = 0;

	/**
	 * @var int
	 */
	protected $num_scheduled_events = 0;

	/**
	 * @var int
	 */
	protected $posted_service_charge_count = 0;

	/**
	 * @var boolean
	 */
	private $is_analyzed = FALSE;
	
	public function __construct(ECash_Transactions_Schedule $schedule)
	{
		$this->schedule = $schedule;
	}

	public function getStoppingLocation()
	{
		$this->analyze();
		return $this->stopping_location;
	}

	public function getHasACHFees()
	{
		$this->analyze();
		return count($this->ach_fee_count);		
	}
	
	public function getMaxReattempts()
	{
		$this->analyze();
		return $max_reattempts;		
	}

	public function getOutstandingACH()
	{
		$this->analyze();
		return $this->outstanding_ach;		
	}

	public function getHasArrangements()
	{
		$this->analyze();
		return $this->has_arrangements;
	}

	public function getHasFailedArrangements()
	{
		$this->analyze();
		return $this->has_failed_arrangements;
	}

	public function getHasDebtConsolidationPayments()
	{
		$this->analyze();
		return $this->has_debt_consolidation_payments;
	}

	public function getHasManualRenewals()
	{
		$this->analyze();
		return $this->has_manual_renewals;
	}
	
	public function getHasScheduledReattempts()
	{
		$this->analyze();
		return $this->has_scheduled_reattempts;
	}
	
	public function getQuickCheckCount()
	{
		$this->analyze();
		return count($this->quickchecks);
	}

	public function getQuickChecks()
	{
		$this->analyze();
		return $this->quickchecks;
	}

	public function getFatalFailureCount()
	{
		$this->analyze();
		return $this->num_fatal_failures;
	}

	/**
	 * Giant method to get all the possible stats in one pass.
	 * Big? Yes. Efficient? Also Yes.
	 */
	private function analyze()
	{
		if($this->is_analyzed)
			return;

		$this->max_reattempts = countMaxReattempts();
		
		foreach ($this->schedule as $transaction)
		{
			// Used to determine whether or not the schedule has any
			// scheduled events
			if($e->status === 'scheduled')
			{
				$this->num_scheduled_events++;
			}
			
			if($e->status === 'scheduled' && $e->context === 'reattempt')
				$this->has_scheduled_reattempts = TRUE;
			
			if($e->type === 'repayment_principal' && $e->context === 'manual')
				$this->has_manual_renewals = TRUE;
			
			if($e->type === 'payment_debt' || $e->type === 'payment_debt_principal' || $e->type === 'payment_debt_fees')
				$this->has_debt_consolidation_payments = TRUE;
			
			if($e->status !== 'scheduled')
				$this->num_registered_events++;

			if($e->type === 'quickcheck')
				$this->quickchecks[] = $e;
			
			if ($e->context === 'arrangement' || $e->context === 'partial')
			{
				if ($e->status === 'scheduled')
					$this->has_arrangements = TRUE;

				// Mantis:9820 - business rule needs to apply to failed arrangements only.  flagging state here.
				if ($e->status === 'failed')
					$this->has_failed_arrangements = TRUE;
			}

			switch($e->status)
			{
				case 'failed':
					if(isset($e->ach_return_code_id) && $failure_map[$e->ach_return_code_id]['is_fatal'] === 'yes' && $e->bank_aba == $e->current_bank_aba && $e->bank_account == $e->current_bank_account)
						$this->num_fatal_failures++;
					break;
				case 'pending':
					// There was a problem with Complete Schedule not seeing the pending
					// service charges and would produce additional charges.  This appears
					// to fix it and not cause harm elsewhere. [Mantis:1680]
					if ($this->isServiceChargePayment($e))
						$this->posted_service_charge_count++;
					break;
			}

			if (($e->status === 'complete') || ($verify && ($e->status != 'failed')))
			{
				switch($e->type)
				{
					case 'assess_fee_ach_fail':
						$this->ach_fee_count++;
						$this->outstanding_ach[] = $e;
						break;
					
					case 'check_disbursement':
						$this->initial_principal = $e->principal;
						break;
						
					// Converted sc event means a) they already accrued a sc, and 2) they already paid a sc
					case 'converted_sc_event':
						$this->posted_service_charge_count++;
						break;
					case 'payment_service_chg':
						$this->posted_service_charge_count++;
						break;
				}
			}
			
			$this->stopping_location = $transaction;
		}
	   
		$this->is_analyzed = TRUE;		
	}
	
	/**
	 * Copy of scheduling.func.php Count_Max_Reattempts()
	 */
	protected function countMaxReattempts($origin_id = NULL)
	{
		static $recursion_level;
		if (empty($origin_id))
		{
			$max_level = 0;
			foreach($this->schedule as $transaction)
			{
				$recursion_level = 1;
				if ($transaction->OriginId !== NULL && $transaction->OriginId > 0)
				{
					$this->countMaxReattempts($transaction->OriginId);
					$recursion_level--;
					if($recursion_level > $max_level)
						$max_level = $recursion_level;
				}
			}
			return $max_level;
		}
		else
		{
			$recursion_level++;
			foreach($this->schedule as $transaction)
			{
				if ($transaction->RegisterId === $origin_id)
				{
					if ($transaction->OriginId === NULL)
						return 0;
					else
						return countMaxReattempts($transaction->OriginId) + 1;
				}
			}
		}
	}

	public function isServiceChargePayment($event)
	{
		if(in_array($event->context, array('generated', 'arrange_next')))
		{
			foreach($event->amounts as $ea)
			{
				if($ea->event_amount_type == 'service_charge' && $ea->amount < 0)
					return TRUE;
			}
		}
		return FALSE;
	}


	/*************************************************************************
	 * Working on replacing all methods below with the single-pass analyze() *
	 * Some methods below may still be useful.                               *
	 *************************************************************************/

	/**
	 * Returns the next scheduled debit for the transaction.
	 *
	 * @return ECash_Transactions_Transaction
	 */
	public function getNextScheduledDebit()
	{
		$cur_transaction = NULL;
		foreach ($this->schedule as $transaction)
		{
			/* @var $transaction ECash_Transactions_Transaction */
			$cur_date = $cur_transaction->getDate();
			if ($transaction->isScheduled() && $transaction->getTotalAmount() < 0)
			{
				$date = $transaction->getDate();
				if ($cur_transaction === NULL || $date < $cur_date)
				{
					$cur_transaction = $transaction; 
				}
			}
		}
		
		return $cur_transaction;
	}
	
	public function getNextDueAmount()
	{
		$amount = 0;
		$date = $this->getNextEventDate();
		
		foreach ($this->schedule as $transaction)
		{
			if ($transaction->isScheduled() 
				&& $transaction->getDate() == $date
				&& $transaction->getTotalAmount() < 0)
			{
				$amount += -$transaction->getTotalAmount();
			}
		}
		
		return $amount;
	}
	
	/**
	 * Returns a list of transactions from the last failure set
	 *
	 * @return Array
	 */
	public function getLatestFailSet()
	{
		$failures = array();
		$fail_time = NULL;
		
		foreach ($this->schedule as $transaction)
		{
			/* @var $transaction ECash_Transactions_Transaction */
			$external = $transaction->getExternalInfo();
			/* @var $external ECash_Transactions_Transaction_IReturnable */
			if ($transaction->isFailed() && $external instanceof ECash_Transactions_Transaction_IReturnable)
			{
				if ($fail_time == NULL || $external->getReturnDate() > $fail_time)
				{
					$fail_time = $external->getReturnDate();
					$failures = array($transaction);
				}
				elseif ($external->getReturnDate() == $fail_time)
				{
					$failures[] = $transaction;
				}
			}
		}
		
		return $failures;
	}
	
	/**
	 * Returns the date of the next scheduled action
	 * @deprecated 
	 * @todo replace all calls with getNextScheduledDebit()->getDate();
	 */
	public function getNextEventDate()
	{
		$min_date = NULL;
		$now = time();
		foreach ($this->schedule as $transaction)
		{
			if ($transaction->isScheduled())
			{
				$td = $transaction->getDate();
				if ($td > $now && ($min_date === NULL || $td < $min_date))
				{
					$min_date = $td;
				}
			}
		}
		
		return $min_date;
	}

	/**
	 * This doesn't return the number of completed items, in this case 'registered' means 'not scheduled'
	 *
	 * @return int
	 */
	public function getRegisteredCount()
	{
		$count = 0;
		foreach ($this->schedule as $transaction)
		{
			if (!$transaction->isScheduled())
			{
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Returns the number of pending transactions on this schedule
	 *
	 * @return int number of pending transactions
	 */
	public function getPendingCount()
	{
		$count = 0;
		foreach ($this->schedule as $transaction)
		{
			if ($transaction->isPending())
			{
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Returns balance amounts
	 *
	 * @param int $mask mask of ECash_Transactions_Transaction::STATUS_* constants
	 * @return array array of total balance amounts keyed by amount type (fees, principal, etc.)
	 */
	public function getBalanceAmounts($mask = ECash_Transactions_Transaction::STATUS_PENDING)
	{
		$balance_amounts = array();
		foreach($this->schedule as $transaction)
		{
			if($transaction->getStatus() & $mask)
			{
				$amounts = $transaction->getAmounts();
				foreach($amounts as $type => $amount)
				{
					if(empty($balance_amounts[$type]))
						$balance_amounts[$type] = 0;
					$balance_amounts[$type] += $amount;
				}
			}
		}
		return $balance_amounts;
	}
	
	/**
	 * Returns all transactions in a specific status.
	 *
	 * @param int $mask mask of ECash_Transactions_Transaction::STATUS_* constants
	 * @return ECash_Transactions_Transaction[]
	 */
	public function getTransactionsByStatus($mask)
	{
		$transactions = array();

		foreach ($this->schedule as $transaction)
		{
			if ($transaction->getStatus() & $mask)
			{
				$transactions[] = $transaction;
			}
		}

		return $transactions;
	}

	/**
	 * Returns all transactions for a specific date.
	 *
	 * @param int $date
	 * @return ECash_Transactions_Transaction[]
	 */
	public function getTransactionsByDate($date)
	{
		$transactions = array();

		foreach ($this->schedule as $transaction)
		{
			if ($transaction->getDate() === $date)
			{
				$transactions[] = $transaction;
			}
		}

		return $transactions;
	}

	/**
	 * Returns an array of all transactions from this schedule
	 * where the type matches $type.
	 * ex.
	 *   $my_transactions = $my_schedule->getTransactionsByType(ECash_Transactions_Transaction::TYPE_PAYDOWN);
	 *
	 * Will return all paydown transactions. Use the TYPE_* constants on
	 * ECash_Transactions_Transaction
	 *
	 * @param string $type
	 */
	public function getTransactionsByType($type)
	{
		$output = array();
		foreach ($this->schedule as $transaction)
		{
			if ($transaction->getType() === $type)
			{
				$output[] = $transaction;
			}
		}
		return $output;
	}
	
	/**
	 * Gets all interest only payments
	 *
	 * @return array of ECash_Transactions_Transaction objects
	 */
	public function getInterestOnlyPayments()
	{
		$int_only_payments = array();

		$mask = ECash_Transactions_Transaction::STATUS_SCHEDULED | ECash_Transactions_Transaction::STATUS_PENDING | ECash_Transactions_Transaction::STATUS_COMPLETE;
		
		foreach($this->schedule as $transaction)
		{
			/** @TODO change these to constants, or replace with getInterestOnlyPaymentTypes() */
			/** also not sure if we should just look for first withdrawl */
			if (in_array($transaction->getType(), array(ECash_Transactions_TransactionType::TYPE_INTEREST_PAYMENT))
				&& ($transaction->getStatus() & $mask))
			{
				$int_only_payments[] = $transaction;
			}			
		}

		return $int_only_payments;
	}
	

	/**
	 * Gets the fund transaction (if it exists)
	 *
	 * @return ECash_Transactions_Transaction
	 */
	public function getFund()
	{
		$mask = ECash_Transactions_Transaction::STATUS_SCHEDULED | ECash_Transactions_Transaction::STATUS_PENDING | ECash_Transactions_Transaction::STATUS_COMPLETE;

		foreach($this->schedule as $transaction)
		{
			/** @TODO change these to constants, or replace with getFundTypes() */
			if (in_array($transaction->getType(), array(ECash_Transactions_TransactionType::TYPE_ACH_DISBURSEMENT,
														ECash_Transactions_TransactionType::TYPE_MONEYGRAM_DISBURSEMENT,
														ECash_Transactions_TransactionType::TYPE_CHECK_DISBURSEMENT))
				&& ($transaction->getStatus() & $mask))
			{
				return $transaction;
			}
		}

		return NULL;
	}

	/**
	 * Gets the last completed (or scheduled) payment transaction from the schedule
	 *	
	 * @param int $mask mask of ECash_Transactions_Transaction::STATUS_* constants
	 * @return ECash_Transactions_Transaction
	 * @TODO Ported from David Ihnen's Interest_Calculator::getInterestPaidPrincipalAndDate() grrr..
	 */
	public function getLastPayment($mask = ECash_Transactions_Transaction::STATUS_COMPLETE)
	{
		$last_payment = NULL;

		foreach($this->schedule as $transaction)
		{
			if($transaction->getStatus() & $mask)
			{
				$amounts = $transaction->getAmounts();
				/** @TODO change to constants */
				if ((isset($amounts['principal']) && $amounts['principal'] != 0) ||
					(isset($amounts['service_charge']) && $amounts['service_charge'] != 0))
				{
					$last_payment = $transaction;
				}
			}
		}

		return $last_payment;
	}

	protected function traceReattempts($origin_id)
	{
		foreach ($this->schedule as $transaction)
		{
			if ($transaction->getTransactionId() === $origin_id)
			{
				if ($transaction->getOriginId() != NULL)
				{
					return 1+$this->traceReattempts($transaction->getOriginId());
				}
			}
		}
		return 1;
	}

	/**
	 * Returns only scheduled debits the schedule
	 *
	 * @param array $schedule_array
	 * @return array
	 */
	public function getScheduledDebits()
	{
		$scheduled_payments = array();
		foreach ($this->schedule as $transaction)
		{
			$amounts = $transaction->getAmounts();
			if (($transaction->getStatus() == ECash_Transactions_Transaction::STATUS_SCHEDULED) &&
				(($amounts['principal'] + $amounts['fee']) < 0.0))
			{
				$scheduled_payments[] = $transaction;
			}
		}

		return $scheduled_payments;
	}
		
	/**
	 * Returns the total  balance of the type of transactions given in $mask
	 *
	 * @param int $mask ECash_Transactions_Transaction::STATUS_*
	 * @return float
	 */
	public function getBalance($mask = ECash_Transactions_Transaction::STATUS_COMPLETE)
	{
		$total = 0;
		foreach($this->schedule as $transaction)
		{
			/* @var $transaction ECash_Transactions_Transaction */
			if($transaction->Status & $mask)
			{
				/**
				 * The total balance should not ever include 'irrecoverable' amounts.
				 */
				$total += array_sum(array_diff_key(
					$transaction->getAmounts(),
					array(ECash_Transactions_Transaction::AMOUNT_TYPE_IRRECOVERABLE => false)
				));
			}
		}
		return $total;
	}
	
	/**
	 * Convenience methods replacing Analyze_Schedule
	 */
	public function getTotalComplete()
	{
		return $this->getBalance(ECash_Transactions_Transaction::STATUS_COMPLETE);
	}

	/**
	 * convenience method to replacing analyze schedule's total_paid
	 *
	 * @return string total paid amount from bcadd-ing all complete balances
	 */
	public function getTotalPaid()
	{
		return array_sum($this->getPaidAmounts());
	}

	/**
	 * Similar to getBalanceAmounts, but returns an array of paid
	 * amounts (status: completed, amount usually negative or
	 * non-zero)
	 *
	 * @return array
	 */ 
	public function getPaidAmounts()
	{
		$paid_amounts = array();
		foreach($this->schedule as $transaction)
		{
			if($transaction->getStatus() & ECash_Transactions_Transaction::STATUS_COMPLETE)
			{
				$amounts = $transaction->getAmounts();
				foreach($amounts as $type => $amount)
				{
					if(empty($paid_amounts[$type]))
						$paid_amounts[$type] = 0;
					if (($type == ECash_Transactions_Transaction::AMOUNT_TYPE_SERVICE_CHARGE && $amount != 0)
					     || $amount < 0)
					{
						$paid_amounts[$type] += $amount;
					}
				}
			}
		}
		return $paid_amounts;
	}
	
	/**
	 * Similar to getTransactionTypeCount, but returns a count of paid
	 * amounts (status: completed, amount usually negative or
	 * non-zero)
	 *
	 * @return array
	 */ 
	public function getPaidCount($type = NULL)
	{		
		$count = 0;
		foreach($this->schedule as $transaction)
		{
			if($transaction->getStatus() & ECash_Transactions_Transaction::STATUS_COMPLETE
			   && $type !== NULL
			   && $transaction->getType() == $type)
			{
				if (($type == ECash_Transactions_Transaction::AMOUNT_TYPE_SERVICE_CHARGE && $amount != 0)
				     || $amount < 0)
				{
					$count++;
				}
			}
		}
		
		return $count;
	}
	
	/**
	 * @return bool
	 */
	public function getHasFatalReturns()
	{
		foreach ($this->schedule as $transaction)
		{
			/* @var $transaction ECash_Transactions_Transaction */
			$external_info = $transaction->getExternalInfo();
			
			/* @var $external_info ECash_Transactions_Transaction_IReturnable */
			if ($transaction->isFailed()
				&& $external_info instanceof ECash_Transactions_Transaction_IReturnable
				&& $external_info->isFatalFailure())
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @return int count
	 */
	public function getTransactionTypeCount($type, $mask = ECash_Transactions_Transaction::STATUS_ALL)
	{
		$trans = $this->getTransactionsByStatus($mask);
		
		$count = 0;
		foreach ($trans as $t)
		{
			if ($t->getType() == $type)
			{
				$count++;
			}
		}
		
		return $count;
	}

	public function getPastDueBalance()
	{
		$completed_balance = $this->getBalanceAmounts(ECash_Transactions_Transaction::STATUS_COMPLETE);
		$sc_balance = $completed_balance[ECash_Transactions_Transaction::AMOUNT_TYPE_SERVICE_CHARGE] 
			+ $completed_balance[ECash_Transactions_Transaction::AMOUNT_TYPE_SERVICE_CHARGE];
		
		$failed_non_reatt = 0;
		$completed_reatt = 0;
		foreach ($this->schedule as $transaction)
		{
			/* @var $transaction ECash_Transactions_Transaction */
			if ($transaction->isFailed() && !$transaction->isReattempt())
			{
				$amounts = $transaction->getAmounts();
				$failed_non_reatt += -$amounts[ECash_Transactions_Transaction::AMOUNT_TYPE_PRINCIPAL];
			}
			elseif ($transaction->isComplete() && $transaction->isReattempt())
			{
				$amounts = $transaction->getAmounts();
				$failed_non_reatt += -$amounts[ECash_Transactions_Transaction::AMOUNT_TYPE_PRINCIPAL];
			}
		}
		
		return $sc_balance - ($failed_non_reatt - $completed_reatt);
	}

	/**
	 * 
	 * @return int number of posted service charges
	 */
	public function getPostedServiceChargeCount()
	{
		$type_array = array(
			ECash_Transactions_TransactionType::TYPE_INTEREST_PAYMENT
			// converted SC transaction should also go here for CLK
		);
		$count = 0;
		foreach ($this->schedule as $transaction)
		{
			if (($transaction->isPending() || $transaction->isComplete())
				&& in_array($transaction->getType(), $type_array))
			{
				$count++;
			}
		}
		
		return $count;
	}

	/**
	 * @TODO Not sure what this actually does
	 * 
	 * @param int $adjustment_span number of days to advance effective date of transaction before finding the next business date
	 * @return array unix timestamps of shifted dates
	 */
	public function getShiftedDates($adjustment_span = 1)
	{
		$shifted_dates = array();
		$normalizer = ECash::getFactory()->getDateNormalizer();
		
		foreach ($this->schedule as $transaction)
		{
			$end_date = $normalizer->advanceBusinessDays($transaction->DateEffective, $adjustment_span);
			$next_business_date = $normalizer->normalize(time());
			while ($next_business_date < $ending_date)
			{
				$shifted_dates[] = $next_business_date;
				//go one business day forward
				$next_business_date = $normalizer->advanceBusinessDays($next_business_date, 1);
			}
			
		}

		return $shifted_dates;
	}

	public function getIsCancellable($cancellation_delay = NULL)
	{
		/**
		 * scheduling.funk indicates that you can cancel after
		 * any transaction, as long as the cancelation delay has
		 * not passed (normally ACH waiting period).
		 */

		/**
		 * This business logic was gleamed from Loan_Data::Cancel_Loan(), scheduling.func.php::analyze_schedule()
		 */
		$schedule_iterator = $this->schedule->getIterator();
		
		//FALSE if there are no registered items to cancel
		if($schedule_iterator->count() == 0)
		{
			return FALSE;
		}
			
		$last_item = $schedule_iterator[$schedule_iterator->count() - 1];
		$normalizer = ECash::getFactory()->getDateNormalizer();
		/**
		 * apparently this extra day is added because he rule is
		 * "If it's been less than x days since the last payment."
		 */
		if ($cancellation_delay !== NULL)
		{
			$cancel_limit = $normalizer->normalize(strtotime('+'. ($cancellation_delay + 1) . ' days', $last_item->getDate()));
				
			if($this->event_date >= $cancel_limit)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}

?>
