<?php

/**
 * A DB_IStatement_1 for MySQL_4 results
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_MySQL4StatementAdapter_1 implements IteratorAggregate, DB_IStatement_1
{
	/**
	 * @var MySQL_4
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $schema;

	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @var resource
	 */
	protected $result;

	/**
	 * @var int
	 */
	protected $last_row_count;

	/**
	 * Executes a query and returns a statement adapter for the result
	 *
	 * @param MySQL_4 $db
	 * @param string $schema
	 * @param string $query
	 * @return DB_MySQL4StatementAdapter_1
	 */
	public static function fromQuery(MySQL_4 $db, $schema, $query)
	{
		$st = new self($db, $schema);
		$st->runQuery($query);

		return $st;
	}

	/**
	 * Creates a statement adapter as a prepared query
	 *
	 * @param DB_MySQL4Adapter_1 $db
	 * @param string $schema
	 * @param string $query
	 * @return DB_MySQL4StatementAdapter_1
	 */
	public static function fromPrepare(DB_MySQL4Adapter_1 $db, $schema, $query)
	{
		$st = new self($db->getMySQL4(), $schema);
		$st->prepare = new DB_EmulatedPrepare_1($db, $query);

		return $st;
	}

	/**
	 * Frees any outstanding result
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if ($this->result)
		{
			$this->db->Free_Result($this->result);
			$this->result = NULL;
		}
	}

	/**
	 * Executes the statement with the given arguments
	 *
	 * @param array $args
	 * @return bool
	 */
	public function execute(array $args = NULL)
	{
		if (!$this->prepare)
		{
			throw new DB_MySQL4AdapterException_1('No statement was prepared');
		}

		if ($this->result)
		{
			$this->db->Free_Result($this->result);
			$this->result = NULL;
		}

		$this->runQuery($this->prepare->getQuery($args));
		return TRUE;
	}

	/**
	 * Fetches a single row
	 *
	 * @param int $fetch_mode
	 * @return mixed
	 */
	public function fetch($fetch_mode = self::FETCH_ASSOC)
	{
		if ($this->result === NULL)
		{
			throw new DB_MySQL4AdapterException_1('No result');
		}

		switch ($fetch_mode)
		{
			case self::FETCH_ASSOC:
				return $this->db->Fetch_Array_Row($this->result);
			case self::FETCH_OBJ:
				return $this->db->Fetch_Object_Row($this->result);
			case self::FETCH_ROW:
				return $this->db->Fetch_Row($this->result);
			case self::FETCH_BOTH:
				// @todo support this in mysql_4?
				return mysql_fetch_array($this->result);
		}

		throw new DB_MySQL4AdapterException_1('Invalid fetch type');
	}

	/**
	 * Fetches all rows
	 *
	 * @param int $fetch_mode
	 * @return array
	 */
	public function fetchAll($fetch_mode = self::FETCH_ASSOC)
	{
		$rows = array();
		while (($row = $this->fetch($fetch_mode)) !== FALSE)
		{
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Returns the number of affected rows
	 *
	 * @return int
	 */
	public function rowCount()
	{
		return $this->last_row_count;
	}

	/**
	 * Gets an external iterator
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new DB_StatementIterator_1($this);
	}

	/**
	 * @param MySQL_4 $db
	 * @param string $schema
	 */
	private function __construct(MySQL_4 $db, $schema)
	{
		$this->db = $db;
		$this->schema = $schema;
	}

	/**
	 * Runs a query
	 *
	 * @param string $query
	 * @return void
	 */
	protected function runQuery($query)
	{
		try
		{
			$this->result = $this->db->Query($this->schema, $query);
			$this->last_row_count = $this->db->Affected_Row_Count();
		}
		catch (Exception $e)
		{
			throw new DB_MySQL4AdapterException_1($e->getMessage());
		}
	}
}

?>