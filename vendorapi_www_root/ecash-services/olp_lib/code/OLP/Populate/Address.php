<?php

/** Generate a random, fake address.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_Address extends OLP_Populate_PopulateItem
{
	/**
	 * @var PopulateItemNumber
	 */
	protected $random_number;
	
	/**
	 * @var PopulateItemAddressSuffix
	 */
	protected $random_suffix;
	
	/**
	 * @var PopulateItemState
	 */
	protected $random_state;
	
	/**
	 * @var PopulateItemCity
	 */
	protected $random_city;
	
	/** Initializes class.
	 */
	public function __construct()
	{
		$this->data = array(
			'street' => '',
			'city' => '',
			'state' => '',
			'state_abbr' => '',
			'zip_code' => 0,
			
			'address' => '', // All merged into one line
		);
		
		$this->random_number = new OLP_Populate_Number();
		$this->random_suffix = new OLP_Populate_AddressSuffix();
		$this->random_state = new OLP_Populate_State();
		$this->random_city = new OLP_Populate_City(dirname(__FILE__) . '/cities.txt');
	}
	
	/** Generate a random, fake address. Excludes WV, VA, and UT because
	 *
	 * @param mixed $min If passed, theses are passed along to PopulateItemState
	 * @param mixed $max Not used
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->street = $this->random_number->getRandomItem(100, 20000) . ' ' .
			$this->random_city->getRandomItem(4, 20) . ' ' .
			$this->random_suffix->getRandomItem();
		$this->city = $this->random_city->getRandomItem(3, 50);
		$this->state_abbr = $this->random_state->getRandomItem($min);
		$this->state = $this->random_state->state;
		$this->zip_code = $this->random_number->getRandomItem(10000, 99999);
		
		$this->address = "{$this->street}, {$this->city}, {$this->state_abbr} {$this->zip_code}";
		
		return $this->address;
	}
	
}

?>
