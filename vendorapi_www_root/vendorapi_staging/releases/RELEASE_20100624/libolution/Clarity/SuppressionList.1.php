<?php
/**
 * Suppression list class.
 *
 * This version can replace the Suppress_List class in lib. It removes the databse specific code
 * and requires that you give it the list at construction.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class TSS_SuppressionList_1 implements TSS_ISuppressionList_1
{
	protected $list_values;

	/**
	 * Constructor
	 *
	 * @param array $values an array of values that represent the values to check in the list
	 */
	public function __construct(array $values)
	{
		$this->list_values = $values;
	}

	/**
	 * Matches $value against the suppression list and returns TRUE if there is a match.
	 *
	 * @param string $value
	 * @return bool
	 */
	public function match($value)
	{
		$matched = FALSE;

		foreach ($this->list_values as $pattern)
		{
			if (preg_match('/^\/.*\/$/', $pattern))
			{
				$matched = (preg_match($pattern . 'i', $value) !== 0);
			}
			elseif (preg_match('/(?<!\\\)[\*%\?\+]/', $pattern) !== 0)
			{
				$preg = $this->translateWildcard($pattern);
				$matched = (preg_match($preg . 'i', $value) !== 0);
			}
			else
			{
				$pattern = preg_replace('/\\\([\*%\?\+])/', '\\1', $pattern);
				$matched = (strcasecmp($pattern, $value) == 0);
			}
			if ($matched) break;
		}

		return $matched;
	}

	/**
	 * Takes wildcard characters and converts them to a regex pattern match within $value.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function translateWildcard($value)
	{
		$tokens = preg_split('/(?<!\\\)([*%?+])/', $value, NULL, PREG_SPLIT_DELIM_CAPTURE);

		$pattern = '';

		foreach ($tokens as $index => $token)
		{
			switch ($token)
			{
				// translate to regex
				case '+': $token = '.+'; break;
				case '*': $token = '.*'; break;
				case '%': $token = '.'; break;
				case '?': $token = '.?'; break;

				default:
					$token = preg_replace('/\\\([\*%\?\+])/', '\\1', $token);
					$token = preg_quote($token);
					break;
			}

			$pattern .= $token;
		}

		$pattern = "/^{$pattern}$/";
		return $pattern;
	}
}
