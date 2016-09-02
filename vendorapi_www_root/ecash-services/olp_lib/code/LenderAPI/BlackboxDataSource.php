<?php

/**
 * The data source which wraps an OLPBlackbox_Data object for the VendorAPI
 * transform layer.
 * 
 * The general usage would be that the XML source for the LenderAPI has a section
 * such as:
 * <campaign>
 *  <campaign_name />
 * </campaign>
 * 
 * If this section is intended to represent the state data of the current 
 * campaign being posted, you would do something like:
 * 
 * $iterator = new LenderAPI_BlackboxDataSource($this->state_data);
 * foreach ($iterator as $key => $value) 
 * {
 * 	print "$key => $value";
 * }
 * 
 * Which would print out "campaign_name => x" where x is the value in 
 * $this->state_data. NO OTHER VALUES from $this->state_data would be printed out.
 * 
 * You could also add a delegate (see LenderAPI/BlackboxDataSource/) which could
 * present a key which does not exist in the state data but could be assembled
 * from it.
 * 
 * State data is just used as an example for simplicity, the main job of this
 * class is actually to wrap Blackbox_Data.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package LenderAPI
 * @version $Id: BlackboxDataSource.php 31819 2009-01-21 04:48:46Z olp_release $
 */
class LenderAPI_BlackboxDataSource implements Iterator
{
	/**
	 * @var array
	 */
	protected $keys = array();

	/**
	 * @var OLPBlackbox_Data|Blackbox_IStateData
	 */
	protected $data;

	/**
	 * @var Cache_Memcache
	 */
	protected $storage_object;

	/**
	 * @var array
	 */
	protected $delegates = array();

	/**
	 * Determines whether the init function for the keys has been run.
	 * @var bool
	 */
	protected $keys_are_set_up = FALSE;

	/**
	 * The xpath key to find the allowed properties for this wrapper.
	 * @var string
	 */
	protected $xpath_key;

	/**
	 * Cache expiry time in seconds.
	 * Defaults to 15 minutes like default memcache.
	 * @var int
	 */
	const CACHE_EXPIRE_SECONDS = 900;

	/**
	 * Create a LendorAPI data source which wraps an OLPBlackbox_Data object.
	 * @param OLPBlackbox_Data $data The data to wrap.
	 * @param string $xpath_key This data source must end up a child node of the
	 * "data" node in the fake XML that will be used to power the transform
	 * layer. This key will be used as the node name.
	 * @param mixed $storage_object In reality this will probably always be a
	 * memcache object, but you could pass something else that also had a get/set
	 * method without too much problem.
	 * @return void
	 */
	public function __construct($data, $xpath_key, $storage_object = NULL)
	{
		if (!$data instanceof Blackbox_Data 
			&& !$data instanceof Blackbox_IStateData)
		{
			throw new Blackbox_Exception(sprintf(
				'class %s cannot wrap %s, must wrap IStateData or Data',
				__CLASS__,
				var_export($data, TRUE))
			);
		}

		$this->xpath_key = $xpath_key;
		$this->data = $data;
		if ($storage_object)
		{
			$this->setStorageObject($storage_object);
		}
	}


	/**
	 * The automagical __set function.
	 *
	 * @param string $key
	 * @param mixed $val
	 * @return void
	 */
	public function __set ($key, $val)
	{
		if ($this->data->propertyExists($key))
		{
			$this->data->$key = $val;
		}
		else
		{
			$delegate = $this->getDelegateFor($key);
			if ($delegate)
			{
				$delegate->setValue($val);
			}
		}
	}


	/**
	 * The current value during iteration, found using the list of keys.
	 * @return mixed
	 */
	public function current()
	{
		$this->setupKeys();

		$value = NULL;

		$key = current($this->keys);

		$delegate = $this->getDelegateFor($key);

		if ($delegate)
		{
			$value = $delegate->value();
		}
		elseif (isset($this->data->$key))
		{
			$value = $this->data->$key;
		}

		return $value;
	}

	/**
	 * Required for Iterator interface, returns current key for iteration.
	 * @return string
	 */
	public function key()
	{
		$this->setupKeys();
		return current($this->keys);
	}

	/**
	 * Required for Iterator interface, rewinds the this object for iteration.
	 * @return void
	 */
	public function rewind()
	{
		$this->setupKeys();
		reset($this->keys);
	}

	/**
	 * Required for Iterator interface, determines whether current item is valid.
	 * @return bool
	 */
	public function valid()
	{
		$this->setupKeys();
		return $this->key() !== FALSE;
	}

	/**
	 * Required for Iterator interface, moves to the next item.
	 * @return void
	 */
	public function next()
	{
		$this->setupKeys();
		next($this->keys);
	}

