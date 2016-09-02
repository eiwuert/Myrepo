<?php

	/**
	 * business object to represent a single ecash financial transaction,
	 * whether it be in the transaction or ledger
	 *
	 */
	class ECash_Transactions_Transaction extends Object_1
	{
		/**
		 * Principal amounts.
		 */
		const AMOUNT_TYPE_PRINCIPAL = 'principal';

		/**
		 * Service charge amounts.
		 */
		const AMOUNT_TYPE_SERVICE_CHARGE = 'service_charge';

		/**
		 * Fee amounts.
		 */
		const AMOUNT_TYPE_FEE = 'fee';

		/**
		 * Irrecoverable amounts.
		 */
		const AMOUNT_TYPE_IRRECOVERABLE = 'irrecoverable';

		/**
		 * Transaction is scheduled but may never actually occur. Transaction is tentative.
		 */
		const STATUS_SCHEDULED = 1;

		/**
		 * Transaction has been dispatched to the proper clearing house.
		 */
		const STATUS_PENDING = 2;

		/**
		 * Transaction has failed to process because of one or more reasons. No money was collected.
		 */
		const STATUS_FAILED = 4;

		/**
		 * Transaction has been successfully processed by clearing house, and money has been collected.
		 */
		const STATUS_COMPLETE = 8;

		/**
		 * Transaaction is currently -stalled-. If the status remains suspended, it will not occur on the date specified.
		 */
		const STATUS_SUSPENDED = 16;

		/**
		 * convenience status for pending and complete
		 */
		const STATUS_PENDING_AND_COMPLETE = 12;

		/**
		 * Mask for all statuses
		 */
		const STATUS_ALL = 0xffffffff;
		
		/**
		 * @var ECash_Models_EventSchedule
		 */
		public $event;

		/**
		 * @var array of ECash_Models_TransactionRegisters indexed by register_id
		 */
		protected $transaction_registers = array();

		/**
		 * @var array of ECash_Models_TransactionLedgers indexed by register_id
		 */
		protected $transaction_ledgers = array();

		/**
		 * @var ECash_Models_EventAmount[]
		 */
		protected $transaction_amounts = array();

		/**
		 * @var ECash_Application
		 */
		protected $application;

		/**
		 * @var string event type name short
		 */
		protected $type;

		/**
		 * @var ECash_Models_EventType
		 */
		protected $type_model;

		/**
		 * @var array of ECash_Models_TransactionType indexed by transaction_register_id
		 */
		protected $transaction_types = array();
		
		/**
		 * @var int
		 */
		protected $status = self::STATUS_SCHEDULED;

		/**
		 * @var ECash_Models_Reference_List
		 */
		protected $transaction_amount_types = NULL;

		/**
		 * @var ECash_Models_Reference_TransactionType List
		 *
		 * If this being static becomes an issue, we should re-evaluate it
		 */
		protected static $event_type_list = NULL;

		/**
		 * @var ECash_Models_Reference_TransactionType List
		 *
		 * If this being static becomes an issue, we should re-evaluate it
		 */
		protected static $transaction_type_list = NULL;

		/**
		 * @var ECash_Transactions_Transaction_Ach|ECash_Transactions_Transaction_Ecld polymorphic depending on clearing type
		 */
		protected $external_info;
		
		/**
		 * @var string
		 */
		protected $principal;
		
		/**
		 * @var string
		 */
		protected $service_charge;
		
		/**
		 * @var string
		 */
		protected $fee;
		
		/**
		 * @var string
		 */
		protected $irrecoverable;
		
		
		/**
		 * constructor is protected, use static methods to get an instance.
		 */
		protected function __construct(ECash_Application $application)
		{
			$this->application = $application;
			$this->transaction_amount_types = ECash::getFactory()->getReferenceList('EventAmountType');
			self::$event_type_list = ECash::getFactory()->getReferenceList('EventType');
			self::$transaction_type_list = ECash::getFactory()->getReferenceList('TransactionType');
		}

		/**
		 * builds a new transaction and returns it.
		 *
		 * @return ECash_Transactions_Transaction
		 */
		public static function getNew(ECash_Application $application)
		{
			$class_name = ECash::getFactory()->getClassString('Transactions_Transaction');
			$inst = new $class_name($application);
			$inst->status = self::STATUS_SCHEDULED;

			$transaction_schedule = ECash::getFactory()->getModel('EventSchedule');

			$transaction_schedule->date_modified = time();
			$transaction_schedule->date_created = time();
			$transaction_schedule->company_id = $application->getCompanyId();
			$transaction_schedule->application_id = $application->getId();
			$transaction_schedule->origin_id = NULL;
			$transaction_schedule->origin_group_id = NULL;
			$transaction_schedule->configuration_trace_data = '';
			/** @TODO maybe allow an override for dda via setSource() */
			$transaction_schedule->source_id = ECash::getFactory()->getReferenceList('Source')->toId('ecashinternal');
			$transaction_schedule->context = ECash_Models_EventSchedule::CONTEXT_GENERATED;
			$transaction_schedule->is_shifted = 0; //wtf is this?			
			$transaction_schedule->event_status = ECash_Models_EventSchedule::STATUS_SCHEDULED;
			
			$inst->event = $transaction_schedule;

			return $inst;
		}

		/**
		 * Loads all transactions for an application
		 *
		 * @param ECash_Application $application
		 * @return array ECash_Transactions_Transaction
		 */
		public static function getAll(ECash_Application $application)
		{
			$schedule_list = ECash::getFactory()->getModel('EventScheduleList');
			$schedule_list->loadBy(array('application_id' => $application->Id));

			$transaction_list = ECash::getFactory()->getModel('TransactionList');
			$transaction_list->loadBy(array('application_id' => $application->Id));

			$all_amounts = array();
			$all_amounts = ECash::getFactory()->getModel('EventAmount');
			$all_amounts->loadBy(array('application_id' => $application->Id));

			$amounts_by_transaction = array();
			foreach ($all_amounts as $amount_model)
			{
				$transaction_id = $amount_model->event_schedule_id;
				if (empty($amounts_by_transaction[$transaction_id]))
					$amounts_by_transaction[$transaction_id] = array();

				$amounts_by_transaction[$transaction_id][] = $amount_model;
			}

			$transaction_array = array();

			foreach ($schedule_list as $schedule_event)
			{
				$class_name = ECash::getFactory()->getClassString('Transactions_Transaction');
				$transaction = new $class_name($application);
				$transaction->event = $schedule_event;
				$transaction->type = self::$event_type_list->toName($schedule_event->event_type_id);
				
				$transaction->type_model = self::$event_type_list[$schedule_event->event_type_id];
				
				$transaction->transaction_amounts = $amounts_by_transaction[$schedule_event->event_schedule_id];

				$transaction_array[$schedule_event->event_schedule_id] = $transaction;
				
			}

			foreach ($transaction_list as $transaction_models)
			{
				$transaction_register = $transaction_models[ECash_Models_TransactionList::INDEX_REGISTER];
				$transaction = $transaction_array[$transaction_register->event_schedule_id];
				$transaction->transaction_registers[$transaction_register->transaction_register_id] = $transaction_register;
				$transaction->transaction_ledgers[$transaction_register->transaction_register_id] = $transaction_models[ECash_Models_TransactionList::INDEX_LEDGER];

				$transaction->status = self::toStatusId($transaction->event->event_status, $transaction_register->transaction_status);
				$transaction->transaction_types[$transaction_register->transaction_register_id] = self::$transaction_type_list[$transaction_register->transaction_type_id];

			}

			return $transaction_array;
		}
		
		/**
		 * If anything in transaction_registers is invalid, the whole
		 * transaction is invalid
		 *
		 * @return bool
		 */
		public function getInvalid()
		{
			foreach ($this->transaction_registers as $tr)
			{
				if ($tr->invalid)
				{
					return TRUE;
				}
			}
			return FALSE;
		}
		
		public function getEventScheduleId()
		{
			return $this->event->event_schedule_id;
		}
		
		/**
		 * @param int $origin_id
		 */
		public function setOriginId($origin_id)
		{
			$this->event->origin_id = $origin_id;
		}

		/**
		 * @return int
		 */
		public function getOriginId()
		{
			return $this->event->origin_id;
		}

		/**
		 * Sets the date for this transaction. Also computes the effective date.
		 *
		 * @param string $date YYYY-mm-dd
		 */
		public function setDate($date)
		{			
			if ($this->status !== self::STATUS_SCHEDULED && $this->status !== self::STATUS_SUSPENDED)
			{
				throw new ECash_Transactions_TransactionException("Can not set date once transaction has been moved beyond pending.");
			}

			$this->event->date_event = $date;

			$this->event->date_effective = $this->calculateDateEffective(strtotime($date));
		}


		/**
		 * Determines the date effective based on given date
		 *
		 * @param int $date_event unix timestamp of event date
		 * @return int unix timestamp of calculated date effective
		 */
		protected function calculateDateEffective($date_event)
		{
			//set the effective date
			if ($this->type == NULL)
			{
				throw new ECash_Transactions_TransactionException("Type must be set to determine delay information for date_effective");
			}

			$transaction_type_model = self::$event_type_list[$this->type];

			$date_effective = $date_event;

			//this should maybe change to an effective_delay column on transaction_type
			if ($transaction_type_model->clearing_type != ECash_Models_Reference_TransactionType::CLEARING_ACCRUED_CHARGE &&
				$transaction_type_model->period_type == ECash_Models_Reference_TransactionType::PERIOD_TYPE_BUSINESS)
			{
				$normalizer = ECash::getFactory()->getDateNormalizer();
				$date_effective = $normalizer->advanceBusinessDays($date_event, 1);
			}
			
			return $date_effective;
		}
		
		public function setShifted($bool = TRUE)
		{
			$this->event->is_shifted = $bool ? 1 : 0;
		}

		/**
		 * Sets the context of the transaction
		 * 
		 * @param string $context one of the ECash_Models_EventSchedule::CONTEXT_* constants
		 * @return void
		 */
		public function setContext($context)
		{
			$this->event->context = $context;
		}

		/**
		 * Gets the context of the transaction
		 * 
		 * @return string one of the ECash_Models_EventSchedule::CONTEXT_* constants
		 */
		public function getContext()
		{
			return $this->event->context;
		}

		/**
		 * clearing type
		 *
		 * @return string a ECash_Models_TransactionType::CLEARING_* constant
		 */ 
		public function getClearingType()
		{
			foreach ($this->transaction_types as $tt)
			{
				return $tt->clearing_type;
			}
		}


		/**
		 * Returns the transaction type for this transaction.  Will match one of the TYPE_*
		 * constants in this ECash_Transactions_TransactionType
		 *
		 * @return string
		 */
		public function getType()
		{
			return $this->type;
		}

		/**
		 * Sets the type of this transaction. Use the TYPE_* constants.
		 *
		 * @param string $type
		 */
		public function setType($type)
		{
			if ($this->status !== self::STATUS_SCHEDULED
				&& $this->status !== self::STATUS_SUSPENDED)
			{
				throw new ECash_Transactions_TransactionException("You may only set the type before it has moved to pending status.");
			}

			$this->type = $type;
			$this->event->event_type_id = self::$event_type_list->toId($type);
			$this->type_model = self::$event_type_list[$this->type];
		}

		/**
		 * Sets configuration_trace_data
		 *
		 * @TODO maybe rename this method to something more self-explanatory
		 * @param string $string
		 */
		public function setTrace($string)
		{
			$this->event->configuration_trace_data = $string;
		}

		/**
		 * Returns the date of this transaction
		 *
		 * @return int unix timestamp
		 */
		public function getDate()
		{
			return $this->event->date_event;
		}

		/**
		 * Returns the effective date (date it will be dispatched) of this transaction
		 *
		 * @return int unix timestamp
		 */
		public function getDateEffective()
		{
			return $this->event->date_effective;
		}

		/**
		 * returns the modified date of this transaction
		 * 
		 * @return int unix timestamp
		 */
		public function getDateModified()
		{
			return $this->event->date_modified;
		}
		
		/**
		 * returns the created date of this transaction
		 *
		 * @return unknown
		 */
		public function getDateCreated()
		{
			return $this->event->date_created;
		}
		
		/**
		 * This is an implementation of the following query using models. 
		 * 
		 * SELECT DATE(th_2.date_created)
		 * FROM
		 * 	transaction_history AS th_2
		 * WHERE
		 * 	th_2.transaction_register_id = tr.transaction_register_id
		 * AND
		 * 	tr.transaction_status = 'complete'
		 * AND
		 * 	th_2.status_after = 'complete'
		 * ORDER BY th_2.date_created DESC
		 * LIMIT 1
		 *
		 * @return string|NULL
		 */
		public function getDateCompleted()
		{
			$time = 0;
			foreach ($this->transaction_registers as $tr)
			{
				if ($tr->transaction_status == 'complete')
				{
					$thl = ECash::getFactory()->getModel('TransactionHistoryList');
					$thl->loadBy(array('transaction_register_id' => $tr->transaction_register_id,
						'status_after' => 'complete'));
					foreach ($thl as $th)
					{
						if (strtotime($th->date_created) > $time)
						{
							$time = strtotime($th->date_created);
						}
					}
				}
			}
			if ($time > 0)
			{
				return date("Y-m-d", $time);
			}
			return NULL;
		}
		
		public function getEventName()
		{
			return $this->type_model->name;
		}
		
		public function getPrincipal()
		{
			if (!isset($this->principal))
			{
				$this->principal = $this->searchAmounts('principal'); 
			}
			return $this->principal;
		}
		
		public function getFeeAmount()
		{
			return $this->event->amount_non_principal;
		}
		
		public function getPrincipalAmount()
		{
			return $this->event->amount_principal;
		}
		
		public function getServiceCharge()
		{
			if (!isset($this->service_charge))
			{
				$this->service_charge = $this->searchAmounts('service_charge'); 
			}
			return $this->service_charge;
		}
		
		public function getFee()
		{
			if (!isset($this->fee))
			{
				$this->fee = $this->searchAmounts('fee'); 
			}
			return $this->fee;
		}
		
		public function getIrrecoverable()
		{
			if (!isset($this->irrecoverable))
			{
				$this->irrecoverable = $this->searchAmounts('irrecoverable'); 
			}
			return $this->irrecoverable;
		}
		
		protected function searchAmounts($name_short)
		{
			if (is_null($this->transaction_amounts))
			{
				return 0;
			}
			foreach ($this->transaction_amounts as $amount)
			{
				$eat = ECash::getFactory()->getReferenceList('EventAmountType');
				if ($eat->toName($amount->event_amount_type_id) == $name_short)
				{
					return $amount->amount;
				}
			}
			return 0;
		}
		
		/**
		 * Returns this transaction's status. will be one of the STATUS_* constants
		 *
		 * @return int
		 */
		public function getStatus()
		{
			return $this->status;
		}

		/**
		 * Returns this transaction's status as the db model string.  Only used by the front end during transaction table rendering
		 * 
		 * @depricated
		 * @return string
		 */
		public function getStatusString()
		{
			foreach ($this->transaction_registers as $tr)
			{
				return $tr->transaction_status;
			}
			return $this->event->event_status;
		}
		
		/**
		 * Returns the trace for this transaction
		 *
		 * @return string
		 */
		public function getTrace()
		{
			return $this->event->configuration_trace_data;
		}

		/**
		 * Add an amount to this Transaction.  $type should be one of the AMOUNT_* constants
		 *
		 * @todo put observer here
		 * @param int $amount
		 * @param string $type
		 */
		public function addAmount($amount, $type)
		{
			if ($this->status !== self::STATUS_SCHEDULED)
			{
				throw new ECash_Transactions_TransactionException("Cannot add amounts to a transaction once it has moved beyond scheduled status!");
			}

			$transaction_amount = ECash::getFactory()->getModel('EventAmount');
			

			$transaction_amount->event_amount_type_id = $this->transaction_amount_types->toId($type);
			$transaction_amount->amount = $amount;

			$transaction_amount->application_id = $this->application->getId();
			$transaction_amount->company_id = $this->application->getCompanyId();

			$transaction_amount->date_modified = time();
			$transaction_amount->date_created = time();

			/* I don't think these should be set
			$transaction_amount->transaction_register_id = 0; // is this obsolete?
			$transaction_amount->num_reattempt = 0; // ???
			*/

			$this->transaction_amounts[] = $transaction_amount;
		}

		/**
		 * Given a schedule's balances, and an amount, distribute the amount
		 * over the balances, giving the following priority: Fees, Interest, Principal
		 * @param int $amount
		 * @param array $balances
		 *
		 * @return remaining balances after this transaction
		 */
		public function addDistributedAmounts($amount, $balances)
		{
			// First amount goes towards outstanding fees
			if ($amount > 0 && isset($balances[self::AMOUNT_TYPE_FEE]))
			{
				$fee_amount = min($balances[self::AMOUNT_TYPE_FEE], $amount);
				$amount -= $fee_amount;
				$this->addAmount($fee_amount, self::AMOUNT_TYPE_FEE);
				$balances[self::AMOUNT_TYPE_FEE] -= $fee_amount;
			}

			// Second (if remaining) amount goes towards outstanding interest
			if ($amount > 0 && isset($balances[self::AMOUNT_TYPE_SERVICE_CHARGE]))
			{
				$int_amount = min($balances[self::AMOUNT_TYPE_SERVICE_CHARGE], $amount);
				$amount -= $int_amount;
				$this->addAmount($int_amount, self::AMOUNT_TYPE_SERVICE_CHARGE);
				$balances[self::AMOUNT_TYPE_SERVICE_CHARGE] -= $int_amount;
			}

			// Any remaining amounts are applied towards the principal
			if ($amount > 0)
			{
				$this->addAmount($amount, self::AMOUNT_TYPE_PRINCIPAL);
				$balances[self::AMOUNT_TYPE_PRINCIPAL] -= $amount;
			}

			return $balances;
		}

		/**
		 * Returns total amount for this transaction
		 */
		public function getTotalAmount()
		{
			$total_amount = 0;
			foreach ($this->transaction_amounts as $amount)
			{
				$total_amount += $amount->amount;
			}
			return $total_amount;
		}

		public function getAmounts()
		{
			$amount_summary = array();

			foreach ($this->transaction_amounts as $amount)
			{
				$name_short = $this->transaction_amount_types->toName($amount->event_amount_type_id);
				if (!isset($amount_summary[$name_short]))
				{
					$amount_summary[$name_short] = 0;
				}
				$amount_summary[$name_short] += $amount->amount;
			}

			return $amount_summary;
		}

		/**
		 * Moves the transaction to pending status.
		 */
		public function setPending()
		{
			$this->setStatus(self::STATUS_PENDING);
						
			if (count($this->transaction_registers) == 0)
			{
			
				$event_transaction_list = ECash::getFactory()->getReferenceList('EventTransaction');
				$event_transaction_list->loadBy(array('event_type_id' => $this->event->event_type_id));

				$amounts = $this->getAmounts();

				$transaction_type = ECash::getFactory()->getReferenceModel('TransactionType');
				
				foreach ($event_transaction_list as $event_transaction)
				{						
					$tr = ECash::getFactory()->getModel('TransactionRegister');
					$tr->date_created = time();
					$tr->company_id = $this->application->getCompanyId();
					$tr->application_id = $this->application->getId();
					$tr->modifying_agent_id = ECash::getAgent()->getAgentId();
					$tr->transaction_type_id = $event_transaction->transaction_type_id;

					$tt = $transaction_type[$event_transaction->transaction_type_id];

					$amount_observer = new DB_Models_ColumnObserver_1($tr, 'transaction_register_id');

					if ($event_transaction_list->valid())
					{
						//there should be two register records
						foreach ($this->transaction_amounts as $amount)
						{
							$name_short = $this->transaction_amount_types->toName($amount->transaction_amount_type_id);

							$affects_principal = ($tt->affects_principal == 'yes');
							$is_principal = ($name_short == self::AMOUNT_TYPE_PRINCIPAL);
							
							if ($affects_principal == $is_principal)
							{
								$tr->amount += $amount->amount;
								$amount->date_modified = time();
								$amount_observer->addTarget($amount);
							}
						}
					}
					else
					{
						//there is only one register record
						$tr->amount = $amounts[self::AMOUNT_TYPE_PRINCIPAL] + $amounts[self::AMOUNT_TYPE_SERVICE_CHARGE] + $amounts[self::AMOUNT_TYPE_FEE];
						foreach ($this->transaction_amounts as $amount)
						{
							$amount->date_modified = time();
							$amount_observer->addTarget($amount);							
						}
					}

					//attach observer for id
					$schedule_observer = new DB_Models_ColumnObserver_1($this->event, 'event_schedule_id');
					$schedule_observer->addTarget($tr);

				
					$this->transaction_registers[] = $tr;
				}

			}

			//for update
			foreach ($this->transaction_registers as $transaction_register)
			{
				$transaction_register->date_modified = time();
				$transaction_register->transaction_status = ECash_Models_TransactionRegister::STATUS_PENDING;
				$transaction_register->date_effective = $this->event->date_effective;
			}

			$this->event->event_status = ECash_Models_EventSchedule::STATUS_REGISTERED;
		}
		
		/**
		 * Moves transaction to failed status.
		 *
		 */
		public function setFailed()
		{
			$this->setStatus(self::STATUS_FAILED);

			foreach ($this->transaction_registers as $transaction_register)
			{			
				$transaction_register->transaction_status = ECash_Models_TransactionRegister::STATUS_FAILED;
				//delete associated transaction_ledger rows
				if (!empty($this->transaction_ledgers[$transaction_register->transaction_register_id]))
				{
					$this->transaction_ledgers[$transaction_register->transaction_register_id]->setDeleted(TRUE);
				}
			}
		}

		/**
		 * @depricated
		 * @TODO discuss how assessments and adjustments can be immediately completed (or done intellegently by the schedule)
		 */
		public function forceComplete()
		{
			$transaction_type_model = self::$event_type_list[$this->type];

			if ($transaction_type_model->clearing_type !== ECash_Models_Reference_TransactionType::CLEARING_ACCRUED_CHARGE)
			{
				throw new ECash_Transactions_TransactionException("Clearing type: {$transaction_type_model->clearing_type}.  Only accrued charges may be forced into complete status.");
			}
			//pretend this was pending
			$this->status = self::STATUS_PENDING;
			//force it to complete
			$this->setComplete();
		}

		/**
		 * Moves transaction to complete status. Posts the transaction to the ledger.
		 */
		public function setComplete()
		{
			$this->setStatus(self::STATUS_COMPLETE);

			$needs_ledgers = count($this->transaction_ledgers) == 0;

			foreach ($this->transaction_registers as $transaction_register)
			{
				$transaction_register->transaction_status = ECash_Models_TransactionRegister::STATUS_COMPLETE;

				if ($needs_ledgers)
				{
					// Create the ledger row for each register
					$ledger = ECash::getFactory()->getModel('TransactionLedger');

					$ledger->date_created = time();
					$ledger->company_id = $this->application->getCompanyId();
					$ledger->application_id = $this->application->getId();
					$ledger->transaction_type_id = $transaction_register->transaction_type_id;
					$ledger->amount = $transaction_register->amount;
					$ledger->date_posted = time();
					$ledger->source_id = $this->event->source_id;

					//attach observer for id
					$register_observer = new DB_Models_ColumnObserver_1($transaction_register, 'transaction_register_id');
					$register_observer->addTarget($ledger);

					$this->transaction_ledgers[] = $ledger;
				}
			}

			foreach ($this->transaction_ledgers as $ledger)
			{
				$ledger->date_modified = time();
			}
		}

		/**
		 * Suspends the transaction.
		 */
		public function setSuspended()
		{
			$this->setStatus(self::STATUS_SUSPENDED);
			$this->event->event_status = ECash_Models_EventSchedule::STATUS_SUSPENDED;
		}

		/**
		 * Convenience method: Is the transaction in a scheduled status
		 *
		 * @return bool
		 */
		public function isScheduled() { return ($this->status === self::STATUS_SCHEDULED); }

		/**
		 * Convenience method: Is the transaction in a pending status
		 *
		 * @return bool
		 */
		public function isPending() { return ($this->status === self::STATUS_PENDING); }

		/**
		 * Convenience method: Is the transaction in a completed status
		 *
		 * @return bool
		 */
		public function isComplete() { return ($this->status === self::STATUS_COMPLETE); }

		/**
		 * Convenience method: Is the transaction in a suspended state
		 *
		 * @return bool
		 */
		public function isSuspended() { return ($this->status === self::STATUS_SUSPENDED); }

		/**
		 * Convenience method: Is the transaction failed
		 *
		 * @return bool
		 */
		public function isFailed() { return ($this->status === self::STATUS_FAILED); }


		/**
		 * Returns a list of models that need to be saved (insert or update).
		 * This is only used by ECash_Transactions_Schedule, as a mechanism to most
		 * efficiently insert/update multiple transactions at the same time.
		 *
		 * @return array
		 */
		public function getModifiedModels()
		{
			$modified = array();
			$register_observers = array();
			$observer = NULL;

			if ($this->event instanceof DB_Models_WritableModel_1 && $this->event->isAltered())
			{
				$modified[] = $this->event;
			}

			foreach ($this->transaction_registers as $transaction_register)
			{
				if ($transaction_register->isAltered())
				{
					$modified[] = $transaction_register;
				}
			}

			foreach ($this->transaction_ledgers as $transaction_ledger)
			{
				if ($transaction_ledger->isAltered())
				{
					$modified[] = $this->transaction_ledger;
				}
			}

			foreach ($this->transaction_amounts as $transaction_amount)
			{
				if ($transaction_amount->isAltered())
				{
					if ($schedule_observer !== NULL)
					{
						$schedule_observer->addTarget($transaction_amount);
					}
					if ($observer !== NULL)
					{
						$observer->addTarget($transaction_amount);
					}
					$modified[] = $transaction_amount;
				}
			}

			return $modified;
		}

		/**
		 * this seems a bit too dangerous ATM.  It should probably
		 * examine the status of the register and ledger entries
		 * before deleting (smart delete)
		 */
		public function delete()
		{
			if ($this->event instanceof DB_Models_WritableModel_1) $this->event->setDeleted(TRUE);
			if ($this->transaction_register instanceof DB_Models_WritableModel_1) $this->transaction_register->setDeleted(TRUE);
			if ($this->transaction_ledger instanceof DB_Models_WritableModel_1) $this->transaction_ledger->setDeleted(TRUE);

			foreach ($this->transaction_amounts as $transaction_amount)
			{
				if ($transaction_amount instanceof DB_Models_WritableModel_1) $transaction_amount->setDeleted(TRUE);
			}
		}

		/**
		 * A pass-through to the model's isStored().
		 *
		 * Used to determine if this transaction is new (from the schedule builder) or already in the database
		 *
		 * @return void
		 */
		public function isStored()
		{
			return $this->event->isStored();
		}

		/**
		 * Returns the event_schedule_id for this transaction. If the model has not been
		 * saved it will throw an exception.
		 *
		 * @return int
		*/
		public function getTransactionId()
		{
			if ($this->event->isStored())
			{
				return $this->event->event_schedule_id;
			}
			else
			{
				throw new ECash_Transactions_TransactionException("No schedule row is known!");
			}
		}
		
		/**
		 * Returns the transaction_register_id for this transaction. If the model has not been
		 * saved it will throw an exception.
		 *
		 * @depricated should not be used unless neccessary
		 * @return int
		*/
		public function getRegisterId()
		{
			if ($this->transaction_register->isStored())
			{
				return $this->transaction_register->transaction_register_id;
			}
			else
			{
				throw new ECash_Transactions_TransactionException("No transaction_register row is known!");
			}
		}

		/**
		 * Returns the transaction_ledger_id for this transaction. May need to save the model in order
		 * to get this value.
		 *
		 * @depricated should not be used unless neccessary
		 * @return int
		 */
		public function getLedgerId()
		{
			if ($this->transaction_ledger instanceof DB_Models_WritableModel_1)
			{
				if (!$this->transaction_ledger->isStored()) $this->transaction_ledger->save();
			}
			else
			{
				throw new ECash_Transactions_TransactionException("No transaction_ledger row is known!");
			}

			return $this->transaction_ledger->transaction_ledger_id;
		}

		/**
		 * validation for state changes.
		 *
		 * @var int[][]
		 */
		protected static $valid_transitions = array(
			self::STATUS_SCHEDULED => array(
				self::STATUS_PENDING,
				self::STATUS_SUSPENDED
			),
			self::STATUS_PENDING => array(
				self::STATUS_FAILED,
				self::STATUS_COMPLETE
			),
			self::STATUS_SUSPENDED => array(
				self::STATUS_SCHEDULED
			),
			self::STATUS_COMPLETE => array(),
			self::STATUS_FAILED => array()

		);

		/**
		 * Changes the transactions status to $status. Will throw an exception if the transition is invalid.
		 *
		 * @param int $status
		 */
		protected function setStatus($status)
		{
			if (!in_array($status, self::$valid_transitions[$this->status]))
			{
				throw new ECash_Transactions_TransactionException("Invalid status transition.");
			}
			$this->status = $status;
		}

		/**
		 * Changes status row values to Transaction Statuses
		 *
		 * @param string $db_register_status
		 * @param string $db_transaction_status
		 */
		public static function toStatusId($db_register_status, $db_transaction_status = NULL)
		{			
			if ($db_register_status !== NULL)
			{
				$db_register_status = strtolower($db_register_status);
				switch ($db_register_status)
				{
					case ECash_Models_TransactionSchedule::EVENT_STATUS_REGISTERED:
						//do nothing, fall through to the next switch
						break;

					case ECash_Models_TransactionSchedule::EVENT_STATUS_SCHEDULED:
						return self::STATUS_SCHEDULED;

					case ECash_Models_TransactionSchedule::EVENT_STATUS_SUSPENDED:
						return self::STATUS_SUSPENDED;

					default:
						throw new Exception("No such status: '{$db_register_status}'");
				}
			}
			
			$db_transaction_status = strtolower($db_transaction_status);
			switch ($db_transaction_status)
			{
				case ECash_Models_TransactionRegister::STATUS_PENDING:
					return self::STATUS_PENDING;

				case ECash_Models_TransactionRegister::STATUS_FAILED:
					return self::STATUS_FAILED;
					
				case ECash_Models_TransactionRegister::STATUS_COMPLETE:
					return self::STATUS_COMPLETE;
					
				default:
					throw new Exception("No such status: '{$db_transaction_status}'");
			}
		}
		

		/**
		 * Returns the external business object for a transaction (ach or ecld currently)
		 *
		 * The type of object to return is based on the clearing type of that transaction
		 * 
		 * @return ECash_Transactions_Transaction_Component
		 */
		public function getExternalInfo()
		{
			if (empty($this->external_info))
			{
				switch ($this->type_model->clearing_type)
				{
					case ECash_Models_Reference_TransactionType::CLEARING_ACH:
						$this->external_info = new ECash_Transactions_Transaction_Ach($this);
						break;
						
					case ECash_Models_Reference_TransactionType::CLEARING_QUICKCHECK:
						$this->external_info = new ECash_Transactions_Transaction_Ecld($this);
						break;
				}
			}
			
			return $this->external_info;
		}
		
		/**
		 * Returns the previous status for this transaction.
		 * 
		 * This will operate according to the database. If you change a 
		 * transaction status and do not save it the return value for this 
		 * function will NOT change.
		 *
		 * @return string
		 */
		public function getPreviousStatus()
		{
			if ($this->getTransactionId())
			{
				$status_history = ECash::getFactory()->getModel('TransactionHistoryList');
				/** @TODO this needs to change (in DDL too) to transaction_id */
				$status_history->loadBy(array('transaction_register_id' => $this->getTransactionId()));
				
				return $status_history->current()->status_before;
			}
		}

		/**
		 * Converted from scheduling.func.php Analyze_Schedule (pending_end)
		 *
		 * @return int unix timestamp of pending end date
		 */
		public function getPendingEndDate()
		{
			//non-scheduled items don't have a pending end date (and should use DateEffective instead)
			if (!$this->isScheduled())
			{
				return NULL;
			}

			$end_date = NULL;
			$days = $this->type_model->pending_period;
			
			if ($this->Type == ECash_Transactions_TransactionType::TYPE_QUICK_CHECK_PAYMENT)
			{
				$end_date = $this->getDate();
			}
			else
			{
				$end_date = $this->getDateEffective();
			}

			if ($this->type_model->period_type == ECash_Models_Reference_TransactionType::PERIOD_TYPE_BUSINESS)
			{
				$normalizer = ECash::getFactory()->getDateNormalizer();
				return $normalizer->advanceBusinessDays($end_date, $days);
			}

			//calendar days
			return strtotime('+{$days} days', $end_date);
		}

		public function isReattempt()
		{
			return (!empty($this->event->origin_id));
		}

		public function getShifted()
		{
			return (!empty($this->event->is_shifted));
		}
	}

?>
