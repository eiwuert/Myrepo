<?php

/**
 * Decorator that inverts the result of it's decorated condition
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_CFE_Condition_Not implements ECash_CFE_ICondition
{
	/**
	 * @var ECash_CFE_ICondition
	 */
	protected $condition;

	/**
	 * @param ECash_CFE_ICondition $condition
	 */
	public function __construct($condition)
	{
		$this->condition = $condition;
	}

	/**
	 * Inverts the internal condition
	 *
	 * @param ECash_CFE_IContext $context
	 * @return bool
	 */
	public function isValid(ECash_CFE_IContext $context)
	{
		if ($this->condition instanceof ECash_CFE_ICondition)
		{
			return !$this->condition->isValid($context);
		}
		else
		{
			$value = $this->condition;
			if ($value instanceof ECash_CFE_IExpression)
			{
				$value = $value->evaluate($context);
			}
			return ($value == FALSE);
		}
	}
}

?>
