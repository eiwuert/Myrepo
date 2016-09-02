<?php
/**
 * Blackbox_RuleCollection class file.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */

/**
 * A collection of Blackbox rules
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class Blackbox_RuleCollection implements Blackbox_IRule, IteratorAggregate, Countable, Blackbox_IRuleCollection
{
	/**
	 * An array of rules to run
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 * Adds a rule to the collection
	 *
	 * @param Blackbox_IRule $rule The rule to add to the collection
	 *
	 * @return void
	 */
	public function addRule(Blackbox_IRule $rule)
	{
		$this->rules[] = $rule;
	}

	/**
	 * Runs all the rules in the collection.  If any rule
	 * fails, it will not run any remaining rules.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return bool TRUE if all rules in the collection pass.
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = TRUE;

		foreach ($this->rules as $rule)
		{
			if (!$rule->isValid($data, $state_data))
			{
				$valid = FALSE;
				break;
			}
		}

		return $valid;
	}

	/**
	 * Returns an iterator object of the rules for this collection.
	 *
	 * @return Iterator Rules in an iterator.s
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->rules);
	}

	/**
	 * Returns the number of rules this collection has.
	 *
	 * Required for the Countable interface.
	 *
	 * @return int number of rules
	 */
	public function count()
	{
		return count($this->rules);
	}

	/**
	 * Allows you to get a nice pretty print out of the entire blackbox
	 * tree instead of having to do a print_r, or similar, and get the entire
	 * structure dumped to the screen.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string = "Rule Collection:   [" . get_class($this) . "]" . "\n";
		foreach ($this->rules as $rule)
		{
			$string .= preg_replace('/^/m', '   ', strval($rule));
		}
		return $string;
	}
}

?>
