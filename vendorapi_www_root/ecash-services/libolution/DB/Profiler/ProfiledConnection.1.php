<?php
/**
 * @package DB
 */

/**
 * Database profiler for libolution
 * @author Jordan Raub <jordan.raub@dataxltd.com>
 */
class DB_Profiler_ProfiledConnection_1
	extends Object_1
	implements DB_IConnection_1
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db = null;

	/**
	 * @var DB_IProfiler_1
	 */
	protected $profiler = null;

	/**
	 * ctor
	 *
	 * @param DB_IConnection_1 $db
	 * @param IProfiler_1 $profiler
	 */
	public function __construct(DB_IConnection_1 $db, DB_Profiler_IProfiler_1 $profiler)
	{
		$this->db = $db;
		$this->profiler = $profiler;
	}

	/**
	 * query
	 *
	 * @param string $query
	 * @return mixed
	 */
	public function query($query)
	{
		$this->profiler->startQuery($query);
		$result = $this->db->query($query);
		$this->profiler->endQuery($query);

		return $result;
	}

	/**
	 * Prepares a query for subsequent execution
	 *
	 * @param string $query
	 * @return DB_IStatement_1
	 */
	public function prepare($query)
	{
		/**
		 * can't use this because a pdo statement can't be instantiated
		 * except by a pdo class... this will just redecorate fin anyway
		 *
		 * $profiledStatement = $this->db->getStatementClass();
		 */
		return new DB_Profiler_ProfiledStatement_1(
			$query,
			$this->db->prepare($query),
			$this->profiler
		);
	}

	/**
	 * Executes a resultset-less query
	 *
	 * @param string $query
	 * @return int
	 */
	public function exec($query)
	{
		$this->profiler->startQuery($query);
		$result = $this->db->exec($query);
		$this->profiler->endQuery($query);

		return $result;
	}

	/**
	 * signify the start of a transaction
	 */
	public function beginTransaction()
	{
		$this->profiler->beginTransaction();
		$this->db->beginTransaction();
	}

	/**
	 * show the transaction was committed
	 */
	public function commit()
	{
		$this->db->commit();
		$this->profiler->commit();
	}

	/**
	 * show a transaction was rolled back
	 */
	public function rollBack()
	{

		$this->db->rollBack();
		$this->profiler->rollBack();
	}

	/**
	 * Indicates whether the connection is in a transaction
	 * @return bool
	 */
	public function getInTransaction()
	{
		return $this->db->getInTransaction();
	}

	/**
	 * Returns the last auto-increment ID
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->db->lastInsertId();
	}

	/**
	 * Quotes a string according to the connection's requirements
	 *
	 * @param string $string
	 * @return string
	 */
	public function quote($string)
	{
		return $this->db->quote($string);
	}

	/**
	 * Quotes a database object (table name, etc.)
	 *
	 * @param string $string
	 */
	public function quoteObject($string)
	{
		return $this->db->quoteObject($string);
	}

	/**
	 * Allows calling any defined method on the underlying connection
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		$reflect = new ReflectionObject($this->db);
		try
		{
			$reflect->getMethod($method);
		}
		catch(ReflectionException $e)
		{
			throw new BadMethodCallException(
				"Attempt to call non-existent method, " . $method
			);
		}

		return call_user_func_array(array($this->db, $method), $args);
	}
}
?>
