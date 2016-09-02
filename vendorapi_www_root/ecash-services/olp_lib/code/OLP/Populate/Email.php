<?php

/** Generate a random, real email address.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_Email extends OLP_Populate_PopulateItem
{
	/**
	 * @var PopulateItemNumber
	 */
	protected $random_number;
	
	/** Initializes class.
	 */
	public function __construct()
	{
		$this->data = array(
			'email' => '',
		);
		
		$this->random_number = new OLP_Populate_Number();
	}
	
	/** Generate a random, fake phone number.
	 *
	 * @param mixed $min Not used
	 * @param mixed $max Not used
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->email = $this->random_number->getRandomItem(1000, 99999999) . '@tssmasterd.com';
		
		return $this->email;
	}
}

?>
