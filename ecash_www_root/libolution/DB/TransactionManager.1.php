<?php
	/**
	 * @package DB
	 */

	/**
	 * Helps manage transactions when the current state of a transaction is unknown
	 *
	 * This class helps manage transaction within core functions that begin a
	 * transaction themselves, but also may be called within a transaction that was
	 * begun by a higher-level function. In this case, you MUST reserve the right
	 * to begin a transaction for functions that absolutely must, and the very
	 * top-level methods in your application (APIs, etc.).
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class DB_TransactionManager_1
	{
		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * Indicates whether we're borrowing a transaction
		 *
		 * @var bool
		 */
		protected $borrowed = FALSE;

		/**
		 * @param DB_IConnection_1 $db
		 */
		public function __construct(DB_IConnection_1 $db)
		{
			$this->db = $db;
		}

		/**
		 * Begins a transaction if one hasn't already been started
		 * @return void
		 */
		public function beginTransaction()
		{
			if (!$this->db->getInTransaction())
			{
				$this->borrowed = FALSE;
				$this->db->beginTransaction();
			}
			else
			{
				$this->borrowed = TRUE;
			}
		}

		/**
		 * Commits the current transaction if we own it, otherwise do nothing
		 * @return void
		 */
		public function commit()
		{
			if (!$this->borrowed)
			{
				$this->db->commit();
			}
		}

		/**
		 * Rolls back the current transaction if we own it
		 * If we don't own the transaction, an exception is thrown to let
		 * the owner know that their transaction was aborted
		 *
		 * @throws DB_TransactionAbort_1
		 * @return void
		 */
		public function rollBack()
		{
			// if we're being called from a transaction that's
			// been aborted (most likely from another instance
			// of ourselves), then don't rock the boat
			if ($this->db->getInTransaction())
			{
				$this->db->rollBack();
			}

			if ($this->borrowed)
			{
				throw new DB_TransactionAbortedException_1();
			}
		}
	}

?>