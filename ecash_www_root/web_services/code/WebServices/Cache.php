<?php
/**
 * Webservice call cache object
 * 
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @package WebService
 * 
 * 
 */
class WebServices_Cache implements WebServices_ICache
{
	/**
	 * Logging object for logging output
	 *
	 * @var AppLog
	 */
	protected $log;
	/**
	 * cache data
	 *
	 * @var stdclass
	 */
	protected $cache;

	/**
	 * Constructor for base appclient object
	 *
	 * @param Applog $log
	 * @return void
	 */
	public function __construct(Applog $log)
	{
		$this->log = $log;
		$this->cache = new stdclass();
	}
	/**
	 * Checks if an id for a function is in the cache
	 *
	 * @param string $function
	 * @param string $id
	 * @return object
	 */
	public function getCache($function, $id)
	{
		if ($this->hasCache($function, $id))
		{
			$value = $this->cache->{$function}[$id];
			if(is_object($value))
			{
				return $this->recursiveClone($value);	
			}
			else
			{
				return $value;
			}		
		}
		else
		{
			return NULL;
		}
		
	}

	/**
	 * Checks if an id for a function is in the cache
	 *
	 * @param string $function
	 * @param string $id
	 * @return object
	 */
	public function hasCache($function, $id)
	{
		return (!empty($this->cache->$function) &&
				!empty($id) &&
				(is_string($id) || is_int($id)) &&
				array_key_exists($id, $this->cache->{$function}));
	}

	/**
	 * Stores a call in the cach
	 *
	 * @param string $function
	 * @param string $id
	 * @param object $value
	 * @return void
	 */
	public function storeCache($function, $id, $value)
	{
		if (empty($this->cache->$function))
		{
			$this->cache->$function = array();
		}
		if(is_object($value))
		{
			$this->cache->{$function}[$id] = $this->recursiveClone($value);
		}
		else
		{
			$this->cache->{$function}[$id] = $value;
		}
		
	}
	/**
	 * Removes value from the cache
	 *
	 * @param string $function
	 * @param string $id
	 * @return void
	 */
	public function removeCache($function, $id)
	{
		if (!empty($this->cache->$function))
		{
			unset($this->cache->{$function}[$id]);
		}
	}
	/**
	 * recursiveClone
	 *
	 * @param object $obj
	 * @return object
	 */
	protected function recursiveClone($obj)
	{
		$return_obj = new stdclass();
		foreach($obj as $name => $value)
		{
			if(gettype($value)=='object')
			{
				$return_obj->$name= $this->recursiveClone($obj->$name);
			}
			else
			{
				$return_obj->$name = $obj->$name;
			}
		}
		return $return_obj;
	} 

}


?>
