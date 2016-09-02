<?php

/** Generate random numbers.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_Number extends OLP_Populate_PopulateItem
{
	/**
	 * @var int
	 */
	protected $pad_length;
	
	/** If you want 0's padded up front to make a specific length,
	 * pass in the string length you wish here.
	 *
	 * @param int $pad_length
	 */
	public function __construct($pad_length = 0)
	{
		$this->data = array(
			'number' => 0,
		);
		
		$this->pad_length = $pad_length;
	}
	
	/** Generate a random number between min/max. Defaults to full
	 * random range. If min is set but max isn't, does range between
	 * 0 and min.
	 *
	 * @param int $min Minimum string length
	 * @param int $max Maximum string length
	 * @return mixed If padding, may be string. Otherwise, integer.
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		// mt_rand handles NULLs for us.
		$this->number = mt_rand($min, $max);
		
		if ($this->pad_length)
		{
			$this->number = str_pad($this->number, $this->pad_length, '0', STR_PAD_LEFT);
		}
		
		return $this->number;
	}
}

?>
