<?php
/** Generate random City
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Kaleb Woldearegay <kaleb.woldearegay@sellingsource.com>
 */
class OLP_Populate_City extends OLP_Populate_Word
{
	public function __construct($file_source)
	{
		parent::__construct($file_source);
		/** 
		 * If the file is not used for some reason and if the 
		 * getRandomWord() is called, let the valid characters 
		 * be out of dot, space and dash..
		 *
		 */
		$this->valid_characters='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}
	/**
	 * Regular expression for City: City shall begin with atleast two 
	 * letters [like St. Louis] and followed by letters a hyphen(dash) or a dot(.) or space....
	 *
	 * @param int $min Minimum word length
	 * @param int $max Maximum word length
	 * @return string
	 */
	public function getPattern($min,$max)
	{
		return '/^[A-Z]{'.$min.',}([A-Z-. ]{'.$min.','.$max.'})?$/i';
	}
	/**
	 * The word read from the file may need adjustment like chopping 
	 * or adding words/characters.
	 * For City, no adjustment is needed when read from 
	 * olp_lib/code/OLP/Populate/cities.txt
	 *
	 * @param string $word word(s) read from the single line of the file
	 * @return string
	 */	
	public function modifyWord($word)
	{
		return $word;
	}
}
?>
