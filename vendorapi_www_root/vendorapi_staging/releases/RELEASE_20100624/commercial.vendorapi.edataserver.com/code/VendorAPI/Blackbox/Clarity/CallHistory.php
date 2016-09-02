<?php
/*
 * Stores Clarity call results
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Clarity_CallHistory implements IteratorAggregate
{
	/**
	 * @var array
	 */
	protected $history = array();

	/**
	 * Adds the given result to the history
	 *
	 * @param TSS_DataX_Result $result
	 * @return void
	 */
	public function addResult(Clarity_UW_Result $result)
	{
		$call = $result->getCallType();
		$this->history[$call] = $result;
	}

	/**
	 * Indicates whether a result exists for the given call type
	 * @param string $call
	 * @return bool
	 */
	public function hasResult($call)
	{
		return isset($this->history[$call]);
	}

	/**
	 * Returns the stored result for the given call type
	 *
	 * If there is no stored result for that call type, null is returned.
	 *
	 * @param string $call
	 * @return Clarity_UW_Result|null
	 */
	public function getResult($call)
	{
		if (isset($this->history[$call]))
		{
			return $this->history[$call];
		}
		return NULL;
	}

	/**
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->history);
	}
}

?>
