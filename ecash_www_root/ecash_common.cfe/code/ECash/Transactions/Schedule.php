<?php

	/**
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Transactions_Schedule extends Object_1 implements IteratorAggregate, Countable
	{
		/**
		 * @var ECash_Application
		 */
		protected $application;

		/**
		 * @var ECash_Transactions_Transaction
		 */
		protected $transactions;

		/**
		 * @var ECash_Data_Schedule
		 */
		protected $data;

		/**
		 * @var ECash_Transactions_ScheduleAnalyzer
		 */
		protected $schedule_analyzer;

		/**
		 * @var int
		 */
		protected $skip_period = 0;

		/**
		 * @param ECash_Application $application
		 */
		public function __construct(ECash_Application $application)
		{
			$this->application = $application;
			$this->data = ECash::getFactory()->getData('Schedule');
			$this->load();
		}

		/**
		 * Returns a new transaction for this schedule
		 *
		 * @return ECash_Transactions_Transaction
		 */
		public function newTransaction()
		{
			return ECash_Transactions_Transaction::getNew($this->application);
		}

		/**
		 * Returns the interest calculator owning the schedule.
		 *
		 * @return ECash_Scheduling_IInterestCalculator
		 */
		public function getInterestCalculator()
		{
			return $this->application->getInterestCalculator();
		}
		
		/**
		 * Populates the transactions based on the stored application
		 *
		 */
		protected function load()
		{
			/**
			 * Fetch all schedule info from the database, churning out
			 * ECash_Transaction objects
			 */
			$this->transactions = ECash_Transactions_Transaction::getAll($this->application);
		}

		public function getSkipPeriod()
		{
			return $this->skip_period;
		}

		public function setSkipPeriod($skip_period)
		{
			$this->skip_period = $skip_period;
		}
		
		/**
		 * Returns a stdclass containing data about this schedule's balance
		 *
		 * @return stdClass
		 */
		public function getBalanceInformation()
		{
			return $this->data->getBalanceInformation($this->application->getId());
		}

		/**
		 * Returns balance info for recoveries
		 *
		 * @return stdClass
		 */
		public function getRecoveredAmounts()
		{
			return $this->data->getRecoveryAmounts($this->application->getId());
		}
		
		public function RescheduleNextPayment($to_date)
		{
			$from_date = $this->getAnalyzer()->getNextEventDate();
			foreach ($this->getIterator() as $transaction)
			{
				/* @var $transaction ECash_Transactions_Transaction */
				if ($transaction->isScheduled() 
					&& $transaction->getDate() == $from_date 
					&& $transaction->getTotalAmount() < 0) //ensure that it is really a debit
				{
					$transaction->setDate($to_date);
					$transaction->setShifted();
				}
			}
		}

		/**
		 * Removes all scheduled or suspended Transactions from the transactions.
		 */
		public function removeScheduledTransactions()
		{
			$new_list = array();
			foreach ($this->transactions as $transaction)
			{
				if (!$transaction->isSuspended() && !$transaction->isScheduled())
				{
					$new_list[] = $transaction;
				}
				else
				{
					$this->scheduleDelete($transaction);
				}
			}
			$this->transactions = $new_list;
		}

		/**
		 * This is probably only used by more complex operations
		 * (such as bankruptcy) where you can't delete a transaction
		 * simply based on status or type, so transaction examination
		 * is done outside of this method and deletion is handled here
		 * [JustinF]
		 *
		 * @param int $transaction_id ID of the transaction to be deleted
		 * @return void
		 */
		public function removeTransaction(ECash_Transactions_Transaction $transaction)
		{
			$this->scheduleDelete($transaction);
		}
		
		/**
		 * Returns the transaction in the schedule matching the given ID.
		 * 
		 * If the transaction was not found NULL is returned.
		 *
		 * @param int $transaction_id
		 * @return ECash_Transactions_Transaction
		 */
		public function getTransactionById($transaction_id)
		{
			foreach ($this->transactions as $transaction)
			{
				/* @var $transaction ECash_Transactions_Transaction */
				try 
				{
					if ($transaction->getTransactionId() == $transaction_id)
					{
						return $transaction;
					}
				}
				catch (ECash_Transactions_TransactionException $e)
				{
					// go to the next item.
				}
			}
			
			return NULL;
		}
		
		/**
		 * @var array
		 */
		protected $scheduled_deletes = array();

		/**
		 * Schedules a transaction for deletion at the next save()
		 *
		 * @param ECash_Transactions_Transaction $transaction
		 */
		protected function scheduleDelete(ECash_Transactions_Transaction $transaction)
		{
			$transaction->delete();
			$this->scheduled_deletes[] = $transaction;
		}

		/**
		 * Writes the schedule to the database.
		 * This will delete the existing schedule for this application.
		 *
		 */
		public function save()
		{
			$db = ECash_Config::getMasterDbConnection();

			$db->beginTransaction();

			try
			{
				$batch = new DB_Models_Batch_1($db);

				$this->addToBatch($this->scheduled_deletes, $batch);
				$this->addToBatch($this->transactions, $batch);

				$batch->execute();

				$db->commit();
			}
			catch (Exception $e)
			{
				$db->rollBack();
				$log = ECash::getLog();
				$log->write("Failed to write schedule.", Log_ILog_1::LOG_ERROR);
				throw $e;
			}
		}

		/**
		 * Adds a set of transactions to a batch save
		 *
		 * @param array $transactions array of ECash_Transactions_Transaction
		 * @param DB_Models_Batch_1 $batch batch to add to
		 */
		private function addToBatch(array $transactions, DB_Models_Batch_1 $batch)
		{
			foreach ($transactions as $transaction)
			{
				$models = $transaction->getModifiedModels();

				foreach ($models as $model)
				{
					$batch->save($model);
				}
			}
		}

		public function addTransaction(ECash_Transactions_Transaction $transaction)
		{
			$this->transactions[] = $transaction;
		}

		/**
		 * Mostly for use in conjunction with the ScheduleBuilder which spits out an array of transactions
		 * (because many transactions may be scheduled for one date)
		 */
		public function addTransactions(array $transactions)
		{
			/**
			 * Iterate through these (rather than using an array
			 * function) so we throw away the key and append the value
			 */ 
			foreach($transactions as $transaction)
			{
				$this->transactions[] = $transaction;				
			}
		}

		/**
		 * Gets schedule analyzer for this applications schedule
		 *
		 * @return ECash_Transactions_ScheduleAnalyzer
		 */
		public function getAnalyzer()
		{
			if($this->schedule_analyzer === NULL)
			{
				$this->schedule_analyzer = new ECash_Transactions_ScheduleAnalyzer($this);
			}

			return $this->schedule_analyzer;
		}

		/**
		 * Returns an iterator for this schedule
		 *
		 * @return ArrayIterator
		 */
		public function getIterator()
		{
			return new ArrayIterator($this->transactions);
		}

		/**
		 * For Countable interface
		 * 
		 * @return int count of transactions
		 */
		public function count()
		{
			return count($this->transactions);
		}
		
		public function getApplication()
		{
			return $this->application;	
		}
	}
?>