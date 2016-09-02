<?php

class VendorAPI_CFE_Node implements ECash_CFE_IExpression
{
	/**
	 * @var array ECash_CFE_IExpression[]
	 */
	protected $expression = array();

	/**
	 * @var array VendorAPI_CFE_Transition[]
	 */
	protected $transition = array();

	/**
	 * @param ECash_CFE_IExpression $e
	 * @param string $name
	 * @return void
	 */
	public function addExpression(ECash_CFE_IExpression $e, $name = '')
	{
		$this->expression[] = array($e, $name);
	}

	/**
	 * @param VendorAPI_CFE_Transition $t
	 * @return void
	 */
	public function addTransition(VendorAPI_CFE_Transition $t)
	{
		$this->transition[] = $t;
	}

	public function isValid(ECash_CFE_IContext $c)
	{
		$this->evaluate($c);
		return FALSE;
	}

	public function execute(ECash_CFE_IContext $c)
	{
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		$result = new ECash_CFE_ArrayContext(array(
			'' => NULL,
		));

		foreach ($this->expression as $e)
		{
			list($expr, $name) = $e;

			/* @var $expr ECash_CFE_IExpression */
			$value = $expr->evaluate($c);
			$result->setAttribute($name, $value);
		}

		foreach ($this->transition as $t)
		{
			/* @var $t Transition */
			if ($t->isValid($result))
			{
				$target = $t->getTarget();
				$target->evaluate($c);
				break;
			}
		}
	}
}