<?php

class ECash_Models_Reference_List implements Iterator, ArrayAccess
{
	const CACHE_PREFIX = 'Reference_List';

	private $table = NULL; //local reference to DB_Models_ReferenceTable_1
	private $store;
	private $table_name;
	private $empty_model; //empty DB_Models_ReferenceModel_1 (for table name)
	private $prefetch;
	private $selection_args;

	/** I'm setting prefetch to TRUE by default b/c I'm not sure if
	 *  the statements inside ReferenceModel (st_byname & st_byid)
	 *  will work after sitting in * the cache a long time.
	 */
	public function __construct(DB_Models_ReferenceModel_1 $empty, Cache_IStore $store = NULL, $prefetch = TRUE, array $selection_args = array())
	{
		if(count($selection_args) > 0) $prefetch = FALSE;
		
		$this->table_name = $empty->getTableName();
		$this->empty_model = $empty;
		$this->prefetch = $prefetch;
		$this->selection_args = $selection_args;
		$this->setStore($store);
	}

	public function setStore(Cache_IStore $store = NULL)
	{
		$this->store = $store;
	}

	private function getTable()
	{
		if(!is_null($this->store))
		{
			$table = $this->store->get(self::CACHE_PREFIX.$this->table_name);

			//if it's not there yet, put something there
			if(is_null($table))
			{
				if(!is_null($this->table))
				{
					//put our local copy in the store
					$table = $this->table;
				}
				else
				{
					//load a new one to put in the store
					$table = new DB_Models_ReferenceTable_1($this->empty_model, $this->prefetch, $this->selection_args);
				}

				//then put it in the store so it's available next time, and remove the local reference
				$this->store->put(self::CACHE_PREFIX.$this->table_name, $table);
				$this->table = NULL;
			}

			return $table;
		}

		if(is_null($this->table))
		{
			$this->table = new DB_Models_ReferenceTable_1($this->empty_model, $this->prefetch, $this->selection_args);
		}
		return $this->table;
	}

	public function __call($name, array $args)
	{
		if(method_exists($this, $name))
		{
			return call_user_func_array(array($this, $name), $args);
		}
		else
		{
			$table = $this->getTable();
			return call_user_func_array(array($table, $name), $args);
		}
	}
	/**
	 * Advances the internal iterator
	 * @return mixed
	 */
	public function next()
	{
		return $this->getTable()->next();
	}

	/**
	 * Return the current item
	 * @return mixed
	 */
	public function current()
	{
		return $this->getTable()->current();
	}

	/**
	 * Advances the internal iterator
	 * @return bool
	 */
	public function valid()
	{
		return $this->getTable()->valid();
	}

	/**
	 * Rewinds the internal iterator
	 * @return void
	 */
	public function rewind()
	{
		return $this->getTable()->rewind();
	}

	/**
	 * Returns the key of the current item
	 * @return mixed
	 */
	public function key()
	{
		return $this->getTable()->key();
	}
	/**
	 * ArrayAccess to items
	 * @param mixed $index ID/name
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return $this->getTable()->offsetGet($index);
	}

	/**
	 * Can't be used
	 * @throws exception
	 */
	public function offsetSet($index, $value)
	{
		return $this->getTable()->offsetSet($index, $value);
	}

	/**
	 * Can't be used
	 * @throws exception
	 */
	public function offsetUnset($index)
	{
		return $this->getTable()->offsetUnset($index);
	}

	/**
	 * ArrayAccess overloading for isset()
	 * @param mixed $index ID/name
	 * @return bool
	 */
	public function offsetExists($index)
	{
		return $this->getTable()->offsetExists($index);
	}

	/**
	 * Iterates through the reference model and
	 * returns an array populated with the values
	 * of the column specified
	 *
	 * @param string $key Column name from reference model
	 * @return array
	 */
	public function toArray($key)
	{
		$return_array = array();
		while($row = $this->getTable()->next())
		{
			$return_array[] = $row->$key;
		}
		return $return_array;
	}

	// dont ask
	public function __toString() { return ''; }

}

?>