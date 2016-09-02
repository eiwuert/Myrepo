<?php

/** Select randomly from a simple array.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_Array extends OLP_Populate_PopulateItem
{
	/**
	 * @var array
	 */
	protected $source_array;
	
	/** Stores a random array to pick from.
	 *
	 * @param array $source_array
	 */
	public function __construct(array $source_array)
	{
		$this->data = array(
			'key' => 0,
			'value' => '',
		);
		
		$this->source_array = $source_array;
	}
	
	/** Return a random value from the array.
	 *
	 * @param mixed $min Not used
	 * @param mixed $max Not used
	 * @return mixed
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->key = array_rand($this->source_array);
		$this->value = $this->source_array[$this->key];
		
		return $this->value;
	}
}
?>
