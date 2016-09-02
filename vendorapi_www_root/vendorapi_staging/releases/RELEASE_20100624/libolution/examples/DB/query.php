<?php
	/**
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 * @package Examples
	 */

	/**
	 * In this simple example, we'll create an extended version of a class that's only
	 * purpose will be to add a date_modified to the update query. If we aren't using prepared
	 * statements this is easy: simply refactor the query building into a separate method
	 * that returns the query as a string. Descendant classes can then add to the query
	 * as they feel fit. With prepared statements, however, this becomes more troublesome
	 * since whatever will be executing the statement needs both the query string and
	 * its arguments. It is this precise problem that DB_Query_1 attempts to solve.
	 *
	 * By encapsulating both the query string and the arguments into a combined object,
	 * you can still abstract your query building into a separate method, but instead of
	 * returning a string, you return an instance of DB_Query_1.
	 */
	
	class A
	{
		protected $db;
		protected $name;
		protected $value;
		
		public function __construct(DB_IConnection_1 $db, $name)
		{
			$this->db = $db;
			$this->name = $name;
		}
		
		public function setValue($value)
		{
			$this->value = $value;
		}
		
		public function save()
		{
			// get the update query and execute it
			$q = $this->buildUpdate($this->name, $this->value);
			$q->execute($this->db);
		}
		
		/**
		 * @return DB_Query_1
		 */
		protected function buildUpdate($name, $value)
		{
			$query = "
				UPDATE table
				SET value = ?
				WHERE name = ?
			";
			
			// this encapsulates our query and arguments,
			// but without executing the statement
			return new DB_Query_1(
				$query,
				array($value, $name)
			);
		}
	}
	
	class B extends A
	{
		/**
		 * Also updates date_modified
		 * @return DB_Query_1
		 */
		protected function buildUpdate($name, $value)
		{
			$query = "
				UPDATE table
				SET value = ?,
					date_modified = ?
				WHERE name = ?
			";
			
			// now we can add additional arguments
			// without changes to the code that will
			// prepare and execute the statement
			return new DB_Query_1(
				$query,
				array($value, time(), $name)
			);
		}
	}
	
?>