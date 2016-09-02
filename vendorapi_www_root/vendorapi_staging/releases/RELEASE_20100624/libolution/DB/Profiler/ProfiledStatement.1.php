<?php
/**
 * @package DB
 */

/**
 * Database profiler for libolution
 * @author Jordan Raub <jordan.raub@dataxltd.com>
 */
class DB_Profiler_ProfiledStatement_1 implements IteratorAggregate, DB_IStatement_1
{
	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @var DB_IStatement_1
	 */
	protected $stmt = null;

	/**
	 * @var DB_IProfiler_1
	 */
	protected $profiler = null;

	/**
	 * ctor
	 *
	 * @param DB_IStatement_1 $stmt
	 * @param IProfiler_1 $profiler
	 */
	public function __construct($query, DB_IStatement_1 $stmt, DB_Profiler_IProfiler_1 $profiler)
	{
		$this->query = $query;
		$this->stmt = $stmt;
		$this->profiler = $profiler;
	}

	/**
	 * exec
	 *
	 * @param array $args
	 * @return bool
	 */
	public function execute(array $args = NULL)
	{
		$this->profiler->startQuery($this->query, empty($args) ? array() : $args);
		$retval = $this->stmt->execute($args);
		$this->profiler->endQuery($this->query, empty($args) ? array() : $args);

		return $retval;
	}

	/**
	 * Fetches a single row
	 * @param int $fetch_mode
	 * @return mixed
	 */
	public function fetch($fetch_mode = self::FETCH_ASSOC)
	{
		return $this->stmt->fetch($fetch_mode);
	}

	/**
	 * Fetches all rows
	 * @param int $fetch_mode
	 * @return array
	 */
	public function fetchAll($fetch_mode = self::FETCH_ASSOC)
	{
		return $this->stmt->fetchAll($fetch_mode);
	}

	/**
	 * Returns the number of affected rows
	 * @return int
	 */
	public function rowCount()
	{
		return $this->stmt->rowCount();
	}

	public function __call($method, $args)
	{
		$reflect = new ReflectionObject($this->stmt);
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

		return call_user_func_array(array($this->stmt, $method), $args);
	}

	/**
	 * Gets the underlying statement's iterator
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		if (!$this->stmt instanceof Traversable)
		{
			throw new BadMethodCallException('Statement is not traversable');
		}
		return $this->stmt;
	}
}
?>
