<?php

/**
 * Implements a system of autocorrecting words.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class OLP_AutoCorrect
{
	/**
	 * @var int
	 */
	private $words_corrected = 0;
	
	/**
	 * Auto-correct words. Supports being passed one word, or an array of
	 * words to check against.
	 *
	 * @param string|array $word
	 * @return string
	 */
	public final function autoCorrect($word)
	{
		$this->words_corrected = 0;
		
		if (is_scalar($word))
		{
			$corrected_word = $this->processWord($word);
			
			if ($corrected_word != $word) $this->words_corrected++;
		}
		elseif (is_array($word))
		{
			$corrected_word = array();
			
			foreach ($word AS $key => $original_word)
			{
				$processed_word = $this->processWord($original_word);
				
				if ($processed_word != $original_word) $this->words_corrected++;
				
				$corrected_word[$key] = $processed_word;
			}
		}
		else
		{
			throw new InvalidArgumentException("AutoCorrect requires a scalar or array input.");
		}
		
		return $corrected_word;
	}
	
	/**
	 * Returns how many words were modified by the last run.
	 *
	 * @return int
	 */
	public final function getAutoCorrectedCount()
	{
		return $this->words_corrected;
	}
	
	/**
	 * Processes the auto-correction over a word.
	 *
	 * @param string $word
	 * @return string
	 */
	abstract public function processWord($word);
}

?>
