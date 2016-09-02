<?php

/**
 * A DB_IConnection_1 adapter for the TSS MySQL_4 database class
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_MySQL4Adapter_1 implements DB_IConnection_1
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
	 * @var bool
	 */
	protected $in_transaction = FALSE;

	/**
	 * @param MySQL_4 $db
	 * @param string $schema
	 */
	public function __construct(MySQL_4 $db, $schema)
	{
		$this->db = $db;
		$this->schema = $schema;
	}

	/**
	 * Returns the underlying MySQL_4 instance
	 *
	 * @return MySQL_4
	 */
	public function getMySQL4()
	{
		return $this->db;
	}

	/**
	 * Runs a query
	 *
	 * @param string $query
	 * @return DB_IStatement_1
	 */
	public function query($query)
	{
		return DB_MySQL4StatementAdapter_1::fromQuery($this->db, $this->schema, $query);
	}

	/**
	 * Executes a result-less statement
	 *
	 * @param string $query
	 * @return int
	 */
	public function exec($query)
	{
		try
		{
			$this->db->Query($this->schema, $query);
			return $this->db->Affected_Row_Count();
		}
		catch (Exception $e)
		{
			throw new DB_MySQL4AdapterException_1($e->getMessage());
		}
	}

	/**
	 * Prepares a query
	 *
	 * @param string $query
	 * @return DB_IStatement_1
	 */
	public function prepare($query)
	{
		return DB_MySQL4StatementAdapter_1::fromPrepare($this, $this->schema, $query);
	}

	/**
	 * Begins a transaction
	 *
	 * @return void
	 */
	public function beginTransaction()
	{
		if ($this->in_transaction)
		{
			throw new DB_MySQL4AdapterException_1('Transaction already in progress');
		}
		$this->exec("BEGIN TRANSACTION");
		$this->in_transaction = TRUE;
	}

	/**
	 * Indicates whether the connection is in a transaction
	 *
	 * @return bool
	 */
	public function getInTransaction()
	{
		return $this->in_transaction;
	}

	/**
	 * Commits the current transaction
	 *
	 * @return void
	 */
	public function commit()
	{
		if (!$this->in_transaction)
		{
			throw new DB_MySQL4AdapterException_1('Transaction has not been started');
		}
		$this->exec('COMMIT');
		$this->in_transaction = FALSE;
	}

	/**
	 * Rollsback the current transaction
	 *
	 * @return void
	 */
	public function rollBack()
	{

		if (!$this->in_transaction)
		{
			throw new DB_MySQL4AdapterException_1('Transaction has not been started');
		}
		$this->exec('ROLLBACK');
		$this->in_transaction = FALSE;
	}

	/**
	 * Returns the last auto-increment ID
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->db->Insert_Id();
	}

	/**
	 * Quotes a string
	 *
	 * @param string $string
	 * @return string
	 */
	public function quote($string)
	{
		// @todo support this in MySQL_4?
		return "'".mysql_escape_string($string)."'";
	}

	/**
	 * Quotes a database object (like a table name, etc.)
	 *
	 * @param string $string
	 * @return string
	 */
	public function quoteObject($string)
	{
		// @todo support this in MySQL_4?
		return '`'.$string.'`';
	}
}

?>
