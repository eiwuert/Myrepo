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
	 * Used in the isValid loop to keep track of validity.
	 *
	 * @see Blackbox_RuleCollection::isValid()
	 * @var bool
	 */
	protected $valid = TRUE;
	
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
	 * Orders rules in the rules collection. This is needed if there is preference of order in 
	 * running the rules. One case might be running the military rule before all the others.
	 *
	 * @param array $rule_instances - instances of the rules in order
	 * 
	 * @return void
	 */
	public function orderRules(array $rule_instances)
	{
		if (!empty($rule_instances))
		{
			$rule_instances=array_reverse($rule_instances,TRUE);
			foreach ($rule_instances as $instance)
			{
				$this->pushToTop($instance);
			}
		}
	}
	
	/**
	 * Find the given instance in the collection and move it to the top so that it would run first
	 *
	 * @param Blackbox_IRule $instance
	 * 
	 * @return void
	 */
	private function pushToTop(Blackbox_IRule $instance)
	{
		if (!empty($this->rules))
		{
			foreach ($this->rules as $index=>$rule)
			{
				if (0==strcmp(get_class($instance),get_class($rule)))
				{
					$temp=$this->rules[$index];
					unset($this->rules[$index]);
					array_unshift($this->rules,$temp);
					unset($temp);
					break;
				}
			}
		}
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
		foreach ($this->rules as $rule)
		{
			// since runRule is subclassed, children could change $this->valid
			if ($this->isInvalidated() || !$this->runRule($rule, $data, $state_data))
			{
				$this->setValid(FALSE);
				break;
			}
		}

		return $this->valid;
	}
	
	/**
	 * Use this instead of setting $this->valid = BOOL
	 * 
	 * @see Blackbox_TargetCollection::setValid() for why this is important.
	 * @param bool $bool TRUE/FALSE depending on the desired validity of the
	 * collection.
	 */
	protected function setValid($bool)
	{
		$this->valid = $bool && !$this->isInvalidated();
	}
	
	/**
	 * Whether this object has been specifically invalidated.
	 * 
	 * @return bool
	 */
	protected function isInvalidated()
	{
		return $this->valid === FALSE;
	}
	
	/**
	 * Run an individual rule. This method is made for overloading.
	 * 
	 * Children may need to do something before/after each rule is run.
	 *
	 * @param Blackbox_IRule $rule The rule to run.
	 * @param Blackbox_Data $data The data for the application being run.
	 * @param Blackbox_IStateData $state_data The state data for the run.
	 * @return bool Whether the rules is valid.
	 */
	protected function runRule(Blackbox_IRule $rule, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $rule->isValid($data, $state_data);
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
