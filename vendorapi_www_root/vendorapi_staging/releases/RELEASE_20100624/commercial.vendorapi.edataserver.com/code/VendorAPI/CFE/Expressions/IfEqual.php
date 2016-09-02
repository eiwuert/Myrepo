<?php

/**
 * An expression that implements a basic "If left == right" and evaulates a
 * "then" or "else"
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_CFE_Expressions_IfEqual implements ECash_CFE_IExpression
{
	/**
	 * @var mixed
	 */
	protected $left;

	/**
	 * @var mixed
	 */
	protected $right;

	/**
	 * @var mixed
	 */
	protected $then;

	/**
	 * @var mixed
	 */
	protected $else;

	/**
	 * Populate expression evaulator with left and right side of "=="
	 * equation as well as the "then" and "else" to return
	 * @param mixed $left
	 * @param mixed $right
	 * @param mixed $then
	 * @param mixed $else
	 * @return mixed
	 */
	public function __construct($left, $right, $then, $else = NULL)
	{
		$this->left = $left;
		$this->right = $right;
		$this->then = $then;
		$this->else = $else;
	}

	/**
	 * If 'left' == 'right' expression is true, returns 'then' expression, otherwise returns 'else' expression
	 * @param ECash_CFE_IContext $c
	 * @return mixed
	 */
	public function evaluate(ECash_CFE_IContext $c)
	{
		$left = ($this->left instanceof ECash_CFE_IExpression)
				? $this->left->evaluate($c)
				: $this->left;
				
		$right = ($this->right instanceof ECash_CFE_IExpression)
				? $this->right->evaluate($c)
				: $this->right;

		if ($left == $right)
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