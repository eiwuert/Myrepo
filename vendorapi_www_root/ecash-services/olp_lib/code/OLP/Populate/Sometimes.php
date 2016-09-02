<?php
/** Wrapper class to sometimes return random data.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_Sometimes extends OLP_Populate_PopulateItem
{
	/**
	 * @var PopulateItem
	 */
	protected $subclass;
	
	/**
	 * @var float
	 */
	protected $percent_happens;
	
	/**
	 * @var mixed
	 */
	protected $default_value;
	
	/** Pass in a PopulateItem and how often you want it to return data.
	 * Default value is what it will return otherwise.
	 *
	 * @param PopulateItem $subclass Class to decorate.
	 * @param float $percent_happens Between 0.0 and 1.0
	 * @param mixed $default_value
	 */
	public function __construct(OLP_Populate_PopulateItem $subclass, $percent_happens = 0.5, $default_value = '')
	{
		$this->data = array(
			'random_picked' => FALSE,
			'random_value' => $default_value,
		);
		
		$this->subclass = $subclass;
		$this->percent_happens = $percent_happens;
		$this->default_value = $default_value;
	}
	
	/** Randomly pick random item. Min/max get passed to subobject.
	 *
	 * @param mixed $min Not used
	 * @param mixed $max Not used
	 * @return mixed
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->random_picked = mt_rand() / mt_getrandmax() < $this->percent_happens;
		
		if ($this->random_picked)
		{
			$this->random_value = $this->subclass->getRandomItem($min, $max);
		}
		else
		{
			$this->random_value = $this->default_value;
		}
		
		return $this->random_value;
	}
	
	/** Magic magic getter. If we didn't pick the subobject, return default
	 * values instead of what the item would normally return. Otherwise,
	 * return what the subobject has.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($this->data[$name]))
			return $this->data[$name];
		elseif ($this->random_picked)
		{
			return $this->subclass->{$name};
		}
		elseif (isset($this->subclass->{$name}))
		{
			return $this->default_value;
		}
	}
}

?>
