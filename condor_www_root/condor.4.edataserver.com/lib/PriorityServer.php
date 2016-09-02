<?php
/**
 * This class represents a priority server with it's hostname, port, and minimum priority score.
 * 
 * @author Brian Feaver
 */
class PriorityServer
{
	/**
	 * @var int
	 */
	const MAX_PRIORITY = 100;
	
	/**
	 * @var string
	 */
	private $host;
	
	/**
	 * @var int
	 */
	private $port;
	
	/**
	 * @var int
	 */
	private $minimum_priority = self::MAX_PRIORITY;
	
	private function __construct($host, $port, $minimum_priority)
	{
		$this->host = $host;
		$this->port = (int) $port;
		$this->minimum_priority = (int) $minimum_priority;
	}
	
	/**
	 * Creates a new priority server.
	 * 
	 * @param string $host
	 * @param int $port
	 * @param int $minimum_priority
	 * @return PriorityServer
	 */
	public static function newInstance($host, $port, $minimum_priority)
	{
		return new self($host, $port, $minimum_priority);
	}
	
	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}
	
	/**
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}
	
	/**
	 * @return int
	 */
	public function getMinimumPriority()
	{
		return $this->minimum_priority;
	}
	
	/**
	 * Compares two priority servers.
	 * 
	 * @param PriorityServer $o1
	 * @param PriorityServer $o2
	 * @return int
	 */
	public static function compare(PriorityServer $o1, PriorityServer $o2)
	{
		if ($o1->getMinimumPriority() == $o2->getMinimumPriority()) return 0;
		return ($o1->getMinimumPriority() < $o2->getMinimumPriority()) ? -1 : 1;
	}
}
