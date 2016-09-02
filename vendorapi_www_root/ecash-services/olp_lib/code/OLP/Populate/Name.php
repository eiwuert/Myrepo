<?php
/** Generate random Name
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Kaleb Woldearegay <kaleb.woldearegay@sellingsource.com>
 */
class OLP_Populate_Name extends OLP_Populate_Word
{
	public function __construct($file_source)
	{
		parent::__construct($file_source);
		//valid characters for name
		$this->valid_characters='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}
	/**
	 * Regular expression for Name:  
	 *
	 * @param int $min Minimum word length
	 * @param int $max Maximum word length
	 * @return string
	 */
	public function getPattern($min,$max)
	{
		return '/^[' . preg_quote($this->valid_characters) . ']{'.$min.','.$max.'}$/i';
	}
	/**
	 * The word read from the file may need adjustment like chopping 
	 * or adding words/characters.
	 * For Name, no adjustment is needed when read from 
	 * olp_lib/code/OLP/Populate/names.txt
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
