<?php

/**
 * Represents a portion of the state object
 * that supports multiple rows.
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_StateObjectMultiPart extends VendorAPI_StateObjectPart implements ArrayAccess, Iterator, Countable
{
	/**
	 * State Object?
	 *
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * The index we're currently working on
	 *
	 * @var int
	 */
	protected $index;

	/**
	 * The data to iterate over when doing it that way
	 *
	 * @var array
	 */
	protected $iterative_data;

	/**
	 *
	 *
	 * @param VendorAPI_StateObject $state
	 */
	public function __construct(VendorAPI_StateObject $state)
	{
		$this->state = $state;
	}

	/**
	 * Returns the value, assuming the index has already
	 * been set.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if (is_numeric($this->index))
		{
			$idx = $this->index;
			$this->index = FALSE;
			return $this->data[$this->state->getCurrentVersion()][$idx][$key];
		}
		else
		{
			throw new RuntimeException('no index defined');
		}
	}

	/**
	 * Sets the value in the indexed row
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		if (is_numeric($this->index))
		{
			$idx = $this->index;
			$this->index = FALSE;
			$this->state->updateVersion();
			if (!is_array($this->data[$this->state->getCurrentVersion()]))
			{
				$this->data[$this->state->getCurrentVersion()] = array();
				$this->data[$this->state->getCurrentVersion()][$idx] = array();
			}
			elseif (!is_array($this->data[$this->state->getCurrentVersion()][$idx]))
			{
				$this->data[$this->state->getCurrentVersion()][$idx] = array();
			}
			$this->data[$this->state->getCurrentVersion()][$idx][$key] = $value;
		}
		else
		{
			throw new RuntimeException('no index defined');
		}
	}

	/**
	 * Remove one of the rows
	 *
	 * @param int $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		if ($this->validOffset($offset))
		{
			foreach ($this->data as $version => $data)
			{
				unset($this->data[$version][$offset]);
			}
		}
	}

	/**
	 * Does the row exist?
	 *
	 * @param int $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		if ($this->validOffset($offset))
		{
			$versions = $this->orderedVersions(TRUE);
			foreach ($versions as $version => $data)
			{
				if (array_key_exists($offset, $data))
				{
					return TRUE;
				}
			}
			return FALSE;
		}
	}

	/**
	 * Assign the row to an array of values
	 *
	 * @param int $offset
	 * @param array $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset === NULL)
		{
			$offset = $this->highestIndex() + 1;
			if (is_array($value))
			{
				$data = $this->getData();
				foreach ($data as $key => $val)
				{
					if ($val == $value)
					{
						return;
					}
				}
				$this->state->updateVersion();
				$this->data[$this->state->getCurrentVersion()][$offset] = $value;
			}
			else
			{
				throw new RuntimeException('Invalid value.');
			}
		}
		elseif ($this->validOffset($offset))
		{
			if (is_array($value))
			{
				$data = $this->getData();
				foreach ($data as $key => $val)
				{
					if ($val == $value)
					{
						return;
					}
				}
				$this->state->updateVersion();
				$this->data[$this->state->getCurrentVersion()][$offset] = $value;
			}
			else
			{
				throw new RuntimeException('Invalid value.');
			}
		}
	}

	/**
	 * Set the index we're currently working with
	 * and return a reference to this so that we
	 * can use the __get stuff with it.
	 *
	 * @param int $offset
	 * @return VendorAPI_StateObjectMultiPart
	 */
	public function offsetGet($offset)
	{
		if (!is_numeric($offset))
		{
			$offset = $this->highestIndex() + 1;
		}
		if ($this->validOffset($offset))
		{
			$this->index = $offset;
			return $this;
		}
	}

	/**
	 * Is the offset a valid one?
	 *
	 * @param int $offset
	 * @return boolean
	 */
	protected function validOffset($offset)
	{
		if (is_numeric($offset))
		{
			return TRUE;
		}
		throw new RuntimeException("Invalid offset ($offset).");
	}

	/**
	 * Fetch the highest index among all of the versions
	 *
	 * @return int
	 */
	public function highestIndex()
	{
		$highest = -1;
		if (is_array($this->data))
		{
			foreach ($this->data as $version => $data)
			{
				foreach ($data as $idx => $d)
				{
					$highest = $idx > $highest ? $idx : $highest;
				}
			}
		}
		return $highest;
	}

	/**
	 * Append a new row
	 *
	 * @return $this
	 */
	public function append(array $value = array())
	{
		$idx = $this->highestIndex() + 1;
		$this->state->updateVersion();
		$this->data[$this->state->getCurrentVersion()][$idx] = $value;
		return $this->offsetGet($idx);
	}

	/**
	 * Flatten the versioned data into one solid array
	 *
	 * @return array
	 */
	public function getData()
	{
		$versioned_data = $this->orderedVersions();
		$return = array();
		foreach ($versioned_data as $version => $version_data)
		{
			foreach ($version_data as $row_num => $row_data)
			{
				foreach ($row_data as $col => $val)
				{
					$return[$row_num][$col] = $val;
				}
			}
		}
		return $return;
	}


	/**
	 * Grab all of the table data since the last version. If null
	 * is provided as the version, it'll grab the entire changeset
	 *
	 * @param int|null $version
	 */
	public function getTableDataSince($version = NULL)
	{
		$versioned_data = $this->orderedVersions();
		$return = array();
		foreach ($versioned_data as $v => $version_data)
		{
			if (is_null($version) || $v > $version)
			{
				foreach ($version_data as $row_num => $row_data)
				{
					foreach ($row_data as $col => $val)
					{
						$return[$row_num][$col] = $val;
					}
				}
			}
		}
		return $return;
	}

	/**
	 * Rewind the iterator
	 *
	 * @return boolean
	 */
	public function rewind()
	{
		$this->iterative_data = $this->getData();
		return reset($this->iterative_data);
	}

	/**
	 * Return the current thing
	 *
	 * @return mixed
	 */
	public function current()
	{
		if (is_array($this->iterative_data))
		{
			return current($this->iterative_data);
		}
		return FALSE;
	}

	/**
	 * Return the current key
	 *
	 * @return mixed
	 */
	public function key()
	{
		if (is_array($this->iterative_data))
		{
			return key($this->iterative_data);
		}
		return FALSE;
	}

	/**
	 * Advance the poitner?
	 *
	 * @return mixed
	 */
	public function next()
	{
		if (is_array($this->iterative_data))
		{
			return next($this->iterative_data);
		}
		return FALSE;
	}

	/**
	 * Are we valid?
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return $this->current() !== FALSE;
	}

	/**
	 * How many rows do we have?
	 *
	 * @return int
	 */
	public function count()
	{
		if (!is_array($this->iterative_data))
		{
			$this->iterative_data = $this->getData();
		}
		return count($this->iterative_data);
	}
}
