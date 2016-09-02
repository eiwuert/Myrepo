<?php

/**
 * Debugging configuration class for VendorAPI Blackbox
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_DebugConfig
{
	// should be false for normal operation
	const NO_CHECKS = 'NO_CHECKS';

	// should be true for normal operation
	const PREV_CUSTOMER = 'PREV_CUSTOMER';
	const USED_INFO = 'USED_INFO';
	const DATAX = 'DATAX';
	const FT = 'FT';
	const CL = 'CL';
	const DATAX_FRAUD = 'DATAX_FRAUD';
	const RULES = 'RULES';
	const SUPPRESSION_LISTS = 'SUPPRESSION_LISTS';

	/**
	 * The flags that have been set on this object.
	 * @var array
	 */
	protected $flags = array(
	);

	/**
	 * @param array $flags Initial flags to set up
	 * @return void
	 */
	public function __construct($flags = array())
	{
		foreach ($flags as $flag => $value)
		{
			$this->setFlag($flag, $value);
		}
	}

	/**
	 * Generic helper function to determine if a rule should be skipped.
	 *
	 * This checks the flag passed in, "RULES" if the flag is not RULES,
	 * and finally "NO_CHECKS" to determine whether to skip the rule.
	 *
	 * @param string $flag
	 * @return bool TRUE = flag indicates skip, FALSE = flag indicates run
	 */
	public function skipRule($flag = NULL)
	{
		if (!$flag) $flag = self::RULES;

		// if the flag is EXPLICITLY set, i.e. not NULL, use that
		// if the flag was not explicitly set, defer to NO_CHECKS
		if ($this->flagTrue($flag)) return FALSE;
		if ($this->flagFalse($flag)) return TRUE;

		return $this->flagTrue(self::NO_CHECKS);
	}

	/**
	 * Determine if a flag is explicitly FALSE (not NULL).
	 *
	 * @param string $flag
	 * @return bool whether the flag is FALSE
	 */
	public function flagFalse($flag)
	{
		return isset($this->flags[$flag])
			&& $this->flags[$flag] === FALSE;
	}

	/**
	 * Determine if a flag is explicitly TRUE
	 *
	 * @param string $flag
	 * @return bool whether the flag is TRUE
	 */
	public function flagTrue($flag)
	{
		return isset($this->flags[$flag])
			&& $this->flags[$flag] === TRUE;
	}

	/**
	 * Sets the state of a flag in the config.
	 *
	 * @param string $flag
	 * @param mixed $value
	 * @return void
	 */
	public function setFlag($flag, $value = TRUE)
	{
		$this->flags[$flag] = $value;
	}

	/**
	 * Indicates whether a flag has been set
	 *
	 * @param string $flag
	 * @return bool
	 */
	public function hasFlag($flag)
	{
		return isset($this->flags[$flag]);
	}

	/**
	 * Removes a flag from the config object.
	 *
	 * @param string $flag
	 * @return void
	 */
	public function unsetFlag($flag)
	{
		unset($this->flags[$flag]);
	}

	/**
	 * Return the value of a flag.
	 *
	 * @param string $flag
	 * @return mixed NULL if not set
	 */
	public function getFlag($flag)
	{
		return isset($this->flags[$flag])
			? $this->flags[$flag]
			: NULL;
	}

	/**
	 * Returns a copy of the flags array
	 *
	 * @return array flags set in this object
	 */
	public function getFlags()
	{
		return $this->flags;
	}
}

?>
