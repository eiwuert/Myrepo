<?php

/**
 * Interact with the application session database.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class SessionService
{
	/**
	 * @var Session
	 */
	protected $session;
	protected $std_class_keys = array();
	
	/**
	 * Create a SessionService interface to use in a PHP Soap server object.
	 * 
	 * @param Session $session The session class to manipulate the session with.
	 * @return string
	 */
	public function __construct(Session $session)
	{
		$this->session = $session;
	}
	
	public function setStdClassKeys(array $keys)
	{
		$this->std_class_keys = $keys;
	}
	
	/**
	 * Create a session and return the session_id.
	 * 
	 * @param string $session_id The session id to create.
	 * @param int $lock_time The amount of time to lock the newly acquired 
	 * session for.
	 * @return SessionReadResponse
	 */
	public function createSessionAndReadAsJson($session_id, $lock_time = 60)
	{
		return $this->makeSessionAndReadAsJson($session_id, $lock_time);
	}
	
	/**
	 * Creates a new session without providing a session id to use.
	 * @see createSessionAndReadAsJson
	 * @param int|float|string $lock_time Number of seconds to lock for after
	 * acquiring the session.
	 * @return SessionReadResponse
	 */
	public function newSessionAndReadAsJson($lock_time = 60)
	{
		return $this->makeSessionAndReadAsJson(NULL, $lock_time);
	}
		
	/**
	 * Acquire a lock on the specified session_id and read the session data out
	 * as a JSON object.
	 * 
	 * @param string $session_id
	 * @param int $block_seconds The number of seconds to block waiting for a
	 * lock on the session. Unlike some interfaces, 0 passed in as this parameter
	 * will actually just ensure no more than 1 attempt instead of infinite block.
	 * @param int $timeout The number of seconds to lock this session for.
	 * @return SessionReadResponse
	 */
	public function acquireAndReadAsJson($session_id, $seconds_to_block_for = 70, $seconds_to_lock_for = 60)
	{
		$response = new SessionReadResponse();
		$response->session_id = $session_id;
		
		try 
		{
			$response->session_lock_key = $this->session->lock($session_id, $seconds_to_block_for, $seconds_to_lock_for);
		}
		catch (InvalidArgumentException $e)
		{
			throw new SenderException($e->getMessage());
		}
		catch (SessionLockException $e)
		{
			throw new SenderException($e->getMessage());
		}
		
		$response->session = $this->session->read($session_id, $response->session_lock_key);
		$response->session = json_encode($response->session);
		
		return $response;
	}

	/**
	 * Save a JSON object as the session and then release the lock on the session
	 * specified with $session_id.
	 * 
	 * @param string $session_id The session to write/release.
	 * @param string $session_lock_key The key used to lock the session, gotten via
	 * the property {@see SessionReadResponse::$session_lock_key} generated from
	 * a call to an acquire* function on this service.
	 * @param string $json_data The JSON object representation of how the session
	 * should look after being saved.
	 * @return string
	 */
	public function jsonSaveAndRelease($session_id, $session_lock_key, $json_data)
	{
		$decoded_data = $this->jsonDecodeAndTranslate($json_data);
		
		if (!is_array($decoded_data)) throw new SenderException('Unable to decode json data.');
		
		try
		{
			$this->session->save($session_id, $session_lock_key, $decoded_data);
			$this->session->release($session_id, $session_lock_key);
		}
		catch (InvalidArgumentException $e)
		{
			throw new SenderException($e->getMessage());
		}
	}
	
	/**
	 * Does the work of creating a session, locking it and then reading it's
	 * contents as JSON (which should just be "{}").
	 * 
	 * @param string $session_id Optional session id to use, otherwise this
	 * function will just invent a session id.
	 * @param int|float|string $lock_time The amount of seconds to lock the 
	 * newly created session for.
	 * @return SessionReadResponse
	 */
	protected function makeSessionAndReadAsJson($session_id = NULL, $lock_time = 60)
	{
		if (!is_numeric($lock_time)) throw new SenderException('Lock time must be numeric.');
		$lock_time = floatval($lock_time);
		
		$response = new SessionReadResponse();
		
		try
		{
			$response->session_id = $this->session->create($session_id);
		}
		catch (InvalidArgumentException $e)
		{
			throw new SenderException($e->getMessage());
		}
		
		$response->session_lock_key = $this->session->lock($response->session_id, 0, $lock_time);
		$session_data = $this->session->read($response->session_id, $response->session_lock_key);
		
		if ($session_data)
		{
			throw new SessionException(
				"Newly created session {$response->session_id} had stuff in it's session: " 
				. var_export($session_data, TRUE));
		}
		
		$response->session = json_encode(array());
		return $response;
	}
	
	/**
	 * Decode JSON input and then change any multidimensional keys that need to
	 * be stdClass objects into them.
	 * @param string $data The JSON to be decoded.
	 * @return array
	 */
	protected function jsonDecodeAndTranslate($data)
	{
		$data = @json_decode($data, TRUE);
		if (!is_array($data)) 
			throw new SenderException('Unable to decode json data, must be an object/array.');
		
		foreach ($this->std_class_keys as $key)
		{
			// TODO: move the explode to the setter?
			$keys = explode('/', trim($key, '/'));
			if ($keys) $this->findAndChangeKey($keys, $data);
		}
		
		return $data;
	}
	
	
	/**
	 * Find a key $path in $data and then change the final key match to a stdClass.
	 * 
	 * This was introduced because we're reading out the legacy config constructed
	 * by OLP which has arrays and stdClass objects in a strange configuration.
	 * The majority are arrays, but we have to change certain elements to be a
	 * stdClass, which is what this method does.
	 * 
	 * Given information like this:
	 * 
	 * path = array(
	 * 	'config', 'promo_status',
	 * );
	 * 
	 * data = array(
	 * 	config => array(
	 * 		promo_status => array(
	 * 			valid => valid,
	 * 		),
	 * 	),
	 * );
	 * 
	 * This function will turn $data['config']['promo_status'] into a stdClass
	 * that has the key "valid" with a value of "valid".
	 * 
	 * @param array $path The list of keys to address the subkey in $data that
	 * needs to be turned into a stdClass.
	 * @param array $data The data with sub arrays that need to be changed into
	 * stdClass objects.
	 * @return void
	 */
	public function findAndChangeKey(array $path, &$data)
	{
		$current_key = $path[0];
		
		// whatever we access is what we're going to try to traverse through or
		// change to a stdClass, it can't be a string or an int, for example
		if (!is_array($this->access($data, $current_key))
			&& !$this->access($data, $current_key) instanceof stdClass) return;
		
		if (count($path) == 2 && $path[1] == '*')
		{
			// caller specified something like array('tofu', '*') and we are tofu, so convert children
			foreach ($this->access($data, $current_key) as $child_key => $junk_not_by_ref)
			{
				if ($this->access($this->access($data, $current_key), $child_key))
					$this->findAndChangeKey(array($child_key), $this->access($data, $current_key));
			}
		}
		elseif (count($path) > 1)
		{
			// key still has some parts to it like array('blue', 'toy') and $current_key is 'blue'
			$this->findAndChangeKey(array_slice($path, 1), $this->access($data, $current_key));
		}
		else if (count($path) == 1 && is_array($this->access($data, $current_key)))
		{
			// we are the final part of the "xpath" style address! convert!
			$obj = new stdClass();
			$ref =& $this->access($data, $current_key);
			foreach ($ref as $key => $value)
			{
				$obj->$key = $value;
			}
			$ref = $obj;
		}
		else
		{
			throw new InvalidArgumentException('invalid state');
		}
	}
	
	/**
	 * Get a reference to an item in an array or stdClass.
	 * 
	 * @param stdClass|array $item The item to get the property reference from.
	 * @param string $key The property/key name.
	 * @return mixed NULL If the $key doesn't exist or $item is a weird type.
	 */
	protected function &access(&$item, $key)
	{
		if (is_array($item) && array_key_exists($key, $item))
		{
			return $item[$key];
		}
		elseif ($item instanceof stdClass && property_exists($item, $key))
		{
			return $item->$key;
		}
		
		return NULL;
	}
}

?>
