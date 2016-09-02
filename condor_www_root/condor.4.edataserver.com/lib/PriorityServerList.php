<?php
require_once 'PriorityServer.php';

/**
 * Class representing a list of PriorityServer objects.
 * 
 * @author Brian Feaver
 */
class PriorityServerList
{
	/**
	 * @var ArrayObject
	 */
	private $server_list;
	
	/**
	 * Default constructor.
	 */
	public function __construct()
	{
		$this->server_list = new ArrayObject();
	}
	
	/**
	 * Adds a server to the list.
	 * 
	 * @param PriorityServer $server the server to add to the list
	 */
	public function add(PriorityServer $server)
	{
		$this->server_list->append($server);
	}
	
	/**
	 * Returns the PriorityServer that has a minimum priority greater than or equal to $minimum_priority.
	 * 
	 * @param int $minimum_priority
	 * @return PriorityServer
	 */
	public function get($minimum_priority)
	{
		$priority_server = FALSE;
		
		// Ensures that our list is in minimum priority order
		$this->server_list->uasort(array('PriorityServer', 'compare'));
		
		foreach ($this->server_list as $server)
		{
			if ($minimum_priority <= $server->getMinimumPriority())
			{
				$priority_server = $server;
				break;
			}
		}
		
		return $priority_server;
	}
}