<?php

/** Generate random words.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class OLP_Populate_Word extends OLP_Populate_PopulateItem
{
	/**
	 * @var string
	 */
	protected $valid_characters = '';
	
	/**
	 * @var resource
	 */
	protected $file_source;
	
	/**
	 *@var string
	 */
	protected $pattern;
	
	/** File source is a file that contains a word per line. Defaults
	 * to the unix path for dictionary words.
	 *
	 * @param string $file_source
	 */

	public function __construct($file_source = '/usr/share/dict/words')
	{
		$this->data = array(
			'word' => '',
			'source' => 'unknown',
		);
		
		$this->file_source = $file_source;
	}
	
	/** Returns a random word between MIN/MAX length. Defaults to 3 - 10.
	 * If cannot randomly pick a real word, returns a randomly generated
	 * string instead.
	 *
	 * @param int $min Minimum word length
	 * @param int $max Maximum word length
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		// Initialize values
		if (is_null($min)) $min = 3;
		if (is_null($max)) $max = 10;
		
		// Try getting a dictionary word first
		$this->word = $this->getDictionaryWord($min, $max);
		$this->source = 'dictionary';
		
		// If that failed, just generate purely random string
		if (!$this->word)
		{
			$this->word = $this->getRandomWord($min, $max);
			$this->source = 'random';
		}
		
		return $this->word;
	}
	
	
	/** Get a random word from /usr/share/dict/words. Verifies that word does
	 * not contain non-standard characters. Converts the word into uppercase.
	 *
	 * NOTE: This code is designed to not need to read in the whole file. That
	 * means that we will never read the first line, and word's chances of
	 * being picked depend on how big the word before them is. But it can
	 * handle files of huge lengths.
	 *
	 * Can fail to return a string if you ask for a length that is not very
	 * popular in the file. Will try X number of random words until it fails.
	 *
	 * @param int $min Minimum word length
	 * @param int $max Maximum word length
	 * @return string or FALSE if failed to find one in time.
	 */
	protected function getDictionaryWord($min, $max)
	{
		$word = FALSE;
		if (!file_exists($this->file_source) || !is_readable($this->file_source))
		{
			
			return FALSE;
		}

		$handle = fopen($this->file_source, 'r');
		$tries = 15; // How many times we should try before we give up
		if ($handle)
		{
			// Get max file length
			fseek($handle, 0, SEEK_END);
			$length = ftell($handle);
			//getPattern() will be dynamically binded.
			$this->pattern=$this->getPattern($min,$max);
			do
			{
				// Go to a random location in the file
				fseek($handle, mt_rand(0, $length), SEEK_SET);
				
				// Ignore the first line, as we may be in a middle of a word.
				fgets($handle);
				
				// Read in the word. If at end of file, will fail preg_match and try again
				//Pass the word read from file to modifyWord() for any adjustment
				$word = $this->modifyWord(strtoupper(rtrim(fgets($handle))));
				if (preg_match($this->pattern, $word) == 1)
				{
					break;
				}
				else
				{
					$word = FALSE;
				}
			} while (--$tries);
			
			fclose($handle);
		}		
		return $word;
	}
	
	/** Generate a random word, all uppercase.
	 *
	 * @param int $min Minimum word length
	 * @param int $max Maximum word length
	 * @return string
	 */
	protected function getRandomWord($min, $max)
	{
		$valid_length = strlen($this->valid_characters) - 1;
		$word = '';
		
		for ($length = mt_rand($min, $max); $length; $length--)
		{
			$word .= substr($this->valid_characters, mt_rand(0, $valid_length), 1);
		}
		
		return $word;
	}
	/**
	 * to be implemented by the deriving class to provide the regular expression
	 * @param int $min Minimum  word length
	 * @param int $max Maximum  word length
	 * @return string
	 */
	 
	abstract function getPattern($min,$max);
	/**
	 * to be implemented by the deriving class to modify the word read from file/database as needed
	 * @param string $word Word(s) read from a line of the file
	 * @return string
	 */
	abstract function modifyWord($word);
}

?>
