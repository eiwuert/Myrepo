<?php
/**
 * Defines the OLPBlackbox_Factory_Rules class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Factory for OLP specific rules.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Factory_Rules extends Blackbox_Factory_Rules
{
	/**
	 * Gets an instance of a rule.
	 *
	 * @param string $name   The name of the rule
	 * @param array  $params Parameters used to set up the rule
	 *
	 * @return IRule
	 */
	public static function getRule($name, $params = array())
	{
		$class = 'OLPBlackbox_Rule_' . $name;

		if (!class_exists($class))
		{
			throw new InvalidArgumentException("Invalid rule $name given.");
		}

		$instance = new $class();

		if (!empty($params))
		{
			$instance->setupRule($params);
		}

		return $instance;
	}
}
?>
