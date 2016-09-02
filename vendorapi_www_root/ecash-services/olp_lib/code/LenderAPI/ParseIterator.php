<?php

/**
 * Parent class for subclasses which are interested in traversing a list of 
 * target_data information and making key => value pairs to be, again, iterated
 * over from the more awkward keys in the database.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package LenderAPI
 */
abstract class LenderAPI_ParseIterator implements IteratorAggregate
{
	/**
	 * The actual data we'll store, once parsed out.
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * The key pattern this parser is looking for, should result in a single 
	 * reference which is used for intermediary indexing.
	 * 
	 * e.g: if the pattern is "a_b_c_([0-9]{1,3})" it will look for a value
	 * which also uses the same number captured in the ([0-9]{1,3}) portion.
	 * @return string 
	 */
	protected abstract function getKeyPattern();
	
	/**
	 * Searches for a value pattern, {@see getKeyPattern()}
	 * @return string
	 */
	protected abstract function getValuePattern();

	/**
	 * Main parsing function, intended to be called in the constructor of children.
	 * @param object|array &$iterable List of stuff to parse, which should
	 * represent target_data for a single target. Must be either array or anunknown
	 * object which implements ArrayAccess and Iterable. (Most likely this will
	 * be an ArrayObject)
	 * @param bool $unset_mode Whether to erase the keys we use in $iterable
	 * @return void (Sets up $this->data)
	 */
	protected function parseIterable(&$iterable, $unset_mode = FALSE)
	{
		if (!($iterable instanceof Traversable && $iterable instanceof ArrayAccess)
			&& !is_array($iterable))
		{
			throw new InvalidArgumentException(sprintf(
				'constructor for %s must get an iterable/arrayaccess item, got %s',
				get_class($this),
				var_export($iterable, TRUE)
			));
		}
		
		$data_tuples = array();
		
		$unsets = array();
		
		foreach ($iterable as $key => $value)
		{
			$key_number = $this->parseKey($key);
			if ($key_number)
			{
				$this->addKey($key_number, $value, $data_tuples);
			}
			
			$value_number = $this->parseValue($key);
			if ($value_number)
			{
				$this->addValue($value_number, $value, $data_tuples);
			}
			
			if (($key_number || $value_number))
			{
				if ($unset_mode) $unsets[] = $key;
			}
		}
		
		$this->data = $this->combineKeyValues($data_tuples);
		
		// we count $this->data instead of $data_tuples, because $data_tuples
		// might have empty entries we prune out with combineKeyValues
		if (!count($this->data))
		{
			throw new InvalidArgumentException(sprintf(
				'%s must be passed items with keys satisfying this pattern: %s',
				get_class($this),
				$this->getKeyPattern()
			));
		}
		
		if ($unsets)
		{
			foreach ($unsets as $key_to_unset)
			{
				unset($iterable[$key_to_unset]);
			}
		}
	}
		
	/**
	 * Combines a list of tuples into a dictionary.
	 * @param array $data list of tuple arrays 
	 * @return array
	 */
	protected function combineKeyValues(array $data)
	{
		$new = array();
		
		foreach ($data as $d)
		{
			if (!empty($d[0]))
			{
				$new[$d[0]] = $d[1];
			}
		}
		
		return $new;
	}
	
	/**
	 * Looks for a key which is the "key" part of a key-value pair for whatever target
	 * data stuff we're grabbing.
	 * 
	 * @param string $key The key to check.
	 * @return NULL|int The number of constant this is. I.E. the number portion of:
	 * "vendor_api_constant_name_1"
	 */
	protected function parseKey($key)
	{
		$matches = array();
		
		if (preg_match($this->getKeyPattern(), $key, $matches))
		{
			return $matches[1];
		}
		
		return NULL;
	}
	
	/**
	 * Parses a value in the same style that {@see parseKey()} parses a key.
	 * @param string $key The key to check.
	 * @return NULL|int The number, if any, that this value applies to in the 
	 * sequenced data.
	 */
	protected function parseValue($key)
	{
		$matches = array();
		
		if (preg_match($this->getValuePattern(), $key, $matches))
		{
			return $matches[1];
		}
		
		return NULL;
	}
	
	/**
	 * Add a key to the intermediary list of data.
	 * @see __construct()
	 * @param int|string $number The number key to add.
	 * @param string $value The value, representing a key in the key => value
	 * pairs defined in target data to be used in source XML for LenderAPI.
	 * @param array &$data The data to add the tuple to.
	 * @return void
	 */
	protected function addKey($number, $value, array &$data)
	{
		$number = intval($number);
		
		if (isset($data[$number]))
		{
			$data[$number][0] = $value;
		}
		else
		{
			$data[$number] = array($value, NULL);
		}
	}
	
	/**
	 * Add a value to the intermediary list of data.
	 * @see __construct()
	 * @param int|string $number The number key to add.
	 * @param string $value Represents a value in the key => value
	 * pairs defined in target data to be used in source XML for LenderAPI.
	 * @param array &$data The data to add the tuple to.
	 * @return void
	 */
	protected function addValue($number, $value, array &$data)
	{
		$number = intval($number);
		if (isset($data[$number]))
		{
			$data[$number][1] = $value;
		}
		else 
		{
			$data[$number] = array(NULL, $value);
		}
	}
	
	/**
	 * Required by the IteratorAggregate interface, allows
	 * traversal over the data we've parsed out.
	 * 
	 * @see IteratorAggregate::getIterator()
	 * @return ArrayIterator
	 */
	public function getIterator() 
	{
		return new ArrayIterator($this->data);
	}
		
	/**
	 * Returns the data which has been parsed out of the parseIterable method.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}
}

?>
