<?php
	/**
	 * @package DB
	 */

	/**
	 * A query that is executable
	 * This allows a function to return a prepar(ed/able) query but not
	 * require a database connection (i.e., abstract the construction of
	 * a query from its execution)
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class DB_Query_1
	{
		/**
		 * @var string
		 */
		protected $query;
		
		/**
		 * @var array
		 */
		protected $args;

		/**
		 * @param string $query
		 * @param array $args
		 */
		public function __construct($query, array $args = NULL)
		{
			$this->query = $query;
			$this->args = $args;
		}

		/**
		 * @param DB_Database_1 $db
		 * @return PDOStatement
		 */
		public function execute(DB_IConnection_1 $db)
		{
			if ($this->args)
			{
				$st = $db->prepare($this->query);
				$st->execute($this->args);
			}
			else
			{
				$st = $db->query($this->query);
			}

			return $st;
		}
	}

?>