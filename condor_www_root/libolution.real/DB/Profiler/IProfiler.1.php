<?php

	/**
	 * Database profiler for libolution
	 * @author Jordan Raub <jordan.raub@dataxltd.com>
	 */
	interface DB_Profiler_IProfiler_1
	{
		/**
		 * signify the start of a transaction
		 */
		public function beginTransaction();

		/**
		 * show the transaction was committed
		 */
		public function commit();

		/**
		 * show a transaction was rolled back
		 *
		 */
		public function rollBack();

		/**
		 * start a query timer
		 *
		 * NOTE: this function does not log anything.
		 * it just saves the start time of a specific query
		 *
		 * @param string|DB_IStatement_1 $query
		 * @param array $args
		 */
		public function startQuery($query, array $args = array());

		/**
		 * end the query timer and log the resulting elapsed time and the query
		 *
		 * @param string|DB_IStatement_1 $query
		 * @param array $args
		 */
		public function endQuery($query, array $args = array());

		/**
		 * show the start of a script
		 *
		 */
		public function startScript();

		/**
		 * show the start of a script
		 *
		 */
		public function endScript();
	}
?>