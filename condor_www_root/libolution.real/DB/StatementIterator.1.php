<?php

	/**
	 * An Iterator adapter for DB_IStatement_1
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class DB_StatementIterator_1 implements Iterator
	{
		/**
		 * @var DB_IStatement_1
		 */
		protected $st;

		/**
		 * @var mixed
		 */
		protected $current;

		/**
		 * @var int
		 */
		protected $key = 0;

		/**
		 * @param DB_IStatement_1 $st
		 */
		public function __construct(DB_IStatement_1 $st)
		{
			$this->st = $st;
		}

		/**
		 * Returns the underlying statement object
		 *
		 * @return DB_IStatement_1
		 */
		public function getStatement()
		{
			return $this->st;
		}

		/**
		 * Advances to the first item; iterator CANNOT be rewound
		 * @return void
		 */
		public function rewind()
		{
			$this->current = $this->st->fetch();
			$this->key = 0;
		}

		/**
		 * Returns the current row
		 * @return void
		 */
		public function current()
		{
			return $this->current;
		}

		/**
		 * Returns the current key
		 * @return void
		 */
		public function key()
		{
			return $this->key;
		}

		/**
		 * Advances to the next row
		 * @return void
		 */
		public function next()
		{
			$this->current = $this->st->fetch();
			$this->key++;
		}

		/**
		 * Indicates whether a row is available
		 * @return void
		 */
		public function valid()
		{
			return ($this->current !== FALSE);
		}
	}

?>
