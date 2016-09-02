<?php

/**
 * An expression that implements a basic If
 * @author andrewm
 */
class VendorAPI_CFE_Expressions_If implements ECash_CFE_IExpression
{
	/**
	 * @var ECash_CFE_IExpression
	 */
	protected $if;

	/**
	 * @var mixed
	 */
	protected $then;

	/**
	 * @var mixed
	 */
	protected $else;

	/**
	 * @param ECash_CFE_IExpression $if
	 * @param mixed $then Can implement ECash_CFE_IExpression
	 * @param mixed $else Can implement ECash_CFE_IExpression
	 */
	public function __construct(ECash_CFE_IExpression $if, $then, $else = NULL)
	{
		$this->if = $if;
		$this->then = $then;
		$this->else = $else;
	}

	/**
	 * If 'if' expression is true, returns 'then' expression, otherwise returns 'else' expression
	 * @param ECash_CFE_IContext $c
	 * @return mixed
	 */
	public function evaluate(ECash_CFE_IContext $c)
	{
		if ($this->if->evaluate($c))
		{
			return ($this->then instanceof ECash_CFE_IExpression)
				? $this->then->evaluate($c)
				: $this->then;
		}
		return ($this->else instanceof ECash_CFE_IExpression)
			? $this->else->evaluate($c)
			: $this->else;
	}
}

?>