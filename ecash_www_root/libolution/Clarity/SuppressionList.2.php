<?php
/**
 * A new and improved suppression list class.@global 
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class TSS_SuppressionList_2 implements TSS_ISuppressionList_1
{
	protected $lookup_table = array();

	protected $regex_list = array();

	/**
	 * Constructor
	 *
	 * @param array $values an array of values that represent the values to check in the list
	 */
	public function __construct(array $values)
	{
		foreach ($values as $value)
		{
			$this->storeValue($value);
		}
	}

	/**
	 * Matches $value against the suppression list and returns TRUE if there is a match.
	 *
	 * @param string $value
	 * @return bool
	 */
	public function match($value)
	{
		if (isset($this->lookup_table[strtolower($value)]))
		{
			return TRUE;
		}

		foreach ($this->regex_list as $pattern)
		{
			if (preg_match($pattern, $value) !== 0)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	private function storeValue($value)
	{
		if (preg_match('/^\/.*\/$/', $value))
		{
			$this->regex_list[] = $value . 'i';
		}
		elseif (preg_match('/(?<!\\\)[\*%\?\+]/', $value))
		{
			$this->regex_list[] = $this->translateWildcard($value) . 'i';
		}
		else
		{
			$this->lookup_table[strtolower(preg_replace('/\\\([\*%\?\+])/', '\\1', $value))] = TRUE;
		}
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