	/**
	 * Allow this data source to use a storage object for it's keys.
	 * @param object $object A storage object which implements get/set like
	 * Cache_Memcache does.
	 * @return void
	 */
	public function setStorageObject($object)
	{
		// essentially, we want a {@see Cache_Memcache} type object.
		if (!method_exists($object, 'get') || !method_exists($object, 'set'))
		{
			throw new InvalidArgumentException(sprintf(
				'%s does not know how to store things in %s',
				__CLASS__,
				var_export($object, TRUE))
			);
		}

		$this->storage_object = $object;
	}

	/**
	 * Obtains a delegate class to handle data translation for keys.
	 * @param string $key The property name the client is trying to access.
	 * @return LenderAPI_BlackboxDataSource_IDelegate
	 */
	protected function getDelegateFor($key)
	{
		if (!array_key_exists($key, $this->delegates)
			|| !$this->delegates[$key] instanceof LenderAPI_BlackboxDataSource_IDelegate)
		{
			$class_name = $this->getClassFor($key);

			if (!class_exists($class_name))
			{
				return NULL;
			}

			$this->delegates[$key] = new $class_name($this->data);
		}

		return $this->delegates[$key];
	}

	/**
	 * Sets the fields up that will be presented to the LendorAPI.
	 * 
	 * @return void
	 */
	protected function setupKeys()
	{
		if ($this->keys_are_set_up) return;

		// only pull out of cache for Blackbox_Data, see func doc
		if ($this->storage_object 
			&& $this->xmlFileNotModified())
		{
			$keys = $this->storage_object->get($this->storageHash());
			if (is_array($keys))
			{
				$this->keys = $keys;
				$this->keys_are_set_up = TRUE;
				return;
			}
		}

		$this->keys = $this->getFieldsFromXMLFile();

		$this->storageObjectSet($this->storageHash(), $this->keys);

		$this->keys_are_set_up = TRUE;
	}

	/**
	 * Sets a key/value in the storage object if possible.
	 * @param string $key The key to set in the storage object.
	 * @param mixed $value The value to save.
	 * @return bool Whether the value was able to be stored.
	 */
	protected function storageObjectSet($key, $value)
	{
		if (!$this->storage_object) return FALSE;

		$this->storage_object->set($key, $value);
		return TRUE;
	}

	/**
	 * Parses the XML example file (essentially a config) and returns fields that
	 * must be made available to the LendorAPI transform layer.
	 * @return array
	 */
	protected function getFieldsFromXMLFile()
	{
		$doc = new DOMDocument();
		try
		{
			$doc->load(self::getXMLFile());
		}
		catch (Exception $e)
		{
			throw new Blackbox_Exception(
				'unable to parse LendorAPI example XML: ' . $e->getMessage()
			);
		}

		$xpath = new DOMXPath($doc);

		$return = array();

		foreach ($xpath->query("//data/{$this->xpath_key}")->item(0)->childNodes as $node)
		{
			/* @var $node DOMElement */
			if (!$node instanceof DOMElement) continue;

			$return[] = $node->nodeName;
		}

		return $return;
	}

	/**
	 * Returns the "hash" to use to store the keys in the storage object.
	 * 
	 * Contractually, since all LenderAPI data sources are being assembled from
	 * the same XML source, the xpath_key is a good enough unique identifier to
	 * store the keys for this wrapper object.
	 * 
	 * @return string
	 */
	protected function storageHash()
	{
		return sprintf(
			'blackboxdatasource/%s', 
			$this->xpath_key
		);
	}

	/**
	 * Determine if the XML file has been modified within the cache time.
	 *
	 * Granted, if it has been modified (we've released code) then this class
	 * will pull and reparse the XML file each time it's loaded.
	 *
	 * @return bool TRUE if the file has not been modified recently.
	 */
	protected function xmlFileNotModified()
	{
		return (time() - filemtime(self::getXMLFile())) > self::CACHE_EXPIRE_SECONDS;
	}

	/**
	 * Returns the location of the master XML example file which determines what
	 * information is supposed to be available to the LendorAPI transform layer.
	 *
	 * @return string Path to XML file.
	 */
	public static function getXMLFile()
	{
		return dirname(__FILE__) . '/xml/transport_example.xml';
	}

	/**
	 * Return a class name for a key request which will produce an item that
	 * should "exist" in the data source.
	 *
	 * @param string $key Name of a key that's supposed to exist on this data
	 * source.
	 * @return string Class name.
	 */
	protected function getClassFor($key)
	{
		return 'LenderAPI_BlackboxDataSource_' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
	}
}
?>
