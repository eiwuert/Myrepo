<?php
/**
 * Defines the OLPBlackbox_DebugConf class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Debugging configuration class for Blackbox factory stuff in OLP.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_DebugConf
{
	// should be false for normal operation
	const NO_CHECKS = 'NO_CHECKS';

	// should be true for normal operation
	const PREV_CUSTOMER = 'PREV_CUSTOMER';
	const USED_INFO = 'USED_INFO';
	const DATAX_IDV = 'DATAX_IDV';
	const DATAX_PERF = 'DATAX_PERF';
	const RULES = 'RULES';
	const LIMITS = 'LIMITS';
	const ABA = 'ABA';
	const FRAUD_SCAN = 'FRAUD_SCAN';
	const CFE_RULES = 'CFE_RULES';
	const PREACT_CHECK = 'PREACT_CHECK';
	const ROOT_PROPERTY_SHORT = 'ROOT_PROPERTY_SHORT';
	
	// array constants
	const USE_TIER = 'USE_TIER';
	const EXCLUDE_TIER = 'EXCLUDE_TIER';
	const TARGETS_RESTRICT = 'TARGETS_RESTRICT';
	const TARGETS_EXCLUDE = 'TARGETS_EXCLUDE';
	const RULES_EXCLUDE = 'RULES_EXCLUDE';
	const RULES_INCLUDE = 'RULES_INCLUDE';
	
	/**
	 * The flags that have been set on this object.
	 *
	 * @var array
	 */
	protected $flags = array(
		self::USE_TIER => array(),
		self::EXCLUDE_TIER => array(),
		self::TARGETS_RESTRICT => array(),
		self::TARGETS_EXCLUDE => array(),
		self::RULES_EXCLUDE => array(),
		self::RULES_INCLUDE => array(),
	);

	/**
	 * Constructor for OLPBlackbox_DebugConf
	 *
	 * @param array $flags Initial flags to set up
	 * @return void
	 */
	public function __construct($flags = array())
	{
		$this->class_reflection = new ReflectionClass(__CLASS__);

		foreach ($flags as $flag => $value)
		{
			$this->setFlag($flag, $value);
		}
	}

	/**
	 * Generic helper function to determine if a rule should be skipped.
	 *
	 * This checks the flag passed in, "RULES" if the flag is not RULES,
	 * and finally "NO_CHECKS" to determine whether to skip the rule
	 * for debugging purposes. It is up to the caller to connect rules and flags
	 * before calling this function. When in doubt, use flagTrue and flagFalse
	 * yourself.
	 *
	 * @param string $flag Constant flag name (Use NULL for rule name based checks)
	 * @param string $rule Rule name
	 *
	 * @return bool TRUE = flag indicates skip, FALSE = flag indicates run
	 */
	public function debugSkipRule($flag = NULL, $rule = NULL)
	{
		// not all flags they could ask about in a generic manner make
		// sense for this method. (i.e. don't let them check the array flags here)
		if (in_array($flag, array(self::USE_TIER,
								self::EXCLUDE_TIER,
								self::TARGETS_RESTRICT,
								self::TARGETS_EXCLUDE)))
		{
			throw new InvalidArgumentException(
				"cannot determine debug skip status for flag $flag"
			);
		}

		if ($flag !== NULL && $flag !== self::RULES)
		{
			// if the flag and rule is EXPLICITLY set, i.e. not NULL, defer to that.
			if ($this->flagTrue($flag)) return FALSE;
			if ($this->flagFalse($flag)) return TRUE;
		}
		else
		{
			if ($rule)
			{
				// if the flag and rule is EXPLICITLY set, i.e. not NULL try and get the response from that
				if (in_array($rule, $this->getFlag(self::RULES_INCLUDE))) return FALSE;
				if (in_array($rule, $this->getFlag(self::RULES_EXCLUDE))) return TRUE;
			}
			// rules that don't have their own specific flags
			// are controlled with the RULES flag.
			if ($this->flagTrue(self::RULES)) return FALSE;
			if ($this->flagFalse(self::RULES)) return TRUE;
		}
		// if the flag was not explicitly set, defer to the
		// NO_CHECKS setting. If it's not been set or is false
		// the caller should not debug skip.
		return $this->flagTrue(self::NO_CHECKS) ? TRUE : FALSE;
	}

	/**
	 * Sets the state of a flag in the config.
	 *
	 * @param string $flag Class constant like OLPBlackbox_DebugConf::PREV_CUSTOMER
	 * @param mixed $value Usually an array for something like OLPBlackbox_DebugConf::USE_TIER
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return void
	 */
	public function setFlag($flag, $value = TRUE)
	{
		if (!$this->class_reflection->hasConstant($flag))
		{
			throw new InvalidArgumentException(
				"flag $flag is not allowed to be set."
			);
		}

		if (in_array($flag, array(self::USE_TIER,
						self::EXCLUDE_TIER,
						self::TARGETS_RESTRICT,
						self::TARGETS_EXCLUDE)))
		{
			if (!is_array($value))
			{
				throw new InvalidArgumentException(
					'flag must be array'
				);
			}

			if (in_array($flag, array(self::USE_TIER, self::EXCLUDE_TIER)))
			{
				/**
				 * TODO: Conceptually, this is a good idea, but we're currently using USE_TIER and EXCLUDE_TIER
				 * to reduce the target list, so we could have USE_TIER = 1, 2, 3, 4, and EXCLUDE_TIER =
				 * 0, 2, 3, 4. The Factory handles this just fine, but the exception below was being thrown.
				 *
				 * Once we're able to get rid of old Blackbox completely, we can look at going back to this.
				if (($flag == self::USE_TIER && array_intersect($value, $this->getFlag(self::EXCLUDE_TIER)))
						|| ($flag == self::EXCLUDE_TIER && array_intersect($value, $this->getFlag(self::USE_TIER))))
				{
					throw new InvalidArgumentException("you cannot simultaneously use and exclude a tier.");
				}
				*/
			}
			elseif (in_array($flag, array(self::TARGETS_RESTRICT, self::TARGETS_EXCLUDE)))
			{
				$value = array_map('strtoupper', $value);

				if (($flag == self::TARGETS_RESTRICT 
						&& array_intersect($value, $this->getFlag(self::TARGETS_EXCLUDE)))
					|| ($flag == self::TARGETS_EXCLUDE 
						&& array_intersect($value, $this->getFlag(self::TARGETS_RESTRICT))))
				{
					throw new Blackbox_Exception(
						"restricted and excluded targets cannot overlap."
					);
				}
			}
		}

		$this->flags[$flag] = $value;
	}

	/**
	 * Removes a flag from the config object.
	 *
	 * @param string $flag Class constant flag, such as OLPBlackbox_DebugConf::PREV_CUSTOMER
	 *
	 * @return void
	 */
	public function unsetFlag($flag)
	{
		unset($this->flags[$flag]);
	}

	/**
	 * Return the value of a flag.
	 *
	 * @param string $flag Class constant flag, such as OLPBlackbox_DebugConf::PREV_CUSTOMER
	 *
	 * @return mixed NULL if not set, TRUE if set, other value if dictated by the flag type.
	 */
	public function getFlag($flag)
	{
		return array_key_exists($flag, $this->flags) ? $this->flags[$flag] : NULL;
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

	/**
	 * Convenience function to determine if a flag is FALSE (not NULL).
	 *
	 * @param string $flag Class constant flag, such as OLPBlackbox_DebugConf::PREV_CUSTOMER
	 *
	 * @return bool whether the flag is FALSE
	 */
	public function flagFalse($flag)
	{
		return $this->getFlag($flag) === FALSE;
	}

	/**
	 * Convenience function to determine if a flag is TRUE.
	 *
	 * @param string $flag Class constant flag, such as OLPBlackbox_DebugConf::PREV_CUSTOMER
	 *
	 * @return bool whether the flag is TRUE
	 */
	public function flagTrue($flag)
	{
		return $this->getFlag($flag) === TRUE;
	}
}

?>
