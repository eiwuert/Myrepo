<?php

class ECash_DB_CachedConfig implements DB_IDatabaseConfig_1
{
	/**
	 * @var DB_IDatabaseConfig_1
	 */
	protected $config;
	
	/**
	 * @var DB_IConnection_1
	 */
	protected $connection;
	
	public function __construct(DB_IDatabaseConfig_1 $config)
	{
		$this->config = $config;
	}
	

	/**
	 * prototype: return a PDO connection
	 * @return DB_IConnection_1
	 */
	public function getConnection()
	{
		if (empty($this->connection))
		{
			$this->connection = $this->config->getConnection();
			$this->setTimeZone(date_default_timezone_get());
			$this->setCharType('latin1', 'latin1_swedish_ci');
		}
		
		return $this->connection;
	}
	public function getDSN()
	{
		return $this->config->getDSN();
	}
	protected function setTimeZone($timezone)
	{
		DB_Util_1::execPrepared($this->getConnection(), 'SET time_zone = ?', array($timezone));
	}
	
	protected function setCharType($chartype, $collation)
	{
		DB_Util_1::execPrepared($this->getConnection(), 'SET NAMES ? COLLATE ?', array($chartype, $collation));
	}
	
	/**
	 * Allows full decoration of passed object regardless of type.
	 *
	 * @param string $method
	 * @param array $parms
	 */
	public function __call($method, $parms)
	{
		$call = array($this->config, $method);
		if (is_callable($call))
		{
			call_user_func_array($call, $parms);
		}
		else
		{
			throw new BadMethodCallException("Method '{$method}' does not exist on " . get_class($this->config));
		}
	}
}
?>
