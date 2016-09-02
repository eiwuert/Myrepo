<?php

/** Generate a random, fake phone number.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_PhoneNumber extends OLP_Populate_PopulateItem
{
	/** Initializes class.
	 */
	public function __construct()
	{
		$this->data = array(
			'divider' => '-',
			
			'area_code' => 0,
			'local_a' => 555,
			'local_b' => 0,
			'local' => '',
			
			'phone_number' => '',
		);
	}
	
	/** Generate a random, fake phone number.
	 *
	 * @param mixed $min Not used
	 * @param mixed $max Not used
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->area_code = mt_rand(200, 999);
		$this->local_b = mt_rand(1000, 9999);
		
		$this->local = $this->local_a . $this->divider . $this->local_b;
		$this->phone_number = $this->area_code . $this->divider . $this->local;
		
		return $this->phone_number;
	}
}

?>
