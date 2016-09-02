<?php

/**
 * A Part... Of a State Object
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_StateObjectPart
{
	/**
	 * State?
	 *
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * An array of data?
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Construct?
	 *
	 * @param VendorAPI_StateObject $state
	 */
	public function __construct(VendorAPI_StateObject $state)
	{
		$this->state = $state;
		$this->data = array();
	}

	/**
	 * Gets the latest value of a column in
	 * the table we're acting on
	 *
	 * @param unknown_type $key
	 * @return mixed
	 */
	public function __get($key)
	{
		$versioned_info = $this->orderedVersions(TRUE);
		foreach ($versioned_info as $version => $data)
		{
			if (array_key_exists($key, $data))
			{
				return $data[$key];
			}
		}
		return array_key_exists($key, $this->data[$this->state->getCurrentVersion()]) ? $this->data[$this->state->getCurrentVersion()][$key] : NULL;
	}

	/**
	 * Set the new thing in the latest
	 * revision of the object
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return unknown
	 */
	public function __set($key, $value)
	{
		/*$old = $this->__get($key);
		if ($value !== $old)
		{*/
			$this->state->updateVersion();
			$this->data[$this->state->getCurrentVersion()][$key] = $value;
		//}
	}

	/**
	 * Unsets the given key
	 * @param $key
	 * @return void
	 */
	public function __unset($key)
	{
		$this->state->updateVersion();
		$this->data[$this->state->getCurrentVersion()][$key] = NULL;
	}

	/**
	 * Returns true if the data is set.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset($key)
	{
		$versions = $this->orderedVersions(TRUE);
		foreach ($versions as $ver=>$data)
		{
			// isset is an order of magnitude faster
			// than array_key_exists, so use it first
			if (isset($data[$key]))
			{
				return TRUE;
			}
			elseif (array_key_exists($key, $data))
			{
				// setting it to NULL (the only case where isset == FALSE
				// and array_key_exists == TRUE) unsets it
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * Grab the latest and greatest data
	 * out of the versioned here.
	 *
	 * @return array
	 */
	public function getData()
	{
		$versioned_info = $this->orderedVersions(FALSE);
		$return = array();
		foreach ($versioned_info as $key => $value)
		{
			foreach ($value as $col => $val)
			{
				$return[$col] = $val;
			}
		}
		return $return;
	}

	/**
	 * Returns the data since a provided
	 * version. If version is left null, all table data
	 * will be returned
	 *
	 * @param int $version
	 * @return array
	 */
	public function getTableDataSince($version = NULL)
	{
		$versioned_info = $this->orderedVersions(FALSE);
		$return = array();
		foreach ($versioned_info as $v => $value)
		{
			if (is_null($version) || $v > $version)
			{
				foreach ($value as $col => $val)
				{
					$return[$col] = $val;
				}
			}
		}
		return $return;
	}

	/**
	 * Return an array of versioned info
	 * in a neat little package.
	 *
	 * @param boolean $reverse
	 * @return array
	 */
	public function orderedVersions($reverse = FALSE)
	{
		$return = array();
		if (is_array($this->data) && count($this->data))
		{
			foreach ($this->data as $key => $value)
			{
				if (is_numeric($key))
				{
					$return[$key] = $this->data[$key];
				}
			}
			($reverse) ? krsort($return) : ksort($return);
		}
		return $return;
	}

	public function __toString()
	{
		foreach ($this->getData() as $key => $value)
		{
			$data .= $key;

			if (is_null($value))
			{
				$data .= ': null';
			}
			elseif (is_scalar($value))
			{
				if (trim($value) === '')
				{
					$data .= ': &lt;empty string&gt;';
				}
				else
				{
					$data .= ': ' . $value;
				}
			}
			else
			{
				$data .= "\n `-------------------\n";
				ob_start();
				var_dump($value);

				$var_dump = ob_get_clean();
				if (extension_loaded('xdebug'))
				{
					$var_dump = strip_tags($var_dump);
				}

				foreach (explode("\n", trim($var_dump)) as $line)
				{
					$data .= ' | ' . $line . "\n";
				}
				$data .= "  --------------------\n";
			}
			$data .= "\n";
		}
		return $data;
	}
}