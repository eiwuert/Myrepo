<?php

/**
 * Simple replacement of words, based upon a simple data source.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_AutoCorrect_Replace extends OLP_AutoCorrect
{
	/**
	 * @var array
	 */
	protected $replacement_data;
	
	/**
	 * Sets up the simple replacement autocorrection system.
	 *
	 * @param array $replacement_data
	 */
	public function __construct(array $replacement_data)
	{
		$this->replacement_data = $replacement_data;
	}
	
	/**
	 * Processes the auto-correction over a word.
	 *
	 * @param string $word
	 * @return string
	 */
	public function processWord($word)
	{
		$replacement = $this->getReplacementWord($word);
		
		return $replacement === NULL || $replacement === FALSE ? $word : $replacement;
	}
	
	/**
	 * Gets the replacement word for this word.
	 *
	 * @param string $word
	 * @return string
	 */
	protected function getReplacementWord($word)
	{
		$replacement = NULL;
		
		if (array_key_exists($word, $this->replacement_data))
		{
			$replacement = $this->replacement_data[$word];
		}
		
		return $replacement;
	}
}

?>
