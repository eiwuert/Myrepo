<?php

/** Generate a random date.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_Date extends OLP_Populate_PopulateItem
{
	/**
	 * @var string
	 */
	protected $format;
	
	/** Pass in the requested string format for the date.
	 *
	 * @param string $format
	 */
	public function __construct($format = 'Y-m-d')
	{
		$this->data = array(
			'timestamp' => 0,
			'date' => '',
			
			'year' => 0,
			'month' => 0,
			'day' => 0,
		);
		
		$this->format = $format;
	}
	
	/** Return a random date between MIN/MAX. Defaults to between
	 * today and 50 years ago. Uses strtotime to parse inputs.
	 *
	 * @param string $min
	 * @param string $max
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		// Setup defaults
		if (is_null($min)) $min = time();
		if (is_null($max)) $max = strtotime('-50 year');
		
		// Convert non-timestamps to timestamps
		if (!is_int($min)) $min = strtotime($min);
		if (!is_int($max)) $max = strtotime($max);
		
		if ($min > $max)
		{
			$this->timestamp = mt_rand($max, $min);
		}
		else
		{
			$this->timestamp = mt_rand($min, $max);
		}
		
		$this->date = date($this->format, $this->timestamp);
		$this->year = date('Y', $this->timestamp);
		$this->month = date('m', $this->timestamp);
		$this->day = date('d', $this->timestamp);
		
		return $this->date;
	}
}

?>
