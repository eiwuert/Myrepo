<?php
/** Common interface for populate items. Does magic with $data.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class OLP_Populate_PopulateItem
{
	/**
	 * @var array
	 */
	protected $data = array();
	
	/** Magically gets a value.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($this->data[$name]))
			return $this->data[$name];
		else
			return NULL;
	}
	
	/** Magically sets a value.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	protected function __set($name, $value)
	{
		if (isset($this->data[$name]))
			$this->data[$name] = $value;
	}
	
	/** Magically determine if a value is set.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}
	
	/** Return a random item.
	 *
	 * @param mixed $min
	 * @param mixed $max
	 * @return mixed
	 */
	abstract public function getRandomItem($min = NULL, $max = NULL);
}
?>
