<?php

/** Return a random street suffix.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_AddressSuffix extends OLP_Populate_PopulateItem
{
	/**
	 * @var array
	 */
	protected $addresssuffix = array(
		'STREET' => 'ST',
		'DRIVE' => 'DR',
		'LANE' => 'LN',
		'CIRCLE' => 'CR',
		'PLACE' => 'PL',
	);
	
	/** Initializes class.
	 */
	public function __construct()
	{
		$this->data = array(
			'suffix' => '',
			'suffix_abbr' => '',
		);
	}
	
	/** Returns a random street suffix.
	 *
	 * @param mixed $min Not used
	 * @param mixed $max Not used
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->suffix = array_rand($this->addresssuffix);
		$this->suffix_abbr = $this->addresssuffix[$this->suffix];
		
		return $this->suffix_abbr;
	}
}

?>
