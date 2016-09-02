<?php
	/**
	 * base class for all multi-row models. This class allows iteration of model wrappers
	 * without using unnecessary memory, or iterating the set more times than necessary
	 *
	 * @author Todd Huish <todd.huish@sellingsource.com>
	 * @package DB.Models
	 */
	interface DB_Models_IIterativeModel_1 extends Iterator, Countable
	{
		/**
		 * The child class is expected to override this method with one that
		 * returns a string of the class name that will be used.
		 * @return string
		 */
		public function getClassName();

		/**
		 * Returns the raw data that the cursor is currently pointing at.
		 *
		 * @return array
		 */
		public function currentRawData();

		/**
		 * Cycles the resultset and produces a DB_Models_ModelList_1
		 *
		 * @return DB_Models_ModelList_1
		 */
		public function toList();

		/**
		 * Cycles the resultset and produces an array
		 *
		 * @return array
		 */
		public function toArray();
	}
?>