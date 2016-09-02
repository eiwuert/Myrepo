<?php
/**
 * Base class for loanaction service client calls
 * 
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @package WebService
 * 
 */
abstract class WebServices_Client_LoanActionClient extends WebServices_Client
{
	/**
	 * The logged in agent
	 *
	 * @var Integer
	 */
	protected $agent_id;

	/**
	 * webservice cache object
	 *
	 * @var WebService_Cache
	 */
	protected $cache;
	
	/**
	 * Constructor for base loanactionclient object
	 *
	 * @param Applog $log
	 * @param ApplicationService $app_service
	 * @param integer $agent_id
	 * @param WebServices_ICache $cache
	 * @return void
	 */
	public function __construct(Applog $log, $app_service, $agent_id, WebServices_ICache $cache)
	{
		parent::__construct($log, $app_service);
		$this->agent_id = $agent_id;
		$this->cache = $cache;
	}

	/**
	 * @param array $args - the data to send
	 * @return int|FALSE
	 */
	public function insert($args)
	{
		$application_id = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $application_id;

		$args['modifying_agent_id'] = $this->agent_id;
		$application_id = $this->getService()->save($args);
		return $application_id;
	}
	/**
	 * Performs the call to the underlying service, clearing all buffered calls
	 *
	 * @return mixed
	 */	
	public function flush()
	{
		return $this->getService()->flush();
	}
	/**
	 * enables and disables buffering of calls
	 *
	 * @param boolean $enabled
	 * @return void
	 */
	public function enableBuffer($enabled)
	{
		$this->getService()->setAggregateEnabled($enabled);
	}
}

?>
