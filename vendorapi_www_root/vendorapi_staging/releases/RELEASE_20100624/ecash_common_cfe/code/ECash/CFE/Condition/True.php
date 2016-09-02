<?php
/**
 * Condition comparing a parameter against
 * the boolean true
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class ECash_CFE_Condition_True implements ECash_CFE_ICondition
{
	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		return $this->isValid($c);
	}


	/**
	 * Returns a boolean version of the value thing
	 * @param ECash_CFE_IContext $c
	 * @return boolean
	 */
	public function isValid(ECash_CFE_IContext $c)
	{
		$value = ($this->value instanceof ECash_CFE_IExpression)
							? $this->value->evaluate($c)
							: $this->value;
		return (bool)$value;
	}
}
