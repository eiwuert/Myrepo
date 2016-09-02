<?php

/**
 * 
 * @author stephans
 *
 */
class ECash_CFE_Expression_Count implements ECash_CFE_IExpression
{
	/**
	 * The variable name
	 *
	 * @var string
	 */
	protected $attr;
	
	public function __construct($attr)
	{
		$this->attr = $attr;
	}
	
	public function getAttr()
	{
		return $this->attr;
	}
	
	/**
	 * Returns a count.. of the value..
	 *
	 * @param CFE_IContext $c
	 * @return mixed
	 */
	public function evaluate(ECash_CFE_IContext $c)
	{
		if ($this->getAttr() instanceof ECash_CFE_IExpression)
		{
			$value = $this->getAttr()->evaluate($c);
		}
		else
		{
			$value = $this->getAttr();
		} 
		return count($value);
	}
}
