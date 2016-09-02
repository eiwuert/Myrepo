<?php

class VendorAPI_CFE_Transition
{
	public function __construct(VendorAPI_CFE_Node $target, ECash_CFE_IExpression $condition = NULL)
	{
		$this->condition = $condition;
		$this->target = $target;
	}

	public function isValid(ECash_CFE_IContext $c)
	{
		if ($this->condition)
		{
			return (bool)$this->condition->evaluate($c);
		}
		return TRUE;
	}

	public function getTarget()
	{
		return $this->target;
	}
}