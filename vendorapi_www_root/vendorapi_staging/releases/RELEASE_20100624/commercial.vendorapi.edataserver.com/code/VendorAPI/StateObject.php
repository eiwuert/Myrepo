<?php

/**
 * Vendor API state object
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_StateObject
{
	/**
	 * @var StateObjectPart
	 */
	protected $data;

	/**
	 * What
	 *
	 * @var int
	 */
	protected $version;

	/**
	 * Have we updated the version since
	 * we last serialized?
	 *
	 * @var boolean
	 */
	protected $updated_version;

	/**
	 * Array of Paerts
	 *
	 * @var Array
	 */
	protected $data_parts;
	
	/**
	 * Special list of reference data
	 * 
	 * @var Array
	 */
	protected $reference_parts;

	/**
	 * ?
	 *
	 * @param int $version
	 */
	public function __construct($version = 0)
	{
		$this->data = new VendorAPI_StateObjectPart($this);
		$this->version = $version;
		$this->data_parts = array();
		$this->reference_parts = array();
	}

	/**
	 * Set the current version
	 *
	 * @param int  $version
	 * @return void
	 */
	public function setCurrentVersion($version)
	{
		$this->version = $version;
	}

	/**
	 * Return the current verision
	 *
	 * @return string
	 */
	public function getCurrentVersion()
	{
		return $this->version;
	}

	/**
	 * VendorAPI_StateObject Set
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		if ($this->isPart($name))
		{
			throw new RuntimeException("Invalid part object.");
		}
		elseif ($value instanceof VendorAPI_StateObjectPart)
		{
			$this->data_parts[$name] = $value;
		}
		else
		{
			$this->data->__set($name, $value);
		}
	}

	/**
	 * VendorAPI_StateObject Get
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if ($this->isPart($name))
		{
			return $this->getPart($name);
		}
		else
		{
			return $this->data->__get($name);
		}
	}

	public function __isset($name)
	{
		return isset($this->data_parts[$name]) || isset($this->data->$name);
	}

	/**
	 * Is this a part?
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function isPart($name)
	{
		return isset($this->data_parts[$name])
			&& $this->data_parts[$name] instanceof VendorAPI_StateObjectPart;
	}

	/**
	 * Is this a multirow part?
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function isMultiPart($name)
	{
		return isset($this->data_parts[$name]) ? $this->data_parts[$name] instanceof VendorAPI_StateObjectMultiPart : FALSE;
	}

	/**
	 * Returns a new part?
	 *
	 * @param string $name
	 * @return VendorAPI_StateObjectMultiPart | VendorAPI_StateObjectPart
	 */
	protected function getPart($name)
	{
		if (!$this->isPart($name))
		{
			throw new RuntimeException("Invalid part name.");
		}
		return $this->data_parts[$name];
	}

	/**
	 * Returns the parts of the state object without the multiparts.
	 *
	 * @return array
	 */
	public function getSingleParts()
	{
		$return = array();
		foreach ($this->data_parts as $part)
		{
			if (!$part instanceof VendorAPI_StateObjectMultiPart)
			{
				$return[] = $part;
			}
		}
		return $return;
	}

	/**
	 * Returns the parts of the state object.
	 *
	 * @return array
	 */
	public function getAllParts()
	{
		return array_keys($this->data_parts);
	}

	/**
	 * Update the version?
	 *
	 * @return void
	 */
	public function updateVersion($force = FALSE)
	{
		if (!$this->updated_version || $force)
		{
			++$this->version;
			$this->updated_version = TRUE;
		}

	}

	/**
	 * Return the latest and greatest data
	 * for a table. If no table is provided, it'll
	 * supply an array for every table.
	 *
	 * @param string|null $table
	 * @return array
	 */
	public function getData($table = NULL)
	{

		if (!empty($table))
		{
			$return = array();
			if ($this->data_parts[$table] instanceof VendorAPI_StateObjectPart)
			{
				$data = $this->data_parts[$table]->getData();
				if (!empty($data))
				{
					$return[$table] = $data;
				}
			}
			else
			{
				throw new RuntimeException('Invalid table '.$table.'.');
			}
		}
		else
		{
			$return = $this->data->getData();
			foreach ($this->data_parts as $table => $table_data)
			{
				$data = $table_data->getData();
				if (!empty($data))
				{
					$return[$table] = $data;
				}
			}
		}
		return $return;
	}

	/**
	 * Returns all of the table data since a version
	 *
	 * @param int $version
	 * @return array
	 */
	public function getTableDataSince($version = NULL)
	{
		$return = array();
		foreach($this->data_parts as $table => $part)
		{
			$data = $part->getTableDataSince($version);
			if (!empty($data))
			{
				$return[$table] = $data;
			}
		}
		return $return;
	}

	/**
	 * Create a new part
	 *
	 * @param string $name
	 * @param boolean $is_multi
	 * @return VendorAPI_StateObjectPart
	 */
	public function createPart($name, $is_multi = FALSE)
	{
		if (!$this->isPart($name))
		{
			$class = $is_multi ? 'VendorAPI_StateObjectMultiPart' : 'VendorAPI_StateObjectPart';
			$this->data_parts[$name] = new $class($this);
		}
		return $this->data_parts[$name];
	}

	/**
	 * Creates a new mult part object
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function createMultiPart($name)
	{
		return $this->createPart($name, TRUE);
	}

	/**
	 * Unserializing so we know that we have not
	 * incremented our version yet
	 *
	 * @return void
	 */
	public function __wakeup()
	{
		$this->updated_version = FALSE;
	}

	/**
	 * Return an array of every version
	 * and the changes that take place in those versions
	 * @return array
	 */
	public function getVersionedData()
	{
		$return = array();
		foreach ($this->data_parts as $part_name => $part)
		{
			$versioned_info = $part->orderedVersions(FALSE);
			foreach ($versioned_info as $version => $value)
			{
				if (!is_array($return[$version]))
				{
					$return[$version] = array();
				}
				$return[$version][$part_name] = $value;
			}
		}
		ksort($return);
		return $return;
	}
	
	/**
	 * Return an array of data for this object
	 * @return string
	 */
	public function getStateData()
	{
		return $this->data->getData();
	}
	
	/**
	 * Creates a new reference part for $table
	 * @param String $table
	 * @return void
	 */
	protected function createReferencePart($table)
	{
		$this->reference_parts[$table] = new VendorAPI_StateObjectMultiPart($this);
		return $this->reference_parts[$table];
	}
	
	/**
	 * Get a reference part for table $table, Returns boolean false
	 * if the part does not already exist.
	 * @param String $table
	 * @return VendorAPI_StateObjectMultiPart | FALSE
	 */
	public function getReferencePart($table)
	{
		if ($this->reference_parts[$table] instanceof VendorAPI_StateObjectMultiPart)
		{
			return $this->reference_parts[$table];	
		}
		return FALSE;
	}
	
	/**
	 * Adds a new reference part and appends it to the part
	 * @param String $table 
	 * @param Array $data
	 * @return void
	 */
	public function addReferencePart($table, array $data)
	{
		if (($part = $this->getReferencePart($table)) === FALSE)
		{
			$part = $this->createReferencePart($table);
		}
		$part[] = $data;	
	}
	
	/**
	 * Returns an array containing table => array(rows)
	 * of all the reference data
	 * @param Integer $version
	 * @return array
	 */
	public function getReferenceData($version = NULL)
	{
		$return = array();
		foreach ($this->reference_parts as $table => $part)
		{
			$part_data = $part->getTableDataSince($version);
			if (!empty($part_data))
			{
				$return[$table] = $part_data;
			}
		}
		return $return;
	}
	
	/**
	 * removes a reference part from the list
	 * @return array
	 */
	public function removeReferencePart($table)
	{
		unset($this->reference_parts[$table]);	
	}

	public function __toString()
	{
		$data .= 'Version: ' . $this->version . "\n";
		$data .= "Data:\n";

		foreach (explode("\n", (string)$this->data) as $line)
		{
			$data .= '    ' . $line . "\n";
		}

		foreach ($this->getAllParts() as $part)
		{
			$data .= ucwords($part) . ":\n";

			foreach (explode("\n", (string)$this->getPart($part)) as $line)
			{
				$data .= '    ' . $line . "\n";
			}
		}
		
		return $data;
	}
}

?>
