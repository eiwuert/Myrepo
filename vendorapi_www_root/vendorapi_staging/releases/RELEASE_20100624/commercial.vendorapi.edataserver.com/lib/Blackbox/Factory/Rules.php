<?php
/**
 * A factory for Blackbox rules
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Factory_Rules
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
		$class = 'Blackbox_Rule_' . $name;

		if (!class_exists($class))
		{
			throw new InvalidArgumentException("Invalid rule $name given.");
		}

		$instance = new $class();
		$instance->setupRule($params);
		return $instance;
	}
}

?>
