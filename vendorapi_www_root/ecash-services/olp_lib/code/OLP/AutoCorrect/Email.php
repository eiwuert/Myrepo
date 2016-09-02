<?php

/**
 * Auto-corrects email addresses.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_AutoCorrect_Email extends OLP_AutoCorrect
{
	/**
	 * @var OLP_AutoCorrect
	 */
	protected $autocorrect_tld;
	
	/**
	 * @var OLP_AutoCorrect
	 */
	protected $autocorrect_domain_base;
	
	/**
	 * Initializes the Email Auto Correction system.
	 *
	 * @param OLP_AutoCorrect $tld
	 * @param OLP_AutoCorrect $domain_base
	 */
	public function __construct(OLP_AutoCorrect $tld, OLP_AutoCorrect $domain_base)
	{
		$this->autocorrect_tld = $tld;
		$this->autocorrect_domain_base = $domain_base;
	}
	
	/**
	 * Processes the auto-correction over a word.
	 *
	 * @param string $word
	 * @return string
	 */
	public function processWord($word)
	{
		$replacement = $word;
		
		$parts = $this->splitEmail($word);
		if ($parts)
		{
			$replacement = sprintf("%s@%s.%s",
				$parts['local'],
				$this->autocorrect_domain_base->processWord($parts['domain']),
				$this->autocorrect_tld->processWord($parts['tld'])
			);
		}
		
		return $replacement;
	}
	
	/**
	 * Split an email address into its parts.
	 *
	 * @param string $email
	 * @return array
	 */
	protected function splitEmail($email)
	{
		$parts = NULL;
		
		if (preg_match('/^([^@]+)@(.+)\.([^\.]+)$/', $email, $matches))
		{
			$parts = array(
				'local' => $matches[1],
				'domain' => $matches[2],
				'tld' => $matches[3],
			);
		}
		
		return $parts;
	}
}

?>
