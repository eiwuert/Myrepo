<?php
/**
 * Base class for query service client calls
 * 
 * @author Eric Johney <eric.johney@sellingsource.com>
 * @package WebService
 */
abstract class WebServices_Client_QueryClient extends WebServices_Client
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
	 * @param WebServices_Cache $cache
	 * @return void
	 */
	public function __construct(Applog $log, WebServices_WebService $app_service, $agent_id, WebServices_ICache $cache)
	{
		parent::__construct($log, $app_service);
		$this->agent_id = $agent_id;
		$this->cache = $cache;
	}
	
	/**
	 * 
	 */
	public function getNotOKApplications($ssn, $application_id, $application_statuses)
	{
		$result = $this->runCall(__FUNCTION__, array(array('ssn' => $ssn, 'application_id' => $application_id, 'statuses' => $application_statuses)));
		if (!empty($result) && is_array($result))
		{
			return $result;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * 
	 */
	public function getApplicationsForSearch($last_run, $this_run)
	{
		return $this->runCall(__FUNCTION__, array(array('last_run' => $last_run, 'this_run' => $this_run)));
	}

	protected function unwrap($res)
	{
		if(! $res instanceOf stdClass) return $res;
		if(count((array)$res) == 0) return null;  //WTF PHP
		if(isset($res->entry) && is_array($res->entry))
		{
			$a = array();
			foreach($res->entry as $k => $v)
			{
				$a[$v->key] = $this->unwrap($v->value);
			}
			return $a;
		}
		return $res;

	}

	/**
	 * Enter description here...
	 *
	 */
	protected function runCall($method, $args)
	{
		$retval = FALSE;
		if (!$this->getService()->isReadEnabled(__FUNCTION__)) return $retval;

		$retval = call_user_func_array(array($this->getService(), $method), $args);

		/*
		if (isset($retval->return->entry) && $retval->return->entry instanceof Traversable)
		{
			$retval = $this->parseKeyValue($a);
		}
		*/

		if(isset($retval->return->string2anyTypeMap) && is_array($retval->return->string2anyTypeMap))
		{
			foreach($retval->return->string2anyTypeMap as $k => $v) {
				$retval->return->string2anyTypeMap[$k] = $this->parseKeyValue($v->entry);
			}
			$retval = $retval->return->string2anyTypeMap;
		}
		else
		{
			$retval = $this->unwrap($retval->return);
		}
		
		return $retval;
	}
	
	protected function parseKeyValue($result)
	{
		$a = array();
		if (is_array($result))
		{
			foreach ($result as $k => $v)
			{
				$a[$v->key] = $v->value;
			}
		}
		elseif (is_object($result) && isset($result->key) && isset($result->value))
		{
			$a[$result->key] = $result->value;
		}
		return $a;
	}
	
	/**
	 * catch all web service passthru
	 * @param array $args - the data to send
	 * @return mixed|FALSE
	 */
	public function __call($method, $args)
	{
		return $this->runCall($method, $args);
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
