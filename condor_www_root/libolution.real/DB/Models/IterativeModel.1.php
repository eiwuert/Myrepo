<?php
	/**
	 * @package DB.Models
	 */

	/**
	 * base class for all multi-row models. This class allows iteration of model wrappers
	 * without using unnecessary memory, or iterating the set more times than necessary
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	abstract class DB_Models_IterativeModel_1 extends DB_Models_DatabaseModel_1 implements Iterator, Countable
	{
		/**
		 * The child class is expected to write to this
		 * @var PDOStatement
		 */
		protected $statement = NULL;

		/**
		 * Contains the current array fetched from the statement object
		 * @var array
		 */
		protected $current = NULL;

		/**
		 * The child class is expected to overload this method, returning an object
		 * that extends ModelBase. This method will be called anytime the iterator
		 * is attempting to factory a model object from the selected data
		 *
		 * @param array $db_row
		 *
		 * @return DB_Models_DatabaseModel
		 */
		protected abstract function createInstance(array $db_row);

		/**
		 * The child class is expected to override this method with one that
		 * returns a string of the class name that will be used.
		 * @return string
		 */
		public abstract function getClassName();

		/**
		 * Returns the number of rows in the statement.
		 *
		 * @todo MAKE SURE THIS WORKS IN PHP 5.2.1
		 * @return int
		 */
		public function count()
		{
			if ($this->statement === NULL)
				throw new Exception("No statement available!");

			return $this->statement->rowCount();
		}

		/**
		 * Resets the cursor
		 * @return void
		 */
		public function rewind()
		{
			if ($this->statement === NULL)
				throw new Exception("No statement available!");

			$this->current = $this->statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST);
		}

		/**
		 * Returns the item that the cursor is currently pointing at.
		 *
		 * @return DB_Models_ModelBase
		 */
		public function current()
		{
			if ($this->statement === NULL)
				throw new Exception("No statement available!");

			return $this->current === FALSE ? NULL : $this->createInstance($this->current);
		}
		
		/**
		 * Returns the raw data that the cursor is currently pointing at.
		 *
		 * @return array
		 */
		public function currentRawData()
		{
			if ($this->statement === NULL)
				throw new Exception("No statement available!");
			
			return $this->current === FALSE ? NULL : $this->current;
		}

		/**
		 * Returns the current 'key'. Not implemented currently.
		 *
		 * @return 0
		 */
		public function key()
		{
			if ($this->statement === NULL)
				throw new Exception("No statement available!");

			return 0;
		}

		/**
		 * Fetches the next item in the database, and updates the cursor
		 *
		 * @return DB_Models_ModelBase
		 */
		public function next()
		{
			if ($this->statement === NULL)
				throw new Exception("No statement available!");

			$this->current = $this->statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);

			return $this->current === FALSE ? NULL : $this->createInstance($this->current);
		}

		/**
		 * Checks to see if we're at the end of the list
		 *
		 * @return bool
		 */
		public function valid()
		{
			if ($this->statement === NULL)
				throw new Exception("No statement available!");

			return ($this->current !== FALSE);
		}

		/**
		 * Cycles the resultset and produces a DB_Models_ModelList_1
		 *
		 * @return DB_Models_ModelList_1
		 */
		public function toList()
		{
			$list = new DB_Models_ModelList_1($this->getClassName(), $this->getDatabaseInstance());
			foreach ($this as $instance)
			{
				$list->add($instance);
			}
			return $list;
		}

		/**
		 * Cycles the resultset and produces an array
		 *
		 * @return array
		 */
		public function toArray()
		{
			$a = array();

			foreach ($this->statement as $row)
			{
				$a[] = $this->createInstance($row);
			}

			return $a;
		}
	}
?>