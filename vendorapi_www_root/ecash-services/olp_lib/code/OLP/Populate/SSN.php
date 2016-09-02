<?php

/** Generate a random, fake social security number.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_SSN extends OLP_Populate_Number
{
	/** Initializes class.
	 */
	public function __construct()
	{
		parent::__construct(2);
		
		$this->data = array_merge($this->data, array(
			'ssn_1' => '',
			'ssn_2' => '',
			'ssn_3' => '',
			'ssn' => '',
		));
	}
	
	/** Generate a random, fake social security number.
	 *
	 * @param mixed $min Not used
	 * @param mixed $max Not used
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->ssn_1 = '8' . parent::getRandomItem(0, 99);
		$this->ssn_2 = parent::getRandomItem(1, 99);
		$this->ssn_3 = parent::getRandomItem(1000, 9999);
		
		$this->ssn = "{$this->ssn_1}-{$this->ssn_2}-{$this->ssn_3}";
		
		return $this->ssn;
	}
}

?>
