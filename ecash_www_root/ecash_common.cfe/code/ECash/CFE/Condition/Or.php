<?php

/**
 * Composite condition that implements OR logic
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_CFE_Condition_Or implements ECash_CFE_ICondition
{
	/**
	 * @param ECash_CFE_ICondition $cond1
	 * @param ECash_CFE_ICondition $cond2
	 */
	public function __construct(ECash_CFE_ICondition $cond1, ECash_CFE_ICondition $cond2)
	{
		$args = func_get_args();
		foreach ($args as $cond)
		{
			if ($cond instanceof ECash_CFE_ICondition)
			{
				$this->conditions[] = $cond;
			}
		}
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		return $this->isValid($c);
	}


	/**
	 * Valid if all conditions are valid
	 * @param ECash_CFE_IContext $c
	 * @return bool
	 */
	public function isValid(ECash_CFE_IContext $c)
	{
		foreach ($this->conditions as $condition)
		{
			if ($condition->isValid($c))
			{
				return TRUE;
			}
		}
		return FALSE;
	}
}

?>