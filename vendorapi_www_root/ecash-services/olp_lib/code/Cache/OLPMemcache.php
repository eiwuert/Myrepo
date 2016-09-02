<?php

/**
 * Class for storing memcache items and keeping track of them in a 'XPath'ish way.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Cache_OLPMemcache extends Cache_Memcache
{
	/**
	 * Path separator for key names.
	 *
	 * This is the separator for keys, the convention being forward slash, 
	 * so that keys look like "cache/olp/path/to/key"
	 * 
	 * @var string
	 */
	protected static $path_separator = '/';
	
	/**
	 * The name of the memcache key that stores the dictionary of keys this class knows about.
	 *
	 * Initialized in constructor.
	 * 
	 * @var string
	 */
	protected $key_name = '';
	
	/**
	 * In memory representation of the keys we've pushed to memcache.
	 * 
	 * This is made publicly available via the getter/setter methods,
	 * so you can simply access ->keys
	 *
	 * @var array
	 */
	protected $keys = FALSE;
	
	/**
	 * Prefix for storage, this is initialized to cache/olp in the constructor.
	 *
	 * @var string
	 */
	protected $prefix = '';
	
	/**
	 * Instance of the object returned via {@see getInstance}
	 * 
	 * @var Cache_OLPMemcache
	 */
	protected static $instance = NULL;
	
	/**
	 * Protected constructor. Should not be called, call getInstance() instead.
	 * 
	 * @return void
	 */
	public function __construct()
	{
		$this->prefix = 'cache/olp';
		$this->key_name = sprintf('%s/%s', $this->prefix, 'keys');
		parent::__construct();
	}
	
	/**
	 * Get an instance of the Cache_OLPMemcache object.
	 *
	 * @return Cache_OLPMemcache
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new Cache_OLPMemcache();
		}
		
		return self::$instance;
	}
	
	/**
	 * Public getter, used to get keys and prefix initially.
	 *
	 * @param string $key The property to get.
	 * 
	 * @return mixed NULL if not set
	 */
	public function __get($key)
	{
		if ($key == 'prefix')
		{
			return $this->prefix;	
		}
		elseif ($key == 'keys')
		{
			$this->initKeys();
			return $this->keys;
		}
		
		return NULL;
	}
	
	/**
	 * Public setter, cannot be used. (all properties are read-only.)
	 *
	 * @param string $key Property to set.
	 * @param mixed $value Value to set on property.
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @return void
	 */
	public function __set($key, $value)
	{
		throw new InvalidArgumentException(strval($key) . 'is read-only');
	}
	
	/**
	 * Checks to see if a property is set
	 *
	 * @param string $key Property to check.
	 *
	 * @return bool if the property is set
	 */
	public function __isset($key)
	{
		return in_array($key, array('prefix', 'keys'));
	}
	
	/**
	 * Unsets a property, cannot be used. (all properties are read-only.)
	 *
	 * @param unknown_type $key
	 * 
	 * @return void
	 */
	public function __unset($key)
	{
		throw new IllegalArgumentException(strval($key) . ' is read-only');
	}
	
	/**
	 * Keys are available through the getter/setter, this is an alias for ->keys.
	 *
	 * @return array
	 */
	public function getKeys()
	{
		return $this->keys;
	}
	
	/**
	 * Initializes the keys array and saves in memcache if uninitialized.
	 *
	 * @return void
	 */
	protected function initKeys()
	{	
		if ($this->keys === FALSE)
		{
			// try to pull "structure" from memcache
			$this->keys = parent::get($this->key_name);
		}
		
		if ($this->keys === FALSE)
		{
			$this->keys = array();
			
			// never expire this key
			parent::set($this->key_name, $this->keys, 0);
		}
	}
	
	/**
	 * Deletes a memcache key, can use glob notation.
	 * 
	 * The real "magic" of this class is that if you delete a key named
	 * "path/to/key" then both "path/to/key" and any derivatives such as 
	 * "path/to/key/1" and "path/to/key/2" will also be deleted. By using "glob"
	 * notation, such as "path/to/key/*", only entries 1 and 2 mentioned before
     * will be deleted, "path/to/key" will remain intact in memcache.
	 *
	 * @param string $key Key to delete from memcache.
	 * 
	 * @return bool whether the delete operation succeeded or not
	 */
	public function delete($key)
	{
		$this->initKeys();
		$key = $this->normalizeKeyPath($key);
		
		$delete_ok = TRUE;
		
		/**
		 * Regardless if the key is "path/to/stuff" or "path/to/stuff/*"
		 * all keys beginning with "path/to/stuff/" must be deleted, so we 
		 * alter $key to be "path/to/stuff/". However, if key is the non-glob 
		 * version, we remove the key explicitly.
		 */
		if (substr($key, -1) == '*')
		{
			$key = substr($key, 0, -1);
		}
		else 
		{
			$this->keyRemove($key);
			if (!parent::delete($key))
			{
				$delete_ok = FALSE;
			}
			$key .= self::$path_separator;
		}
		
		// traverse the keys that we believe are set and remove those
		// which are logical "sub keys" of $key
		$key_length = strlen($key);
		foreach ($this->keys as $set_key)
		{
			if (strlen($set_key) < $key_length) continue;
			
			// set_key looks like key at the beginning
			if (substr($set_key, 0, $key_length) == $key)
			{
				$this->keyRemove($set_key);
				if (!parent::delete($set_key))
				{
					$delete_ok = FALSE;
				}
			}
		}
		
		return $delete_ok;
	}
	
	/**
	 * Set a key in memcache (and remember that it's been set in $this->keys.)
	 *
	 * Overwrites any key already set.
	 * 
	 * @param string $key Key to set.
	 * @param mixed $value Value to set key to in memcache.
	 * @param int $expire 0 for never expires, int to specify expires time.
	 * @param int $flag Memcache flags {@see Memcache::add}
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @return bool Success/failure.
	 */
	public function set($key, $value, $expire = self::MEMCACHE_EXPIRE, $flag = 0)
	{
		$this->initKeys();
		
		$key = $this->normalizeKeyPath($key);
		
		if (in_array($key, $this->keys))
		{
			$this->delete($key);
		}
		
		return $this->realAdd($key, $value, $expire, $flag);
	}
	
	/**
	 * Add a key in memcache (and remember that it's been set in $this->keys.)
	 *
	 * This differs from {@see set} in that if the key is already present, this
	 * method will not overwrite it.
	 * 
	 * @param string $key Key to add.
	 * @param mixed $value Value to set key to in memcache.
	 * @param int $expire 0 for never expires, int to specify expires time.
	 * @param int $flag Memcache flags {@see Memcache::add}
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @return bool Success/failure.
	 */
	public function add($key, $value, $expire = self::MEMCACHE_EXPIRE, $flag = 0) 
	{
		$this->initKeys();
		
		$key = $this->normalizeKeyPath($key);
		
		if (in_array($key, $this->keys))
		{
			// this could really throw an exception :(
			return FALSE;
		}
		
		return $this->realAdd($key, $value, $expire, $flag);
	}
	
	/**
	 * Helper function to add a key to memcache.
	 *
	 * @param string $key Key to add.
	 * @param mixed $value Value to set key to in memcache.
	 * @param int $expire 0 for never expires, int to specify expires time.
	 * @param int $flag Memcache flags {@see Memcache::add}
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @return bool Success/failure.
	 */
	protected function realAdd($key, $value, $expire = self::MEMCACHE_EXPIRE, $flag = 0)
	{
		$this->keyAdd($key);
		return parent::add($key, $value, $expire, $flag);
	}
	
	/**
	 * Get a key from memcache
	 *
	 * 
	 * @param string|array $key Key(s) to get.
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @return string|array|boolean Value set for the key(s) or FALSE on failure
	 */
	public function get($key)
	{
		if (is_array($key))
		{
			foreach ($key as $ordinal => $value)
			{
				$key[$ordinal] = $this->normalizeKeyPath($value);
			}
		}
		else
		{
			$key = $this->normalizeKeyPath($key);
		}
		
		return parent::get($key);
	}
	
	/**
	 * Helper function that stores the key in our dictionary of keys (updates memcache.)
	 *
	 * @param string $key Key to store in our list of keys.
	 * 
	 * @return bool Whether the set operation worked.
	 */
	protected function keyAdd($key)
	{
		$this->initKeys();
		$this->keys[$key] = $key;
		return parent::set($this->key_name, $this->keys, 0);
	}
	
	/**
	 * Helper function which removes key from our list of keys in memcache.
	 *
	 * @param string $key Key to remove.
	 * 
	 * @return bool whether or not the set operation worked.
	 */
	protected function keyRemove($key)
	{
		$this->initKeys();
		unset($this->keys[$key]);
		return parent::set($this->key_name, $this->keys, 0);
	}
	
	/**
	 * This function just removes leading prefix from search strings.
	 * 
	 * key such as "my/path/" will be changed to "cache/olp/my/path"
	 * 
	 * @param string $key key to use to traverse struct for entry
	 * 
	 * @return string normalized string
	 */
	protected function normalizeKeyPath($key)
	{
		if (!is_string($key))
		{
			throw new InvalidArgumentException(sprintf(
				'memcache key must be a string, got %s',
				strval($key))
			);
		}
		
		// scrub away trailing leading/trailing /'s
		$key = trim($key, self::$path_separator);
		
		if (!strlen($key))
		{
			throw new InvalidArgumentException(
				'memcache key must be non-empty string'
			);
			
		}
		
		if ($key == 'keys' || $key == $this->key_name)
		{
			throw new InvalidArgumentException(
				$this->key_name . ' is a reserved memcache key'
			);
		}
		
		// add prefix to this key if not present already
		if (substr($key, 0, strlen($this->prefix)) != $this->prefix)
		{
			$key = sprintf('%s/%s', $this->prefix, $key);
		}
		
		return $key;
	}
}

?>
