<?php
/**
 * Extension of the SPL ArrayObject class.
 * 
 * Implements helper methods for common functionality.
 *
 * @author Brian Feaver <brian.feaver@Sellingsource.com>
 */
class TSS_ArrayObject extends ArrayObject
{
	/**
	 * Overloads the offsetGet method so that it checks to see if $index exists before returning it.
	 * 
	 * This method prevents the PHP notice from being generated if the index does not exist.
	 *
	 * @param mixed $index
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return $this->offsetExists($index) ? parent::offsetGet($index) : NULL;
	}
	
	/**
	 * Returns the value at $index or $default if $index does not exist.
	 *
	 * @param mixed $index
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($index, $default)
	{
		return $this->offsetExists($index) ? parent::offsetGet($index) : $default;
	}
}
