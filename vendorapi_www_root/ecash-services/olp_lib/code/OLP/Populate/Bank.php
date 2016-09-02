<?php
/** Generate random Bank
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Kaleb Woldearegay <kaleb.woldearegay@sellingsource.com>
 */
class OLP_Populate_Bank extends OLP_Populate_Word
{
	public function __construct($file_source)
	{
		parent::__construct($file_source);
		/** 
		 * If the file is not used for some reason and if the 
		 * getRandomWord() is called, let the valid 
		 * characters be out of dot, space, slash, dash..
		 *
		 */
		$this->valid_characters='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	}
	/**
	 * Regular expression for Bank: The bank name shall begin with 
	 * either a letter or a digit
	 *
	 * @param int $min Minimum word length
	 * @param int $max Maximum word length
	 * @return string
	 */
	public function getPattern($min,$max)
	{
		return '/^[A-Z0-9]([A-Z0-9-,&.\/ ]*(\([A-Z0-9-,.&\/ ]+\))?)+$/i';
	}
	/**
	 * The word read from the file may need adjustment like chopping
	 * or adding words/characters.
	 * For Bank, no adjustment needed as read from olp_lib/code/OLP/Populate/bank.txt
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
